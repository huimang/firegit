<?php

/**
 *
 * @author: ronnie
 * @since: 2017/1/13 22:41
 * @copyright: 2017@firegit.com
 * @filesource: UserModelTest.php
 */
class UserModelTest extends PHPUnit_Framework_TestCase
{
    public function testUser()
    {
        $ini = new \Yaf\Config\Ini(dirname(__DIR__) . '/../../conf/db.ini', 'db');
        \huimang\db\Db::init($ini->db->toArray());

        require_once dirname(__DIR__).'/UserModel.php';
        $uModel = new UserModel();

//        $this->tryInsert($uModel);
//        $this->tryGet($uModel);
//        $this->tryUpdate($uModel);
        $this->tryLogin($uModel);
        $this->tryChangePwd($uModel);
    }

    public function tryInsert(UserModel $model)
    {
        $userId = $model->addUser(
            'firegit2',
            'firegit',
            'ronnie2@huimang.com',
            '邓小龙',
            ['sex' => UserModel::SEX_MALE]
        );
        var_dump($userId);
        $this->assertNotEmpty($userId);
    }

    public function tryGet(UserModel $model)
    {
        $user = $model->getUser(16);
        $this->assertNotEmpty($user);
        $this->assertArrayNotHasKey('password', $user);
    }

    public function tryUpdate(UserModel $model)
    {
        $userId = 17;
        $user = $model->getUser($userId);
        $this->assertNotEmpty($user);

        $model->updateUser($user['user_id'], [
            'status' => 0
        ]);

        $user2 = $model->getUser($userId);
        $this->assertEquals(0, $user2['status']);
    }

    public function tryLogin(UserModel $model)
    {
        $this->assertEquals(-1, $model->login('', 'firegit'));
        $this->assertEquals(-2, $model->login('sdfssss', 'firegit'));
        $this->assertEquals(-2, $model->login('firegit', 'firegitssss'));

        $this->assertGreaterThan(0, $model->login('firegit', '2016World'));
        $this->assertEquals(-3, $model->login('firegit2', 'firegit'));
    }

    public function tryChangePwd(UserModel $model)
    {
        $userId = 1;
        $oldPwd = '2016World';
        $newPwd = '2017World';
        $model->updatePassword($userId, $oldPwd, $newPwd);

        $user = $model->getUser($userId);
        $this->assertNotEmpty($user);
        $this->assertEquals(-2, $model->login($user['username'], $oldPwd));
        $this->assertEquals($userId, $model->login($user['username'], $newPwd));

        $model->updatePassword($userId, $newPwd, $oldPwd);
        $this->assertEquals($userId, $model->login($user['username'], $oldPwd));

        $model->updatePassword(16, '2017World', '2018World');
        $model->updatePassword(16, '2018World', '2017World');
    }
}
