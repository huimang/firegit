<?php
/**
 *
 * @author: ronnie
 * @since: 2017/1/15 15:14
 * @copyright: 2017@firegit.com
 * @filesource: ICache.php
 */

namespace huimang\cache;


interface ICache
{
    /**
     * 设置缓存
     * @param string $key 键
     * @param mixed $value 值
     * @param int $expire 过期时间，单位：s，默认：3600s
     * @return mixed
     */
    public function set($key, $value, $expire = 3600);

    /**
     * 获取缓存
     * @param string $key
     * @return mixed|null 没有获取到时返回null
     */
    public function get($key);

    /**
     * 删除缓存
     * @param string $key
     * @return mixed
     */
    public function del($key);
}