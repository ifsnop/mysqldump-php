<?php

/*
* Database MySQLDump Class File
* Copyright (c) 2009 by James Elliott
* James.d.Elliott@gmail.com
* GNU General Public License v3 http://www.gnu.org/licenses/gpl.html
*
*/

class MySQLDump
{

    // This can be set both on constructor or manually
    public $host;
    public $user;
    public $pass;
    public $db;
    public $filename = 'dump.sql';

    // Usable switch
    public $droptableifexists = false;

    //compress
    public $compress = false;

    // Internal stuff
    private $tables = array();
    private $db_handler;
    private $file_handler;

    /**
     * Constructor of MySQLDump
     *
     * @param string $db        Database name
     * @param string $user      MySQL account username
     * @param string $pass      MySQL account password
     * @param string $host      MySQL server to connect to
     * @return null
     */
    public function __construct($db = '', $user = '', $pass = '', $host = 'localhost')
    {
        $this->db = $db;
        $this->user = $user;
        $this->pass = $pass;
        $this->host = $host;
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
        if (!empty($filename)) {
            $this->filename = $filename;
        }
        // We must set a name to continue
        if (empty($this->filename)) {
            throw new \Exception("Output file name is not set", 1);
        }
        // Check for zlib
        if ((true === $this->compress) && !function_exists("gzopen")) {
            throw new \Exception("Compression is enabled, but zlib is not installed or configured properly", 1);
        }
        // Trying to bind a file with block
        if (true === $this->compress) {
            $this->file_handler = gzopen($this->filename, "wb");
        } else {
            $this->file_handler = fopen($this->filename, "wb");
        }
        if (false === $this->file_handler) {
            throw new \Exception("Output file is not writable", 2);
        }
        // Connecting with MySQL
        try {
            $this->db_handler = new \PDO("mysql:dbname={$this->db};host={$this->host}", $this->user, $this->pass);
        } catch (\PDOException $e) {
            throw new \Exception("Connection to MySQL failed with message: " . $e->getMessage(), 3);
        }
        // Fix for always-unicode output
        $this->db_handler->exec("SET NAMES utf8");
        // https://github.com/clouddueling/mysqldump-php/issues/9
        $this->db_handler->setAttribute(PDO::ATTR_ORACLE_NULLS, PDO::NULL_NATURAL);
        // Formating dump file
        $this->writeHeader();
        // Listing all tables from database
        $this->tables = array();
        foreach ($this->db_handler->query("SHOW TABLES") as $row) {
            array_push($this->tables, current($row));
        }
        // Exporting tables one by one
        foreach ($this->tables as $table) {
            $this->write("-- --------------------------------------------------------\n\n");
            $this->getTableStructure($table);
            $this->listValues($table);
        }
        // Releasing file
        if (true === $this->compress) {
            return gzclose($this->file_handler);
        }

        return fclose($this->file_handler);
    }

    /**
     * Output routine
     *
     * @param string $string  SQL to write to dump file
     * @return bool
     */
    private function write($string)
    {
        if (true === $this->compress) {
            if (false === gzwrite($this->file_handler, $string)) {
                throw new \Exception("Writting to file failed! Probably, there is no more free space left?", 4);
            }
        } else {
            if (false === fwrite($this->file_handler, $string)) {
                throw new \Exception("Writting to file failed! Probably, there is no more free space left?", 4);
            }
        }
    }

    /**
     * Writting header for dump file
     *
     * @return null
     */
    private function writeHeader()
    {
        // Some info about software, source and time
        $this->write("-- mysqldump-php SQL Dump\n");
        $this->write("-- https://github.com/clouddueling/mysqldump-php\n");
        $this->write("--\n");
        $this->write("-- Host: {$this->host}\n");
        $this->write("-- Generation Time: " . date('r') . "\n\n");
        $this->write("--\n");
        $this->write("-- Database: `{$this->db}`\n");
        $this->write("--\n\n");
    }

    /**
     * Table structure extractor
     *
     * @param string $tablename  Name of table to export
     * @return null
     */
    private function getTableStructure($tablename)
    {
        $this->write("--\n-- Table structure for table `$tablename`\n--\n\n");
        if (true === $this->droptableifexists) {
            $this->write("DROP TABLE IF EXISTS `$tablename`;\n\n");
        }
        foreach ($this->db_handler->query("SHOW CREATE TABLE `$tablename`") as $row) {
            $this->write($row['Create Table'] . ";\n\n");
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
        $this->write("--\n-- Dumping data for table `$tablename`\n--\n\n");
        foreach ($this->db_handler->query("SELECT * FROM `$tablename`", PDO::FETCH_NUM) as $row) {
            $vals = array();
            foreach ($row as $val) {
                $vals[] = is_null($val) ? "NULL" : $this->db_handler->quote($val);
            }
            $this->write("INSERT INTO `$tablename` VALUES(" . implode(", ", $vals) . ");\n");
        }
        $this->write("\n");
    }
}
