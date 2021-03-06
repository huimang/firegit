<?php
/**
 *
 * @author: ronnie
 * @since: 2017/1/12 23:26
 * @copyright: 2017@firegit.com
 * @filesource: DbImpl.php
 */

namespace huimang\db;

use huimang\Exception;

class DbImpl
{
    private $conf = [];
    /**
     * @var \PDO
     */
    private $pdo;

    /**
     * @var string 库名
     */
    private $db;
    /**
     * @var string 表名
     */
    private $tb;

    /**
     * @var array 获取的数据列
     */
    private $fields = [];
    /**
     * @var array 查询条件
     */
    private $causes = [];

    /**
     * @var array 绑定的参数
     */
    private $params = [];

    /**
     * @var array 保存的语句
     */
    private $saves = [];

    /**
     * @var array 保存的数据
     */
    private $datas = [];

    /**
     * @var array groupBy
     */
    private $groups = [];
    /**
     * @var array sortBy
     */
    private $orders = [];
    /**
     * @var string limit
     */
    private $limits;

    /**
     * 当发生重复时如何保留数据
     * @var array
     */
    private $duplicates = null;

    /**
     * @var int 最后插入的ID
     */
    private $lastInsertId;

    /**
     * @var bool 是否清空
     */
    private $_reset = true;

    /**
     * 初始化db
     * @param string $db
     * @param array $conf
     */
    public function __construct(string $db, array $conf = [])
    {
        $this->db = $db;
        $this->conf = $conf;
    }

    /**
     * 检查是否设置了table
     */
    private function checkTable()
    {
        if (!$this->tb) {
            throw new Exception('db.tableIsEmpty');
        }
    }

    /**
     * 获取链接
     * @param bool $write 是否为写操作
     * @throws Exception
     * @return \PDO
     */
    private function getConn($write = true)
    {
        if ($this->pdo) {
            return $this->pdo;
        }

        $driver = $this->pdo['driver'] ?? 'mysql';
        $dsn = '';
        switch ($driver) {
            case 'mysql':
                $dsn = sprintf(
                    'mysql:dbname=%s;host=%s;port=%s;charset=%s',
                    $this->db,
                    $this->conf['host'] ?? '127.0.0.1',
                    $this->pdo['port'] ?? 3306,
                    $this->pdo['charset'] ?? 'utf8'
                );
                break;
            default:
                throw new Exception('db.driverNotSupported driver:' . $driver);
        }

        return $this->pdo = new \PDO($dsn, $this->conf['user'], $this->conf['pass']);
    }

    /**
     * 设置查询条件，$where用=，而这里建议用非等于以外的表达式
     * @param $key
     * @param $cause 如">"、"<="等
     * @param $value
     * @return $this
     * @throws Exception
     */
    public function whereCause($key, $cause, $value)
    {
        if ($cause == 'between') {
            if (!is_array($value) || count($value) != 2) {
                throw new Exception('db.betwwenMustSupplyArrayWith2Elements');
            }
            $this->causes[] = "(`{$key}` between ? AND ?)";
            $this->params[] = $value[0];
            $this->params[] = $value[1];
            return $this;
        }
        $this->causes[] = "(`$key`{$cause}?)";
        $this->params[] = $value;
        return $this;
    }

    /**
     * 设置是否清空
     * @param $reset
     * @return $this
     */
    public function setReset($reset)
    {
        $this->_reset = $reset;
        return $this;
    }

    /**
     * 重置查询项
     */
    private function reset()
    {
        if (!$this->_reset) {
            return;
        }
        $this->tb = null;
        $this->fields = [];
        $this->causes = [];
        $this->params = [];
        $this->groups = [];
        $this->orders = [];
        $this->limits = null;
        $this->saves = [];
        $this->datas = [];
        $this->duplicates = null;
        $this->_reset = true;
    }

