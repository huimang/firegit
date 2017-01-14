<?php

/**
 *
 * @author: ronnie
 * @since: 2017/1/15 10:50
 * @copyright: 2017@firegit.com
 * @filesource: RepoModelTest.php
 */
class RepoModelTest extends PHPUnit_Framework_TestCase
{
    public function testRepo()
    {
        require_once dirname(__DIR__) . '/Repo.php';
        $model = new RepoModel();
//        $this->tryCreateGroup($model);
//        $this->tryCreateRepo($model);
        $this->trySetRepoUser($model);
    }

    public function tryCreateGroup(RepoModel $model)
    {
        try {
            $model->addGroup('foo', 1, 'foo repositories');
        } catch (\huimang\Exception $ex) {
            $this->assertEquals('repo.groupNameIllegal', $ex->getMessage());
        }

        try {
            $model->addGroup('merge', 1, 'foo repositories');
        } catch (\huimang\Exception $ex) {
            $this->assertEquals('repo.groupNameProtected', $ex->getMessage());
        }

        $groupId = $model->addGroup('huimang', 1, 'huimangss仓库');
        $this->assertGreaterThan(0, $groupId);
    }


    /**
     * 创建repo
     * @param RepoModel $model
     */
    public function tryCreateRepo(RepoModel $model)
    {
        try {
            $model->addRepo(100000, 'firegit', 1, 'aaa');
        } catch (\huimang\Exception $ex) {
            var_dump($ex->getExtra());
            $this->assertEquals('repo.groupNotFound', $ex->getMessage());
        }

        $model->addRepo(2, 'hello', 1, 'Git Repositories Manage System');
    }

    public function trySetRepoUser(RepoModel $model)
    {
        $model->setRepoUser(10005, 17);
        $this->assertEquals(true, $model->isRepoUser(17, 10005));
    }

}
