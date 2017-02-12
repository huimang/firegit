<?php

use huimang\Exception;
use huimang\db\Db;

/**
 *
 * @author: ronnie
 * @since: 2017/1/15 11:22
 * @copyright: 2017@firegit.com
 * @filesource: GroupTrait.php
 */
trait GroupTrait
{
    private static $protectedGroups;

    /**
     * 添加分组
     * @param string $name
     * @param int $userId
     * @param string $summary
     * @return int
     * @throws Exception repo.groupNameIllegal 分组名不合法
     * @throws Exception repo.groupNameProtected 该分组名被保护
     * @throws Exception repo.groupNameUsed 该分组名已经使用过
     */
    public function addGroup(string $name, int $userId, string $summary = '')
    {
        $this->checkGroupName($name);

        $db = Db::get();

        // 检查groupName是否存在
        if ($db->table('repo_group')->where(['name' => $name])->getExist()) {
            throw new Exception('repo.groupNameUsed');
        }

        $db->table('repo_group')
            ->save([
                'name' => $name,
                'user_id' => $userId,
                'summary' => $summary,
                'create_time' => time(),
                'status' => 1,
            ])
            ->insert();
        return $db->getLastInsertId();
    }

    /**
     * 编辑分组
     * @param $groupId
     * @param array $data
     */
    public function updateGroup($groupId, array $data)
    {
        $body = array_intersect_key($data, array_flip(['summary', 'status']));
        if (!$body) {
            return;
        }

        Db::get()->table('repo_group')
            ->save($body)
            ->where(['rgroup_id' => $groupId])
            ->update();
    }

    /**
     * 获取分组
     * @param int $groupId
     * @return array|null
     */
    public function getGroup(int $groupId)
    {
        return Db::get()->table('repo_group')
            ->where(['rgroup_id' => $groupId])
            ->whereCause('status', '!=', -1)
            ->getOne();
    }

    /**
     * 通过分组名获取分组
     * @param string $name
     * @return array|null
     */
    public function getGroupByName(string $name)
    {
        return Db::get()->table('repo_group')
            ->where(['name' => $name])
            ->whereCause('status', '!=', -1)
            ->getOne();
    }

    /**
     * 通过分组名后去多个分组
     * @param array $names
     * @return array [$name => $groupId]
     */
    public function getGroupsByNames(array $names)
    {
        $rows = Db::get()
            ->table('repo_group')
            ->field('rgroup_id', 'name')
            ->in('name', $names)
            ->where('status != ?', -1)
            ->getOne();
        $rows = array_column($rows, 'rgroup_id', 'name');

        return array_map(function ($name) use ($rows) {
            return $rows[$name] ?? false;
        }, $names);
    }


    /**
     * 检查group名称
     * @param $group
     * @throws Exception
     */
    private function checkGroupName($group)
    {
        if (!preg_match(self::GROUPNAME_RULE, $group)) {
            throw new Exception('repo.groupNameIllegal');
        }

        $protected = $this->getProtectedGroups();
        if (in_array($group, $protected)) {
            throw new Exception('repo.groupNameProtected');
        }
    }

    /**
     * 获取保护的分组名称
     * @return array
     */
    private function getProtectedGroups()
    {
        if (!self::$protectedGroups) {
            $ini = new \Yaf\Config\Ini(CONF_PATH . '/models/repo.ini', 'group');
            self::$protectedGroups = $ini->name->protected->toArray();
        }
        return self::$protectedGroups;
    }
}