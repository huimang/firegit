<?php
/**
 *
 * @author: ronnie
 * @since: 2017/1/12 23:06
 * @copyright: 2017@firegits.com
 * @filesource: Db.php
 */

namespace huimang\db;

use huimang\Exception;

class Db
{
    private static $confs = [];

    /**
     * 初始化
     * @param array $confs
     */
    public static function init(array $confs)
    {
        self::$confs = $confs;
    }

    /**
     * 获取db对象
     * @param null $db
     * @throws Exception
     * @return DbImpl
     */
    public static function get($db = null)
    {
        if ($db === null) {
            if (isset(self::$confs['default'])) {
                $db = self::$confs['default'];
            } else {
                throw new Exception('db.dbNameIsRequired');
            }
        }
        if (!isset(self::$confs['dbs'][$db])) {
            throw new Exception('db.dbConfNotFound db:'.$db);
        }
        $poolName = self::$confs['dbs'][$db];
        if (!isset(self::$confs['pool'][$poolName])) {
            throw new Exception('db.poolNotFound poolName:'.$poolName);
        }

        return new DbImpl($db, self::$confs['pool'][$poolName]);
    }
}
