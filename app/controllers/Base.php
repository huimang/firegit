<?php

/**
 *
 * @author: ronnie
 * @since: 2017/1/17 10:48
 * @copyright: 2017@firegit.com
 * @filesource: Base.php
 */
abstract class BaseController extends \Yaf\Controller_Abstract
{
    protected $needLogin = true;
    protected $user;
    protected $userId = 0;

    /**
     * @var LayoutPlugin
     */
    protected $_layout;

    /**
     * 初始化
     */
    public function init()
    {
        if ($this->_request->method != 'GET') {
            $this->disableView();
        } else {
            if ($this->_request->action[0] == '_') {
                throw \huimang\Exception::newEx('mustRequestByPost');
            }
        }

        if (!$this->needLogin) {
            return;
        }
        $userCookie = $_COOKIE['fgu'] ?? '';
        if (!$userCookie) {
            throw new \huimang\Exception('login');
        }

        $mask = new \huimang\encrypt\Mask();
        $raw = $mask->decrypt($userCookie);
        $arr = explode(',', $raw);
        $vcode = array_pop($arr);
        if ($vcode != md5(implode(',', $arr) . 'hell0World')) {
            error_log('cookie.wrongVcode cookie:' . $raw);
            throw new \huimang\Exception('login');
        }
        if (count($arr) != 5) {
            error_log('cookie.wrongLength cookie:' . $raw);
            throw new \huimang\Exception('login');
        }
        $expire = array_pop($arr);
        if ($expire < time()) {
            setcookie('fgu', null, 0, '/');
            throw new \huimang\Exception('login');
        }
        $this->user = [
            'user_id' => $arr[0],
            'username' => $arr[1],
            'role' => $arr[2],
            'realname' => $arr[3],
        ];
        $this->userId = $arr[0];

        $this->_layout = \Yaf\Registry::get('layout');
        if ($this->_layout) {
            $this->_layout->user = $this->user;
            $this->_layout->userNav = '';
        }
    }

    public function disableView()
    {
        \Yaf\Dispatcher::getInstance()->disableView();
        \Yaf\Registry::set('disableView', true);
    }

    /**
     * 设置post的数据
     * @param array $datas
     */
    public function setPostDatas(array $datas)
    {
        \Yaf\Registry::set('postDatas', $datas);
    }
}
