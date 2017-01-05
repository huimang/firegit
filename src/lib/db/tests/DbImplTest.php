<?php
/**
 *
 * @author: ronnie
 * @since: 2017/1/13 00:55
 * @copyright: 2017@hunbasha.com
 * @filesource: DbImplTest.php
 */

namespace firegit\lib\db\tests;


use firegit\lib\db\DbImpl;


class DbImplTest extends \PHPUnit_Framework_TestCase
{
    private $conf = [
        'driver' => 'mysql',
        'host' => '192.168.56.102',
        'port' => 3306,
        'user' => 'firegit',
        'pass' => 'firegit',
        'charset' => 'utf8',
    ];

//
//    public function testDbImpl()
//    {
//        $dbImpl = new DbImpl('firegit', $this->conf);
//        $fields = $dbImpl->table('repo')->getFields();
//        var_dump($fields);
//
//        $indexs = $dbImpl->table('repo')->getIndexs();
//        var_dump($indexs);
//    }

    public function testGet()
    {
        $dbImpl = new DbImpl('firegit', $this->conf);
        $rows = $dbImpl->table('repo')->where(['group' => ['exp' => '\'firegit\''], 'name' => 'firegit'])->get();
        var_dump($rows);
    }
}
