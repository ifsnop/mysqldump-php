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
    const MAXLINESIZE = 1000000;
    
    // This can be set both on constructor or manually
    public $host;
    public $user;
    public $pass;
    public $db;
    public $filename = 'dump.sql';
    
    // Internal stuff
    private $settings = array();
    private $tables = array();
    private $views = array();
    private $db_handler;
    private $file_handler;
    private $defaultSettings = array(
        'include-tables' => array(),
        'exclude-tables' => array(),
        'compress' => false,
        'no-data' => false,
            /* http://dev.mysql.com/doc/refman/5.1/en/mysqldump.html#option_mysqldump_no-data */
        'add-drop-table' => false,
            /* http://dev.mysql.com/doc/refman/5.1/en/mysqldump.html#option_mysqldump_add-drop-table */
        'single-transaction' => true,
            /* http://dev.mysql.com/doc/refman/5.1/en/mysqldump.html#option_mysqldump_single-transaction */
        'lock-tables' => false,
            /* http://dev.mysql.com/doc/refman/5.1/en/mysqldump.html#option_mysqldump_lock-tables */
        'add-locks' => true,
            /* http://dev.mysql.com/doc/refman/5.1/en/mysqldump.html#option_mysqldump_add-locks */
        'extended-insert' => true
            /* http://dev.mysql.com/doc/refman/5.1/en/mysqldump.html#option_mysqldump_extended-insert */
        );

    /**
     * Constructor of MySQLDump
     *
     * @param string $db        Database name
     * @param string $user      MySQL account username
     * @param string $pass      MySQL account password
     * @param string $host      MySQL server to connect to
     * @return null
     */
    public function __construct($db = '', $user = '', $pass = '', $host = 'localhost', $settings = null)
    {
        $this->db = $db;
        $this->user = $user;
        $this->pass = $pass;
        $this->host = $host;
        $this->settings = $this->extend($this->defaultSettings, $settings);
    }

    /**
     * jquery style extend, merges arrays (without errors if the passed values are not arrays)
     * extend($defaults, $options);
     *
     * @return array $extended
     */
    public function extend() {
        $args = func_get_args();
        $extended = array();
        if( is_array($args) && count($args)>0 ) {
            foreach($args as $array) {
                if(is_array($array)) {
                    $extended = array_merge($extended, $array);
                }
            }
        }
        return $extended;
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
        if ( (true === $this->settings['compress']) && !function_exists("gzopen") ) {
            throw new \Exception("Compression is enabled, but zlib is not installed or configured properly", 1);
        }
        // Trying to bind a file with block
        if ( true === $this->settings['compress'] ) {
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
            if ( empty($this->settings['include-tables']) || 
        	(!empty($this->settings['include-tables']) && 
        	in_array(current($row), $this->settings['include-tables'], true)) ) {
                array_push($this->tables, current($row));
            }
        }
        // Exporting tables one by one
        foreach ($this->tables as $table) {
            if ( in_array($table, $this->settings['exclude-tables'], true) ) {
                continue;
            }
            $is_table = $this->getTableStructure($table);
            if ( true === $is_table && false === $this->settings['no-data'] ) {
                $this->listValues($table);
            }
        }
        foreach ($this->views as $view) {
            $this->write($view);
        }
        // Releasing file
        if ( true === $this->settings['compress'] ) {
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
	$bytesWritten = 0;
        if ( true === $this->settings['compress'] ) {
            if ( false === ($bytesWritten = gzwrite($this->file_handler, $string)) ) {
                throw new \Exception("Writting to file failed! Probably, there is no more free space left?", 4);
            }
        } else {
            if ( false === ($bytesWritten = fwrite($this->file_handler, $string)) ) {
                throw new \Exception("Writting to file failed! Probably, there is no more free space left?", 4);
            }
        }
        return $bytesWritten;
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
        foreach ($this->db_handler->query("SHOW CREATE TABLE `$tablename`") as $row) {
            if ( isset($row['Create Table']) ) {
                $this->write("-- --------------------------------------------------------\n\n");
                $this->write("--\n-- Table structure for table `$tablename`\n--\n\n");
                if ( true === $this->settings['add-drop-table'] ) {
                    $this->write("DROP TABLE IF EXISTS `$tablename`;\n\n");
                }
                $this->write($row['Create Table'] . ";\n\n");
                return true;
            }
            if ( isset($row['Create View']) ) {
                $view  = "-- --------------------------------------------------------\n\n";
                $view .= "--\n-- Table structure for view `$tablename`\n--\n\n";
                $view .= $row['Create View'] . ";\n\n";
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
        $this->write("--\n-- Dumping data for table `$tablename`\n--\n\n");
        
        if ( $this->settings['single-transaction'] ) {
            $this->db_handler->exec("SET GLOBAL TRANSACTION ISOLATION LEVEL REPEATABLE READ");
    	    $this->db_handler->exec("START TRANSACTION");
    	}
        if ( $this->settings['lock-tables'] )
    	    $this->db_handler->exec("LOCK TABLES `$tablename` READ LOCAL");
	if ( $this->settings['add-locks'] )
    	    $this->write("LOCK TABLES `$tablename` WRITE;\n");
    	
    	$onlyOnce = true; $lineSize = 0;
        foreach ($this->db_handler->query("SELECT * FROM `$tablename`", PDO::FETCH_NUM) as $row) {
            $vals = array();
            foreach ($row as $val) {
                $vals[] = is_null($val) ? "NULL" : $this->db_handler->quote($val);
            }
            if ($onlyOnce || !$this->settings['extended-insert'] ) {
        	$lineSize += $this->write("INSERT INTO `$tablename` VALUES (" . implode(",", $vals) . ")");
        	$onlyOnce = false;
    	    } else {
    		$lineSize += $this->write(",(" . implode(",", $vals) . ")"); 
    	    }
    	    if ( ($lineSize > MySQLDump::MAXLINESIZE) || !$this->settings['extended-insert'] ) {
    		$onlyOnce = true; 
    		$lineSize = $this->write(";\n");
    	    }
    	}
    	if ( !$onlyOnce )
    	    $this->write(";\n");

	if ( $this->settings['add-locks'] )
    	    $this->write("UNLOCK TABLES;\n");
        if ( $this->settings['single-transaction'] )
    	    $this->db_handler->exec("COMMIT");
        if ( $this->settings['lock-tables'] )
    	    $this->db_handler->exec("UNLOCK TABLES");
    	    
    	return;
    }
}
