<?php

/**
 *
 * @author: ronnie
 * @since: 2017/1/22 11:15
 * @copyright: 2017@firegit.com
 * @filesource: My.php
 */
class MyController extends BaseController
{
    protected $roles = [
        1 => '普通',
        2 => '管理员',
    ];

    public function init()
    {
        parent::init();

        $model = new UserModel();
        $user = $model->getUser($this->userId);
        $this->_view->user = $user;
        if ($this->_layout) {
            $this->_view->userNav = $this->_layout->userNav = $this->_request->action;
            $this->_layout->mainNav = 'my';
        }
    }

    public function accountAction()
    {
        $this->_layout->title = '我的>帐号';
    }

    public function _accountAction()
    {
        $username = $_POST['username'];
        $email = $_POST['email'];
        $model = new UserModel();
        $model->updateUser($this->userId, [
            'email' => $_POST['email'],
            'username' => $_POST['username'],
        ]);
    }


    public function passwordAction()
    {
        $this->_layout->title = '我的>密码';
        $this->_layout->userNav = 'password';
    }

    public function _passwordAction()
    {
        $oldPwd = $_POST['oldpwd'];
        $newPwd = $_POST['newpwd'];
        $model = new UserModel();
        $model->updatePassword($this->userId, $oldPwd, $newPwd);

        // 注销登录
        setcookie('fgu', null, 0, '/');
    }
}
