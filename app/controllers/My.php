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
    public function init()
    {
        parent::init();
        $model = new UserModel();
        $user = $model->getUser($this->userId);
        $this->_view->user = $user;
    }

    public function accountAction()
    {

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
}
