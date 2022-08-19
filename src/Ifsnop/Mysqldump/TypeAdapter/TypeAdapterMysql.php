<?php

namespace Ifsnop\Mysqldump\TypeAdapter;

use Exception;

class TypeAdapterMysql extends TypeAdapterFactory
{
    const DEFINER_RE = 'DEFINER=`(?:[^`]|``)*`@`(?:[^`]|``)*`';

    // Numerical Mysql types
    public array $mysqlTypes = [
        'numerical' => [
            'bit',
            'tinyint',
            'smallint',
            'mediumint',
            'int',
            'integer',
            'bigint',
            'real',
            'double',
            'float',
            'decimal',
            'numeric'
        ],
        'blob' => [
            'tinyblob',
            'blob',
            'mediumblob',
            'longblob',
            'binary',
            'varbinary',
            'bit',
            'geometry', /* http://bugs.mysql.com/bug.php?id=43544 */
            'point',
            'linestring',
            'polygon',
            'multipoint',
            'multilinestring',
            'multipolygon',
            'geometrycollection',
        ]
    ];

    public function databases(): string
    {
        $this->check_parameters(func_num_args(), 1, __METHOD__);
        $args = func_get_args();
        $databaseName = $args[0];

        $resultSet = $this->dbHandler->query("SHOW VARIABLES LIKE 'character_set_database';");
        $characterSet = $resultSet->fetchColumn(1);
        $resultSet->closeCursor();

        $resultSet = $this->dbHandler->query("SHOW VARIABLES LIKE 'collation_database';");
        $collationDb = $resultSet->fetchColumn(1);
        $resultSet->closeCursor();

        return "CREATE DATABASE /*!32312 IF NOT EXISTS*/ `${databaseName}`" .
            " /*!40100 DEFAULT CHARACTER SET ${characterSet} " .
            " COLLATE ${collationDb} */;" . PHP_EOL . PHP_EOL .
            "USE `${databaseName}`;" . PHP_EOL . PHP_EOL;
    }

    public function show_create_table($tableName): string
    {
        return "SHOW CREATE TABLE `$tableName`";
    }

    public function show_create_view($viewName): string
    {
        return "SHOW CREATE VIEW `$viewName`";
    }

    public function show_create_trigger($triggerName): string
    {
        return "SHOW CREATE TRIGGER `$triggerName`";
    }

    public function show_create_procedure($procedureName): string
    {
        return "SHOW CREATE PROCEDURE `$procedureName`";
    }

    public function show_create_function($functionName): string
    {
        return "SHOW CREATE FUNCTION `$functionName`";
    }

    public function show_create_event($eventName): string
    {
        return "SHOW CREATE EVENT `$eventName`";
    }

    public function create_table($row): string
    {
        if (!isset($row['Create Table'])) {
            throw new Exception("Error getting table code, unknown output");
        }

        $createTable = $row['Create Table'];
        if ($this->dumpSettings['reset-auto-increment']) {
            $match = "/AUTO_INCREMENT=[0-9]+/s";
            $replace = "";
            $createTable = preg_replace($match, $replace, $createTable);
        }

        if ($this->dumpSettings['if-not-exists'] ) {
            $createTable = preg_replace('/^CREATE TABLE/', 'CREATE TABLE IF NOT EXISTS', $createTable);
        }

        return "/*!40101 SET @saved_cs_client     = @@character_set_client */;".PHP_EOL.
            "/*!40101 SET character_set_client = ".$this->dumpSettings['default-character-set']." */;".PHP_EOL.
            $createTable.";".PHP_EOL.
            "/*!40101 SET character_set_client = @saved_cs_client */;".PHP_EOL.
            PHP_EOL;
    }

    public function create_view($row): string
    {
        $ret = "";
        if (!isset($row['Create View'])) {
            throw new Exception("Error getting view structure, unknown output");
        }

        $viewStmt = $row['Create View'];

        $definerStr = $this->dumpSettings['skip-definer'] ? '' : '/*!50013 \2 */'.PHP_EOL;

        if ($viewStmtReplaced = preg_replace(
            '/^(CREATE(?:\s+ALGORITHM=(?:UNDEFINED|MERGE|TEMPTABLE))?)\s+('
            .self::DEFINER_RE.'(?:\s+SQL SECURITY DEFINER|INVOKER)?)?\s+(VIEW .+)$/',
            '/*!50001 \1 */'.PHP_EOL.$definerStr.'/*!50001 \3 */',
            $viewStmt,
            1
        )) {
            $viewStmt = $viewStmtReplaced;
        };

        $ret .= $viewStmt.';'.PHP_EOL.PHP_EOL;
        return $ret;
    }

