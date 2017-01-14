<?php
/**
 *
 * @author: ronnie
 * @since: 2017/1/12 23:16
 * @copyright: 2017@firegit.com
 * @filesource: Exception.php
 */

namespace huimang;

class Exception extends \Exception
{
    private $extra;

    /**
     * 设置extra信息
     * @param $extra
     */
    public function setExtra($extra) {
        $this->extra = $extra;
    }

    /**
     * 获取extra信息
     * @return mixed
     */
    public function getExtra()
    {
        return $this->extra;
    }

    /**
     * 抛出异常
     * @param string $message
     * @param mixed $extra
     * @return Exception
     */
    public static function newEx(string $message, $extra = null)
    {
        error_log("exception msg:{$message};extra:".var_export($extra, true));
        $exp = new self($message);
        $exp->setExtra($extra);
        return $exp;
    }
}
