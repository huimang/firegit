<?php
/**
 *
 * @author: ronnie
 * @since: 2017/1/17 10:54
 * @copyright: 2017@firegit.com
 * @filesource: Mask.php
 */
namespace huimang\encrypt;

class Mask
{
    private $mask = 's234^&9da7d9b2f**KLD0@';

    /**
     * 初始化配置
     * @param $conf
     * @return mixed
     */
    public function init($conf = array())
    {
        if (isset($conf['mask'])) {
            $this->mask = $conf['mask'];
        }
    }

    /**
     * 加密
     * @param string $data
     * @return string
     */
    public function encrypt(string $data)
    {
        return bin2hex($this->doMask($data));
    }

    /**
     * 解密
     * @param string $data
     * @return string
     */
    public function decrypt(string $data)
    {
        return $this->doMask(hex2bin($data));
    }

    /**
     * 数据掩码
     * @param $data
     * @return  掩码
     */
    private function doMask($data)
    {
        $dataMd5 = md5($this->mask, true);
        $len = strlen($data);

        $result = '';

        $i = 0;
        while ($i < $len) {
            $j = 0;
            while ($i < $len && $j < 16) {
                $result .= $data[$i] ^ $dataMd5[$j];

                $i++;
                $j++;
            }
        }
        return $result;
    }
}
