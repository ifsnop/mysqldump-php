<?php 

namespace Clouddueling\Mysqldump;

use Exception;
use PDO;
use PDOException;

class Mysqldump
{
    const MAXLINESIZE = 1000000;

    // This can be set both on constructor or manually
    public $host;
    public $user;
    public $pass;
    public $db;
    public $fileName = 'dump.sql';

    // Internal stuff
    private $_settings = array();
    private $_tables = array();
    private $_views = array();
    private $_dbHandler;
    private $_dbType;
    private $_compressManager;
    private $_typeAdapter;
    private $_pdoOptions;

    /**
     * Constructor of Mysqldump. Note that in the case of an SQLite database
     * connection, the filename must be in the $db parameter.
     *
     * @param string $db        Database name
     * @param string $user      SQL account username
     * @param string $pass      SQL account password
     * @param string $host      SQL server to connect to
     * @param string $type      SQL database type
     * @return null
     */
    public function __construct($db = '', $user = '', $pass = '',
        $host = 'localhost', $type = "mysql", $settings = array(),
        $pdoOptions = array(PDO::ATTR_PERSISTENT => true,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"))
    {
        $defaultSettings = array(
            'include-tables' => array(),
            'exclude-tables' => array(),
            'compress' => 'None',
            'no-data' => false,
            'add-drop-database' => false,
            'add-drop-table' => false,
            'single-transaction' => true,
            'lock-tables' => false,
            'add-locks' => true,
            'extended-insert' => true,
            'disable-foreign-keys-check' => false
        );

        $this->db = $db;
        $this->user = $user;
        $this->pass = $pass;
        $this->host = $host;
        $this->_dbType = strtolower($type);
        $this->_pdoOptions = $pdoOptions;
        $this->_settings = Mysqldump::array_replace_recursive($defaultSettings, $settings);
    }

    /**
     * Custom array_replace_recursive to be used if PHP < 5.3
     *
     * @return array
     */
    public static function array_replace_recursive($array1, $array2)
    {
        if ( function_exists('array_replace_recursive') ) {
            return array_replace_recursive($array1, $array2);
        } else {
            foreach ($array2 as $key => $value) {
                if (is_array($value)) {
                    $array1[$key] = Mysqldump::array_replace_recursive($array1[$key], $value);
                } else {
                    $array1[$key] = $value;
                }
            }
            return $array1;
        }
    }

    /**
     * Connect with PDO
     *
     * @return bool
     */
    private function connect()
    {

        // Connecting with PDO
        try {
            switch ($this->_dbType) {
                case 'sqlite':
                    $this->_dbHandler = new PDO("sqlite:" . $this->db, null, null, $this->_pdoOptions);
                    break;
                case 'mysql': case 'pgsql': case 'dblib':
                    $this->_dbHandler = new PDO(
                        $this->_dbType . ":host=" .
                        $this->host.";dbname=" . $this->db, $this->user,
                        $this->pass, $this->_pdoOptions
                    );
                    // Fix for always-unicode output
                    $this->_dbHandler->exec("SET NAMES utf8");
                    break;
                default:
                    throw new Exception("Unsupported database type (" . $this->_dbType . ")", 3);
            }
        } catch (PDOException $e) {
            throw new Exception(
                "Connection to " . $this->_dbType . " failed with message: " .
                $e->getMessage(), 3
            );
        }

        $this->_dbHandler->setAttribute(PDO::ATTR_ORACLE_NULLS, PDO::NULL_NATURAL);
        $this->_typeAdapter = TypeAdapterFactory::create($this->_dbType);
    }

    /**
     * Main call
     *
     * @param string $filename  Name of file to write sql dump to
     * @return bool
     */
    public function start($filename = '')
    {
        // Output file can be redefined here
        if ( !empty($filename) ) {
            $this->fileName = $filename;
        }
        // We must set a name to continue
        if ( empty($this->fileName) ) {
            throw new Exception("Output file name is not set", 1);
        }

        // Connect to database
        $this->connect();

        // Create a new compressManager to manage compressed output
        $this->_compressManager = CompressManagerFactory::create($this->_settings['compress']);

        if (! $this->_compressManager->open($this->fileName)) {
            throw new Exception("Output file is not writable", 2);
        }

        // Formating dump file
        $this->_compressManager->write($this->getHeader());

        if ( $this->_settings['add-drop-database'] ) {
            $this->_compressManager->write($this->_typeAdapter->add_drop_database($this->db, $this->_dbHandler));
        }

        // Listing all tables from database
        $this->_tables = array();
        if (empty($this->_settings['include-tables'])) {
            // include all tables for now, blacklisting happens later
            foreach($this->_dbHandler->query($this->_typeAdapter->show_tables($this->db)) as $row) {
                array_push($this->_tables, current($row));
            }
        } else {
            // include only the tables mentioned in include-tables
            foreach ($this->_dbHandler->query($this->_typeAdapter->show_tables($this->db)) as $row) {
                if (in_array(current($row), $this->_settings['include-tables'], true)) {
                    array_push($this->_tables, current($row));
                    unset($this->_settings['include-tables'][array_search(current($row), $this->_settings['include-tables'])]);
                }
            }
        }

        // If there still are some tables in include-tables array, that means
        // that some tables weren't found. Give proper error and exit.
        if ( 0 < count($this->_settings['include-tables']) ) {
            throw new Exception("Table (" . implode(",", $this->_settings['include-tables']) . ") not found in database", 4);
        }

        // Disable checking foreign keys
        if ( $this->_settings['disable-foreign-keys-check'] ) {
            $this->_compressManager->write(
                $this->_typeAdapter->start_disable_foreign_keys_check()
            );
        }

        // Exporting tables one by one
        foreach ($this->_tables as $table) {
            if (in_array($table, $this->_settings['exclude-tables'], true)) {
                continue;
            }
            $is_table = $this->getTableStructure($table);
            if (true === $is_table && false === $this->_settings['no-data']) {
                $this->listValues($table);
            }
        }

        // Exporting views one by one
        foreach ($this->_views as $view) {
            $this->_compressManager->write($view);
        }

        // Enable checking foreign keys if needed
        if ( $this->_settings['disable-foreign-keys-check'] ) {
            $this->_compressManager->write(
                $this->_typeAdapter->end_disable_foreign_keys_check()
            );
        }

        $this->_compressManager->close();
    }

    /**
     * Returns header for dump file
     *
     * @return null
     */
    private function getHeader()
    {
        // Some info about software, source and time
        $header = "-- sqldump-php SQL Dump\n" .
                "-- https://github.com/clouddueling/mysqldump-php\n" .
                "--\n" .
                "-- Host: {$this->host}\n" .
                "-- Generation Time: " . date('r') . "\n\n" .
                "--\n" .
                "-- Database: `{$this->db}`\n" .
                "--\n\n";

        return $header;
    }

    /**
     * Table structure extractor
     *
     * @param string $tablename  Name of table to export
     * @return null
     */
    private function getTableStructure($tablename)
    {
        $stmt = $this->_typeAdapter->show_create_table($tablename);
        foreach ($this->_dbHandler->query($stmt) as $r) {
            if (isset($r['Create Table'])) {
                $this->_compressManager->write(
                    "-- --------------------------------------------------------" .
                    "\n\n" .
                    "--\n" .
                    "-- Table structure for table `$tablename`\n" .
                    "--\n\n"
                );

                if ($this->_settings['add-drop-table']) {
                    $this->_compressManager->write("DROP TABLE IF EXISTS `$tablename`;\n\n");
                }
                $this->_compressManager->write($r['Create Table'] . ";\n\n");
                return true;
            }

            if ( isset($r['Create View']) ) {
                $view  = "-- --------------------------------------------------------" .
                        "\n\n" .
                        "--\n" .
                        "-- Table structure for view `$tablename`\n" .
                        "--\n\n";
                $view .= $r['Create View'] . ";\n\n";
                $this->_views[] = $view;
                return false;
            }
        }
    }

    /**
     * Table rows extractor
     *
     * @param string $tablename  Name of table to export
     * @return null
     */
    private function listValues($tablename)
    {
        $this->_compressManager->write(
            "--\n" .
            "-- Dumping data for table `$tablename`\n" .
            "--\n\n"
        );

        if ( $this->_settings['single-transaction'] ) {
            $this->_dbHandler->exec($this->_typeAdapter->start_transaction());
        }

        if ( $this->_settings['lock-tables'] ) {
            $lockstmt = $this->_typeAdapter->lock_table($tablename);
            if ( strlen($lockstmt) ) {
                $this->_dbHandler->exec($lockstmt);
            }
        }

        if ( $this->_settings['add-locks'] ) {
            $this->_compressManager->write($this->_typeAdapter->start_add_lock_table($tablename));
        }

        $onlyOnce = true; $lineSize = 0;
        $stmt = "SELECT * FROM `$tablename`";
        foreach ($this->_dbHandler->query($stmt, PDO::FETCH_NUM) as $r) {
            $vals = array();
            foreach ($r as $val) {
                $vals[] = is_null($val) ? "NULL" :
                $this->_dbHandler->quote($val);
            }
            if ($onlyOnce || !$this->_settings['extended-insert'] ) {
                $lineSize += $this->_compressManager->write(
                    "INSERT INTO `$tablename` VALUES (" . implode(",", $vals) . ")"
                );
                $onlyOnce = false;
            } else {
                $lineSize += $this->_compressManager->write(",(" . implode(",", $vals) . ")");
            }
            if ( ($lineSize > Mysqldump::MAXLINESIZE) ||
                    !$this->_settings['extended-insert'] ) {
                $onlyOnce = true;
                $lineSize = $this->_compressManager->write(";\n");
            }
        }

        if ( !$onlyOnce ) {
            $this->_compressManager->write(";\n");
        }

        if ( $this->_settings['add-locks'] ) {
            $this->_compressManager->write($this->_typeAdapter->end_add_lock_table($tablename));
        }

        if ( $this->_settings['single-transaction'] ) {
            $this->_dbHandler->exec($this->_typeAdapter->commit_transaction());
        }

        if ( $this->_settings['lock-tables'] ) {
            $lockstmt = $this->_typeAdapter->unlock_table($tablename);
            if ( strlen($lockstmt) ) {
                $this->_dbHandler->exec($lockstmt);
            }
        }
    }
}

/**
 * Enum with all available compression methods
 *
 */
abstract class CompressMethod
{
    public static $enums = array(
        "None",
        "Gzip",
        "Bzip2"
    );

    public static function isValid($c)
    {
        return in_array($c, self::$enums);
    }
}

abstract class CompressManagerFactory
{
    private $_fileHandle = null;

    public static function create($c)
    {
        $c = ucfirst(strtolower($c));
        if (! CompressMethod::isValid($c)) {
            throw new Exception("Compression method ($c) is not defined yet", 1);
        }

        $method =  __NAMESPACE__ . "\\" . "Compress" . $c;

        return new $method;
    }
}

class CompressBzip2 extends CompressManagerFactory
{
    public function __construct()
    {
        if (! function_exists("bzopen")) {
            throw new Exception("Compression is enabled, but bzip2 lib is not installed or configured properly", 1);
        }
    }

    public function open($filename)
    {
        $this->_fileHandler = bzopen($filename . ".bz2", "w");
        if (false === $this->_fileHandler) {
            return false;
        }

        return true;
    }

    public function write($str)
    {
        $bytesWritten = 0;
        if (false === ($bytesWritten = bzwrite($this->_fileHandler, $str))) {
            throw new Exception("Writting to file failed! Probably, there is no more free space left?", 4);
        }

        return $bytesWritten;
    }

    public function close()
    {
        return bzclose($this->_fileHandler);
    }
}

class CompressGzip extends CompressManagerFactory
{
    public function __construct()
    {
        if (! function_exists("gzopen") ) {
            throw new Exception("Compression is enabled, but gzip lib is not installed or configured properly", 1);
        }
    }

    public function open($filename)
    {
        $this->_fileHandler = gzopen($filename . ".gz", "wb");
        if (false === $this->_fileHandler) {
            return false;
        }

        return true;
    }

    public function write($str)
    {
        $bytesWritten = 0;
        if (false === ($bytesWritten = gzwrite($this->_fileHandler, $str))) {
            throw new Exception("Writting to file failed! Probably, there is no more free space left?", 4);
        }

        return $bytesWritten;
    }

    public function close()
    {
        return gzclose($this->_fileHandler);
    }
}

class CompressNone extends CompressManagerFactory
{
    public function open($filename)
    {
        $this->_fileHandler = fopen($filename, "wb");
        if (false === $this->_fileHandler) {
            return false;
        }

        return true;
    }

    public function write($str)
    {
        $bytesWritten = 0;
        if (false === ($bytesWritten = fwrite($this->_fileHandler, $str))) {
            throw new Exception("Writting to file failed! Probably, there is no more free space left?", 4);
        }

        return $bytesWritten;
    }

    public function close()
    {
        return fclose($this->_fileHandler);
    }
}

/**
 * Enum with all available TypeAdapter implementations
 *
 */
abstract class TypeAdapter
{
    public static $enums = array(
        "Sqlite",
        "Mysql"
    );

    public static function isValid($c)
    {
        return in_array($c, self::$enums);
    }
}

/**
 * TypeAdapter Factory
 *
 */
abstract class TypeAdapterFactory
{
    public static function create($c)
    {
        $c = ucfirst(strtolower($c));
        if (! TypeAdapter::isValid($c)) {
            throw new Exception("Database type support for ($c) not yet available", 1);
        }

        $method =  __NAMESPACE__ . "\\" . "TypeAdapter" . $c;
        return new $method;
    }

    public function show_create_table($tablename)
    {
        return "SELECT tbl_name as 'Table', sql as 'Create Table' " .
            "FROM sqlite_master " .
            "WHERE type='table' AND tbl_name='$tablename'";
    }

    public function show_tables()
    {
        return "SELECT tbl_name FROM sqlite_master where type='table'";
    }

    public function start_transaction()
    {
        return "BEGIN EXCLUSIVE";
    }

    public function commit_transaction()
    {
        return "COMMIT";
    }

    public function lock_table()
    {
        return "";
    }

    public function unlock_table()
    {
        return "";
    }

    public function start_add_lock_table()
    {
        return "\n";
    }

    public function end_add_lock_table()
    {
        return "\n";
    }

    public function start_disable_foreign_keys_check()
    {
        return "\n";
    }

    public function end_disable_foreign_keys_check()
    {
        return "\n";
    }

    public function add_drop_database()
    {
        return "\n";
    }
}

class TypeAdapterPgsql extends TypeAdapterFactory
{
}

class TypeAdapterDblib extends TypeAdapterFactory
{
}

class TypeAdapterSqlite extends TypeAdapterFactory
{
}

class TypeAdapterMysql extends TypeAdapterFactory
{
    public function show_create_table($tableName)
    {
        return "SHOW CREATE TABLE `$tableName`";
    }

    public function show_tables()
    {
        if ( func_num_args() != 1 )
            return "";

        $args = func_get_args();
        $dbName = $args[0];

        return "SELECT TABLE_NAME AS tbl_name " .
            "FROM INFORMATION_SCHEMA.TABLES " .
            "WHERE TABLE_TYPE='BASE TABLE' AND TABLE_SCHEMA='$dbName'";
    }

    public function start_transaction()
    {
        return "SET GLOBAL TRANSACTION ISOLATION LEVEL REPEATABLE READ; " .
            "START TRANSACTION";
    }

    public function commit_transaction()
    {
        return "COMMIT";
    }

    public function lock_table()
    {
        if ( func_num_args() != 1 )
            return "";

        $args = func_get_args();
        $tableName = $args[0];
        return "LOCK TABLES `$tableName` READ LOCAL";
    }

    public function unlock_table()
    {
        return "UNLOCK TABLES";
    }

    public function start_add_lock_table()
    {
        if ( func_num_args() != 1 )
            return "";

        $args = func_get_args();
        $tableName = $args[0];

        return "LOCK TABLES `$tableName` WRITE;\n";
    }

    public function end_add_lock_table()
    {
        return "UNLOCK TABLES;\n";
    }

    public function start_disable_foreign_keys_check()
    {
        return "-- Ignore checking of foreign keys\n" .
            "SET FOREIGN_KEY_CHECKS = 0;\n\n";
    }

    public function end_disable_foreign_keys_check()
    {
        return "\n-- Unignore checking of foreign keys\n" .
            "SET FOREIGN_KEY_CHECKS = 1; \n\n";
    }

    public function add_drop_database()
    {
        $ret = "";
        if ( func_num_args() != 2 )
            return $ret;

        $args = func_get_args();
        $dbName = $args[0];
        $dbHandler = $args[1];

        $ret .= "/*!40000 DROP DATABASE IF EXISTS `" . $dbName . "`*/;\n";

        $rs = $dbHandler->query("SHOW VARIABLES LIKE 'character_set_database';");
        $characterSet = $rs->fetchColumn(1);

        $rs = $dbHandler->query("SHOW VARIABLES LIKE 'collation_database';");
        $collationDb = $rs->fetchColumn(1);

        $ret .= "CREATE DATABASE /*!32312 IF NOT EXISTS*/ `" . $dbName .
            "` /*!40100 DEFAULT CHARACTER SET " . $characterSet .
            " COLLATE " . $collationDb . "*/;\n" .
            "USE `" . $dbName . "`;\n\n";

        return $ret;
    }
}
