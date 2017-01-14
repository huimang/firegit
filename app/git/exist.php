<?php
// 检查GIT库是否存在
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

require_once APPLICATION_PATH . '/app/models/Repo.php';
$model = new RepoModel();
$group = $model->isRepoExist($group, $name);
if (!$group) {
    header('HTTP/1.1 404 Not Found');
    exit();
}
echo $group[0];