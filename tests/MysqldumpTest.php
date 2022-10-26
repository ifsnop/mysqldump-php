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
    public function testTableSpecificLimitsWork()
    {
        $dump = new Mysqldump('mysql:host=localhost;dbname=test;', 'testing', 'testing');

        $dump->setTableLimits([
            'users' => 200,
            'logs' => 500,
            'table_with_invalid_limit' => '41923, 42992'
        ]);

        $this->assertEquals(200, $dump->getTableLimit('users'));
        $this->assertEquals(500, $dump->getTableLimit('logs'));
        $this->assertFalse($dump->getTableLimit('table_with_invalid_limit'));
        $this->assertFalse($dump->getTableLimit('table_name_with_no_limit'));
    }
}
