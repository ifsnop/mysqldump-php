<?php

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
    public $sql_file = "";
    public $filename = "";

    public function start()
    {
        $this->connect();
        $this->list_tables();
        $this->create_sql();

        file_put_contents($this->filename, $this->sql_file);
    }

    public function create_sql()
    {
        $broj = count($this->tables); //Count Database Tables.
        $this->sql_file = "";
        for ($i = 0; $i < $broj; $i++) {
            $table_name = $this->tables[$i]; //Get Table Names.
            $this->dump_table($table_name); //Dump Data to the Output Buffer.
            $this->sql_file .= $this->output; //Display Output.
        }
    }

    public function connect()
    {
        mysql_connect($this->host, $this->user, $this->pass) or die(mysql_error());
        mysql_select_db($this->db) or die(mysql_error());
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
        $this->output .= "\n\n-- Dumping data for table: $tablename\n\n";
        if ($sql !== false) {
            while ($row = mysql_fetch_array($sql)) {
                $broj_polja = count($row) / 2;
                $this->output .= "INSERT INTO `$tablename` VALUES(";
                $buffer = '';
                for ($i=0;$i < $broj_polja;$i++) {
                    $vrednost = $row[$i];

                    if (!is_integer($vrednost))
                        $vrednost = "'".addslashes($vrednost)."'";

                    $buffer .= $vrednost.', ';
                }
                $buffer = substr($buffer,0,count($buffer)-3);
                $this->output .= $buffer . ");\n";
            }
        } else {
            $this->output .= "-- Could not list values of {$tablename}\n\n";
        }
    }

    public function dump_table($tablename)
    {
        $this->output = "";
        $this->get_table_structure($tablename);
        $this->list_values($tablename);
    }

    public function get_table_structure($tablename)
    {
        $this->output .= "\n\n-- Dumping structure for table: $tablename\n\n";
        $sql = mysql_query("DESCRIBE `$tablename`") or die(mysql_error());

        if ($this->droptableifexists)
            $this->output .= "DROP TABLE IF EXISTS `$tablename`;\nCREATE TABLE `$tablename` (\n";
        else
            $this->output .= "CREATE TABLE `$tablename` (\n";

        $this->fields = array();
        while ($row = mysql_fetch_array($sql)) {
            $name = $row[0];
            $type = $row[1];
            $null = $row[2];
            $key = $row[3];
            $default = $row[4];
            $extra = $row[5];

            if (empty($null) || $null == "NO")
                $null = "NOT NULL";

            if ($null == "YES")
                $null = "NULL";

            if ($key == "PRI")
                $primary = $name;

            if ($extra !== "")
                $extra .= ' ';

            $this->output .= "  `$name` $type $null $extra,\n";
        }
        $this->output .= "  PRIMARY KEY  (`$primary`)\n);\n";
    }
}

?>