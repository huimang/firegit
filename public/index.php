<?php
define('APPLICATION_PATH', dirname(__DIR__));
define('CONF_PATH', APPLICATION_PATH.'/conf/');
define('VENDOR_PATH', APPLICATION_PATH.'/vendor/');
define('LOG_PATH', APPLICATION_PATH. '/log/');
define('TMP_PATH', APPLICATION_PATH. '/tmp/');

require VENDOR_PATH.'autoload.php';

$app = new \Yaf\Application(CONF_PATH.'application.ini');
$app->bootstrap()->run();
