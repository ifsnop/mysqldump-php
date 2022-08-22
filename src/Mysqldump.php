<?php

/**
 * PHP version of mysqldump cli that comes with MySQL.
 *
 * Tags: mysql mysqldump pdo php7 php5 database php sql mariadb mysql-backup.
 *
 * @category Library
 * @package  Druidfi\Mysqldump
 * @author   Marko Korhonen <marko.korhonen@druid.fi>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     https://github.com/druidfi/mysqldump-php
 * @see      https://github.com/ifsnop/mysqldump-php
 */

namespace Druidfi\Mysqldump;

use Druidfi\Mysqldump\Compress\CompressInterface;
use Druidfi\Mysqldump\Compress\CompressManagerFactory;
use Druidfi\Mysqldump\TypeAdapter\TypeAdapterFactory;
use Druidfi\Mysqldump\TypeAdapter\TypeAdapterInterface;
use Exception;
use PDO;
use PDOException;

class Mysqldump
{
    // Same as mysqldump.
    const MAXLINESIZE = 1000000;

    // List of available connection strings.
    const UTF8    = 'utf8';
    const UTF8MB4 = 'utf8mb4';

    // Database
    private string $dsn;
    private ?string $user;
    private ?string $pass;
    private string $host;
    private string $dbName;
    private ?PDO $conn = null;
    private string $dbType = '';
    private CompressInterface $io;
    private TypeAdapterInterface $db;

    private array $dumpSettings;
    private array $pdoSettings;

    private array $tableColumnTypes = [];
    private $transformTableRowCallable;
    private $transformColumnValueCallable;
    private $infoCallable;

    // Internal data arrays.
    private array $tables = [];
    private array $views = [];
    private array $triggers = [];
    private array $procedures = [];
    private array $functions = [];
    private array $events = [];

    /**
     * Keyed on table name, with the value as the conditions.
     * e.g. - 'users' => 'date_registered > NOW() - INTERVAL 6 MONTH'
     */
    private array $tableWheres = [];
    private array $tableLimits = [];

    /**
     * Constructor of Mysqldump. Note that in the case of an SQLite database
     * connection, the filename must be in the $db parameter.
     *
     * @param string $dsn PDO DSN connection string
     * @param string|null $user SQL account username
     * @param string|null $pass SQL account password
     * @param array $dumpSettings SQL database settings
     * @param array $pdoSettings PDO configured attributes
     * @throws Exception
     */
    public function __construct(
        string $dsn = '',
        ?string $user = null,
        ?string $pass = null,
        array $dumpSettings = [],
        array $pdoSettings = []
    ) {
        $dumpSettingsDefault = [
            'include-tables' => [],
            'exclude-tables' => [],
            'include-views' => [],
            'compress' => CompressManagerFactory::NONE,
            'init_commands' => [],
            'no-data' => [],
            'if-not-exists' => false,
            'reset-auto-increment' => false,
            'add-drop-database' => false,
            'add-drop-table' => false,
            'add-drop-trigger' => true,
            'add-locks' => true,
            'complete-insert' => false,
            'databases' => false,
            'default-character-set' => self::UTF8,
            'disable-keys' => true,
            'extended-insert' => true,
            'events' => false,
            'hex-blob' => true, /* faster than escaped content */
            'insert-ignore' => false,
            'net_buffer_length' => self::MAXLINESIZE,
            'no-autocommit' => true,
            'no-create-info' => false,
            'lock-tables' => true,
            'routines' => false,
            'single-transaction' => true,
            'skip-triggers' => false,
            'skip-tz-utc' => false,
            'skip-comments' => false,
            'skip-dump-date' => false,
            'skip-definer' => false,
            'where' => '',
            /* deprecated */
            'disable-foreign-keys-check' => true
        ];

        $pdoSettingsDefault = [
            PDO::ATTR_PERSISTENT => true,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ];

        $this->user = $user;
        $this->pass = $pass;
        $this->parseDsn($dsn);

        // This drops MYSQL dependency, only use the constant if it's defined.
        if ('mysql' === $this->dbType) {
            $pdoSettingsDefault[PDO::MYSQL_ATTR_USE_BUFFERED_QUERY] = false;
        }

        $this->pdoSettings = array_replace_recursive($pdoSettingsDefault, $pdoSettings);
        $this->dumpSettings = array_replace_recursive($dumpSettingsDefault, $dumpSettings);
        $this->dumpSettings['init_commands'][] = "SET NAMES ".$this->dumpSettings['default-character-set'];

        if (false === $this->dumpSettings['skip-tz-utc']) {
            $this->dumpSettings['init_commands'][] = "SET TIME_ZONE='+00:00'";
        }

        $diff = array_diff(array_keys($this->dumpSettings), array_keys($dumpSettingsDefault));

        if (count($diff) > 0) {
            throw new Exception("Unexpected value in dumpSettings: (".implode(",", $diff).")");
        }

        if (!is_array($this->dumpSettings['include-tables']) || !is_array($this->dumpSettings['exclude-tables'])) {
            throw new Exception('Include-tables and exclude-tables should be arrays');
        }

        // If no include-views is passed in, dump the same views as tables, mimic mysqldump behaviour.
        if (!isset($dumpSettings['include-views'])) {
            $this->dumpSettings['include-views'] = $this->dumpSettings['include-tables'];
        }

        // Create a new compressManager to manage compressed output
        $this->io = CompressManagerFactory::create($this->dumpSettings['compress']);
    }

