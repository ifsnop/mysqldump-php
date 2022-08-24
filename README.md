# mysqldump-php

[![Run tests](https://github.com/druidfi/mysqldump-php/actions/workflows/tests.yml/badge.svg)](https://github.com/druidfi/mysqldump-php/actions/workflows/tests.yml)
[![Total Downloads](https://poser.pugx.org/druidfi/mysqldump-php/downloads)](https://packagist.org/packages/druidfi/mysqldump-php)
[![Monthly Downloads](https://poser.pugx.org/druidfi/mysqldump-php/d/monthly)](https://packagist.org/packages/druidfi/mysqldump-php)
[![Daily Downloads](https://poser.pugx.org/druidfi/mysqldump-php/d/daily)](https://packagist.org/packages/druidfi/mysqldump-php)
[![Latest Stable Version](https://poser.pugx.org/druidfi/mysqldump-php/v/stable.png)](https://packagist.org/packages/druidfi/mysqldump-php)

This is a PHP version of `mysqldump` cli that comes with MySQL, without dependencies, output compression and sane defaults.

Out of the box, `mysqldump-php` supports backing up table structures, the data itself, views, triggers and events.

`mysqldump-php` is the only library that supports:

- output binary blobs as hex
- resolves view dependencies (using Stand-In tables)
- output compared against original mysqldump
- dumps stored routines (functions and procedures)
- dumps events
- does extended-insert and/or complete-insert
- supports virtual columns from MySQL 5.7
- does insert-ignore, like a REPLACE but ignoring errors if a duplicate key exists
- modifying data from database on-the-fly when dumping, using hooks
- can save directly to Google Cloud storage over a compressed stream wrapper (GZIPSTREAM)

## Requirements

- PHP 7.4 or 8.x - [see supported versions](https://www.php.net/supported-versions.php)
- MySQL 5.7 or newer (and compatible MariaDB)
- [PDO](https://www.php.net/pdo)
- Connections to database are made using the standard DSN, documented in
  [PDO connection string](https://www.php.net/manual/en/ref.pdo-mysql.connection.php).

## Installing

Install using [Composer](https://getcomposer.org/):

```
composer require druidfi/mysqldump-php
```

## Getting started

```php
<?php

try {
    $dump = new \Druidfi\Mysqldump\Mysqldump('mysql:host=localhost;dbname=testdb', 'username', 'password');
    $dump->start('storage/work/dump.sql');
} catch (\Exception $e) {
    echo 'mysqldump-php error: ' . $e->getMessage();
}
```

Refer to the [ifsnop/mysqldump-php Wiki](https://github.com/ifsnop/mysqldump-php/wiki/Full-usage-example) for some
examples and a comparison between mysqldump and mysqldump-php dumps.

## Changing values when exporting

You can register a callable that will be used to transform values during the export. An example use-case for this is
removing sensitive data from database dumps:

```php
$dumper = new \Druidfi\Mysqldump\Mysqldump('mysql:host=localhost;dbname=testdb', 'username', 'password');

$dumper->setTransformTableRowHook(function ($tableName, array $row) {
    if ($tableName === 'customers') {
        $row['social_security_number'] = (string) rand(1000000, 9999999);
    }

    return $row;
});

$dumper->start('storage/work/dump.sql');
```

## Getting information about the dump

You can register a callable that will be used to report on the progress of the dump

```php
$dumper->setInfoHook(function($object, $info) {
    if ($object === 'table') {
        echo $info['name'], $info['rowCount'];
    });
```

## Table specific export conditions

You can register table specific 'where' clauses to limit data on a per table basis.  These override the default `where`
dump setting:

```php
$dumper = new \Druidfi\Mysqldump\Mysqldump('mysql:host=localhost;dbname=testdb', 'username', 'password');

$dumper->setTableWheres([
    'users' => 'date_registered > NOW() - INTERVAL 3 MONTH AND deleted=0',
    'logs' => 'date_logged > NOW() - INTERVAL 1 DAY',
    'posts' => 'isLive=1'
]);
```

## Table specific export limits

You can register table specific 'limits' to limit the returned rows on a per table basis:

```php
$dumper = new \Druidfi\Mysqldump\Mysqldump('mysql:host=localhost;dbname=testdb', 'username', 'password');

$dumper->setTableLimits([
    'users' => 300,
    'logs' => 50,
    'posts' => 10
]);
```

## Constructor and default parameters

```php
/**
 * Constructor of Mysqldump.
 *
 * @param string $dsn        PDO DSN connection string
 * @param string $user       SQL account username
 * @param string $pass       SQL account password
 * @param array  $dumpSettings SQL database settings
 * @param array  $pdoSettings  PDO configured attributes
 */
public function __construct(
    string $dsn = '',
    string ?$user = '',
    string ?$pass = '',
    array $dumpSettings = [],
    array $pdoSettings = []
)

$dumpSettingsDefault = [
    'include-tables' => [],
    'exclude-tables' => [],
    'compress' => Mysqldump::NONE,
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
    'default-character-set' => Mysqldump::UTF8,
    'disable-keys' => true,
    'extended-insert' => true,
    'events' => false,
    'hex-blob' => true, /* faster than escaped content */
    'insert-ignore' => false,
    'net_buffer_length' => Mysqldump::MAXLINESIZE,
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

$pdoSettingsDefaults = [
    PDO::ATTR_PERSISTENT => true,
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => false
];

// missing settings in constructor will be replaced by default options
$this->_pdoSettings = array_replace_recursive($pdoSettingsDefault, $pdoSettings);
$this->_dumpSettings = array_replace_recursive($dumpSettingsDefault, $dumpSettings);
```

## Dump Settings

- **include-tables**
  - Only include these tables (array of table names), include all if empty.
- **exclude-tables**
  - Exclude these tables (array of table names), include all if empty, supports regexps.
- **include-views**
  - Only include these views (array of view names), include all if empty. By default, all views named as the include-tables array are included.
- **if-not-exists**
  - Only create a new table when a table of the same name does not already exist. No error message is thrown if the table already exists. 
- **compress**
  - Gzip, Bzip2, None.
  - Could be specified using the declared consts: Mysqldump::GZIP, Mysqldump::BZIP2 or Mysqldump::NONE
- **reset-auto-increment**
  - Removes the AUTO_INCREMENT option from the database definition
  - Useful when used with no-data, so when db is recreated, it will start from 1 instead of using an old value
- **add-drop-database**
  - https://dev.mysql.com/doc/refman/5.1/en/mysqldump.html#option_mysqldump_add-drop-database
- **add-drop-table**
  - https://dev.mysql.com/doc/refman/5.1/en/mysqldump.html#option_mysqldump_add-drop-table
- **add-drop-triggers**
  - https://dev.mysql.com/doc/refman/5.1/en/mysqldump.html#option_mysqldump_add-drop-trigger
- **add-locks**
  - https://dev.mysql.com/doc/refman/5.1/en/mysqldump.html#option_mysqldump_add-locks
- **complete-insert**
  - https://dev.mysql.com/doc/refman/5.1/en/mysqldump.html#option_mysqldump_complete-insert
- **databases**
  - https://dev.mysql.com/doc/refman/5.1/en/mysqldump.html#option_mysqldump_databases
- **default-character-set**
  - utf8 (default, compatible option), utf8mb4 (for full utf8 compliance)
  - Could be specified using the declared consts: Mysqldump::UTF8 or Mysqldump::UTF8MB4
  - https://dev.mysql.com/doc/refman/5.5/en/charset-unicode-utf8mb4.html
  - https://mathiasbynens.be/notes/mysql-utf8mb4
- **disable-keys**
  - https://dev.mysql.com/doc/refman/5.1/en/mysqldump.html#option_mysqldump_disable-keys
- **events**
  - https://dev.mysql.com/doc/refman/5.7/en/mysqldump.html#option_mysqldump_events
- **extended-insert**
  - https://dev.mysql.com/doc/refman/5.1/en/mysqldump.html#option_mysqldump_extended-insert
- **hex-blob**
  - https://dev.mysql.com/doc/refman/5.1/en/mysqldump.html#option_mysqldump_hex-blob
- **insert-ignore**
  - https://dev.mysql.com/doc/refman/5.7/en/mysqldump.html#option_mysqldump_insert-ignore
- **lock-tables**
  - https://dev.mysql.com/doc/refman/5.1/en/mysqldump.html#option_mysqldump_lock-tables
- **net_buffer_length**
  - https://dev.mysql.com/doc/refman/5.7/en/mysqldump.html#option_mysqldump_net_buffer_length
- **no-autocommit**
  - Option to disable autocommit (faster inserts, no problems with index keys)
  - https://dev.mysql.com/doc/refman/4.1/en/commit.html
- **no-create-info**
  - https://dev.mysql.com/doc/refman/5.1/en/mysqldump.html#option_mysqldump_no-create-info
- **no-data**
  - https://dev.mysql.com/doc/refman/5.1/en/mysqldump.html#option_mysqldump_no-data
  - Do not dump data for these tables (array of table names), support regexps, `true` to ignore all tables
- **routines**
  - https://dev.mysql.com/doc/refman/5.1/en/mysqldump.html#option_mysqldump_routines
- **single-transaction**
  - https://dev.mysql.com/doc/refman/5.1/en/mysqldump.html#option_mysqldump_single-transaction
- **skip-comments**
  - https://dev.mysql.com/doc/refman/5.1/en/mysqldump.html#option_mysqldump_comments
- **skip-dump-date**
  - https://dev.mysql.com/doc/refman/5.1/en/mysqldump.html#option_mysqldump_dump-date
- **skip-triggers**
  - https://dev.mysql.com/doc/refman/5.1/en/mysqldump.html#option_mysqldump_triggers
- **skip-tz-utc**
  - https://dev.mysql.com/doc/refman/5.1/en/mysqldump.html#option_mysqldump_tz-utc
- **skip-definer**
  - https://dev.mysql.com/doc/refman/5.7/en/mysqlpump.html#option_mysqlpump_skip-definer
- **where**
  - https://dev.mysql.com/doc/refman/5.1/en/mysqldump.html#option_mysqldump_where

The following options are now enabled by default, and there is no way to disable them since
they should always be used.

- **disable-foreign-keys-check**
  - https://dev.mysql.com/doc/refman/5.5/en/optimizing-innodb-bulk-data-loading.html

## PDO Settings

- **PDO::ATTR_PERSISTENT**
- **PDO::ATTR_ERRMODE**
- **PDO::MYSQL_ATTR_INIT_COMMAND**
- **PDO::MYSQL_ATTR_USE_BUFFERED_QUERY**
  - https://secure.php.net/manual/en/ref.pdo-mysql.php
  - https://stackoverflow.com/questions/13728106/unexpectedly-hitting-php-memory-limit-with-a-single-pdo-query/13729745#13729745
  - https://secure.php.net/manual/en/mysqlinfo.concepts.buffering.php

## Errors

To dump a database, you need the following privileges :

- **SELECT**
  - In order to dump table structures and data.
- **SHOW VIEW**
  - If any databases has views, else you will get an error.
- **TRIGGER**
  - If any table has one or more triggers.
- **LOCK TABLES**
  - If "lock tables" option was enabled.
- **PROCESS**
  - If you don’t use the --no-tablespaces option.
    
Use **SHOW GRANTS FOR user@host;** to know what privileges user has. See the following link for more information:

- [Which are the minimum privileges required to get a backup of a MySQL database schema?](https://dba.stackexchange.com/questions/55546/which-are-the-minimum-privileges-required-to-get-a-backup-of-a-mysql-database-sc/55572#55572)
- [PROCESS privilege from MySQL 5.7.31 and MySQL 8.0.21 in July 2020](https://anothercoffee.net/how-to-fix-the-mysqldump-access-denied-process-privilege-error/)

## Tests

The testing script creates and populates a database using all possible datatypes. Then it exports it using both
mysqldump-php and mysqldump, and compares the output. Only if it is identical tests are OK.

Some tests are skipped if mysql server doesn't support them.

A couple of tests are only comparing between original sql code and mysqldump-php generated sql, because some options
are not available in mysqldump.

Local setup for tests:

```
docker-compose up -d --build
docker-compose exec php74 /app/tests/scripts/create_users.sh
docker-compose exec php74 /app/tests/scripts/create_users.sh db2
docker-compose exec -w /app/tests/scripts php74 ./test.sh
docker-compose exec -w /app/tests/scripts php80 ./test.sh
docker-compose exec -w /app/tests/scripts php81 ./test.sh
```

## Bugs (from mysqldump, not from mysqldump-php)

After [this](https://bugs.mysql.com/bug.php?id=80150) bug report, a new one has been introduced. _binary is appended
also when hex-blob option is used, if the value is empty.

## TODO

- Handle tablespaces issues
- Update tests (test.sh and test.php) to pass
- Update Mysql links in this README.md

## Contributing

Format all code to PHP-FIG standards.
https://www.php-fig.org/

## Credits

Forked from Diego Torres's version which have latest updates from 2020.
https://github.com/ifsnop/mysqldump-php

Originally based on James Elliott's script from 2009.
https://code.google.com/archive/p/db-mysqldump/

Adapted and extended by Michael J. Calkins.
https://github.com/clouddueling

## License

This project is open-sourced software licensed under the [GPL license](https://www.gnu.org/copyleft/gpl.html)