    public function create_trigger($row): string
    {
        $ret = "";
        if (!isset($row['SQL Original Statement'])) {
            throw new Exception("Error getting trigger code, unknown output");
        }

        $triggerStmt = $row['SQL Original Statement'];
        $definerStr = $this->dumpSettings['skip-definer'] ? '' : '/*!50017 \2*/ ';
        if ($triggerStmtReplaced = preg_replace(
            '/^(CREATE)\s+('.self::DEFINER_RE.')?\s+(TRIGGER\s.*)$/s',
            '/*!50003 \1*/ '.$definerStr.'/*!50003 \3 */',
            $triggerStmt,
            1
        )) {
            $triggerStmt = $triggerStmtReplaced;
        }

        $ret .= "DELIMITER ;;".PHP_EOL.
            $triggerStmt.";;".PHP_EOL.
            "DELIMITER ;".PHP_EOL.PHP_EOL;
        return $ret;
    }

    public function create_procedure($row): string
    {
        $ret = "";
        if (!isset($row['Create Procedure'])) {
            throw new Exception("Error getting procedure code, unknown output. ".
                "Please check 'https://bugs.mysql.com/bug.php?id=14564'");
        }
        $procedureStmt = $row['Create Procedure'];
        if ($this->dumpSettings['skip-definer']) {
            if ($procedureStmtReplaced = preg_replace(
                '/^(CREATE)\s+('.self::DEFINER_RE.')?\s+(PROCEDURE\s.*)$/s',
                '\1 \3',
                $procedureStmt,
                1
            )) {
                $procedureStmt = $procedureStmtReplaced;
            }
        }

        $ret .= "/*!50003 DROP PROCEDURE IF EXISTS `".
            $row['Procedure']."` */;".PHP_EOL.
            "/*!40101 SET @saved_cs_client     = @@character_set_client */;".PHP_EOL.
            "/*!40101 SET character_set_client = ".$this->dumpSettings['default-character-set']." */;".PHP_EOL.
            "DELIMITER ;;".PHP_EOL.
            $procedureStmt." ;;".PHP_EOL.
            "DELIMITER ;".PHP_EOL.
            "/*!40101 SET character_set_client = @saved_cs_client */;".PHP_EOL.PHP_EOL;

        return $ret;
    }

    public function create_function($row): string
    {
        $ret = "";
        if (!isset($row['Create Function'])) {
            throw new Exception("Error getting function code, unknown output. ".
                "Please check 'https://bugs.mysql.com/bug.php?id=14564'");
        }
        $functionStmt = $row['Create Function'];
        $characterSetClient = $row['character_set_client'];
        $collationConnection = $row['collation_connection'];
        $sqlMode = $row['sql_mode'];
        if ( $this->dumpSettings['skip-definer'] ) {
            if ($functionStmtReplaced = preg_replace(
                '/^(CREATE)\s+('.self::DEFINER_RE.')?\s+(FUNCTION\s.*)$/s',
                '\1 \3',
                $functionStmt,
                1
            )) {
                $functionStmt = $functionStmtReplaced;
            }
        }

        $ret .= "/*!50003 DROP FUNCTION IF EXISTS `".
            $row['Function']."` */;".PHP_EOL.
            "/*!40101 SET @saved_cs_client     = @@character_set_client */;".PHP_EOL.
            "/*!50003 SET @saved_cs_results     = @@character_set_results */ ;".PHP_EOL.
            "/*!50003 SET @saved_col_connection = @@collation_connection */ ;".PHP_EOL.
            "/*!40101 SET character_set_client = ".$characterSetClient." */;".PHP_EOL.
            "/*!40101 SET character_set_results = ".$characterSetClient." */;".PHP_EOL.
            "/*!50003 SET collation_connection  = ".$collationConnection." */ ;".PHP_EOL.
            "/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;;".PHP_EOL.
            "/*!50003 SET sql_mode              = '".$sqlMode."' */ ;;".PHP_EOL.
            "/*!50003 SET @saved_time_zone      = @@time_zone */ ;;".PHP_EOL.
            "/*!50003 SET time_zone             = 'SYSTEM' */ ;;".PHP_EOL.
            "DELIMITER ;;".PHP_EOL.
            $functionStmt." ;;".PHP_EOL.
            "DELIMITER ;".PHP_EOL.
            "/*!50003 SET sql_mode              = @saved_sql_mode */ ;".PHP_EOL.
            "/*!50003 SET character_set_client  = @saved_cs_client */ ;".PHP_EOL.
            "/*!50003 SET character_set_results = @saved_cs_results */ ;".PHP_EOL.
            "/*!50003 SET collation_connection  = @saved_col_connection */ ;".PHP_EOL.
            "/*!50106 SET TIME_ZONE= @saved_time_zone */ ;".PHP_EOL.PHP_EOL;


        return $ret;
    }

