<?php
/**
 *
 * @author: ronnie
 * @since: 2017/1/15 15:17
 * @copyright: 2017@firegit.com
 * @filesource: File.php
 */
namespace huimang\cache\impl;

use huimang\cache\ICache;

class File implements ICache
{
    private $root;
    private $compressLength;
    private $maxLength;

    /**
     * File constructor.
     * @param array $conf
     */
    public function __construct($conf = [])
    {
        $this->root = $conf['root'];
        $this->compressLength = $conf['compress_length'] ?? 1024;
        $this->maxLength = $conf['max_length'] ?? 1024 * 1024;
    }

    /**
     * 设置缓存
     * @param string $key 键
     * @param mixed $value 值
     * @param int $expire 过期时间，单位：s
     * @return mixed
     */
    public function set($key, $value, $expire = 3600)
    {
        $file = $this->getKeyFile($key, true);
        $saveValue = serialize($value);
        $zipFlag = 0;
        if (strlen($saveValue) > $this->compressLength) {
            $saveValue = gzcompress($saveValue);
            $zipFlag = 1;
        }

        $content = pack('NC', time() + $expire, $zipFlag).$saveValue;
        file_put_contents($file, $content);
    }

    /**
     * 获取key对应的文件
     * @param string $key
     * @param bool $autoCreate 是否自动创建目录
     * @return string
     */
    private function getKeyFile($key, $autoCreate = false)
    {
        $key = md5($key);
        $path = sprintf('%s/%s/%s/%s', $this->root, substr($key, 0, 2), substr($key, 2, 2), $key);
        if ($autoCreate && !is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }
        return $path;
    }

    /**
     * 获取缓存
     * @param string $key
     * @return mixed|null 没有获取到时返回null
     */
    public function get($key)
    {
        $file = $this->getKeyFile($key);
        if (!is_readable($file)) {
            return null;
        }
        $fh = fopen($file, 'r');
        // 读取4个字节，看是否过时
        $expire = fread($fh, 4);
        $expire = unpack('N', $expire)[1];
        if (time() > $expire) {
            fclose($fh);
            unlink($file);
            return null;
        }
        // 读取1个字节，看是否压缩
        $zipFlag = fread($fh, 1);
        $zipFlag = unpack('C', $zipFlag)[1];
        // 读取1M
        $content = fread($fh, 1024 * 1024);
        if ($zipFlag) {
            $content = gzuncompress($content);
        }
        $content = unserialize($content);
        fclose($fh);

        return $content;
    }

    /**
     * 删除缓存
     * @param string $key
     * @return mixed
     */
    public function del($key)
    {
        $file = $this->getKeyFile($key);
        if (is_file($file)) {
            unlink($file);
        }
    }
}