    /**
     * Get table column types.
     */
    protected function tableColumnTypes(): array
    {
        return $this->tableColumnTypes;
    }

    /**
     * Keyed by table name, with the value as the conditions:
     * e.g. 'users' => 'date_registered > NOW() - INTERVAL 6 MONTH AND deleted=0'
     */
    public function setTableWheres(array $tableWheres)
    {
        $this->tableWheres = $tableWheres;
    }

    public function getTableWhere(string $tableName)
    {
        if (!empty($this->tableWheres[$tableName])) {
            return $this->tableWheres[$tableName];
        } elseif ($this->dumpSettings['where']) {
            return $this->dumpSettings['where'];
        }

        return false;
    }

    /**
     * Keyed by table name, with the value as the numeric limit: e.g. 'users' => 3000
     */
    public function setTableLimits(array $tableLimits)
    {
        $this->tableLimits = $tableLimits;
    }

    /**
     * Returns the LIMIT for the table.  Must be numeric to be returned.
     */
    public function getTableLimit(string $tableName)
    {
        if (!isset($this->tableLimits[$tableName])) {
            return false;
        }

        $limit = $this->tableLimits[$tableName];

        if (!is_numeric($limit)) {
            return false;
        }

        return $limit;
    }

    /**
     * Parse DSN string and extract dbname value
     * Several examples of a DSN string
     *   mysql:host=localhost;dbname=testdb
     *   mysql:host=localhost;port=3307;dbname=testdb
     *   mysql:unix_socket=/tmp/mysql.sock;dbname=testdb
     *
     * @param string $dsn dsn string to parse
     * @throws Exception
     */
    private function parseDsn(string $dsn): void
    {
        if (empty($dsn) || (false === ($pos = strpos($dsn, ':')))) {
            throw new Exception('Empty DSN string');
        }

        $this->dsn = $dsn;
        $this->dbType = strtolower(substr($dsn, 0, $pos));

        if (empty($this->dbType)) {
            throw new Exception('Missing database type from DSN string');
        }

        $dsn = substr($dsn, $pos + 1);
        $dsnArray = [];

        foreach (explode(';', $dsn) as $kvp) {
            $kvpArr = explode('=', $kvp);
            $dsnArray[strtolower($kvpArr[0])] = $kvpArr[1];
        }

        if (empty($dsnArray['host']) && empty($dsnArray['unix_socket'])) {
            throw new Exception('Missing host from DSN string');
        }

        $this->host = (!empty($dsnArray['host'])) ? $dsnArray['host'] : $dsnArray['unix_socket'];

        if (empty($dsnArray['dbname'])) {
            throw new Exception('Missing database name from DSN string');
        }

        $this->dbName = $dsnArray['dbname'];
    }

