<?php
require_once __DIR__ . '/repo/GroupTrait.php';

use huimang\Exception;
use huimang\db\Db;
use huimang\git\Repository;
use huimang\cache\Cache;

/**
 *
 * @author: ronnie
 * @since: 2017/1/15 10:43
 * @copyright: 2017@firegit.com
 * @filesource: Repo.php
 */
class RepoModel
{
    // GIT组名规则 长度为4-16
    const GROUPNAME_RULE = '#^[a-z][a-z\_0-9]{3,15}$#';
    // GIT库名称规则 3-20位
    const REPONAME_RULE = '#^[a-z][a-zA-Z0-9\_\-]{2,19}$#';

    const REPO_ROLE_NORMAL = 0x1;
    const REPO_ROLE_ADMIN = 0x2;

    const ALLREPOS_CACHEKEY = 'allRespos';

    use GroupTrait;

    private static $defaultSettings = [
        // 是否支持匿名访问
        'anonymous' => 0,
    ];


    /**
     * 添加GIT库
     * @param string $groupName
     * @param string $name
     * @param int $userId
     * @param string $summary
     * @return int
     * @throws Exception
     */
    public function addRepo(string $groupName, string $name, int $userId, string $summary, array $setting = [])
    {
        $this->checkRepoNameRule($name);

        // 检查库是否存在
        $group = $this->getGroupByName($groupName);
        if (!$group) {
            // 检查groupName和$userId对应的用户的名称是否一样
            $user = (new UserModel())->getUser($userId);
            if ($user['username'] == $groupName) {
                $groupId = $this->addGroup($groupName, $userId);
            } else {
                throw new Exception('repo.groupNotFound');
            }
        } else {
            // 检查用户是否可以使用该group
            if ($group['user_id'] != $userId) {
                throw new Exception('repo.nopower');
            }
            $groupId = $group['rgroup_id'];
        }
        // 检查是否存在同名的库
        $db = Db::get();
        if ($db->table('repo')
            ->where(
                'rgroup_id=? and name=? and status != ?',
                [
                    $groupId,
                    $name,
                    -1
                ]
            )->getExist()
        ) {
            throw new Exception('repo.nameExisted');
        }

        // 开始建库
        $path = $this->getRepoPath($group['name'], $name);
        $hooks = $this->getRepoHooks();
        Repository::addRepo($path, $hooks);

        $body = [
            'group' => $groupName,
            'rgroup_id' => $groupId,
            'name' => $name,
            'status' => 1,
            'user_id' => $userId,
            'summary' => $summary,
            'create_time' => time(),
        ];

        foreach (self::$defaultSettings as $key => $value) {
            $body['setting'][$key] = $setting[$key] ?? $value;
        }
        $body['setting'] = json_encode($body['setting'], JSON_UNESCAPED_UNICODE);

        $db->table('repo')
            ->save($body)
            ->insert();
        $repoId = $db->getLastInsertId();

        // 将自己添加为管理员
        $this->setRepoUser($repoId, $userId, self::REPO_ROLE_ADMIN);

        $this->updateAllRepos();
        return $repoId;
    }

    /**
     * 获取GIT库地址
     * @param string $group
     * @param string $name
     * @return string
     */
    public function getRepoPath(string $group, string $name)
    {
        return sprintf('%s/%s/%s.git', REPO_PATH, $group, $name);
    }


    /**
     * 删除项目
     * @param int $repoId
     * @param int $userId
     * @throws Exception repo.nopowerToDel 没有权利删除
     */
    public function delRepo(int $repoId, int $userId)
    {
        $repo = $this->getRepo($repoId);
        if (!$repo) {
            return;
        }
        // 检查是否为repo的创始人
        if ($repo['user_id'] != $userId) {
            throw new Exception('repo.nopowerToDel');
        }

        // 从数据库标记删除
        $db = Db::get();
        $db->table('repo')
            ->where(['repo_id' => $repoId])
            ->save(['status' => -1])
            ->update();

        // 删除用户关系
        $db->table('repo_user')
            ->where(['repo_id' => $repoId])
            ->delete();

        // 获取repo的地址
        $path = $this->getRepoPath($repo['group'], $repo['name']);
        system('rm -rf '.$path);
    }

    /**
     * 获取hooks
     * @return array
     */
    private function getRepoHooks()
    {
        $autoloadPath = APPLICATION_PATH . '/bin/hook.php';
        $hookPath = APPLICATION_PATH . '/app/hooks/';
        $preReceive = <<<SHELL
#! /bin/env php
<?php
require "{$autoloadPath}";
require "{$hookPath}PreReceiveHook.php";
\$hook = new PreReceiveHook(dirname(__DIR__));
if (\$hook->execute() === false) {
    exit(1);
}
SHELL;
        $postReceive = <<<SHELL
#! /bin/env php
<?php
require "{$autoloadPath}";
require "{$hookPath}PostReceiveHook.php";
\$hook = new PostReceiveHook(dirname(__DIR__));
if (\$hook->execute() === false) {
    exit(1);
}
SHELL;
        return [
            'pre-receive' => $preReceive,
            'post-receive' => $postReceive,
        ];
    }


    /**
     * 设置GIT库的用户
     * @param int $repoId
     * @param int $userId
     * @param $role
     */
    public function setRepoUser(int $repoId, int $userId, $role = self::REPO_ROLE_NORMAL)
    {
        Db::get()
            ->table('repo_user')
            ->save([
                'repo_id' => $repoId,
                'user_id' => $userId,
                'create_time' => time(),
                'role' => $role
            ])
            // 遇到重复的键则更新角色
            ->onDuplicate(['role'])
            ->insert();
    }

