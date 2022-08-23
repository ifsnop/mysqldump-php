<?php

date_default_timezone_set('UTC');
error_reporting(E_ALL);

require __DIR__ . '/../../vendor/autoload.php';

use Druidfi\Mysqldump\DumpSettings;
use Druidfi\Mysqldump\Mysqldump;
use Druidfi\Mysqldump\Compress\CompressManagerFactory;

$host = $argv[1] ?? 'db'; // Get host name from test.sh
$user = 'travis';

$dumpSettings = [
    'exclude-tables' => ['/^travis*/'],
    'compress' => CompressManagerFactory::NONE,
    'no-data' => false,
    'add-drop-table' => true,
    'single-transaction' => true,
    'lock-tables' => true,
    'add-locks' => true,
    'extended-insert' => false,
    'disable-keys' => true,
    'skip-triggers' => false,
    'add-drop-trigger' => true,
    'routines' => true,
    'databases' => false,
    'add-drop-database' => false,
    'hex-blob' => true,
    'no-create-info' => false,
    'where' => ''
];

try {
    // do nothing test
    print "Create dump with PHP: mysql-php_test000.sql" . PHP_EOL;
    $dump = new Mysqldump("mysql:host=$host;dbname=test001", $user);

    print "Create dump with PHP: mysql-php_test001.sql" . PHP_EOL;
    $dump = new Mysqldump("mysql:host=$host;dbname=test001", $user, "", $dumpSettings);
    $dump->start("output/mysqldump-php_test001.sql");

    // checks if complete-insert && hex-blob works ok together
    print "Create dump with PHP: mysql-php_test001_complete.sql" . PHP_EOL;
    $dumpSettings['complete-insert'] = true;
    $dump = new Mysqldump("mysql:host=$host;dbname=test001", $user, "", $dumpSettings);
    $dump->start("output/mysqldump-php_test001_complete.sql");

    print "Create dump with PHP: mysql-php_test002.sql" . PHP_EOL;
    $dumpSettings['default-character-set'] = DumpSettings::UTF8MB4;
    $dumpSettings['complete-insert'] = true;
    $dump = new Mysqldump("mysql:host=$host;dbname=test002", $user, "", $dumpSettings);
    $dump->start("output/mysqldump-php_test002.sql");

    print "Create dump with PHP: mysql-php_test005.sql" . PHP_EOL;
    $dumpSettings['complete-insert'] = false;
    $dump = new Mysqldump("mysql:host=$host;dbname=test005", $user, "", $dumpSettings);
    $dump->start("output/mysqldump-php_test005.sql");

    print "Create dump with PHP: mysql-php_test006.sql" . PHP_EOL;
    $dump = new Mysqldump(
        "mysql:host=$host;dbname=test006a",
        $user,
        "",
        ["no-data" => true, "add-drop-table" => true]
    );
    $dump->start("output/mysqldump-php_test006.sql");

    print "Create dump with PHP: mysql-php_test008.sql" . PHP_EOL;
    $dump = new Mysqldump(
        "mysql:host=$host;dbname=test008",
        $user,
        "",
        ["no-data" => true, "add-drop-table" => true]
    );
    $dump->start("output/mysqldump-php_test008.sql");

    print "Create dump with PHP: mysql-php_test009.sql" . PHP_EOL;
    $dump = new Mysqldump(
        "mysql:host=$host;dbname=test009",
        $user,
        "",
        ["no-data" => true, "add-drop-table" => true, "reset-auto-increment" => true, "add-drop-database" => true]
    );
    $dump->start("output/mysqldump-php_test009.sql");

    print "Create dump with PHP: mysql-php_test010.sql" . PHP_EOL;
    $dump = new Mysqldump(
        "mysql:host=$host;dbname=test010",
        $user,
        "",
        ["events" => true]
    );
    $dump->start("output/mysqldump-php_test010.sql");

    print "Create dump with PHP: mysql-php_test011a.sql" . PHP_EOL;
    $dump = new Mysqldump(
        "mysql:host=$host;dbname=test011",
        $user,
        "",
        ['complete-insert' =>  false]
    );
    $dump->start("output/mysqldump-php_test011a.sql");

    print "Create dump with PHP: mysql-php_test011b.sql" . PHP_EOL;
    $dump = new Mysqldump(
        "mysql:host=$host;dbname=test011",
        $user,
        "",
        [
        'complete-insert' => true,
        ]
    );
    $dump->start("output/mysqldump-php_test011b.sql");

    print "Create dump with PHP: mysql-php_test012.sql" . PHP_EOL;
    $dump = new Mysqldump(
        "mysql:host=$host;dbname=test012",
        $user,
        "",
        [
        'events' => true,
        'skip-triggers' => false,
        'routines' => true,
        'add-drop-trigger' => true,
        ]
    );
    $dump->start("output/mysqldump-php_test012.sql");

    print "Create dump with PHP: mysql-php_test012b_no-definer.sql" . PHP_EOL;
    $dump = new Mysqldump(
        "mysql:host=$host;dbname=test012",
        $user,
        "",
        [
        "events" => true,
        'skip-triggers' => false,
        'routines' => true,
        'add-drop-trigger' => true,
        'skip-definer' => true,
        ]
    );
    $dump->start("output/mysqldump-php_test012_no-definer.sql");

    print "Create dump with PHP: mysql-php_test013.sql" . PHP_EOL;
    $dump = new Mysqldump(
        "mysql:host=$host;dbname=test001",
        $user,
        "",
        [
        "insert-ignore" => true,
        "extended-insert" => false
        ]
    );
    $dump->start("output/mysqldump-php_test013.sql");

    exit(0);
} catch (Exception $e) {
    print "Error: " . $e->getMessage() . PHP_EOL;
    exit(1);
}
