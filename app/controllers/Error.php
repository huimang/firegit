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

        if ($ex instanceof \huimang\Exception) {
            switch ($ex->getMessage()) {
                case 'notfound':
                    header('HTTP/1.1 404 Not Found');
                    break;
                case 'login':
                    if ($this->_request->getMethod() == 'GET') {
                        $this->_response->setRedirect('/account/login?rurl=' . rawurlencode($this->_request->getRequestUri()));
                    } else {
                        header('HTTP/1.1 401 Unauthorized');
                    }
                    break;
                case 'power':
                    header('HTTP/1.1 401 Unauthorized');
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

                $ini = new \Yaf\Config\Ini(CONF_PATH.'/exception.ini', $modName);
                if ($ini && $ini->get($msgKey)) {
                    $output['desc'] = $ini->get($msgKey);
                }
            }
            echo json_encode($output, JSON_UNESCAPED_UNICODE);
        } else {
            $this->_view->content = '<div class="card-panel">'.nl2br($ex->__toString()).'</div>';
        }
    }
}