    public function create_event($row): string
    {
        $ret = "";
        if (!isset($row['Create Event'])) {
            throw new Exception("Error getting event code, unknown output. ".
                "Please check 'http://stackoverflow.com/questions/10853826/mysql-5-5-create-event-gives-syntax-error'");
        }
        $eventName = $row['Event'];
        $eventStmt = $row['Create Event'];
        $sqlMode = $row['sql_mode'];
        $definerStr = $this->dumpSettings['skip-definer'] ? '' : '/*!50117 \2*/ ';

        if ($eventStmtReplaced = preg_replace(
            '/^(CREATE)\s+('.self::DEFINER_RE.')?\s+(EVENT .*)$/',
            '/*!50106 \1*/ '.$definerStr.'/*!50106 \3 */',
            $eventStmt,
            1
        )) {
            $eventStmt = $eventStmtReplaced;
        }

        $ret .= "/*!50106 SET @save_time_zone= @@TIME_ZONE */ ;".PHP_EOL.
            "/*!50106 DROP EVENT IF EXISTS `".$eventName."` */;".PHP_EOL.
            "DELIMITER ;;".PHP_EOL.
            "/*!50003 SET @saved_cs_client      = @@character_set_client */ ;;".PHP_EOL.
            "/*!50003 SET @saved_cs_results     = @@character_set_results */ ;;".PHP_EOL.
            "/*!50003 SET @saved_col_connection = @@collation_connection */ ;;".PHP_EOL.
            "/*!50003 SET character_set_client  = utf8 */ ;;".PHP_EOL.
            "/*!50003 SET character_set_results = utf8 */ ;;".PHP_EOL.
            "/*!50003 SET collation_connection  = utf8_general_ci */ ;;".PHP_EOL.
            "/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;;".PHP_EOL.
            "/*!50003 SET sql_mode              = '".$sqlMode."' */ ;;".PHP_EOL.
            "/*!50003 SET @saved_time_zone      = @@time_zone */ ;;".PHP_EOL.
            "/*!50003 SET time_zone             = 'SYSTEM' */ ;;".PHP_EOL.
            $eventStmt." ;;".PHP_EOL.
            "/*!50003 SET time_zone             = @saved_time_zone */ ;;".PHP_EOL.
            "/*!50003 SET sql_mode              = @saved_sql_mode */ ;;".PHP_EOL.
            "/*!50003 SET character_set_client  = @saved_cs_client */ ;;".PHP_EOL.
            "/*!50003 SET character_set_results = @saved_cs_results */ ;;".PHP_EOL.
            "/*!50003 SET collation_connection  = @saved_col_connection */ ;;".PHP_EOL.
            "DELIMITER ;".PHP_EOL.
            "/*!50106 SET TIME_ZONE= @save_time_zone */ ;".PHP_EOL.PHP_EOL;
        // Commented because we are doing this in restore_parameters()
        // "/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;" . PHP_EOL . PHP_EOL;

        return $ret;
    }

    public function show_tables(): string
    {
        $this->check_parameters(func_num_args(), 1, __METHOD__);
        $args = func_get_args();
        return "SELECT TABLE_NAME AS tbl_name ".
            "FROM INFORMATION_SCHEMA.TABLES ".
            "WHERE TABLE_TYPE='BASE TABLE' AND TABLE_SCHEMA='${args[0]}'";
    }

