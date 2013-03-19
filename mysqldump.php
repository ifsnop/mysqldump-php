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
    public $tables = array();
    public $connected = false;
    public $output;
    public $droptableifexists = false;
    public $mysql_error;
    public $host = "";
    public $user = "";
    public $pass = "";
    public $db = "";
    public $sql_handler;
    public $filename = "";

    public function start()
    {
        $this->sql_handler = fopen($this->filename, "wb");
        $this->connect();
        $this->list_tables();
        $this->create_sql();
        return fclose($this->sql_handler);
    }
    
    public function write($string) {
        fwrite($this->sql_handler, $string);
    }


    public function create_sql()
    {
        $broj = count($this->tables); //Count Database Tables.
        $this->sql_file = "";
        for ($i = 0; $i < $broj; $i++) {
            $table_name = $this->tables[$i]; //Get Table Names.
            $this->dump_table($table_name); //Dump Data to the Output Buffer.
        }
    }

    public function connect()
    {
        mysql_connect($this->host, $this->user, $this->pass) or die(mysql_error());
        mysql_select_db($this->db) or die(mysql_error());
        mysql_query("SET NAMES utf8"); // Just for shure :)
    }

    public function list_tables()
    {
        $return = true;
        if (!$this->connected)
            $return = false;

        $this->tables = array();
        $sql = mysql_query("SHOW TABLES") or die(mysql_error());

        while ($row = mysql_fetch_array($sql))
            array_push($this->tables, $row[0]);

        return $return;
    }

    public function list_values($tablename)
    {
        $sql = mysql_query("SELECT * FROM `$tablename`");
        $this->write("\n\n-- Dumping data for table: $tablename\n\n");
        if ($sql !== false) {
            while ($row = mysql_fetch_array($sql)) {
                $broj_polja = count($row) / 2;
                $this->write("INSERT INTO `$tablename` VALUES(");
                $buffer = '';
                for ($i=0;$i < $broj_polja;$i++) {
                    $vrednost = $row[$i];

                    if (!is_integer($vrednost))
                        $vrednost = "'".addslashes($vrednost)."'";

                    $buffer .= $vrednost.', ';
                }
                $buffer = substr($buffer,0,count($buffer)-3);
                $this->write($buffer . ");\n");
            }
        } else {
            $this->write("-- Could not list values of {$tablename}\n\n");
        }
    }

    public function dump_table($tablename)
    {
        $this->get_table_structure($tablename);
        $this->list_values($tablename);
    }

    public function get_table_structure($tablename)
    {
        $this->write( "\n\n-- Dumping structure for table: $tablename\n\n" );
        
        if ($this->droptableifexists) {
           $this->write( "DROP TABLE IF EXISTS `$tablename`;\n\n" );
        }
        
        $sql = mysql_query("SHOW CREATE TABLE `$tablename`") or die(mysql_error());        
        if ($row = mysql_fetch_array($sql)) {
           $this->write( $row['Create Table']."\n\n" );
        }
    }
}

?>
