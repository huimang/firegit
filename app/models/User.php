<?php

use huimang\Exception;
use huimang\db\Db;

/**
 *
 * @author: ronnie
 * @since: 2017/1/13 21:58
 * @copyright: 2017@firegit.com
 * @filesource: User.php
 */
class UserModel
{
    const SALT_MASK = 'sdfw3@23145Ds&*';
    const USERNAME_RULE = '#^[a-z][a-zA-Z0-9\.\_\-]{5,19}$#';
    // 密码禁止的规则
    const PWD_RULE = '#^\w{6,12}$#';

    const STATUS_DELETE = -1;
    const STATUS_FORBIDDEN = 0;
    const STATUS_NORMAL = 1;

    const SEX_UNKWON = 0;
    const SEX_MALE = 1;
    const SEX_FEMALE = 2;

    const ROLE_NORMAL = 1;
    const ROLE_ADMIN = 2;

    /**
     * 添加用户
     * @param string $username
     * @param string $password
     * @param string $email
     * @param string $realname
     * @param array $data
     * @return int
     * @throws Exception
     */
    public function addUser(string $username, string $password, string $email, string $realname, array $data = array())
    {
        $email = trim($email);
        $realname = trim($realname);
        $username = trim($username);

        // 检查用户名
        if (!$this->checkUsername($username)) {
            throw new Exception('user.usernameIllegal');
        }

        // 检查邮箱
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('user.emailIllegal');
        }

        // 检查密码
        $this->checkPassword($password);

        $body = array_intersect_key($data, array_flip(['sex', 'phone', 'role', 'status']));
        $body['realname'] = $realname;

        $this->tidyUserData($body);


        if (!isset($body['sex'])) {
            $body['sex'] = self::SEX_UNKWON;
        }

        if (!isset($body['status'])) {
            $body['status'] = self::STATUS_NORMAL;
        }

        if (!isset($body['role'])) {
            $body['role'] = self::ROLE_NORMAL;
        }

        $body['create_time'] = time();
        $body['salt'] = $this->getSalt();
        $body['password'] = $this->packPassword($password, $body['salt']);
        $body['username'] = $username;
        $body['email'] = $email;
        $body['realname'] = $realname;

        $db = Db::get();

        // 检查用户名和email是否注册过
        $exist = $db->table('user')
            ->where('username=? or email = ?', [$username, $email])
            ->getExist();
        if ($exist) {
            throw new Exception('user.userOrEmailExisted');
        }

