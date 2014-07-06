MySQLDump - PHP
=========

[Requirements](https://github.com/ifsnop/mysqldump-php#requirements) |
[Installing](https://github.com/ifsnop/mysqldump-php#installing) |
[Getting started](https://github.com/ifsnop/mysqldump-php#getting-started) |
[API](https://github.com/ifsnop/mysqldump-php#constructor-and-default-parameters) |
[Settings](https://github.com/ifsnop/mysqldump-php#dump-settings) |
[PDO Settings](https://github.com/ifsnop/mysqldump-php#pdo-settings) |
[TODO](https://github.com/ifsnop/mysqldump-php#todo) |
[License](https://github.com/ifsnop/mysqldump-php#license) |
[Credits](https://github.com/ifsnop/mysqldump-php#credits)

[![Build Status](https://travis-ci.org/ifsnop/mysqldump-php.png?branch=master)](https://travis-ci.org/ifsnop/mysqldump-php)
[![Scrutinizer Quality Score](https://scrutinizer-ci.com/g/ifsnop/mysqldump-php/badges/quality-score.png?s=d02891e196a3ca1298619032a538ce8ae8cafd2b)](https://scrutinizer-ci.com/g/ifsnop/mysqldump-php/)
[![Latest Stable Version](https://poser.pugx.org/ifsnop/mysqldump-php/v/stable.png)](https://packagist.org/packages/ifsnop/mysqldump-php)

This is a php version of linux's mysqldump in terminal "$ mysqldump -u username -p...", without dependencies, output compression and sane defaults.

Out of the box, MySQLDump-PHP supports backing up table structures, the data itself, views and triggers.

## Requirements

- PHP 5.3.0 or newer
- MySQL 4.1.0 or newer
- [PDO](http://php.net/pdo)

## Installing

Using [Composer](http://getcomposer.org):

```
$ composer require ifsnop/mysqldump-php:1.*
```

Using [Curl](http://curl.haxx.se):

```
$ curl --silent --location https://github.com/ifsnop/mysqldump-php/archive/v1.3.tar.gz | tar xvfz -
```

## Getting started

With [Autoloader](http://www.php-fig.org/psr/psr-4/)/[Composer](http://getcomposer.org):

```
<?php

use Ifsnop\Mysqldump as IMysqldump;

try {
    $dump = new IMysqldump\Mysqldump('database', 'username', 'password');
    $dump->start('storage/work/dump.sql');
} catch (\Exception $e) {
    echo 'mysqldump-php error: ' . $e->getMessage();
}

?>
```

Plain old PHP:

```
<?php

    include_once(dirname(__FILE__) . '/mysqldump-php-1.3/src/Ifsnop/Mysqldump/Mysqldump.php');
    $dump = new Ifsnop\Mysqldump\Mysqldump( 'database', 'username', 'password');
    $dump->start('storage/work/dump.sql');

?>
```

## Constructor and default parameters

    /**
     * Constructor of Mysqldump. Note that in the case of an SQLite database
     * connection, the filename must be in the $db parameter.
     *
     * @param string $db         Database name
     * @param string $user       SQL account username
     * @param string $pass       SQL account password
     * @param string $host       SQL server to connect to
     * @param string $type       SQL database type ('mysql', 'sqlite', ...)
     * @param array  $dumpSettings SQL database settings
     * @param array  $pdoSettings  PDO configured attributes
     *
     * @return null
     */
    public function __construct(
        $db = '',
        $user = '',
        $pass = '',
        $host = 'localhost',
        $type = 'mysql',
        $dumpSettings = array(),
        $pdoSettings = array()
    )

    $dumpSettingsDefault = array(
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
        'disable-foreign-keys-check' => false,
        'where' => '',
        'no-create-info' => false
    );

    $pdoSettingsDefaults = array(PDO::ATTR_PERSISTENT => true,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8",
        PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => false
    );

    // missing settings in constructor will be replaced by default options
    $this->_pdoSettings = self::array_replace_recursive($pdoSettingsDefault, $pdoSettings);
    $this->_dumpSettings = self::array_replace_recursive($dumpSettingsDefault, $dumpSettings);

## Dump Settings

- **include-tables**
  - Only include these tables (array of table names)
- **exclude-tables**
  - Exclude these tables (array of table names)
- **compress**
  - Gzip, Bzip2, None
- **no-data**
  - http://dev.mysql.com/doc/refman/5.1/en/mysqldump.html#option_mysqldump_no-data
- **add-drop-database**
  - http://dev.mysql.com/doc/refman/5.1/en/mysqldump.html#option_mysqldump_add-drop-database
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
- **where**
  - http://dev.mysql.com/doc/refman/5.1/en/mysqldump.html#option_mysqldump_where
- **no-create-info**
  - http://dev.mysql.com/doc/refman/5.1/en/mysqldump.html#option_mysqldump_no-create-info

## PDO Settings

- **PDO::ATTR_PERSISTENT**
- **PDO::ATTR_ERRMODE**
- **PDO::MYSQL_ATTR_INIT_COMMAND**
- **PDO::MYSQL_ATTR_USE_BUFFERED_QUERY**
  - http://www.php.net/manual/en/ref.pdo-mysql.php
  - http://stackoverflow.com/questions/13728106/unexpectedly-hitting-php-memory-limit-with-a-single-pdo-query/13729745#13729745
  - http://www.php.net/manual/en/mysqlinfo.concepts.buffering.php


## TODO

- Write unit tests.

## Contributing

Format all code to PHP-FIG standards.
http://www.php-fig.org/

## License

This project is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT)

## Credits

Originally based on James Elliott's script from 2009.
http://code.google.com/p/db-mysqldump/

Adapted and extended by Michael J. Calkins.
https://github.com/clouddueling

Currently maintained by Diego Torres.
https://github.com/ifsnop
