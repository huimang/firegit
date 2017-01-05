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

    public function testAll()
    {
        $dbImpl = new DbImpl('firegit', $this->conf);

//        $this->tryExists($dbImpl);
//        $this->tryGet($dbImpl);
//        $this->tryInsert($dbImpl);
        $this->tryUpdate($dbImpl);
    }

    public function tryGet(DbImpl $db)
    {
        $rows = $db->table('repo')->where('(group=\'firegit\' 
        or group=\'hell\') 
        and (status = ? or status=?)', [1 , 3])->getOne();
        $this->assertNotEmpty($rows);

        $dbs = $db->getTables();
        $this->assertArrayHasKey('repo', array_flip($dbs));
    }

    public function tryExists(DbImpl $db)
    {
        $exist = $db->table('repo')->where(['group' => 'firegit'])->getExist();
        $this->assertEquals(true, $exist);
    }

    public function tryInsert(DbImpl $db)
    {
        $db->table('repo')
            ->save([
                'group' => 'bundle',
                'name' => 'HapN',
                'rgroup_id' => 10001,
                'status' => 1,
                'create_time' => time(),
                'user_id' => 10000,
                'summary' => 'HapN框架'
            ])
            ->insert();
        var_dump($db->getLastInsertId());
        $this->assertNotEmpty($db->getLastInsertId());
    }

    public function tryUpdate(DbImpl $db)
    {
        $id = 10002;
        $newName = 'HapN10002';
        $db->table('repo')
            ->where(['repo_id' => $id])
            ->save(['name'=>$newName])
            ->update();
        $row = $db->table('repo')
            ->field('name', 'group')
            ->where(['repo_id='.$id])
            ->getOne();
        $this->assertArrayNotHasKey('repo_id', $row);
        $this->assertEquals($newName, $row['name']);
    }
}