    /**
     * 设置table
     * @param string $table
     * @return $this
     */
    public function table(string $table)
    {
        $this->tb = $table;
        return $this;
    }

    /**
     * 设置获取的数据列
     * @param \string[] ...$fields
     * @return $this
     */
    public function field(string ... $fields)
    {
        foreach ($fields as $field) {
            if (strpos($field, '(') === false) {
                $this->fields[] = preg_replace('#^([^\s\'\$\s\-]+)#', '`\1`', $field);
            } elseif (strpos($field, ',') === false) {
                $this->fields[] = preg_replace('#\(([a-zA-Z][^\)\(\'\$\s\-]*)\)#', '(`\1`)', $field);
            } else {
                // 对于包含,的，考虑其复杂性放弃对数据列的处理
                $this->fields[] = $field;
            }
        }
        return $this;
    }

    /**
     * 设置查询条件
     * @param string|array $where
     * @param array $args
     * @return $this
     * @throws Exception
     */
    public function where($where, array $args = [])
    {
        if (is_string($where)) {
            $this->causes[] = '(' . $this->packWhere($where) . ')';
            foreach ($args as $arg) {
                $this->params[] = $arg;
            }
        } elseif (is_array($where)) {
            foreach ($where as $k => $v) {
                if (is_int($k)) {
                    $this->causes[] = '(' . $this->packWhere($v) . ')';
                } else {
                    $this->causes[] = "(`$k`=?)";
                    $this->params[] = $v;
                }
            }
        } else {
            throw new Exception('db.illegalWhere');
        }
        return $this;
    }

    /**
     * 封装where条件，主要是将字段名称变成`xxxx`的形式
     * @param string $where
     * @return string
     */
    private function packWhere($where)
    {
        $where = preg_replace('#\b([a-zA-Z][a-zA-Z0-9\_\-]+)(\s*)(\=|\+|\-|\*|\/|>|<|&|\||\^|~|\sis|\sbetween\s|\sor\s|\sand\s)#im',
            '`\1`\2\3', $where);
        return $where;
    }

    /**
     * 设置当发生主键或者索引重复时要更改的数据
     * @param array $datas 如果设置的值不变，只需要将value指定为对应的字段名，否则，key设置为字段名，值为要修改的值，请注意，值不会再做sql注入检测，直接作为真实的值
     * @example
     * ->onDuplicate(['foo', 'bar' => (bar+1)])
     * 最后的sql类似：
     * ON DUPLICATE KEY UPDATE `foo`=values(`foo`),`bar`=(`bar`+1)
     * @return $this
     */
    public function onDuplicate(array $datas)
    {
        foreach ($datas as $key => $value) {
            if (is_int($key)) {
                $_key = "`{$value}`";
                $this->duplicates[$_key] = "$_key=VALUES({$_key})";
            } else {
                $this->duplicates["`{$key}`"] = $this->packWhere($value);
            }
        }
        return $this;
    }

    /**
     * in查询
     * @param $key
     * @param array $list
     * @return $this
     */
    public function in($key, array $list)
    {
        $num = count($list);
        if ($num < 1) {
            return $this;
        }
        $args = array_fill(0, $num, '?');
        return $this->where("`$key` in(" . implode(',', $args) . ")", $list);
    }

    /**
     * groupBy操作
     * @param \string[] ...$keys
     * @return $this
     */
    public function group(string ... $keys)
    {
        foreach ($keys as $key) {
            $this->groups[] = "`{$key}`";
        }
        return $this;
    }

    /**
     * @param string $key
     * @param bool $desc
     * @return $this
     */
    public function order(string $key, $desc = true)
    {
        $this->orders[] = "`$key` " . ($desc ? 'desc' : 'asc');
        return $this;
    }

