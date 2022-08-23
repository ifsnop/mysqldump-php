<?php

date_default_timezone_set('UTC');
error_reporting(E_ALL);

$host = $argv[1] ?? 'db'; // Get host name from test.sh
$user = 'travis';
$expected_double = '-2.2250738585072014e-308';
$ret = 0;

print "PHP version is ". phpversion() . PHP_EOL;
print "PDO check: double field" . PHP_EOL . PHP_EOL;

$db = new \PDO("mysql:host=$host;dbname=test001", $user, null, [
    \PDO::ATTR_STRINGIFY_FETCHES => true,
]);

$q = $db->query('SELECT * FROM test000');
$q->setFetchMode(\PDO::FETCH_ASSOC);

foreach ($q as $result) {
    if ($result['col15'] === $expected_double) {
        echo "Success: Double value is the expected!" . PHP_EOL;
        $ret = 0;
    } else {
        echo "Fail: double value is not expected..." . PHP_EOL;
        echo "Expected: " . $expected_double . PHP_EOL;
        echo "Actual:   " . $result['col15'] . PHP_EOL;
        $ret = 1;
    }
}

echo PHP_EOL;

exit($ret);