    /**
     * Connect with PDO.
     *
     * @throws Exception
     */
    private function connect()
    {
        try {
            $this->conn = new PDO($this->dsn, $this->user, $this->pass, $this->pdoSettings);
        } catch (PDOException $e) {
            $message = sprintf("Connection to %s failed with message: %s", $this->host, $e->getMessage());
            throw new Exception($message);
        }

        $this->conn->setAttribute(PDO::ATTR_ORACLE_NULLS, PDO::NULL_NATURAL);
        $this->db = TypeAdapterFactory::create($this->dbType, $this->conn, $this->dumpSettings);
    }

    /**
     * Primary function, triggers dumping.
     *
     * @param string|null $filename Name of file to write sql dump to
     * @throws Exception
     */
    public function start(?string $filename = '')
    {
        $destination = 'php://stdout';

        // Output file can be redefined here
        if (!empty($filename)) {
            $destination = $filename;
        }

        // Connect to database
        $this->connect();

        // Create output file
        $this->io->open($destination);

        // Write some basic info to output file
        if (!$this->dumpSettings['skip-comments']) {
            $this->io->write($this->getDumpFileHeader());
        }

        // Store server settings and use saner defaults to dump
        $this->io->write($this->db->backupParameters());

        if ($this->dumpSettings['databases']) {
            $this->io->write($this->db->getDatabaseHeader($this->dbName));

            if ($this->dumpSettings['add-drop-database']) {
                $this->io->write($this->db->addDropDatabase($this->dbName));
            }
        }

        // Get table, view, trigger, procedures, functions and events structures from database.
        $this->getDatabaseStructureTables();
        $this->getDatabaseStructureViews();
        $this->getDatabaseStructureTriggers();
        $this->getDatabaseStructureProcedures();
        $this->getDatabaseStructureFunctions();
        $this->getDatabaseStructureEvents();

        if ($this->dumpSettings['databases']) {
            $this->io->write($this->db->databases($this->dbName));
        }

        // If there still are some tables/views in include-tables array, that means that some tables or views weren't
        // found. Give proper error and exit. This check will be removed once include-tables supports regexps.
        if (0 < count($this->dumpSettings['include-tables'])) {
            $name = implode(',', $this->dumpSettings['include-tables']);
            $message = sprintf("Table '%s' not found in database", $name);
            throw new Exception($message);
        }

        $this->exportTables();
        $this->exportTriggers();
        $this->exportFunctions();
        $this->exportProcedures();
        $this->exportViews();
        $this->exportEvents();

        // Restore saved parameters.
        $this->io->write($this->db->restoreParameters());

        // Write some stats to output file.
        if (!$this->dumpSettings['skip-comments']) {
            $this->io->write($this->getDumpFileFooter());
        }

        // Close output file.
        $this->io->close();
    }

    /**
     * Returns header for dump file.
     */
    private function getDumpFileHeader(): string
    {
        // Some info about software, source and time
        $header = sprintf(
            "-- mysqldump-php https://github.com/druidfi/mysqldump-php". PHP_EOL.
            "--". PHP_EOL.
            "-- Host: %s\tDatabase: %s". PHP_EOL.
            "-- ------------------------------------------------------". PHP_EOL,
            $this->host,
            $this->dbName
        );

        if (!empty($version = $this->db->getVersion())) {
            $header .= "-- Server version \t". $version . PHP_EOL;
        }

        if (!$this->dumpSettings['skip-dump-date']) {
            $header .= "-- Date: ".date('r'). PHP_EOL . PHP_EOL;
        }

        return $header;
    }

    /**
     * Returns footer for dump file.
     */
    private function getDumpFileFooter(): string
    {
        $footer = '-- Dump completed';

        if (!$this->dumpSettings['skip-dump-date']) {
            $footer .= ' on: '.date('r');
        }

        $footer .= PHP_EOL;

        return $footer;
    }

