<?php
/*
for($i=0;$i<128;$i++) {
    echo "$i>" . bin2hex(chr($i)) . "<" . PHP_EOL;
}
*/

error_reporting(E_ALL);

include_once(dirname(__FILE__) . "/../src/Ifsnop/Mysqldump/Mysqldump.php");

use Ifsnop\Mysqldump as IMysqldump;

$username = 'travis';
$password = 'M6s677xygWjR2Lw9';

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

$dump = new IMysqldump\Mysqldump(
    "mysql:host=localhost;dbname=test001",
    $username,
    $password,
    $dumpSettings);
$dump->start("mysqldump-php_test001.sql");

$dumpSettings['default-character-set'] = IMysqldump\Mysqldump::UTF8MB4;
$dumpSettings['complete-insert'] = true;
$dump = new IMysqldump\Mysqldump(
    "mysql:host=localhost;dbname=test002",
    $username,
    $password,
    $dumpSettings);
$dump->start("mysqldump-php_test002.sql");

$dumpSettings['complete-insert'] = false;
$dump = new IMysqldump\Mysqldump(
    "mysql:unix_socket=/var/run/mysqld/mysqld.sock;dbname=test005",
    $username,
    $password,
    $dumpSettings);
$dump->start("mysqldump-php_test005.sql");

$dump = new IMysqldump\Mysqldump(
    "mysql:unix_socket=/var/run/mysqld/mysqld.sock;dbname=test006a",
    $username,
    $password,
    array("no-data" => true, "add-drop-table" => true));
$dump->start("mysqldump-php_test006.sql");

$dump = new IMysqldump\Mysqldump(
    "mysql:unix_socket=/var/run/mysqld/mysqld.sock;dbname=test008",
    $username,
    $password,
    array("no-data" => true, "add-drop-table" => true));
$dump->start("mysqldump-php_test008.sql");

$dump = new IMysqldump\Mysqldump(
    "mysql:unix_socket=/var/run/mysqld/mysqld.sock;dbname=test009",
    $username,
    $password,
    array("no-data" => true, "add-drop-table" => true, "reset-auto-increment" => true, "add-drop-database" => true));
$dump->start("mysqldump-php_test009.sql");

$dump = new IMysqldump\Mysqldump(
    "mysql:unix_socket=/var/run/mysqld/mysqld.sock;dbname=test010",
    $username,
    $password,
    array("events" => true));
$dump->start("mysqldump-php_test010.sql");

$dump = new IMysqldump\Mysqldump(
    "mysql:unix_socket=/var/run/mysqld/mysqld.sock;dbname=test011",
    $username,
    $password,
    array('complete-insert' =>  false));
$dump->start("mysqldump-php_test011a.sql");

$dump = new IMysqldump\Mysqldump(
    "mysql:unix_socket=/var/run/mysqld/mysqld.sock;dbname=test011",
    $username,
    $password,
    array('complete-insert' =>  true));
$dump->start("mysqldump-php_test011b.sql");



$dump = new IMysqldump\Mysqldump(
    "mysql:host=localhost;dbname=test012",
    $username,
    $password,
    array('complete-insert' =>  true, 'insert-ignore'=>true));
$dump->start("mysqldump-php_test012.sql");


exit;