# MySQLDump - PHP

This is a php version of linux's mysqldump in terminal "$ mysqldump -u username -p...".

## Requirements

PHP 5 >= 5.1.0, PECL pdo >= 0.2.0

## Base usage

    <?php

    $dumpSettings = array(
        'include-tables' => array('table1', 'table2'),
        'exclude-tables' => array('table3', 'table4'),
        'compress' => CompressMethod::GZIP, /* CompressMethod::[GZIP, BZIP2, NONE] */
        'no-data' => false,             /* http://dev.mysql.com/doc/refman/5.1/en/mysqldump.html#option_mysqldump_no-data */
        'add-drop-table' => false,      /* http://dev.mysql.com/doc/refman/5.1/en/mysqldump.html#option_mysqldump_add-drop-table */
        'single-transaction' => true,   /* http://dev.mysql.com/doc/refman/5.1/en/mysqldump.html#option_mysqldump_single-transaction */
        'lock-tables' => false,         /* http://dev.mysql.com/doc/refman/5.1/en/mysqldump.html#option_mysqldump_lock-tables */
        'add-locks' => true,            /* http://dev.mysql.com/doc/refman/5.1/en/mysqldump.html#option_mysqldump_add-locks */
        'extended-insert' => true       /* http://dev.mysql.com/doc/refman/5.1/en/mysqldump.html#option_mysqldump_extended-insert */

    $dump = new MySQLDump('forum','forum_user','forum_pass','localhost', $dumpSettings);
    $dump->start('forum_dump.sql.gz');

## Advanced usage

    <?php

    class Cron_Controller extends Base_Controller
    {
        public function get_backup()
        {
            $conn = Config::get('database.connections.mysql');

            $filename = time() . ".sql";
            $filepath = "storage/work/";

            $dump = new MySQLDump();
            $dump->host     = $conn['host'];
            $dump->user     = $conn['username'];
            $dump->pass     = $conn['password'];
            $dump->db       = $conn['database'];
            $dump->filename = $filepath . $filename;
            $dump->start();

            return "Backup complete.";
        }
    }


## Credits

This was originally written by James Elliott in 2009, I OOP'd it up, outputted to file, simplified the process, fixed some mysql errors, and updated it to PSR standards.

Original site: http://code.google.com/p/db-mysqldump/

Enjoy.