    /**
     * Reads table names from database. Fills $this->tables array so they will be dumped later.
     */
    private function getDatabaseStructureTables()
    {
        // Listing all tables from database
        if (empty($this->dumpSettings['include-tables'])) {
            // include all tables for now, blacklisting happens later
            foreach ($this->conn->query($this->db->showTables($this->dbName)) as $row) {
                $this->tables[] = current($row);
            }
        } else {
            // include only the tables mentioned in include-tables
            foreach ($this->conn->query($this->db->showTables($this->dbName)) as $row) {
                if (in_array(current($row), $this->dumpSettings['include-tables'], true)) {
                    $this->tables[] = current($row);
                    $elem = array_search(current($row), $this->dumpSettings['include-tables']);
                    unset($this->dumpSettings['include-tables'][$elem]);
                }
            }
        }
    }

    /**
     * Reads view names from database. Fills $this->tables array so they will be dumped later.
     */
    private function getDatabaseStructureViews()
    {
        // Listing all views from database
        if (empty($this->dumpSettings['include-views'])) {
            // include all views for now, blacklisting happens later
            foreach ($this->conn->query($this->db->showViews($this->dbName)) as $row) {
                $this->views[] = current($row);
            }
        } else {
            // include only the tables mentioned in include-tables
            foreach ($this->conn->query($this->db->showViews($this->dbName)) as $row) {
                if (in_array(current($row), $this->dumpSettings['include-views'], true)) {
                    $this->views[] = current($row);
                    $elem = array_search(current($row), $this->dumpSettings['include-views']);
                    unset($this->dumpSettings['include-views'][$elem]);
                }
            }
        }
    }

    /**
     * Reads trigger names from database. Fills $this->tables array so they will be dumped later.
     */
    private function getDatabaseStructureTriggers()
    {
        // Listing all triggers from database
        if (false === $this->dumpSettings['skip-triggers']) {
            foreach ($this->conn->query($this->db->showTriggers($this->dbName)) as $row) {
                $this->triggers[] = $row['Trigger'];
            }
        }
    }

    /**
     * Reads procedure names from database. Fills $this->tables array so they will be dumped later.
     */
    private function getDatabaseStructureProcedures()
    {
        // Listing all procedures from database
        if ($this->dumpSettings['routines']) {
            foreach ($this->conn->query($this->db->showProcedures($this->dbName)) as $row) {
                $this->procedures[] = $row['procedure_name'];
            }
        }
    }

    /**
     * Reads functions names from database. Fills $this->tables array so they will be dumped later.
     */
    private function getDatabaseStructureFunctions()
    {
        // Listing all functions from database
        if ($this->dumpSettings['routines']) {
            foreach ($this->conn->query($this->db->showFunctions($this->dbName)) as $row) {
                $this->functions[] = $row['function_name'];
            }
        }
    }

    /**
     * Reads event names from database. Fills $this->tables array so they will be dumped later.
     */
    private function getDatabaseStructureEvents()
    {
        // Listing all events from database
        if ($this->dumpSettings['events']) {
            foreach ($this->conn->query($this->db->showEvents($this->dbName)) as $row) {
                $this->events[] = $row['event_name'];
            }
        }
    }

    /**
     * Compare if $table name matches with a definition inside $arr.
     */
    private function matches(string $table, array $arr): bool
    {
        $match = false;

        foreach ($arr as $pattern) {
            if ('/' != $pattern[0]) {
                continue;
            }

            if (1 == preg_match($pattern, $table)) {
                $match = true;
            }
        }

        return in_array($table, $arr) || $match;
    }

    /**
     * Exports all the tables selected from database
     */
    private function exportTables()
    {
        // Exporting tables one by one
        foreach ($this->tables as $table) {
            if ($this->matches($table, $this->dumpSettings['exclude-tables'])) {
                continue;
            }

            $this->getTableStructure($table);

            if (false === $this->dumpSettings['no-data']) { // don't break compatibility with old trigger
                $this->listValues($table);
            } elseif (true === $this->dumpSettings['no-data']
                || $this->matches($table, $this->dumpSettings['no-data'])) {
                continue;
            } else {
                $this->listValues($table);
            }
        }
    }

