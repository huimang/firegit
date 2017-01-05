<?php
/**
 *
 * @author: ronnie
 * @since: 2017/1/12 23:26
 * @copyright: 2017@hunbasha.com
 * @filesource: DbImpl.php
 */

namespace firegit\lib\db;

use firegit\lib\Exception;

class DbImpl
{
    private $conf = [];
    /**
     * @var \PDO
     */
    private $conn;

    /**
     * @var string 库名
     */
    private $db;
    /**
     * @var string 表名
     */
    private $_table;

    /**
     * @var array 获取的数据列
     */
    private $_field = [];
    /**
     * @var array 查询条件
     */
    private $_where = [];

    /**
     * @var array 绑定的参数
     */
    private $_params = [];

    /**
     * @var array groupBy
     */
    private $_group = [];
    /**
     * @var array sortBy
     */
    private $_sort = [];
    /**
     * @var string limit
     */
    private $_limit;

    /**
     * @var int 最后插入的ID
     */
    private $_lastId;

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
        if (!$this->_table) {
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
        if ($this->conn) {
            return $this->conn;
        }

        $driver = $this->conn['driver'] ?? 'mysql';
        $dsn = '';
        switch ($driver) {
            case 'mysql':
                $dsn = sprintf(
                    'mysql:dbname=%s;host=%s;port=%s;charset=%s',
                    $this->db,
                    $this->conf['host'] ?? '127.0.0.1',
                    $this->conn['port'] ?? 3306,
                    $this->conn['charset'] ?? 'utf8'
                );
                break;
            default:
                throw new Exception('db.driverNotSupported driver:' . $driver);
        }

        return $this->conn = new \PDO($dsn, $this->conf['user'], $this->conf['pass']);
    }

    /**
     * 重置查询项
     */
    private function reset()
    {
        $this->_table = null;
        $this->_field = [];
        $this->_where = [];
        $this->_params = [];
        $this->_group = [];
        $this->_sort = [];
        $this->_limit = null;
        $this->_lastId = null;
    }

    /**
     * 设置table
     * @param string $table
     * @return $this
     */
    public function table(string $table)
    {
        $this->_table = $table;
        return $this;
    }

    /**
     * 设置获取的数据列
     * @param \string[] ...$fields
     * @return $this
     */
    public function field(string ... $fields)
    {
        $this->_field = '`' . $fields . '`';
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
            $this->_where[] = '(' . $where . ')';
            foreach ($args as $arg) {
                $this->_params[] = $arg;
            }
        } elseif (is_array($where)) {
            foreach ($where as $k => $v) {
                if (is_int($k)) {
                    $this->_where[] = $where;
                } elseif (is_array($v)) {
                    if (isset($v['exp'])) {
                        $this->_where[] = "(`$k`={$v['exp']})";
                    }
                } else {
                    $this->_where[] = "(`$k`=?)";
                    $this->_params[] = $v;
                }
            }
        } else {
            throw new Exception('db.illegalWhere');
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
        return $this->where("`$key` in(" . explode(',', $args) . ")", $args);
    }

    /**
     * groupBy操作
     * @param \string[] ...$keys
     * @return $this
     */
    public function group(string ... $keys)
    {
        foreach ($keys as $key) {
            $this->_group[] = "`{$keys}`";
        }
        return $this;
    }

    /**
     * @param string $key
     * @param bool $desc
     * @return $this
     */
    public function sort(string $key, $desc = true)
    {
        $this->_sort[] = "`$key` " . ($desc ? 'desc' : 'asc');
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
        $this->_limit = "{$from}, {$size}";
        return $this;
    }

    public function listDbs()
    {

    }

    /**
     * 执行sql
     * @param string $sql
     * @param array $args
     * @return int 影响行数
     */
    public function execute(string $sql, array $args = [])
    {
        var_dump(func_get_args());
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
        $sth->execute($args);

        $row = $sth->rowCount();
        if ($write) {
            if ($action == 'insert') {
                $this->_lastId = $pdo->lastInsertId();
            }
            $this->reset();
            return $row;
        }

        while (($row = $sth->fetch(\PDO::FETCH_ASSOC)) !== false) {
            yield $row;
        }
        $this->reset();
        return $row;
    }


    /**
     * 获取fields
     */
    public function getFields()
    {
        $this->checkTable();
        $fields = [];
        foreach ($this->execute("SHOW FULL FIELDS FROM `{$this->_table}`") as $row) {
            $fields[$row['Field']] = $row;
        }
        return $fields;
    }

    /**
     * 获取索引
     */
    public function getIndexs()
    {
        $this->checkTable();
        $indexs = [];
        foreach ($this->execute("SHOW INDEX FROM `{$this->_table}`") as $row) {
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
     * 获取数据
     */
    public function get()
    {
        $this->checkTable();
        if (empty($this->_where)) {
            throw new Exception('db.whereIsEmpty');
        }
        $sql = "SELECT " . (empty($this->_field) ? '*' : implode(',', $this->_field)) . " FROM `{$this->_table}`";
        $sql .= " WHERE " . implode(' AND ', $this->_where);
        if (!empty($this->_group)) {
            $sql .= " GROUP BY " . implode(',', $this->_group);
        }
        if (!empty($this->_sort)) {
            $sql .= " SORT " . implode(',', $this->_sort);
        }
        if (!empty($this->_limit)) {
            $sql .= " LIMIT {$this->_limit}";
        }
        $ret = [];
        foreach ($this->execute($sql, $this->_params) as $row) {
            $ret[] = $row;
        }
        return $ret;
    }

    public function __destruct()
    {
        // TODO
    }
}
