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
     * @param int $groupId
     * @param string $name
     * @param int $userId
     * @param string $summary
     * @return int
     * @throws Exception
     */
    public function addRepo(int $groupId, string $name, int $userId, string $summary, array $setting = [])
    {
        $this->checkRepoNameRule($name);

        // 检查库是否存在
        $group = $this->getGroup($groupId);
        if (!$group) {
            throw new Exception('repo.groupNotFound');
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
        $path = sprintf('%s/%s/%s.git', REPO_PATH, $group['name'], $name);
        $hooks = $this->getRepoHooks();
        Repository::addRepo($path, $hooks);

        $body = [
            'group' => $group['name'],
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

        $this->updateAllRepos();
        return $db->getLastInsertId();
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
require '{$autoloadPath}';
require '{$hookPath}PreReceiveHook.php';
\$hook = new PreReceiveHook(dirname(__DIR__));
if (\$hook->execute() === false) {
    exit(1);
}
SHELL;
        $postReceive = <<<SHELL
#! /bin/env php
<?php
require {$autoloadPath};
require {$hookPath}PostReceiveHook.php;
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
        if (!isset($repos[$group]) || !isset($repos[$name])) {
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
}