    /**
     * Exports all the views found in database.
     */
    private function exportViews()
    {
        if (false === $this->dumpSettings['no-create-info']) {
            // Exporting views one by one
            foreach ($this->views as $view) {
                if ($this->matches($view, $this->dumpSettings['exclude-tables'])) {
                    continue;
                }

                $this->tableColumnTypes[$view] = $this->getTableColumnTypes($view);
                $this->getViewStructureTable($view);
            }

            foreach ($this->views as $view) {
                if ($this->matches($view, $this->dumpSettings['exclude-tables'])) {
                    continue;
                }

                $this->getViewStructureView($view);
            }
        }
    }

    /**
     * Exports all the triggers found in database.
     */
    private function exportTriggers()
    {
        foreach ($this->triggers as $trigger) {
            $this->getTriggerStructure($trigger);
        }
    }

    /**
     * Exports all the procedures found in database.
     */
    private function exportProcedures()
    {
        foreach ($this->procedures as $procedure) {
            $this->getProcedureStructure($procedure);
        }
    }

    /**
     * Exports all the functions found in database.
     */
    private function exportFunctions()
    {
        foreach ($this->functions as $function) {
            $this->getFunctionStructure($function);
        }
    }

    /**
     * Exports all the events found in database.
     */
    private function exportEvents()
    {
        foreach ($this->events as $event) {
            $this->getEventStructure($event);
        }
    }

    /**
     * Table structure extractor.
     *
     * @param string $tableName Name of table to export
     * @TODO move specific mysql code to typeAdapter
     */
    private function getTableStructure(string $tableName)
    {
        if (!$this->dumpSettings['no-create-info']) {
            $ret = '';

            if (!$this->dumpSettings['skip-comments']) {
                $ret = "--".PHP_EOL.
                    "-- Table structure for table `$tableName`".PHP_EOL.
                    "--".PHP_EOL.PHP_EOL;
            }

            $stmt = $this->db->showCreateTable($tableName);

            foreach ($this->conn->query($stmt) as $r) {
                $this->io->write($ret);

                if ($this->dumpSettings['add-drop-table']) {
                    $this->io->write(
                        $this->db->dropTable($tableName)
                    );
                }

                $this->io->write($this->db->createTable($r));

                break;
            }
        }

        $this->tableColumnTypes[$tableName] = $this->getTableColumnTypes($tableName);
    }

    /**
     * Store column types to create data dumps and for Stand-In tables.
     *
     * @param string $tableName Name of table to export
     * @return array type column types detailed
     */
    private function getTableColumnTypes(string $tableName): array
    {
        $columnTypes = [];
        $columns = $this->conn->query($this->db->showColumns($tableName));
        $columns->setFetchMode(PDO::FETCH_ASSOC);

        foreach ($columns as $col) {
            $types = $this->db->parseColumnType($col);
            $columnTypes[$col['Field']] = [
                'is_numeric'=> $types['is_numeric'],
                'is_blob' => $types['is_blob'],
                'type' => $types['type'],
                'type_sql' => $col['Type'],
                'is_virtual' => $types['is_virtual']
            ];
        }

        return $columnTypes;
    }

    /**
     * View structure extractor, create table (avoids cyclic references).
     *
     * @param string $viewName Name of view to export
     * @TODO move mysql specific code to typeAdapter
     */
    private function getViewStructureTable(string $viewName)
    {
        if (!$this->dumpSettings['skip-comments']) {
            $ret = (
                '--' . PHP_EOL .
                sprintf('-- Stand-In structure for view `%s`', $viewName) . PHP_EOL .
                '--' . PHP_EOL . PHP_EOL
            );

            $this->io->write($ret);
        }

        $stmt = $this->db->showCreateView($viewName);

        // create views as tables, to resolve dependencies
        foreach ($this->conn->query($stmt) as $r) {
            if ($this->dumpSettings['add-drop-table']) {
                $this->io->write($this->db->dropView($viewName));
            }

            $this->io->write($this->createStandInTable($viewName));

            break;
        }
    }

