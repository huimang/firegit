<?php
/**
 *
 * @author: ronnie
 * @since: 2017/1/12 23:06
 * @copyright: 2017@hunbasha.com
 * @filesource: Db.php
 */

namespace firegit\lib\db;

use firegit\lib\Exception;

class Db
{
    private static $confs = [];

    /**
     * 获取db对象
     * @param null $db
     * @throws Exception
     * @return Db
     */
    public static function get($db = null)
    {
        if ($db === null) {
            if (isset(self::$confs['default_db'])) {
                $db = self::$confs['default_db'];
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

        return new DbImpl(self::$confs['pool'][$poolName]);
    }
}
