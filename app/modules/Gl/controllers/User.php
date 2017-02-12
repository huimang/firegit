<?php

/**
 *
 * @author: ronnie
 * @since: 2017/2/9 14:21
 * @copyright: 2017@firegit.com
 * @filesource: User.php
 */
class UserController extends BaseController
{
    private $roles = [
        1 => '普通',
        2 => '管理员',
    ];

    public function init()
    {
        parent::init();
        if ($this->user['role'] != 2) {
            throw \huimang\Exception::newEx('power');
        }
        if ($this->_view) {
            $this->_view->glNav = 'user';
        }
        if ($this->_layout) {
            $this->_layout->mainNav = 'gl';
        }
    }

    public function indexAction()
    {
        $model = new UserModel();
        $users = $model->pagedGetUsers($this->_page, $this->_size);
        $this->_view->total = $users['total'];
        $this->_view->users = $users['list'];
        $this->_view->roles = $this->roles;
        $this->_view->cuser = $this->user;

        $this->setPagination($users['total']);
        $this->_layout->title = '管理>用户>列表';
    }

    public function addAction()
    {
        $this->_view->roles = $this->roles;
        $this->_layout->title = '管理>用户>添加';
    }

    public function updateAction()
    {
        $userId = array_keys($this->_request->getParams())[0];
        $model = new UserModel();
        $user = $model->getUser($userId);
        if (!$user) {
            throw \huimang\Exception::newEx('notfound');
        }

        $this->_view->roles = $this->roles;
        $this->_view->user = $user;
        $this->_layout->title = '管理>用户>更新';
    }

    public function _addAction()
    {
        $model = new UserModel();
        $userId = $model->addUser($_POST['username'], $_POST['newpwd'], $_POST['email'], $_POST['realname'], [
            'role' => intval($_POST['role']),
            'phone' => intval($_POST['phone']),
        ]);
        $this->setPostDatas(['user_id' => $userId]);
    }

    public function _updateAction()
    {
        $userId = intval($_POST['user_id']);
        if ($userId > 0) {
            $model = new UserModel();
            $info = [
                'username' => $_POST['username'],
                'email' => $_POST['email'],
                'realname' => $_POST['realname'],
                'role' => $_POST['role'],
                'phone' => $_POST['phone'],
            ];
            $model->updateUser($userId, $info);
        }
    }

    public function _deleteAction()
    {
        $userId = $_GET['user_id'];
        // 不能删除自己
        if ($userId > 0 && $userId != $this->userId) {
            $model = new UserModel();
            $model->delUser($userId);
        }
    }
}
