<?php
require dirname(__DIR__) . '/autoload.php';

$app = new \Yaf\Application(CONF_PATH . 'application.ini');
$app->bootstrap()->run();
