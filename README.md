# mysqldump-php

[![Run tests](https://github.com/druidfi/mysqldump-php/actions/workflows/tests.yml/badge.svg)](https://github.com/druidfi/mysqldump-php/actions/workflows/tests.yml)
[![Total Downloads](https://poser.pugx.org/druidfi/mysqldump-php/downloads)](https://packagist.org/packages/druidfi/mysqldump-php)
[![Monthly Downloads](https://poser.pugx.org/druidfi/mysqldump-php/d/monthly)](https://packagist.org/packages/druidfi/mysqldump-php)
[![Daily Downloads](https://poser.pugx.org/druidfi/mysqldump-php/d/daily)](https://packagist.org/packages/druidfi/mysqldump-php)
[![Latest Stable Version](https://poser.pugx.org/druidfi/mysqldump-php/v/stable.png)](https://packagist.org/packages/druidfi/mysqldump-php)

This is a PHP version of `mysqldump` cli that comes with MySQL. It can be used for interacting with the data before
creating the database dump. E.g. it can modify the contents of tables and is thus good for anonymize data.

Out of the box, `mysqldump-php` supports backing up table structures, the data itself, views, triggers and events.

`mysqldump-php` supports:

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

- PHP 7.4 or 8.x with PDO - [see supported versions](https://www.php.net/supported-versions.php)
- MySQL 5.7 or newer (and compatible MariaDB)

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
You can also specify the limits as an array where the first value is the number of rows and the second is the offset

```php
$dumper = new \Druidfi\Mysqldump\Mysqldump('mysql:host=localhost;dbname=testdb', 'username', 'password');

$dumper->setTableLimits([
    'users' => [20, 10], //MySql query equivalent "... LIMIT 20 OFFSET 10"
]);
```
## Dump Settings

Dump settings can be changed from default values with 4th argument for Mysqldump constructor:

```php
$dumper = new \Druidfi\Mysqldump\Mysqldump('mysql:host=localhost;dbname=testdb', 'username', 'password', $pdoOptions);
```

All options:

- **include-tables**
  - Only include these tables (array of table names), include all if empty.
- **exclude-tables**
  - Exclude these tables (array of table names), include all if empty, supports regexps.
- **include-views**
  - Only include these views (array of view names), include all if empty. By default, all views named as the include-tables array are included.
- **if-not-exists**
  - Only create a new table when a table of the same name does not already exist. No error message is thrown if the table already exists. 
- **compress**
  - Possible values: `Bzip2|Gzip|Gzipstream|None`, default is `None`
  - Could be specified using the consts: `CompressManagerFactory::GZIP`, `CompressManagerFactory::BZIP2` or `CompressManagerFactory::NONE`
- **reset-auto-increment**
  - Removes the AUTO_INCREMENT option from the database definition
  - Useful when used with no-data, so when db is recreated, it will start from 1 instead of using an old value
- **add-drop-database**
  - MySQL docs [5.7](https://dev.mysql.com/doc/refman/5.7/en/mysqldump.html#option_mysqldump_add-drop-database)
- **add-drop-table**
  - MySQL docs [5.7](https://dev.mysql.com/doc/refman/5.7/en/mysqldump.html#option_mysqldump_add-drop-table)
- **add-drop-triggers**
  - MySQL docs [5.7](https://dev.mysql.com/doc/refman/5.7/en/mysqldump.html#option_mysqldump_add-drop-trigger)
- **add-locks**
  - MySQL docs [5.7](https://dev.mysql.com/doc/refman/5.7/en/mysqldump.html#option_mysqldump_add-locks)
- **complete-insert**
  - MySQL docs [5.7](https://dev.mysql.com/doc/refman/5.7/en/mysqldump.html#option_mysqldump_complete-insert)
- **databases**
  - MySQL docs [5.7](https://dev.mysql.com/doc/refman/5.7/en/mysqldump.html#option_mysqldump_databases)
- **default-character-set**
  - Possible values: `utf8|utf8mb4`, default is `utf8`
  - `utf8` is compatible option and `utf8mb4` is for full utf8 compliance
  - Could be specified using the consts: `DumpSettings::UTF8` or `DumpSettings::UTF8MB4`
  - MySQL docs [5.7](https://dev.mysql.com/doc/refman/5.7/en/charset-unicode-utf8mb4.html)
- **disable-keys**
  - MySQL docs [5.7](https://dev.mysql.com/doc/refman/5.7/en/mysqldump.html#option_mysqldump_disable-keys)
- **events**
  - MySQL docs [5.7](https://dev.mysql.com/doc/refman/5.7/en/mysqldump.html#option_mysqldump_events)
- **extended-insert**
  - MySQL docs [5.7](https://dev.mysql.com/doc/refman/5.7/en/mysqldump.html#option_mysqldump_extended-insert)
- **hex-blob**
  - MySQL docs [5.7](https://dev.mysql.com/doc/refman/5.7/en/mysqldump.html#option_mysqldump_hex-blob)
- **insert-ignore**
  - MySQL docs [5.7](https://dev.mysql.com/doc/refman/5.7/en/mysqldump.html#option_mysqldump_insert-ignore)
- **lock-tables**
  - MySQL docs [5.7](https://dev.mysql.com/doc/refman/5.7/en/mysqldump.html#option_mysqldump_lock-tables)
- **net_buffer_length**
  - MySQL docs [5.7](https://dev.mysql.com/doc/refman/5.7/en/mysqldump.html#option_mysqldump_net-buffer-length)
- **no-autocommit**
  - Option to disable autocommit (faster inserts, no problems with index keys)
  - MySQL docs [5.7](https://dev.mysql.com/doc/refman/5.7/en/commit.html)
- **no-create-info**
  - MySQL docs [5.7](https://dev.mysql.com/doc/refman/5.7/en/mysqldump.html#option_mysqldump_no-create-info)
- **no-data**
  - Do not dump data for these tables (array of table names), support regexps, `true` to ignore all tables
  - MySQL docs [5.7](https://dev.mysql.com/doc/refman/5.7/en/mysqldump.html#option_mysqldump_no-data)
- **routines**
  - MySQL docs [5.7](https://dev.mysql.com/doc/refman/5.7/en/mysqldump.html#option_mysqldump_routines)
- **single-transaction**
  - MySQL docs [5.7](https://dev.mysql.com/doc/refman/5.7/en/mysqldump.html#option_mysqldump_single-transaction)
- **skip-comments**
  - MySQL docs [5.7](https://dev.mysql.com/doc/refman/5.7/en/mysqldump.html#option_mysqldump_skip-comments)
- **skip-dump-date**
  - MySQL docs [5.7](https://dev.mysql.com/doc/refman/5.7/en/mysqldump.html#option_mysqldump_dump-date)
- **skip-triggers**
  - MySQL docs [5.7](https://dev.mysql.com/doc/refman/5.7/en/mysqldump.html#option_mysqldump_triggers)
- **skip-tz-utc**
  - MySQL docs [5.7](https://dev.mysql.com/doc/refman/5.7/en/mysqldump.html#option_mysqldump_tz-utc)
- **skip-definer**
  - MySQL docs [5.7](https://dev.mysql.com/doc/refman/5.7/en/mysqlpump.html#option_mysqlpump_skip-definer)
- **where**
  - MySQL docs [5.7](https://dev.mysql.com/doc/refman/5.7/en/mysqldump.html#option_mysqldump_where)

The following options are now enabled by default, and there is no way to disable them since they should always be used.

- **disable-foreign-keys-check**
  - MySQL docs [5.7](https://dev.mysql.com/doc/refman/5.7/en/optimizing-innodb-bulk-data-loading.html)

## Privileges

To dump a database, you need the following privileges:

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
docker compose up -d --build
docker compose exec php81 /app/tests/scripts/create_users.sh
docker compose exec php81 /app/tests/scripts/create_users.sh db2
docker compose exec php81 /app/tests/scripts/create_users.sh db3
docker compose exec -w /app/tests/scripts php74 ./test.sh
docker compose exec -w /app/tests/scripts php80 ./test.sh
docker compose exec -w /app/tests/scripts php81 ./test.sh
docker compose exec -w /app/tests/scripts php82 ./test.sh
docker compose exec -w /app/tests/scripts php74 ./test.sh db2
docker compose exec -w /app/tests/scripts php80 ./test.sh db2
docker compose exec -w /app/tests/scripts php81 ./test.sh db2
docker compose exec -w /app/tests/scripts php82 ./test.sh db2
docker compose exec -w /app/tests/scripts php74 ./test.sh db3
docker compose exec -w /app/tests/scripts php80 ./test.sh db3
docker compose exec -w /app/tests/scripts php81 ./test.sh db3
docker compose exec -w /app/tests/scripts php82 ./test.sh db3
```

## Credits

Forked from Diego Torres's version which have latest updates from 2020. Use it for PHP 7.3 and older.
https://github.com/ifsnop/mysqldump-php

Originally based on James Elliott's script from 2009.
https://code.google.com/archive/p/db-mysqldump/

Adapted and extended by Michael J. Calkins.
https://github.com/clouddueling

## License

This project is open-sourced software licensed under the [GPL license](https://www.gnu.org/copyleft/gpl.html)
