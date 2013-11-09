<?php

namespace Clouddueling\Mysqldump;

use Exception;
use PDO;

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
    private $settings = array();
    private $tables = array();
    private $views = array();
    private $dbHandler;
    private $dbType;
    private $compressManager;
    private $typeAdapter;
    private $pdo_options;

    /**
     * Constructor of Mysqldump. Note that in the case of an SQLite database connection, the filename must be in the $db parameter.
     *
     * @param string $db        Database name
     * @param string $user      SQL account username
     * @param string $pass      SQL account password
     * @param string $host      SQL server to connect to
     * @param string $type      SQL database type
     * @return null
     */
    public function __construct($db = '', $user = '', $pass = '',
        $host = 'localhost', $type = "mysql", $settings = null,
        $pdo_options = array(PDO::ATTR_PERSISTENT => true,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION))
    {
        $defaultSettings = array(
            'include-tables' => array(),
            'exclude-tables' => array(),
            'compress' => 'None',
            'no-data' => false,
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
        $this->dbType = strtolower($type);
        $this->pdo_options = $pdo_options;
        $this->settings = Mysqldump::array_replace_recursive($defaultSettings, $settings);
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
            switch ($this->dbType) {
                case 'sqlite':
                    $this->dbHandler = new PDO("sqlite:" . $this->db, null, null, $this->pdo_options);
                    break;
                case 'mysql': case 'pgsql': case 'dblib':
                    $this->dbHandler = new PDO($this->dbType . ":host=" .
                        $this->host.";dbname=" . $this->db, $this->user,
                        $this->pass, $this->pdo_options);
                    // Fix for always-unicode output
                    $this->dbHandler->exec("SET NAMES utf8");
                    break;

                default:
                    throw new Exception("Unsupported database type: '" . $this->dbType . "'", 3);
            }
        } catch (PDOException $e) {
            throw new Exception("Connection to " . $this->dbType . " failed with message: " .
                    $e->getMessage(), 3);
        }

        $this->dbHandler->setAttribute(PDO::ATTR_ORACLE_NULLS, PDO::NULL_NATURAL);
        $this->typeAdapter = TypeAdapterFactory::create($this->dbType);
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
        $this->compressManager = CompressManagerFactory::create($this->settings['compress']);

        if (! $this->compressManager->open($this->fileName)) {
            throw new Exception("Output file is not writable", 2);
        }

        // Formating dump file
        $this->compressManager->write($this->getHeader());

        // Listing all tables from database
        $this->tables = array();
        foreach ($this->dbHandler->query($this->typeAdapter->show_tables($this->db)) as $row) {
            if (empty($this->settings['include-tables']) || (! empty($this->settings['include-tables']) && in_array(current($row), $this->settings['include-tables'], true))) {
                array_push($this->tables, current($row));
            }
        }

        // Disable checking foreign keys
        if ( $this->settings['disable-foreign-keys-check'] ) {
            $this->compressManager->write(
                $this->typeAdapter->start_disable_foreign_keys_check()
            );
        }

        // Exporting tables one by one
        foreach ($this->tables as $table) {
            if (in_array($table, $this->settings['exclude-tables'], true)) {
                continue;
            }
            $is_table = $this->getTableStructure($table);
            if (true === $is_table && false === $this->settings['no-data']) {
                $this->listValues($table);
            }
        }

        // Exporting views one by one
        foreach ($this->views as $view) {
            $this->compressManager->write($view);
        }

        // Enable checking foreign keys if needed
        if ( $this->settings['disable-foreign-keys-check'] ) {
            $this->compressManager->write(
                $this->typeAdapter->end_disable_foreign_keys_check()
            );
        }

        $this->compressManager->close();
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
        $stmt = $this->typeAdapter->show_create_table($tablename);
        foreach ($this->dbHandler->query($stmt) as $r) {
            if (isset($r['Create Table'])) {
                $this->compressManager->write(
                    "-- --------------------------------------------------------" .
                    "\n\n" .
                    "--\n" .
                    "-- Table structure for table `$tablename`\n" .
                    "--\n\n"
                );

                if ($this->settings['add-drop-table']) {
                    $this->compressManager->write("DROP TABLE IF EXISTS `$tablename`;\n\n");
                }
                $this->compressManager->write($r['Create Table'] . ";\n\n");
                return true;
            }

            if ( isset($r['Create View']) ) {
                $view  = "-- --------------------------------------------------------" .
                        "\n\n" .
                        "--\n" .
                        "-- Table structure for view `$tablename`\n" .
                        "--\n\n";
                $view .= $r['Create View'] . ";\n\n";
                $this->views[] = $view;
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
        $this->compressManager->write(
            "--\n" .
            "-- Dumping data for table `$tablename`\n" .
            "--\n\n"
        );

        if ($this->settings['single-transaction']) {
            $this->dbHandler->exec($this->typeAdapter->start_transaction());
        }

        if ($this->settings['lock-tables']) {
            $lockstmt = $this->typeAdapter->lock_table($tablename);
            if(strlen($lockstmt)){
                $this->dbHandler->exec($lockstmt);
            }
        }

        if ( $this->settings['add-locks'] ) {
            $this->compressManager->write($this->typeAdapter->start_add_lock_table($tablename));
        }

        $onlyOnce = true; $lineSize = 0;
        $stmt = "SELECT * FROM `$tablename`";
        foreach ($this->dbHandler->query($stmt, PDO::FETCH_NUM) as $r) {
            $vals = array();
            foreach ($r as $val) {
                $vals[] = is_null($val) ? "NULL" :
                $this->dbHandler->quote($val);
            }
            if ($onlyOnce || !$this->settings['extended-insert'] ) {
                $lineSize += $this->compressManager->write("INSERT INTO `$tablename` VALUES (" . implode(",", $vals) . ")");
                $onlyOnce = false;
            } else {
                $lineSize += $this->compressManager->write(",(" . implode(",", $vals) . ")");
            }
            if ( ($lineSize > Mysqldump::MAXLINESIZE) ||
                    !$this->settings['extended-insert'] ) {
                $onlyOnce = true;
                $lineSize = $this->compressManager->write(";\n");
            }
        }

        if (! $onlyOnce) {
            $this->compressManager->write(";\n");
        }

        if ($this->settings['add-locks']) {
            $this->compressManager->write($this->typeAdapter->end_add_lock_table($tablename));
        }

        if ($this->settings['single-transaction']) {
            $this->dbHandler->exec($this->typeAdapter->commit_transaction());
        }

        if ($this->settings['lock-tables']) {
            $lockstmt = $this->typeAdapter->unlock_table($tablename);
            if( strlen($lockstmt) ){
                $this->dbHandler->exec($lockstmt);
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
    private $fileHandle = null;

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
        $this->fileHandler = bzopen($filename . ".bz2", "w");
        if (false === $this->fileHandler) {
            return false;
        }

        return true;
    }

    public function write($str)
    {
        $bytesWritten = 0;
        if (false === ($bytesWritten = bzwrite($this->fileHandler, $str))) {
            throw new Exception("Writting to file failed! Probably, there is no more free space left?", 4);
        }

        return $bytesWritten;
    }

    public function close()
    {
        return bzclose($this->fileHandler);
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
        $this->fileHandler = gzopen($filename . ".gz", "wb");
        if (false === $this->fileHandler) {
            return false;
        }

        return true;
    }

    public function write($str)
    {
        $bytesWritten = 0;
        if (false === ($bytesWritten = gzwrite($this->fileHandler, $str))) {
            throw new Exception("Writting to file failed! Probably, there is no more free space left?", 4);
        }

        return $bytesWritten;
    }

    public function close()
    {
        return gzclose($this->fileHandler);
    }
}

class CompressNone extends CompressManagerFactory
{
    public function open($filename)
    {
        $this->fileHandler = fopen($filename, "wb");
        if (false === $this->fileHandler) {
            return false;
        }

        return true;
    }

    public function write($str)
    {
        $bytesWritten = 0;
        if (false === ($bytesWritten = fwrite($this->fileHandler, $str))) {
            throw new Exception("Writting to file failed! Probably, there is no more free space left?", 4);
        }

        return $bytesWritten;
    }

    public function close()
    {
        return fclose($this->fileHandler);
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

    public function show_create_table($tablename){
        return "select tbl_name as 'Table', sql as 'Create Table' from sqlite_master where type='table' and tbl_name='$tablename'";
    }

    public function show_tables($dbName){
        return "SELECT tbl_name FROM sqlite_master where type='table'";
    }

    public function start_transaction(){
        return "BEGIN EXCLUSIVE";
    }

    public function commit_transaction(){
        return "COMMIT";
    }

    public function lock_table($tablename){
        return "";
    }

    public function unlock_table($tablename){
        return "";
    }

    public function start_add_lock_table($tablename){
        return "\n";
    }

    public function end_add_lock_table($tablename){
        return "\n";
    }

    public function start_disable_foreign_keys_check() {
        return "\n";
    }

    public function end_disable_foreign_keys_check() {
        return "\n";
    }


}

class TypeAdapterPgsql extends TypeAdapterFactory {}
class TypeAdapterDblib extends TypeAdapterFactory {}
class TypeAdapterSqlite extends TypeAdapterFactory {}

class TypeAdapterMysql extends TypeAdapterFactory
{
    public function show_create_table($tablename){
        return "SHOW CREATE TABLE `$tablename`";
    }

    public function show_tables($dbName){
        return "SELECT TABLE_NAME AS tbl_name FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE='BASE TABLE' AND TABLE_SCHEMA='$dbName'";
    }

    public function start_transaction(){
        return "SET GLOBAL TRANSACTION ISOLATION LEVEL REPEATABLE READ; START TRANSACTION";
    }

    public function commit_transaction(){
        return "COMMIT";
    }

    public function lock_table($tablename){
        return "LOCK TABLES `$tablename` READ LOCAL";
    }

    public function unlock_table($tablename){
        return "UNLOCK TABLES";
    }

    public function start_add_lock_table($tablename){
        return "LOCK TABLES `$tablename` WRITE;\n";
    }

    public function end_add_lock_table($tablename){
        return "UNLOCK TABLES;\n";
    }

    public function start_disable_foreign_keys_check() {
        return "-- Ignore checking of foreign keys\n" .
            "SET FOREIGN_KEY_CHECKS = 0;\n\n";
    }

    public function end_disable_foreign_keys_check() {
        return "\n-- Unignore checking of foreign keys\n" .
            "SET FOREIGN_KEY_CHECKS = 1; \n\n";
    }
}
