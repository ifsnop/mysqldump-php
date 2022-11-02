<?php

namespace Druidfi\Mysqldump\Tests;

use Druidfi\Mysqldump\Mysqldump;
use PHPUnit\Framework\TestCase;

class MysqldumpTest extends TestCase
{
    /**
     * @covers Mysqldump
     */
    public function testTableSpecificWhereConditionsWork()
    {
        $dump = new Mysqldump('mysql:host=localhost;dbname=test', 'testing', 'testing', [
            'where' => 'defaultWhere'
        ]);

        $dump->setTableWheres([
            'users' => 'date_registered > NOW() - INTERVAL 3 MONTH AND is_deleted=0',
            'logs' => 'date_registered > NOW() - INTERVAL 1 DAY',
            'posts' => 'active=1'
        ]);

        $this->assertEquals(
            'date_registered > NOW() - INTERVAL 3 MONTH AND is_deleted=0',
            $dump->getTableWhere('users')
        );

        $this->assertEquals(
            'defaultWhere',
            $dump->getTableWhere('non_overriden_table')
        );
    }

    /**
     * @covers Mysqldump
     */
    public function testDSNWorks()
    {
        $user = 'user';
        $pass = 'password';
        $host = 'localhost';
        $dbName = 'test';
        $dsn = "mysql: host={$host}; dbname={$dbName};";
        $dump = new Mysqldump($dsn, $user, $pass);
        $this->assertEquals($dsn, $this->getPrivate($dump, 'dsn'), 'dsn is not set correctly');
        $this->assertEquals($user, $this->getPrivate($dump, 'user'), 'user is not set correctly');
        $this->assertEquals($pass, $this->getPrivate($dump, 'pass'), 'pass is not set correctly');
        $this->assertEquals($host, $this->getPrivate($dump, 'host'), 'host is not set correctly');
        $this->assertEquals($dbName, $this->getPrivate($dump, 'dbName'), 'dbName is not set correctly');
    }

  /**
     * @covers Mysqldump
     */
    public function testDSNStringExits()
    {
        $this->expectException(\Exception::class);
        $dump = new Mysqldump('', 'testing', 'testing');
    }

    /**
     * @covers Mysqldump
     */
    public function testHostExits()
    {
        $this->expectException(\Exception::class);
        $dump = new Mysqldump('mysql: dbname=test;', 'testing', 'testing');
    }

    /**
     * @covers Mysqldump
     */
    public function testDBTypeExists()
    {
        $this->expectException(\Exception::class);
        $dump = new Mysqldump('host=localhost; dbname=test;', 'testing', 'testing');
    }

    /**
     * @covers Mysqldump
     */
    public function testDBNameExists()
    {
        $this->expectException(\Exception::class);
        $dump = new Mysqldump('mysql: host=localhost', 'testing', 'testing');
    }

    /**
     * @covers Mysqldump
     */
    public function testTableSpecificLimitsWork()
    {
        $dump = new Mysqldump('mysql: host=localhost; dbname=test;', 'testing', 'testing');

        $dump->setTableLimits([
            'users' => 200,
            'logs' => 500,
            'table_with_invalid_limit' => '41923, 42992',
            'table_with_range_limit' => [41923, 42992],
            'table_with_invalid_range_limit' => [41923],
            'table_with_invalid_range_limit2' => [41923, 42992, 42999],
        ]);

        $this->assertEquals(200, $dump->getTableLimit('users'));
        $this->assertEquals(500, $dump->getTableLimit('logs'));
        $this->assertFalse($dump->getTableLimit('table_with_invalid_limit'));
        $this->assertFalse($dump->getTableLimit('table_name_with_no_limit'));
        $this->assertEquals('41923, 42992', $dump->getTableLimit('table_with_range_limit'));
        $this->assertFalse($dump->getTableLimit('table_with_invalid_range_limit'));
        $this->assertFalse($dump->getTableLimit('table_with_invalid_range_limit2'));
    }

    private function getPrivate(Mysqldump $dump, $var)
    {
        $reflectionProperty = new \ReflectionProperty(Mysqldump::class, $var);
        $reflectionProperty->setAccessible(true);
        return $reflectionProperty->getValue($dump);
    }
}