    public function show_views(): string
    {
        $this->check_parameters(func_num_args(), 1, __METHOD__);
        $args = func_get_args();
        return "SELECT TABLE_NAME AS tbl_name ".
            "FROM INFORMATION_SCHEMA.TABLES ".
            "WHERE TABLE_TYPE='VIEW' AND TABLE_SCHEMA='${args[0]}'";
    }

    public function show_triggers(): string
    {
        $this->check_parameters(func_num_args(), 1, __METHOD__);
        $args = func_get_args();
        return "SHOW TRIGGERS FROM `${args[0]}`;";
    }

    public function show_columns(): string
    {
        $this->check_parameters(func_num_args(), 1, __METHOD__);
        $args = func_get_args();
        return "SHOW COLUMNS FROM `${args[0]}`;";
    }

    public function show_procedures(): string
    {
        $this->check_parameters(func_num_args(), 1, __METHOD__);
        $args = func_get_args();
        return "SELECT SPECIFIC_NAME AS procedure_name ".
            "FROM INFORMATION_SCHEMA.ROUTINES ".
            "WHERE ROUTINE_TYPE='PROCEDURE' AND ROUTINE_SCHEMA='${args[0]}'";
    }

    public function show_functions(): string
    {
        $this->check_parameters(func_num_args(), 1, __METHOD__);
        $args = func_get_args();
        return "SELECT SPECIFIC_NAME AS function_name ".
            "FROM INFORMATION_SCHEMA.ROUTINES ".
            "WHERE ROUTINE_TYPE='FUNCTION' AND ROUTINE_SCHEMA='${args[0]}'";
    }

    /**
     * Get query string to ask for names of events from current database.
     *
     * @return string
     */
    public function show_events(): string
    {
        $this->check_parameters(func_num_args(), 1, __METHOD__);
        $args = func_get_args();
        return "SELECT EVENT_NAME AS event_name ".
            "FROM INFORMATION_SCHEMA.EVENTS ".
            "WHERE EVENT_SCHEMA='${args[0]}'";
    }

    public function setup_transaction(): string
    {
        return "SET SESSION TRANSACTION ISOLATION LEVEL REPEATABLE READ";
    }

    public function start_transaction(): string
    {
        return "START TRANSACTION ".
            "/*!40100 WITH CONSISTENT SNAPSHOT */";
    }

    public function commit_transaction(): string
    {
        return "COMMIT";
    }

    public function lock_table(): string
    {
        $this->check_parameters(func_num_args(), 1, __METHOD__);
        $args = func_get_args();
        return $this->dbHandler->exec("LOCK TABLES `${args[0]}` READ LOCAL");
    }

    public function unlock_table(): string
    {
        return $this->dbHandler->exec("UNLOCK TABLES");
    }

    public function start_add_lock_table(): string
    {
        $this->check_parameters(func_num_args(), 1, __METHOD__);
        $args = func_get_args();
        return "LOCK TABLES `${args[0]}` WRITE;".PHP_EOL;
    }

    public function end_add_lock_table(): string
    {
        return "UNLOCK TABLES;".PHP_EOL;
    }

    public function start_add_disable_keys(): string
    {
        $this->check_parameters(func_num_args(), 1, __METHOD__);
        $args = func_get_args();
        return "/*!40000 ALTER TABLE `${args[0]}` DISABLE KEYS */;". PHP_EOL;
    }

    public function end_add_disable_keys(): string
    {
        $this->check_parameters(func_num_args(), 1, __METHOD__);
        $args = func_get_args();
        return "/*!40000 ALTER TABLE `${args[0]}` ENABLE KEYS */;". PHP_EOL;
    }

    public function start_disable_autocommit(): string
    {
        return "SET autocommit=0;".PHP_EOL;
    }

    public function end_disable_autocommit(): string
    {
        return "COMMIT;".PHP_EOL;
    }

    public function add_drop_database(): string
    {
        $this->check_parameters(func_num_args(), 1, __METHOD__);
        $args = func_get_args();
        return "/*!40000 DROP DATABASE IF EXISTS `${args[0]}`*/;". PHP_EOL.PHP_EOL;
    }

