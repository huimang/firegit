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
    }

    public function indexAction()
    {
        $from = intval($_GET['from'] ?? 0);
        $model = new UserModel();
        $users = $model->getUsers(20, $from);
        $this->_view->users = $users;
        $this->_view->roles = $this->roles;
        $this->_view->cuser = $this->user;
    }

    public function addAction()
    {

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
