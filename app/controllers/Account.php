<?php

/**
 *
 * @author: ronnie
 * @since: 2017/1/14 22:05
 * @copyright: 2017@firegit.com
 * @filesource: Account.php
 */
class AccountController extends \Yaf\Controller_Abstract
{
    public function authAction()
    {
        \Yaf\Dispatcher::getInstance()->disableView();

        var_dump($_GET);
        var_dump($_SERVER);
        die();
    }
}