    /**
     * Write a create table statement for the table Stand-In, show create
     * table would return a create algorithm when used on a view.
     *
     * @param string $viewName Name of view to export
     * @return string create statement
     */
    public function createStandInTable(string $viewName): string
    {
        $ret = [];

        foreach ($this->tableColumnTypes[$viewName] as $k => $v) {
            $ret[] = sprintf('`%s` %s', $k, $v['type_sql']);
        }

        $ret = implode(PHP_EOL . ',', $ret);

        return sprintf(
            "CREATE TABLE IF NOT EXISTS `%s` (".PHP_EOL."%s".PHP_EOL.");".PHP_EOL,
            $viewName,
            $ret
        );
    }

    /**
     * View structure extractor, create view.
     *
     * @TODO move mysql specific code to typeAdapter
     * @param string $viewName Name of view to export
     */
    private function getViewStructureView(string $viewName)
    {
        if (!$this->dumpSettings['skip-comments']) {
            $ret = sprintf(
                "--". PHP_EOL.
                "-- View structure for view `%s`". PHP_EOL.
                "--". PHP_EOL . PHP_EOL,
                $viewName
            );

            $this->io->write($ret);
        }

        $stmt = $this->db->showCreateView($viewName);

        // Create views, to resolve dependencies replacing tables with views
        foreach ($this->conn->query($stmt) as $r) {
            // Because we must replace table with view, we should delete it
            $this->io->write($this->db->dropView($viewName));
            $this->io->write($this->db->createView($r));

            break;
        }
    }

    /**
     * Trigger structure extractor.
     *
     * @param string $triggerName Name of trigger to export
     */
    private function getTriggerStructure(string $triggerName)
    {
        $stmt = $this->db->showCreateTrigger($triggerName);

        foreach ($this->conn->query($stmt) as $r) {
            if ($this->dumpSettings['add-drop-trigger']) {
                $this->io->write($this->db->addDropTrigger($triggerName));
            }

            $this->io->write($this->db->createTrigger($r));

            return;
        }
    }

    /**
     * Procedure structure extractor.
     *
     * @param string $procedureName Name of procedure to export
     */
    private function getProcedureStructure(string $procedureName)
    {
        if (!$this->dumpSettings['skip-comments']) {
            $ret = "--".PHP_EOL.
                "-- Dumping routines for database '".$this->dbName."'".PHP_EOL.
                "--".PHP_EOL.PHP_EOL;
            $this->io->write($ret);
        }

        $stmt = $this->db->showCreateProcedure($procedureName);

        foreach ($this->conn->query($stmt) as $r) {
            $this->io->write($this->db->createProcedure($r));

            return;
        }
    }

    /**
     * Function structure extractor.
     *
     * @param string $functionName Name of function to export
     */
    private function getFunctionStructure(string $functionName)
    {
        if (!$this->dumpSettings['skip-comments']) {
            $ret = "--".PHP_EOL.
                "-- Dumping routines for database '".$this->dbName."'".PHP_EOL.
                "--".PHP_EOL.PHP_EOL;
            $this->io->write($ret);
        }

        $stmt = $this->db->showCreateFunction($functionName);

        foreach ($this->conn->query($stmt) as $r) {
            $this->io->write($this->db->createFunction($r));

            return;
        }
    }

    /**
     * Event structure extractor.
     *
     * @param string $eventName Name of event to export
     */
    private function getEventStructure(string $eventName)
    {
        if (!$this->dumpSettings['skip-comments']) {
            $ret = "--".PHP_EOL.
                "-- Dumping events for database '".$this->dbName."'".PHP_EOL.
                "--".PHP_EOL.PHP_EOL;
            $this->io->write($ret);
        }

        $stmt = $this->db->showCreateEvent($eventName);

        foreach ($this->conn->query($stmt) as $r) {
            $this->io->write($this->db->createEvent($r));

            return;
        }
    }

