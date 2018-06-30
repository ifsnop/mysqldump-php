<?php
/*
for($i=0;$i<128;$i++) {
    echo "$i>" . bin2hex(chr($i)) . "<" . PHP_EOL;
}
*/

error_reporting(E_ALL);

include_once(dirname(__FILE__) . "/../src/Ifsnop/Mysqldump/Mysqldump.php");

use Ifsnop\Mysqldump as IMysqldump;

$dumpSettings = array(
    'exclude-tables' => array('/^travis*/'),
    'compress' => IMysqldump\Mysqldump::NONE,
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
    );

// do nothing test
print "starting mysql-php_test000.sql" . PHP_EOL;
$dump = new IMysqldump\Mysqldump(
    "mysql:host=localhost;dbname=test001",
    "travis",
    ""
    );

print "starting mysql-php_test001.sql" . PHP_EOL;
$dump = new IMysqldump\Mysqldump(
    "mysql:host=localhost;dbname=test001",
    "travis",
    "",
    $dumpSettings);
$dump->start("mysqldump-php_test001.sql");

// checks if complete-insert && hex-blob works ok together
print "starting mysql-php_test001_complete.sql" . PHP_EOL;
$dumpSettings['complete-insert'] = true;
$dump = new IMysqldump\Mysqldump(
    "mysql:host=localhost;dbname=test001",
    "travis",
    "",
    $dumpSettings);
$dump->start("mysqldump-php_test001_complete.sql");

print "starting mysql-php_test002.sql" . PHP_EOL;
$dumpSettings['default-character-set'] = IMysqldump\Mysqldump::UTF8MB4;
$dumpSettings['complete-insert'] = true;
$dump = new IMysqldump\Mysqldump(
    "mysql:host=localhost;dbname=test002",
    "travis",
    "",
    $dumpSettings);
$dump->start("mysqldump-php_test002.sql");

print "starting mysql-php_test005.sql" . PHP_EOL;
$dumpSettings['complete-insert'] = false;
$dump = new IMysqldump\Mysqldump(
    "mysql:unix_socket=/var/run/mysqld/mysqld.sock;dbname=test005",
    "travis",
    "",
    $dumpSettings);
$dump->start("mysqldump-php_test005.sql");

print "starting mysql-php_test006.sql" . PHP_EOL;
$dump = new IMysqldump\Mysqldump(
    "mysql:unix_socket=/var/run/mysqld/mysqld.sock;dbname=test006a",
    "travis",
    "",
    array("no-data" => true, "add-drop-table" => true));
$dump->start("mysqldump-php_test006.sql");

print "starting mysql-php_test008.sql" . PHP_EOL;
$dump = new IMysqldump\Mysqldump(
    "mysql:unix_socket=/var/run/mysqld/mysqld.sock;dbname=test008",
    "travis",
    "",
    array("no-data" => true, "add-drop-table" => true));
$dump->start("mysqldump-php_test008.sql");

print "starting mysql-php_test009.sql" . PHP_EOL;
$dump = new IMysqldump\Mysqldump(
    "mysql:unix_socket=/var/run/mysqld/mysqld.sock;dbname=test009",
    "travis",
    "",
    array("no-data" => true, "add-drop-table" => true, "reset-auto-increment" => true, "add-drop-database" => true));
$dump->start("mysqldump-php_test009.sql");

print "starting mysql-php_test010.sql" . PHP_EOL;
$dump = new IMysqldump\Mysqldump(
    "mysql:unix_socket=/var/run/mysqld/mysqld.sock;dbname=test010",
    "travis",
    "",
    array("events" => true));
$dump->start("mysqldump-php_test010.sql");

print "starting mysql-php_test011a.sql" . PHP_EOL;
$dump = new IMysqldump\Mysqldump(
    "mysql:unix_socket=/var/run/mysqld/mysqld.sock;dbname=test011",
    "travis",
    "",
    array('complete-insert' =>  false));
$dump->start("mysqldump-php_test011a.sql");

print "starting mysql-php_test011b.sql" . PHP_EOL;
$dump = new IMysqldump\Mysqldump(
    "mysql:unix_socket=/var/run/mysqld/mysqld.sock;dbname=test011",
    "travis",
    "",
    array('complete-insert' =>  true));
$dump->start("mysqldump-php_test011b.sql");

print "starting mysql-php_test012.sql" . PHP_EOL;
$dump = new IMysqldump\Mysqldump(
    "mysql:unix_socket=/var/run/mysqld/mysqld.sock;dbname=test012",
    "travis",
    "",
    array("events" => true));
$dump->start("mysqldump-php_test012.sql");

print "starting mysql-php_test012b_no-definer.sql" . PHP_EOL;
$dump = new IMysqldump\Mysqldump(
    "mysql:unix_socket=/var/run/mysqld/mysqld.sock;dbname=test012",
    "travis",
    "",
    array(
        "events" => true,
        'skip-definer' => true,
    ));
$dump->start("mysqldump-php_test012_no-definer.sql");

print "starting mysql-php_test013.sql" . PHP_EOL;
$dump = new IMysqldump\Mysqldump(
    "mysql:unix_socket=/var/run/mysqld/mysqld.sock;dbname=test001",
    "travis",
    "",
    array(
        "insert-ignore" => true,
        "extended-insert" => false
    ));
$dump->start("mysqldump-php_test013.sql");

exit(0);
