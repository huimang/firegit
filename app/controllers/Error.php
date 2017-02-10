<?php

/**
 *
 * @author: ronnie
 * @since: 2017/1/17 22:15
 * @copyright: 2017@firegit.com
 * @filesource: Error.php
 */
class ErrorController extends \Yaf\Controller_Abstract
{
    public function errorAction()
    {
        $ex = $this->_request->getException();

        $msg = '';
        if ($ex instanceof \huimang\Exception) {
            switch ($ex->getMessage()) {
                case 'notfound':
                    $msg = '该页面不存在，或者已经被删除。';
                    header('HTTP/1.1 404 Not Found');
                    break;
                case 'login':
                    $msg = '你尚未登录，不能访问该页面。';
                    if ($this->_request->getMethod() == 'GET') {
                        $this->_response->setRedirect('/account/login?rurl=' . rawurlencode($this->_request->getRequestUri()));
                    } else {
                        header('HTTP/1.1 401 Unauthorized');
                    }
                    break;
                case 'power':
                    $msg = '您没有权利操作该页面。';
                    header('HTTP/1.1 401 Unauthorized');
                    break;
                case 'mustRequestByPost':
                    $msg = '必须用POST方法请求该页面。';
                    break;
                default:
//                    header('HTTP/1.1 503 Internal Server Error');
                    break;
            }
        } else {
            header('HTTP/1.1 500 Internal Server Error');
        }

        if ($this->_request->getMethod() == 'POST') {
            // 输出json
            header('Content-type: application/json; charset=utf-8');
            $msg = $ex->getMessage();
            $pos = strpos($msg, '.');

            $output = [
                'status' => 'exception',
                'msg' => $msg,
            ];

            if ($pos !== false) {
                $modName = substr($msg, 0, $pos);
                $msgKey = substr($msg, $pos + 1);

                $ini = new \Yaf\Config\Ini(CONF_PATH . '/exception.ini', $modName);
                if ($ini && $ini->get($msgKey)) {
                    $output['desc'] = $ini->get($msgKey);
                }
            }
            echo json_encode($output, JSON_UNESCAPED_UNICODE);
        } else {
            if (!$msg) {
                $msg = nl2br($ex->__toString());
            }
            $this->_view->content = '<h1>出错了</h1><div class="card-panel gray">' . $msg . '</div>';
        }
    }
}