    /**
     * 更新所有的项目
     */
    private function updateAllRepos()
    {
        $rows = Db::get()->table('repo')
            ->field('repo_id', 'name', 'group', 'setting->\'$.anonymous\' as anonymous')
            ->whereCause('status', '!=', -1)
            ->get();
        $allRepos = [];
        foreach ($rows as $row) {
            $allRepos[$row['group']][$row['name']] = [$row['repo_id'], $row['anonymous']];
        }
        Cache::get()->set(self::ALLREPOS_CACHEKEY, $allRepos, 3600 * 24);
        return $allRepos;
    }


    /**
     * 检查用户是否拥有指定项目
     * @param int $userId
     * @param int $repoId
     * @return bool
     */
    public function isRepoUser(int $userId, int $repoId)
    {
        // 检查是否为超管
        return Db::get()
            ->table('repo_user')
            ->where(['user_id' => $userId, 'repo_id' => $repoId])
            ->getExist();
    }

    /**
     * 检查指定的库是否存在
     * @param $group
     * @param $name
     * @return array|null 如果有值，返回[$groupId,$anonymous]
     */
    public function isRepoExist($group, $name)
    {
        $repos = Cache::get()->get(self::ALLREPOS_CACHEKEY);
        if ($repos === null) {
            $repos = $this->updateAllRepos();
        }
        if (!isset($repos[$group]) || !isset($repos[$group][$name])) {
            return null;
        }
        return $repos[$group][$name];
    }

    /**
     * 获取repo
     * @param $repoId
     * @return array|null
     */
    public function getRepo($repoId)
    {
        $repo = Db::get()
            ->table('repo')
            ->where(['repo_id' => $repoId])
            ->whereCause('status', '!=', -1)
            ->getOne();
        if ($repo) {
            $this->unpackRepo($repo);
        }
        return $repo;
    }

    private function unpackRepo(&$repo)
    {
        if (isset($repo['setting'])) {
            $repo['setting'] = json_decode($repo['setting'], true);
        }
    }

    /**
     * 检查GIT库名称规则
     * @param $name
     * @throws Exception
     */
    private function checkRepoNameRule($name)
    {
        if (!preg_match(self::REPONAME_RULE, $name)) {
            throw new Exception('repo.repoNameIllegal');
        }
    }

    /**
     * 获取用户创建的GIT库
     * @param int $userId
     * @param int $size
     * @param int $page 如果为-1，则直接返回指定的数目的git库，而不返回总数
     * @return array
     */
    public function getUserCreateRepos(int $userId, int $size, int $page = -1)
    {
        $db = Db::get()
            ->table('repo')
            ->where(['user_id' => $userId])
            ->whereCause('status', '!=', -1)
            ->order('repo_id');
        if ($page < 0) {
            return $db->limit($size)->get();
        }

        $total = $db->table('repo')
            ->setReset(false)
            ->getCount();

        if ($total > 0 && ceil($total / $size) > $page) {
            return [
                'total' => $total,
                'list' => $db->limit($size, $page * $size)->get()
            ];
        }
        return [
            'total' => $total,
            'list' => [],
        ];
    }


    /**
     * 获取用户属于的GIT库
     * @param int $userId
     * @param int $size
     * @param int $page 如果为-1，则直接返回指定的数目的git库，而不返回总数
     * @return array
     */
    public function getUserBeloneRepos(int $userId, int $size, int $page = -1)
    {

        $db = Db::get()
            ->table('repo_user')
            ->where(['user_id' => $userId])
            ->order('create_time');
        $total = 0;
        $userRepos = [];
        if ($page < 0) {
            $userRepos = $db->limit($size)
                ->get();
            if (!$userRepos) {
                return [];
            }
        } else {
            $total = $db->setReset(false)->getCount();
            if ($total && ceil($total / $size) <= $page) {
                $userRepos = $db->limit($size, $page * $size)
                    ->get();
            }
            if (!$userRepos) {
                return [
                    'total' => $total,
                    'list' => [],
                ];
            }
        }

        $repoIds = array_column($userRepos, 'repo_id');
        $repos = $db->table('repo')
            ->field('repo_id', 'group', 'name')
            ->in('repo_id', $repoIds)
            ->where(['status' => 1])
            ->get();
        $repos = array_column($repos, null, 'repo_id');
        foreach ($userRepos as $key => $row) {
            $userRepos[$key] = array_merge($row, $repos[$row['repo_id']]);
        }
        if ($page >= 0) {
            return [
                'total' => $total,
                'list' => $userRepos,
            ];
        }
        return $userRepos;
    }

    /**
     * 获取GIT库的用户
     * @param int $repoId
     * @return array
     * [
     *  [
     *    'user_id',
     *    'username',
     *    'email',
     *    'realname',
     *    'role', // 用户角色
     *    'repo_role', // GIT库角色
     *  ]
     * ]
     */
    public function getRepoUsers(int $repoId)
    {
        $db = Db::get();
        $repoUsers = $db->table('repo_user')
            ->field('user_id', 'role')
            ->where(['repo_id' => $repoId])
            ->order('create_time')
            ->get();
        if (!$repoUsers) {
            return [];
        }
        $userIds = array_column($repoUsers, 'user_id');
        $repoUsers = array_column($repoUsers, 'role', 'user_id');
        $userApi = new UserModel();
        $users = $userApi->getUsers($userIds);
        foreach ($users as &$user) {
            $user['repo_role'] = $repoUsers[$user['user_id']];
        }
        return $users;
    }

    /**
     * 删除仓库用户
     * @param int $repoId
     * @param int $userId
     */
    public function delRepoUser(int $repoId, int $userId)
    {
        Db::get()
            ->table('repo_user')
            ->where(['repo_id' => $repoId, 'user_id' => $userId])
            ->delete();
    }
}