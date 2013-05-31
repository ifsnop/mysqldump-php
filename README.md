# MySQLDump - PHP

This is a php version of linux's mysqldump in terminal "$ mysqldump -u username -p...".

## Requirements

PHP 5 >= 5.1.0, PECL pdo >= 0.2.0

## Installation

Install mysqldump using artisan:

    php artisan bundle:install mysqldump

Then in your *application/bundles.php* file, add the following line to load Resizer automatically:

    return array(
        'mysqldump' => array( 'auto' => true )
    );

Or without the `'auto' => true` to load it on demand:

    return array(
        'mysqldump'
    );

## Base usage

    <?php

    $dump = new MySQLDump('forum','forum_user','forum_pass','localhost');
    $dump->start('forum_dump.sql');
    $dump->nodata = false;
    $dump->compress = true;
    $dump->droptableifexists = true;
    $dump->start('forum_dump_with_drops.sql.gz');    
          
## Advanced usage

    <?php

    class Cron_Controller extends Base_Controller
    {
        public function get_backup()
        {
            Bundle::start('mysqldump');
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