    public function add_drop_trigger(): string
    {
        $this->check_parameters(func_num_args(), 1, __METHOD__);
        $args = func_get_args();
        return "DROP TRIGGER IF EXISTS `${args[0]}`;".PHP_EOL;
    }

    public function drop_table(): string
    {
        $this->check_parameters(func_num_args(), 1, __METHOD__);
        $args = func_get_args();
        return "DROP TABLE IF EXISTS `${args[0]}`;".PHP_EOL;
    }

    public function drop_view(): string
    {
        $this->check_parameters(func_num_args(), 1, __METHOD__);
        $args = func_get_args();
        return "DROP TABLE IF EXISTS `${args[0]}`;".PHP_EOL.
            "/*!50001 DROP VIEW IF EXISTS `${args[0]}`*/;".PHP_EOL;
    }

    public function getDatabaseHeader(): string
    {
        $this->check_parameters(func_num_args(), 1, __METHOD__);
        $args = func_get_args();
        return "--".PHP_EOL.
            "-- Current Database: `${args[0]}`".PHP_EOL.
            "--".PHP_EOL.PHP_EOL;
    }

    /**
     * Decode column metadata and fill info structure.
     * type, is_numeric and is_blob will always be available.
     *
     * @param array $colType Array returned from "SHOW COLUMNS FROM tableName"
     * @return array
     */
    public function parseColumnType(array $colType): array
    {
        $colInfo = [];
        $colParts = explode(" ", $colType['Type']);

        if ($fparen = strpos($colParts[0], "(")) {
            $colInfo['type'] = substr($colParts[0], 0, $fparen);
            $colInfo['length'] = str_replace(")", "", substr($colParts[0], $fparen + 1));
            $colInfo['attributes'] = $colParts[1] ?? null;
        } else {
            $colInfo['type'] = $colParts[0];
        }
        $colInfo['is_numeric'] = in_array($colInfo['type'], $this->mysqlTypes['numerical']);
        $colInfo['is_blob'] = in_array($colInfo['type'], $this->mysqlTypes['blob']);
        // for virtual columns that are of type 'Extra', column type
        // could by "STORED GENERATED" or "VIRTUAL GENERATED"
        // MySQL reference: https://dev.mysql.com/doc/refman/5.7/en/create-table-generated-columns.html
        $colInfo['is_virtual'] = strpos($colType['Extra'], "VIRTUAL GENERATED") !== false || strpos($colType['Extra'], "STORED GENERATED") !== false;

        return $colInfo;
    }

    public function backup_parameters(): string
    {
        $ret = "/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;".PHP_EOL.
            "/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;".PHP_EOL.
            "/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;".PHP_EOL.
            "/*!40101 SET NAMES ".$this->dumpSettings['default-character-set']." */;".PHP_EOL;

        if (false === $this->dumpSettings['skip-tz-utc']) {
            $ret .= "/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;".PHP_EOL.
                "/*!40103 SET TIME_ZONE='+00:00' */;".PHP_EOL;
        }

        if ($this->dumpSettings['no-autocommit']) {
            $ret .= "/*!40101 SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT */;".PHP_EOL;
        }

        $ret .= "/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;".PHP_EOL.
            "/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;".PHP_EOL.
            "/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;".PHP_EOL.
            "/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;".PHP_EOL.PHP_EOL;

        return $ret;
    }

    public function restore_parameters(): string
    {
        $ret = "";

        if (false === $this->dumpSettings['skip-tz-utc']) {
            $ret .= "/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;".PHP_EOL;
        }

        if ($this->dumpSettings['no-autocommit']) {
            $ret .= "/*!40101 SET AUTOCOMMIT=@OLD_AUTOCOMMIT */;".PHP_EOL;
        }

        $ret .= "/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;".PHP_EOL.
            "/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;".PHP_EOL.
            "/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;".PHP_EOL.
            "/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;".PHP_EOL.
            "/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;".PHP_EOL.
            "/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;".PHP_EOL.
            "/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;".PHP_EOL.PHP_EOL;

        return $ret;
    }

    /**
     * Check number of parameters passed to function, useful when inheriting.
     * Raise exception if unexpected.
     */
    private function check_parameters(int $num_args, int $expected_num_args, string $method_name)
    {
        if ($num_args != $expected_num_args) {
            throw new Exception("Unexpected parameter passed to $method_name");
        }
    }
}
