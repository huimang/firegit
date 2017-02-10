<?php
/**
 * 用来完成git的授权
 */

$group = $_GET['group'] ?? '';
$name = $_GET['name'] ?? '';

if (!$group || !$name) {
    header('HTTP/1.1 404 Not Found');
    exit();
}


require __DIR__ . '/../../autoload.php';

require_once APPLICATION_PATH.'/app/Bootstrap.php';
$bootstrap = new Bootstrap();
$bootstrap->_initLog();
$bootstrap->_initDb();
$bootstrap->_initCache();

// 检查项目是否存在
require_once APPLICATION_PATH.'/app/models/Repo.php';
$repoModel = new RepoModel();
$repo = $repoModel->isRepoExist($group, $name);
if (!$repo) {
    header('HTTP/1.1 404 Not Found');
    exit();
}

// 如果可以匿名访问，则直接返回200
list($repoId, $anonymous) = $repo;
if ($anonymous) {
    exit();
}

// 检查是否提供了账号密码
$user = $_SERVER['PHP_AUTH_USER'] ?? '';
$pw = $_SERVER['PHP_AUTH_PW'] ?? '';

if (!$user || !$pw) {
    header('HTTP/1.1 401 Unauthorized');
    exit();
}

// 检查用户名和密码是否正确
require_once APPLICATION_PATH . '/app/models/User.php';
$model = new UserModel();
$userId = $model->login($user, $pw);
if ($userId < 0) {
    header('HTTP/1.1 401 Unauthorized');
    exit();
}
$user = $model->getUser($userId);
if (!$user || $user['status'] == 0) {
    header('HTTP/1.1 401 Unauthorized');
    exit();
}

// 检查用户是否可以访问该项目
if ($user['role'] == 1 && !$repoModel->isRepoUser($userId, $repoId)) {
    header('HTTP/1.1 401 Unauthorized');
    exit();
}
// 输出GIT库ID和用户ID
echo $repoId.':'.$userId;