        $db->table('user')->save($body)->insert();
        return $db->getLastInsertId();
    }

    /**
     * 清理用户数据
     * @param $body
     * @throws Exception
     */
    private function tidyUserData(&$body)
    {
        if (isset($body['realname'])) {
            // 检查真实姓名
            $len = mb_strlen($body['realname']);
            if ($len < 2 || $len > 36) {
                throw new Exception('user.realnameLengthIllegal');
            }
        }

        if (isset($body['sex'])) {
            if (!in_array($body['sex'], [self::SEX_UNKWON, self::SEX_MALE, self::SEX_FEMALE])) {
                $body['sex'] = self::SEX_UNKWON;
            }
        }
        if (isset($body['role'])) {
            if (!in_array($body['role'], [self::ROLE_NORMAL, self::ROLE_ADMIN])) {
                $body['role'] = self::ROLE_NORMAL;
            }
        }
        if (isset($body['phone'])) {
            if (!empty($body['phone']) && !preg_match('#1[3-9][0-9]{9}#', $body['phone'])) {
                throw new Exception('user.phoneIllegal');
            }
        }
        if (isset($body['status'])) {
            if (!in_array($body['status'], [self::STATUS_DELETE, self::STATUS_FORBIDDEN, self::STATUS_NORMAL])) {
                $body['status'] = self::STATUS_NORMAL;
            }
        }
    }

    /**
     * 获取用户
     * @param $userId
     * @return array|null
     */
    public function getUser($userId)
    {
        return Db::get()
            ->field('user_id', 'username', 'email', 'realname', 'sex', 'phone', 'role', 'status', 'create_time')
            ->table('user')
            ->where(['user_id' => intval($userId)])
            ->whereCause('status', '!=', self::STATUS_DELETE)
            ->getOne();
    }

    /**
     * 获取用户总数
     * @return int
     */
    public function getUserNum()
    {
        return Db::get()
            ->table('user')
            ->whereCause('status', '!=', self::STATUS_DELETE)
            ->getCount();
    }

    /**
     * 批量获取用户
     * @param $userIds
     * @return array
     */
    public function getUsers($userIds)
    {
        return Db::get()
            ->table('user')
            ->field('user_id', 'username', 'email', 'realname', 'role', 'status')
            ->whereCause('status', '!=', self::STATUS_DELETE)
            ->in('user_id', $userIds)
            ->get();
    }

    /**
     * 获取所有用户
     * @param int $role
     * @return array
     * [
     *  [
     *   'user_id',
     *   'realname'
     *  ]
     * ]
     */
    public function getAllUsers(int $role = 0)
    {
        $db = Db::get()
            ->table('user')
            ->field('user_id', 'realname')
            ->whereCause('status', '!=', self::STATUS_DELETE);
        if ($role > 0) {
            $db->where(['role' => $role]);
        }
        return $db->get();
    }


    /**
     * 分页获取用户
     * @param int $page 从0开始计算
     * @param int $num
     * @return array
     */
    public function pagedGetUsers(int $page, int $num)
    {
        $db = Db::get();
        $count = $db->table('user')
            ->setReset(false)
            ->whereCause('status', '!=', self::STATUS_DELETE)
            ->getCount();
        if ($count > 0 && ceil($count / $num) > $page) {
            $rows = $db
                ->field('user_id', 'username', 'email', 'realname', 'role', 'status', 'create_time')
                ->order('user_id')
                ->limit($num, $page * $num)
                ->get();
            return [
                'total' => $count,
                'list' => $rows,
            ];
        }
        return [
            'total' => $count,
            'list' => [],
        ];
    }

    /**
     * 更新用户信息
     * @param int $userId
     * @param array $info
     * @throws Exception
     */
    public function updateUser(int $userId, array $info)
    {
        $body = array_intersect_key(
            $info,
            array_flip(['username', 'email', 'realname', 'sex', 'phone', 'role', 'status'])
        );
        if (!$body) {
            return;
        }
        $db = Db::get();

        // 获取用户
        $user = $this->getUser($userId);
        if (!$user) {
            throw Exception::newEx('user.userNotFound');
        }

        // 检查是否修改过帐号
        if (isset($body['username']) && $body['username'] == $user['username']) {
            unset($body['username']);
        }

        if (isset($body['username'])) {
            $body['username'] = trim($body['username']);
            if (!$this->checkUsername($body['username'])) {
                throw new Exception('user.usernameIllegal');
            }
            // 检查用户名是否存在
            $exist = $db->table('user')
                ->whereCause('user_id', '!=', $userId)
                ->where(['username' => $body['username']])
                ->getExist();
            if ($exist) {
                throw new Exception('user.usernameExisted');
            }
        }

        // 检查是否修改过email
        if (isset($body['email']) && $body['email'] == $user['email']) {
            unset($body['email']);
        }

        if (isset($body['email'])) {
            $body['email'] = trim($body['email']);
            if (!filter_var($body['email'], FILTER_VALIDATE_EMAIL)) {
                throw new Exception('user.emailIllegal');
            }
            // 检查email是否存在
            $exist = $db->table('user')
                ->whereCause('user_id', '!=', $userId)
                ->where(['email' => $body['email']])
                ->getExist();
            if ($exist) {
                throw new Exception('user.emailExisted');
            }
        }

        if (empty($body)) {
            return;
        }

        $this->tidyUserData($body);

        $db->table('user')
            ->where(['user_id' => $userId])
            ->save($body)
            ->update();
    }

    /**
     * 修改密码
     * @param int $userId
     * @param $oldPassword
     * @param $newPassword
     * @throws Exception user.pwdNotChanged 密码没有做任何修改
     * @throws Exception user.oldPwdWrong 旧密码不正确
     * @throws Exception user.userNotFound 用户不存在
     */
    public function updatePassword(int $userId, $oldPassword, $newPassword)
    {
        if ($oldPassword == $newPassword) {
            throw new Exception('user.pwdNotChanged');
        }

        // 检查新密码格式
        $this->checkPassword($newPassword);

        $db = Db::get();
        $user = $db->table('user')
            ->field('salt', 'password')
            ->where(['user_id' => $userId])
            ->whereCause('status', '!=', self::STATUS_DELETE)
            ->getOne();
        if (!$user) {
            throw new Exception('user.userNotFound');
        }
        if ($this->packPassword($oldPassword, $user['salt']) != $user['password']) {
            throw new Exception('user.oldPwdWrong');
        }
        $salt = $this->getSalt();
        $newPwd = $this->packPassword($newPassword, $salt);
        $db->table('user')
            ->where(['user_id' => $userId])
            ->save([
                'salt' => $salt,
                'password' => $newPwd,
            ])
            ->update();
    }

    /**
     * 登录帐号
     * @param $username
     * @param $password
     * @return int
     * -1:帐号或密码为空
     * -2:帐号或密码错误
     * -3:禁止登录
     * >0:用户ID
     */
    public function login($username, $password)
    {
        if (!$username || !$password) {
            return -1;
        }
        $db = Db::get();
        $db = $db->table('user')
            ->field('user_id', 'salt', 'password', 'status');
        // 根据是否包含@判断是否为邮箱
        if (strpos($username, '@') === false) {
            $db->where(['username' => $username]);
        } else {
            $db->where(['email' => $username]);
        }
        $user = $db->whereCause('status', '!=', self::STATUS_DELETE)
            ->getOne();
        if (!$user) {
            return -2;
        }

        if ($user['status'] == self::STATUS_FORBIDDEN) {
            return -3;
        }

        if ($this->packPassword($password, $user['salt']) != $user['password']) {
            return -2;
        }
        return $user['user_id'];
    }

    /**
     * 删除用户
     * @param int $userId
     */
    public function delUser(int $userId)
    {
        Db::get()
            ->table('user')
            ->where(['user_id' => $userId])
            ->save(['status' => self::STATUS_DELETE])
            ->update();
    }

    /**
     * @param $password
     * @throws Exception user.pwdLengthError 密码长度不正确
     * @throws Exception user.pwdFormatError 密码格式不正确
     */
    private function checkPassword($password)
    {
        $len = strlen($password);
        if ($len < 6 || $len > 12) {
            throw new Exception('user.pwdLengthError');
        }
        if (!preg_match(self::PWD_RULE, $password)) {
            throw new Exception('user.pwdFormatError');
        }
    }


    /**
     * 检查用户名是否合法
     * @param string $username
     * @return bool
     */
    private function checkUsername(string $username)
    {
        return preg_match(self::USERNAME_RULE, $username);
    }

    /**
     * 获取随机的盐
     */
    private function getSalt()
    {
        return md5(microtime() . rand(10000, 99999));
    }

    /**
     * 对密码进行封装
     * @param string $origin
     * @param string $salt
     * @return string
     */
    private function packPassword(string $origin, string $salt)
    {
        return sha1(md5($origin . self::SALT_MASK . $salt));
    }
}
