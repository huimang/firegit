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

    protected $_size = 20;
    protected $_page;

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

            // 分页
            if (isset($_GET['pn'])) {
                $page = intval($_GET['pn']);
                if ($page < 0) {
                    $page = 0;
                }
            } else {
                $page = 0;
            }
            $this->_page = $page;
        }

        $this->_layout = \Yaf\Registry::get('layout');
        if ($this->_layout) {
            $this->_layout->mainNav = 'home';
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

    /**
     * 设置分页数据。数据会设置到$this->_view->pagination
     * @param int $total
     * @param string $pageUrl
     * @param int $showSize
     * @throws \huimang\Exception pageUrlMustContain{page}
     */
    public function setPagination(int $total, string $pageUrl = '', $showSize = 3)
    {

        if ($pageUrl) {
            if (strpos($pageUrl, '{page}') === false) {
                throw new \huimang\Exception('pageUrlMustContain{page}');
            }
        } else {
            $uri = $_SERVER['REQUEST_URI'];
            $pos = strpos($uri, '?');
            if ($pos === false) {
                $pageUrl = $uri . '?pn={page}';
            } else {
                parse_str(substr($uri, $pos + 1), $args);
                foreach ($args as $key => $value) {
                    if ($key == 'pn') {
                        $args[$key] = 'pn={page}';
                    } else {
                        $args[$key] = "{$key}={$value}";
                    }
                }
                $pageUrl = substr($uri, 0, $pos) . '?' . implode('&', $args);
            }
        }
        $pageSize = ceil($total / $this->_size);
        $pagination = [
            'total' => $total,
            'size' => $this->_size,
            'page' => $this->_page + 1,
            'pageSize' => $pageSize,
            'pageUrl' => $pageUrl,
        ];
        // 设置第1页的网址
        $firstUrl = preg_replace('#[\?\&][^\?\&]+\=\{page\}#', '', $pageUrl);

        // 设置需要显示哪些链接

        $pagination['first'] = $this->_page > 0 ? $firstUrl : '';
        $pagination['last'] = $this->_page < $pageSize - 1 ? str_replace('{page}', $pageSize - 1, $pageUrl) : '';

        if ($pageSize <= $showSize * 2 + 1) {
            for ($i = 0; $i < $pageSize; $i++) {
                $pagination['links'][] = [
                    'url' => $i == 0 ? $firstUrl : str_replace('{page}', $i, $pageUrl),
                    'text' => $i + 1,
                    'active' => $i == $this->_page,
                ];
            }
        } else {
            for ($i = 0; $i < $showSize; $i++) {
                $pagination['links'][] = [
                    'text' => $i + 1,
                    'url' => $i == 0 ? $firstUrl : str_replace('{page}', $i, $pageUrl),
                    'active' => $i == $this->_page,
                ];
            }
            $pagination['links'][] = [
                'text' => '...',
                'url' => '',
                'active' => false,
            ];
            for ($i = $pageSize - $showSize; $i < $showSize; $i++) {
                $pagination['links'][] = [
                    'text' => $i + 1,
                    'url' => str_replace('{page}', $i, $pageUrl),
                    'active' => $i == $this->_page,
                ];
            }
        }
        $paginationStr = $this->_view->render(APPLICATION_PATH . '/app/views/layouts/pagination.phtml', $pagination);
        $this->_view->pagination = $paginationStr;
    }
}
