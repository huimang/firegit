<?php
/**
 *
 * @author: ronnie
 * @since: 2017/1/15 15:13
 * @copyright: 2017@firegit.com
 * @filesource: Cache.php
 */
namespace huimang\cache;

use huimang\Exception;

class Cache
{
    private static $confs;

    /**
     * 初始化缓存配置
     * @param array $confs
     */
    public static function init($confs = array())
    {
        self::$confs = $confs;
    }

    /**
     * 获取哪种频道的缓存
     * @param null $channel
     * @return ICache
     * @throws Exception cache.channelNotFound 频道未找到
     */
    public static function get($channel = null)
    {
        $confs = self::$confs;
        if ($channel === null && isset($confs['default']) && isset($confs['channel'][$confs['default']])) {
            $channel = $confs['default'];
        }
        if (!$channel || !isset($confs['channel'][$channel])) {
            throw Exception::newEx('cache.channelNotFound', ['channel' => $channel]);
        }
        $channel = $confs['channel'][$channel];
        $type = $channel['type'] ?? 'file';
        $class = __NAMESPACE__."\\impl\\".ucfirst($type);
        return new $class($channel['conf']);
    }
}
