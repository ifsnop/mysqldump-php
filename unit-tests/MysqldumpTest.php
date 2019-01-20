<?php

use Ifsnop\Mysqldump\Mysqldump;

class MysqldumpTest extends PHPUnit_Framework_TestCase
{

    /** @test */
    public function tableSpecificWhereConditionsWork()
    {
        $dump = new Mysqldump('mysql:host=localhost;dbname=test', 'testing', 'testing', array(
            'where' => 'defaultWhere'
        ));

        $dump->setTableWheres(array(
            'users' => 'date_registered > NOW() - INTERVAL 3 MONTH AND is_deleted=0',
            'logs' => 'date_registered > NOW() - INTERVAL 1 DAY',
            'posts' => 'active=1'
        ));

        $this->assertEquals(
            'date_registered > NOW() - INTERVAL 3 MONTH AND is_deleted=0',
            $dump->getTableWhere('users')
        );

        $this->assertEquals(
            'defaultWhere',
            $dump->getTableWhere('non_overriden_table')
        );
    }
}