    /**
     * 保存数据
     * @param array $arr
     * @param int $mode 0：单行数据
     * @return $this
     * @throws Exception
     */
    public function save(array $arr, int $mode = 0)
    {
        foreach ($arr as $key => $value) {
            $this->saves[] = "`{$key}`";
            if (is_array($value)) {
                if (!isset($value['exp'])) {
                    throw new Exception('db.illegalValue');
                }
                $this->datas[] = $value['exp'];
            } else {
                $this->datas[] = $value;
            }
        }
        return $this;
    }

    /**
     * 设置获取范围
     * @param $size
     * @param int $from
     * @return $this
     */
    public function limit($size, $from = 0)
    {
        $this->limits = "{$from}, {$size}";
        return $this;
    }


    /**
     * 执行sql
     * @param string $sql
     * @param array $args
     * @return array|int 如果是写操作，返回影响行数；读操作返回数组
     * @throws Exception
     */
    public function execute(string $sql, array $args = [])
    {
        error_log("sql:{$sql};args:" . var_export($args, true));
        // 获取sql的类型
        list($action) = explode(' ', $sql, 2);
        $action = strtolower($action);
        $write = false;
        switch ($action) {
            case 'select':
                // fallthrough
            case 'desc':
                // fallthrough
            case 'show':
                // TODO
                break;
            case 'delete':
                // fallthrough
            case 'update':
                // fallthrough
            case 'insert':
                $write = true;
                break;
        }

        $pdo = $this->getConn($write);
        $sth = $pdo->prepare($sql);
        if ($sth->execute($args) === false) {
            throw Exception::newEx('db.error', ['sql' => $sth->queryString, 'err' => $sth->errorInfo()[2]]);
        }

        $row = $sth->rowCount();
        if ($write) {
            if ($action == 'insert') {
                $this->lastInsertId = $pdo->lastInsertId();
            }
            $this->reset();
            return $row;
        }

        $ret = [];
        while (($row = $sth->fetch(\PDO::FETCH_ASSOC)) !== false) {
            $ret[] = $row;
        }
        $this->reset();
        return $ret;
    }


    /**
     * 获取fields
     * @param string $tb 表名，为null则使用->table()方法设置的表名
     * @return array
     */
    public function getFields($tb = null)
    {
        if ($tb === null) {
            $this->checkTable();
            $tb = $this->tb;
        }
        $fields = $this->execute("SHOW FULL FIELDS FROM `{$tb}`");
        return array_column($fields, null, 'Field');
    }

    /**
     * 获取该库的表
     * @return array
     */
    public function getTables()
    {
        $key = "Tables_in_{$this->db}";
        $ret = $this->execute("SHOW TABLES");
        return array_column($ret, $key);
    }

    /**
     * 获取索引
     * @return array
     */
    public function getIndexs()
    {
        $this->checkTable();
        $rows = $this->execute("SHOW INDEX FROM `{$this->tb}`");
        $indexs = [];
        foreach ($rows as $row) {
            $name = $row['Key_name'];
            if (isset($indexs[$name])) {
                $indexs[$name]['Fields'][] = $row['Column_name'];
            } else {
                $indexs[$name] = $row;
                $indexs[$name]['Fields'] = [$row['Column_name']];
                unset($indexs[$name]['Column_name']);
            }
        }
        return $indexs;
    }

    /**
     * 获取最后插入的id
     * @return int
     */
    public function getLastInsertId()
    {
        return $this->lastInsertId;
    }

    /**
     * 获取数据
     * @return array
     * @throws Exception
     */
    public function get()
    {
        $this->checkTable();
        if (empty($this->causes)) {
            throw new Exception('db.whereIsRequired');
        }
        $sql = "SELECT " . (empty($this->fields) ? '*' : implode(',', $this->fields)) . " FROM `{$this->tb}`";
        $sql .= " WHERE " . implode(' AND ', $this->causes);
        if (!empty($this->groups)) {
            $sql .= " GROUP BY " . implode(',', $this->groups);
        }
        if (!empty($this->orders)) {
            $sql .= " ORDER BY " . implode(',', $this->orders);
        }
        if (!empty($this->limits)) {
            $sql .= " LIMIT {$this->limits}";
        }
        return $this->execute($sql, $this->params);
    }

