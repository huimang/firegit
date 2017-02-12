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
    public function _initLog()
    {
        error_reporting(E_ALL | E_NOTICE | E_WARNING);
        ini_set('display_errors', 1);
        ini_set('error_log', LOG_PATH . date('Ymd') . '.log');
        \Yaf\Dispatcher::getInstance()->catchException(true);
    }

    /**
     * 初始化数据库
     */
    public function _initDb()
    {
        $cfg = new \Yaf\Config\Ini(CONF_PATH . 'db.ini', 'db');
        \huimang\db\Db::init($cfg->toArray());
    }

    /**
     * 初始化缓存
     */
    public function _initCache()
    {
        $cfg = new \Yaf\Config\Ini(CONF_PATH . 'cache.ini', 'cache');
        \huimang\cache\Cache::init($cfg->toArray());
    }

    public function _initConfig()
    {
        \Yaf\Registry::set('config', \Yaf\Application::app()->getConfig());

    }

    public function _initRouter()
    {
        $routes = \Yaf\Registry::get("config")->routes;
        if (!empty($routes)) {
            \Yaf\Dispatcher::getInstance()->getRouter()->addConfig($routes);
        }
    }

    /**
     * 初始化插件
     */
    public function _initPlugin(\Yaf\Dispatcher $dispatcher)
    {
        $config = \Yaf\Registry::get('config');
        switch($_SERVER['REQUEST_METHOD']) {
            case 'GET':
                if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest') {
                    // 如果指定返回json，则启用PostPlugin
                    if ($_GET['_of'] == 'json') {
                        $post = new PostPlugin();
                        $dispatcher->registerPlugin($post);
                    }
                    break;
                }
                $withoutLayouts = [];
                if (!empty($config['application']['view']['withoutLayouts'])) {
                    $withoutLayouts = array_filter(explode(',', $config['application']['view']['withoutLayouts']));
                }
                $layout = new LayoutPlugin('layout.phtml');
                $layout->withoutLayouts = $withoutLayouts;   //本配置中设置不需要布局文件的url
                $dispatcher->registerPlugin($layout);

                \Yaf\Registry::set('layout', $layout);
                break;
            case 'POST':
            case 'PUT':
            case 'DELETE':
                $post = new PostPlugin();
                $dispatcher->registerPlugin($post);
                break;
        }
    }
}
