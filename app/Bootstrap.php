<?php
use Yaf\Bootstrap_Abstract;

/**
 *
 * @author: ronnie
 * @since: 2017/1/13 14:34
 * @copyright: 2017@firegit.com
 * @filesource: Bootstrap.php
 */
class Bootstrap extends Bootstrap_Abstract
{
    public function _initDb()
    {
        $cfg = new  \Yaf\Config\Ini(CONF_PATH . 'db.ini', 'db');
        \huimang\db\Db::init($cfg->db->toArray());
    }

    public function _initConfig()
    {
        \Yaf\Registry::set('config', \Yaf\Application::app()->getConfig());
    }

    public function _initLog()
    {
        error_reporting(E_ALL | E_NOTICE | E_WARNING);
        ini_set('display_errors', 1);
        ini_set('error_log', LOG_PATH . date('Ymd') . '.log');
    }

    public function _initRouter()
    {
        $router = \Yaf\Dispatcher::getInstance()->getRouter();
        $routes = \Yaf\Registry::get("config")->routes;
        if (!empty($routes)) {
            $router->addConfig($routes);
        }
    }

    /**
     * 初始化插件
     */
    public function _initPlugin(\Yaf\Dispatcher $dispatcher)
    {
        $config = \Yaf\Registry::get('config');
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            $withoutLayouts = array();
            if (isset($config['application']['view']['withoutLayouts'])) {
                $withoutLayouts = array_filter(explode(',', $config['application']['view']['withoutLayouts']));
            }
            $layout = new LayoutPlugin('layout.phtml');
            $layout->withoutLayouts = $withoutLayouts;   //本配置中设置不需要布局文件的url
            $dispatcher->registerPlugin($layout);

            \Yaf\Registry::set('layout', $layout);
        }
    }
}
