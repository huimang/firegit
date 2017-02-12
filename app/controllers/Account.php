<?php
use huimang\Exception;

/**
 *
 * @author: ronnie
 * @since: 2017/1/14 22:05
 * @copyright: 2017@firegit.com
 * @filesource: Account.php
 */
class AccountController extends BaseController
{
    protected $needLogin = false;
    /**
     * 登录页
     */
    public function loginAction()
    {
        $this->_layout->mainNav = '';
    }

    /**
     * 登录
     */
    public function _loginAction()
    {
        $username = $_POST['username'];
        $password = $_POST['password'];
        $userModel = new UserModel();

        $userId = $userModel->login($username, $password);
        switch (true) {
            case $userId == -1:
                throw new Exception('user.unameOrPwdEmpty');
                break;
            case $userId == -2:
                throw new Exception('user.unameOrPwdWrong');
                break;
            case $userId == -3:
                throw new Exception('user.userForbidden');
                break;
            case $userId > 0:
                $user = $userModel->getUser($userId);
                if (!$user) {
                    throw new Exception('user.notFound');
                }
                $expire = time() + 3600 * 24;
                $rawData = "{$userId},{$user['username']},{$user['role']},{$user['realname']},{$expire}";
                $md5 = md5($rawData.'hell0World');
                $rawData .= ','.$md5;
                $mask = new \huimang\encrypt\Mask();
                $encryptedData = $mask->encrypt($rawData);
                // cookie 保留1天
                setcookie('fgu', $encryptedData, time() + 3600 * 24, '/');
                break;
        }
    }

    public function logoutAction()
    {
        $this->disableView();
        setcookie('fgu', null, 0, '/');
        $this->redirect('/');
    }
}