    /**
     * 获取一条记录，没有获取到就返回null
     * @return mixed|null
     */
    public function getOne()
    {
        if (!$this->limits) {
            $this->limit(1);
        }
        $ret = $this->get();
        return $ret ? array_shift($ret) : null;
    }

    /**
     * 获取总数
     * @return int
     * @throws Exception
     */
    public function getCount()
    {
        $this->checkTable();
        if (empty($this->causes)) {
            throw new Exception('db.whereIsRequired');
        }
        $sql = "SELECT COUNT(0) AS COUNT FROM `{$this->tb}`";
        $sql .= " WHERE " . implode(' AND ', $this->causes);
        if (!empty($this->groups)) {
            $sql .= " GROUP BY " . implode(',', $this->groups);
        }
        $ret = $this->execute($sql, $this->params);
        return isset($ret[0]['COUNT']) ? intval($ret[0]['COUNT']) : 0;
    }

    /**
     * 获取是否存在相应的数据
     * @return bool
     * @throws Exception
     */
    public function getExist()
    {
        $this->checkTable();
        if (empty($this->causes)) {
            throw new Exception('db.whereIsRequired');
        }
        $sql = "SELECT 1 FROM `{$this->tb}`";
        $sql .= " WHERE " . implode(' AND ', $this->causes);
        if (!empty($this->groups)) {
            $sql .= " GROUP BY " . implode(',', $this->groups);
        }
        $ret = $this->execute($sql, $this->params);
        return !empty($ret);
    }

    /**
     * 插入
     * @param int $mode 默认为0 0：智能判断 1：正常插入 2：遇到冲突忽略 3：遇到冲突更新
     * @return int
     */
    public function insert(int $mode = 0)
    {
        // 如果设置过duplcates，则即使mode
        if ($mode == 0) {
            if ($this->duplicates !== null) {
                $mode = 2;
            } else {
                $mode = 1;
            }
        }
        $this->checkTable();

        $sql = "INSERT " . ($mode == 1 ? 'IGNORE ' : '') . "INTO `{$this->tb}`(" . implode(',', $this->saves) . ")";
        $sql .= " VALUES(" . implode(',', array_fill(0, count($this->saves), '?')) . ")";
        if ($mode == 2) {
            if ($this->duplicates === null) {
                $updates = implode(
                    ',',
                    array_map(function ($key) {
                        return "{$key}=VALUES({$key})";
                    }, $this->saves));
            } elseif (!empty($this->duplicates)) {
                $updates = implode(',', $this->duplicates);
            }
            if ($updates) {
                $sql .= ' ON DUPLICATE KEY UPDATE ' . $updates;
            }
        }

        return $this->execute($sql, $this->datas);
    }

    /**
     * 更新
     * @return int
     * @throws Exception
     */
    public function update()
    {
        $this->checkTable();

        if (empty($this->causes)) {
            throw new Exception('db.whereIsRequired');
        }

        $sql = "UPDATE `{$this->tb}` SET " . implode(',', array_map(
                function ($item) {
                    return $item . '=?';
                },
                $this->saves
            ));
        $sql .= " WHERE " . implode(' AND ', $this->causes);
        return $this->execute($sql, array_merge($this->datas, $this->params));
    }

    /**
     * 删除
     * @return int
     * @throws Exception
     */
    public function delete()
    {
        $this->checkTable();

        if (empty($this->causes)) {
            throw new Exception('db.whereIsRequired');
        }

        $sql = "DELETE FROM `{$this->tb}`";
        $sql .= " WHERE " . implode(' AND ', $this->causes);
        return $this->execute($sql, $this->params);
    }


    public function __destruct()
    {
        // TODO
    }
}
