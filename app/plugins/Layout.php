<?php
use \Yaf\Response_Abstract;
use \Yaf\Request_Abstract;
/**
 *
 * @author: ronnie
 * @since: 2016/12/27 01:00
 * @copyright: 2016@firegit.com
 * @filesource: Layout.php
 */
class LayoutPlugin extends \Yaf\Plugin_Abstract
{
    private $_layoutDir;                //布局模板的路径
    private $_layoutFile;               //布局文件名
    private $_layoutVars = array();     //布局的模板变量
    public $withoutLayouts = array();   //不使用布局模板的模块列表

    public function __construct($layoutFile, $layoutDir = null)
    {
        $this->_layoutFile = $layoutFile;
        if ($layoutDir) {
            $this->_layoutDir = $layoutDir;
        }
    }

    public function __set($name, $value)
    {
        $this->_layoutVars[$name] = $value;
    }

    public function dispatchLoopShutDown(Request_Abstract $request, Response_Abstract $response)
    {
    }

    public function dispatchLoopStartup(Request_Abstract $request, Response_Abstract $response)
    {
    }


    public function postDispatch(Request_Abstract $request, Response_Abstract $response)
    {
        $module = strtolower($request->getModuleName());
        $controller = strtolower($request->getControllerName());
        $action = strtolower($request->getActionName());
        if (!empty($this->withoutLayouts)) {
            foreach ($this->withoutLayouts as $url) {
                $urlArr = explode('/', trim($url));
                $count = count($urlArr);
                if (($count == 1) && ($module == $urlArr[0]) || ($controller == $urlArr[0])) {
                    return true;
                } elseif (($count == 2) && ($module == $urlArr[0]) && ($controller == $urlArr[1])) {
                    return true;
                } elseif (($count == 3) && ($module == $urlArr[0]) && ($controller == $urlArr[1]) && ($action == $urlArr[2])) {
                    return true;
                }
            }
        }
        //获取已经设置响应的Body
        $body = $response->getBody();
        $response->clearBody();

        // 如果没有指定layoutDir，则通过模块自动获取
        if (!$this->_layoutDir) {
            if ($module == 'index') {
                $this->_layoutDir = APPLICATION_PATH . '/app/views/layouts';
            } else {
                $this->_layoutDir = APPLICATION_PATH . '/app/modules/' . ucfirst($module) . '/views/layouts';
            }
        }

        $layout = new \Yaf\View\Simple($this->_layoutDir);
        $layout->assign('content', $body);    //相当于$layout->assign('content', $body);
        $layout->assign('layout', $this->_layoutVars);
        //设置响应的body
        $response->setBody($layout->render($this->_layoutFile));
    }
}