    /**
     * Prepare values for output.
     *
     * @param string $tableName Name of table which contains rows
     * @param array $row Associative array of column names and values to be quoted
     */
    private function prepareColumnValues(string $tableName, array $row): array
    {
        $ret = [];
        $columnTypes = $this->tableColumnTypes[$tableName];

        if ($this->transformTableRowCallable) {
            $row = call_user_func($this->transformTableRowCallable, $tableName, $row);
        }

        foreach ($row as $colName => $colValue) {
            if ($this->transformColumnValueCallable) {
                $colValue = call_user_func($this->transformColumnValueCallable, $tableName, $colName, $colValue, $row);
            }

            $ret[] = $this->escape($colValue, $columnTypes[$colName]);
        }

        return $ret;
    }

    /**
     * Escape values with quotes when needed.
     */
    private function escape(?string $colValue, array $colType)
    {
        if (is_null($colValue)) {
            return 'NULL';
        } elseif ($this->dumpSettings['hex-blob'] && $colType['is_blob']) {
            if ($colType['type'] == 'bit' || !empty($colValue)) {
                return sprintf('0x%s', $colValue);
            } else {
                return "''";
            }
        } elseif ($colType['is_numeric']) {
            return $colValue;
        }

        return $this->conn->quote($colValue);
    }

    /**
     * Set a callable that will be used to transform table rows.
     */
    public function setTransformTableRowHook(callable $callable)
    {
        $this->transformTableRowCallable = $callable;
    }

    /**
     * Set a callable that will be used to report dump information.
     */
    public function setInfoHook(callable $callable)
    {
        $this->infoCallable = $callable;
    }

    /**
     * Table rows extractor.
     *
     * @param string $tableName Name of table to export
     */
    private function listValues(string $tableName)
    {
        $this->prepareListValues($tableName);

        $onlyOnce = true;
        $lineSize = 0;
        $colNames = [];

        // colStmt is used to form a query to obtain row values
        $colStmt = $this->getColumnStmt($tableName);

        // colNames is used to get the name of the columns when using complete-insert
        if ($this->dumpSettings['complete-insert']) {
            $colNames = $this->getColumnNames($tableName);
        }

        $stmt = "SELECT ".implode(",", $colStmt)." FROM `$tableName`";

        // Table specific conditions override the default 'where'
        $condition = $this->getTableWhere($tableName);

        if ($condition) {
            $stmt .= " WHERE {$condition}";
        }

        $limit = $this->getTableLimit($tableName);

        if ($limit !== false) {
            $stmt .= " LIMIT {$limit}";
        }

        $resultSet = $this->conn->query($stmt);
        $resultSet->setFetchMode(PDO::FETCH_ASSOC);

        $ignore = $this->dumpSettings['insert-ignore'] ? '  IGNORE' : '';

        $count = 0;
        foreach ($resultSet as $row) {
            $count++;
            $values = $this->prepareColumnValues($tableName, $row);
            if ($onlyOnce || !$this->dumpSettings['extended-insert']) {
                if ($this->dumpSettings['complete-insert'] && count($colNames)) {
                    $lineSize += $this->io->write(
                        "INSERT$ignore INTO `$tableName` (".
                        implode(", ", $colNames).
                        ") VALUES (".implode(",", $values).")"
                    );
                } else {
                    $lineSize += $this->io->write(
                        "INSERT$ignore INTO `$tableName` VALUES (".implode(",", $values).")"
                    );
                }
                $onlyOnce = false;
            } else {
                $lineSize += $this->io->write(",(".implode(",", $values).")");
            }
            if (($lineSize > $this->dumpSettings['net_buffer_length']) || !$this->dumpSettings['extended-insert']) {
                $onlyOnce = true;
                $lineSize = $this->io->write(";".PHP_EOL);
            }
        }

        $resultSet->closeCursor();

        if (!$onlyOnce) {
            $this->io->write(";".PHP_EOL);
        }

        $this->endListValues($tableName, $count);

        if ($this->infoCallable && is_callable($this->infoCallable)) {
            call_user_func($this->infoCallable, 'table', ['name' => $tableName, 'rowCount' => $count]);
        }
    }

