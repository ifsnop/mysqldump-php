# MySQLDump - PHP

This is a php version of linux's mysqldump in terminal "$ mysqldump -u username -p...".

### Requirements

- PHP 5 >= 5.1.0
- PECL pdo >= 0.2.0

### Getting started

    <?php

        include_once('mysqldump-php/src/Clouddueling/Mysqldump/Mysqldump.php');

        use Clouddueling\Mysqldump\Mysqldump;

        $dumpSettings = array(
            'include-tables' => array('table1', 'table2'),
            'exclude-tables' => array('table3', 'table4'),
            'compress' => 'GZIP',
            'no-data' => false,
            'add-drop-table' => false,
            'single-transaction' => true,
            'lock-tables' => false,
            'add-locks' => true,
            'extended-insert' => true,
            'disable-foreign-keys-check' => false
        );

        $dump = new Mysqldump('clouddueling', 'root', 'password', 'localhost', 'mysql', $dumpSettings);
        $dump->start('storage/work/dump.sql');

### API

- **include-tables**
 - Only include these tables.
- **exclude-tables**
 - Exclude these tables.
- **compress**
 - GZIP, BZIP2, NONE
- **no-data**
 - http://dev.mysql.com/doc/refman/5.1/en/mysqldump.html#option_mysqldump_no-data
- **add-drop-table**
 - http://dev.mysql.com/doc/refman/5.1/en/mysqldump.html#option_mysqldump_add-drop-table
- **single-transaction**
 - http://dev.mysql.com/doc/refman/5.1/en/mysqldump.html#option_mysqldump_single-transaction
- **lock-tables**
 - http://dev.mysql.com/doc/refman/5.1/en/mysqldump.html#option_mysqldump_lock-tables
- **add-locks**
 - http://dev.mysql.com/doc/refman/5.1/en/mysqldump.html#option_mysqldump_add-locks
- **extended-insert**
 - http://dev.mysql.com/doc/refman/5.1/en/mysqldump.html#option_mysqldump_extended-insert
- **disable-foreign-keys-check**
 - http://dev.mysql.com/doc/refman/5.5/en/optimizing-innodb-bulk-data-loading.html

### Contributing

Format all code to PHP-FIG standards.
http://www.php-fig.org/

### License

This project is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT)

### Credits

Originally based on James Elliott's script from 2009 but has since been entirely rewritten.
http://code.google.com/p/db-mysqldump/
