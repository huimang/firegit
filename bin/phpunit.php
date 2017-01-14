<?php
require __DIR__ . '/../autoload.php';

require_once APPLICATION_PATH . '/app/Bootstrap.php';
$bootstrap = new Bootstrap();
$bootstrap->_initLog();
$bootstrap->_initDb();
$bootstrap->_initCache();