    /**
     * Table rows extractor, append information prior to dump.
     *
     * @param string $tableName Name of table to export
     */
    public function prepareListValues(string $tableName)
    {
        if (!$this->dumpSettings['skip-comments']) {
            $this->io->write(
                "--".PHP_EOL.
                "-- Dumping data for table `$tableName`".PHP_EOL.
                "--".PHP_EOL.PHP_EOL
            );
        }

        if ($this->dumpSettings['single-transaction']) {
            $this->conn->exec($this->db->setupTransaction());
            $this->conn->exec($this->db->startTransaction());
        }

        if ($this->dumpSettings['lock-tables'] && !$this->dumpSettings['single-transaction']) {
            $this->db->lockTable($tableName);
        }

        if ($this->dumpSettings['add-locks']) {
            $this->io->write($this->db->startAddLockTable($tableName));
        }

        if ($this->dumpSettings['disable-keys']) {
            $this->io->write($this->db->startAddDisableKeys($tableName));
        }

        // Disable autocommit for faster reload
        if ($this->dumpSettings['no-autocommit']) {
            $this->io->write($this->db->startDisableAutocommit());
        }
    }

    /**
     * Table rows extractor, close locks and commits after dump.
     *
     * @param string $tableName Name of table to export.
     * @param integer $count Number of rows inserted.
     */
    public function endListValues(string $tableName, int $count = 0)
    {
        if ($this->dumpSettings['disable-keys']) {
            $this->io->write($this->db->endAddDisableKeys($tableName));
        }

        if ($this->dumpSettings['add-locks']) {
            $this->io->write($this->db->endAddLockTable($tableName));
        }

        if ($this->dumpSettings['single-transaction']) {
            $this->conn->exec($this->db->commitTransaction());
        }

        if ($this->dumpSettings['lock-tables'] && !$this->dumpSettings['single-transaction']) {
            $this->db->unlockTable($tableName);
        }

        // Commit to enable autocommit
        if ($this->dumpSettings['no-autocommit']) {
            $this->io->write($this->db->endDisableAutocommit());
        }

        $this->io->write(PHP_EOL);

        if (!$this->dumpSettings['skip-comments']) {
            $this->io->write(
                "-- Dumped table `".$tableName."` with $count row(s)".PHP_EOL.
                '--'.PHP_EOL.PHP_EOL
            );
        }
    }

    /**
     * Build SQL List of all columns on current table which will be used for selecting.
     *
     * @param string $tableName Name of table to get columns
     *
     * @return array SQL sentence with columns for select
     */
    public function getColumnStmt(string $tableName): array
    {
        $colStmt = [];

        foreach ($this->tableColumnTypes[$tableName] as $colName => $colType) {
            if ($colType['type'] == 'bit' && $this->dumpSettings['hex-blob']) {
                $colStmt[] = sprintf("LPAD(HEX(`%s`),2,'0') AS `%s`", $colName, $colName);
            } elseif ($colType['is_blob'] && $this->dumpSettings['hex-blob']) {
                $colStmt[] = sprintf("HEX(`%s`) AS `%s`", $colName, $colName);
            } elseif ($colType['is_virtual']) {
                $this->dumpSettings['complete-insert'] = true;
            } else {
                $colStmt[] = sprintf("`%s`", $colName);
            }
        }

        return $colStmt;
    }

    /**
     * Build SQL List of all columns on current table which will be used for inserting.
     *
     * @param string $tableName Name of table to get columns
     *
     * @return array columns for sql sentence for insert
     */
    public function getColumnNames(string $tableName): array
    {
        $colNames = [];

        foreach ($this->tableColumnTypes[$tableName] as $colName => $colType) {
            if ($colType['is_virtual']) {
                $this->dumpSettings['complete-insert'] = true;
            } else {
                $colNames[] = sprintf('`%s`', $colName);
            }
        }

        return $colNames;
    }
}
