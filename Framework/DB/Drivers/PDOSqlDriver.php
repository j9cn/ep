<?php
/**
 * Created by PhpStorm.
 * User: JH
 * Date: 2017/11/27
 * Time: 18:43
 */
declare(strict_types=1);

namespace EP\DB\Drivers;

use EP\Exception\EE;
use EP\Exception\ELog;
use EP\I\PDOConnector;
use EP\I\SqlInterface;
use EP\DB\SQLAssembler\SQLAssembler;
use PDOException;
use PDOStatement;
use Exception;
use PDO;

class PDOSqlDriver implements SqlInterface
{
    /**
     * @var PDOStatement
     */
    public $stmt;

    /**
     * @var PDO
     */
    public $pdo;

    /**
     * @var string
     */
    public $sql;

    /**
     * 链式查询每条语句的标示符
     * @var int
     */
    protected $qid = 0;

    /**
     * 以qid为key,存储链式查询生成的sql语句
     * @var array
     */
    protected $querySQL = array(0);

    /**
     * 联系查询生成的参数缓存
     * @var array
     */
    protected $queryParams;

    /**
     * @var string|array
     */
    protected $params;

    /**
     * @var PDOConnector
     */
    protected $connector;

    /**
     * @var SQLAssembler
     */
    protected $SQLAssembler;

    /**
     * 创建数据库连接
     *
     * @param PDOConnector $connector
     * @param SQLAssembler $SQLAssembler
     */
    public function __construct(PDOConnector $connector, SQLAssembler $SQLAssembler)
    {
        $this->setConnector($connector);
        $this->setSQLAssembler($SQLAssembler);

        $this->pdo = $this->connector->getPDO();
        if (!$this->pdo instanceof PDO) {
            ELog::error('init pdo failed!', 500, ELog::ALERT);
        }
    }

    /**
     * 获取单条数据
     *
     * @param string $table
     * @param string $fields
     * @param string|array $where
     *
     * @return mixed
     */
    public function get(string $table, string $fields = '*', array $where)
    {
        return $this->select($fields)->from($table)->where($where)->stmt()->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * 获取表中所有数据
     *
     * @param string $table
     * @param string $fields
     * @param array $where
     * @param int|string $order
     * @param int|string $group_by
     * @param int|string $limit 0 表示无限制
     *
     * @return array
     */
    public function getAll(string $table, string $fields = '*', array $where = [], $order = 1, $group_by = 1, $limit = 0)
    {
        $data = $this->select($fields)->from($table);

        if ($where) {
            $data->where($where);
        }

        if (1 !== $group_by) {
            $data->groupBy($group_by);
        }

        if (1 !== $order) {
            $data->orderBy($order);
        }

        if (0 !== $limit) {
            $data->limit($limit);
        }
        $result = [];
        try {
            $result = $data->stmt()->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $PDOException) {
            ELog::error($PDOException->getMessage());
        }
        return $result;
    }

    /**
     * 插入数据
     * @see SQLAssembler::add()
     *
     * @param string $table 要插入的数据表
     * @param array $data 要插入的数据,批量插入时的数据结构如下
     * @param bool $multi 批量插入数据时,开启事务
     * @param array $insert_data 插入的数据列表
     * @param bool $openTA
     *
     * @return bool|mixed
     */
    public function add(
        string $table, array $data, bool $multi = false, array &$insert_data = array(), bool $openTA = false
    )
    {
        $this->SQLAssembler->add($table, $data, $multi);
        $this->sql = $this->SQLAssembler->getSQL();
        $this->params = $this->SQLAssembler->getParams();

        if ($multi) {
            $add_count = 0;
            if (!empty($this->params)) {
                $inc_name = $this->getAutoIncrementName($table);
                $stmt = $this->prepare($this->sql);

                if ($openTA) {
                    $this->beginTA();
                    try {
                        if (!empty($this->params)) {
                            foreach ($this->params as $p) {
                                if ($stmt->exec($p, true)) {
                                    $add_data_info = array_combine($data['fields'], $p);
                                    if ($inc_name) {
                                        $add_data_info[$inc_name] = $this->insertId();
                                    }

                                    $add_count++;
                                    $insert_data[] = $add_data_info;
                                }
                            }
                        }
                    } catch (Exception $e) {
                        $insert_data = array();
                        $this->rollBack();
                        new EE($e);
                    }
                    $this->commit();
                } else {
                    if (!empty($this->params)) {
                        foreach ($this->params as $p) {
                            if ($stmt->exec($p, true)) {
                                $add_data_info = array_combine($data['fields'], $p);
                                if ($inc_name) {
                                    $add_data_info[$inc_name] = $this->insertId();
                                }

                                $add_count++;
                                $insert_data[] = $add_data_info;
                            }
                        }
                    }
                }
            }
            return $add_count;
        } else {
            $add_count = $this->prepare($this->sql)->exec($this->params, true);
            $last_insert_id = $this->insertId();
            if ($last_insert_id > 0) {
                return $last_insert_id;
            }

            return $add_count;
        }
    }

    /**
     * 带分页的数据查询
     *
     * @param string $table
     * @param string $fields 字段名
     * @param array $where
     * @param string|int $order
     * @param array $page
     * @param int $group_by
     *
     * @return mixed
     */
    public function find(
        string $table, string $fields, array $where, $order = 1, array &$page = array('p' => 1, 'limit' => 50),
        $group_by = 1
    )
    {
        if (!isset($page['result_count'])) {
            $total = $this->get($table, 'COUNT(*) as total', $where);
            $page['result_count'] = (int)$total['total'];
        }
        if (!isset($page['limit'])) {
            $page['limit'] = 25;
        }
        $page['limit'] = max(1, (int)$page['limit']);
        $page['total_page'] = ceil($page['result_count'] / $page['limit']);

        if (!isset($page['p'])) {
            $page['p'] = 1;
        }
        if ($page['p'] <= $page['total_page']) {
            $page['p'] = max(1, $page['p']);
            $this->SQLAssembler->find($table, $fields, $where, $order, $page, $group_by);
            return $this->getPrepareResult(true);
        }

        return array();
    }

    /**
     * 数据更新
     *
     * @param string $table
     * @param array $data
     * @param array $where
     *
     * @return bool
     */
    public function update(string $table, array $data, array $where)
    {
        $this->SQLAssembler->update($table, $data, $where);
        $this->sql = $this->SQLAssembler->getSQL();
        $this->params = $this->SQLAssembler->getParams();
        return $this->prepare($this->sql)->exec($this->params, true);
    }

    /**
     * 删除
     * @see SQLAssembler::del()
     *
     * @param string $table
     * @param array $where
     * @param bool $multi 是否批量删除数据
     * @param bool $openTA 是否开启事务 默认关闭
     *
     * @return int
     */
    public function del(string $table, array $where, bool $multi = false, bool $openTA = false): int
    {
        $del_count = 0;
        $this->SQLAssembler->del($table, $where, $multi);
        $this->sql = $this->SQLAssembler->getSQL();
        $this->params = $this->SQLAssembler->getParams();
        if ($multi) {
            if ($openTA) {
                $this->beginTA();
                try {
                    if (!empty($this->params)) {
                        $stmt = $this->prepare($this->sql);
                        foreach ($this->params as $p) {
                            $del_count += $stmt->exec($p, true);
                        }
                    }
                } catch (Exception $e) {
                    $this->rollBack();
                    new EE($e);
                    return 0;
                }
                $this->commit();
            } else {
                if (!empty($this->params)) {
                    $stmt = $this->prepare($this->sql);
                    foreach ($this->params as $p) {
                        $del_count += $stmt->exec($p, true);
                    }
                }
            }
        } else {
            $del_count = $this->prepare($this->sql)->exec($this->params, true);
        }

        return $del_count;
    }

    /**
     * 执行一条SQL 语句 并返回结果
     *
     * @param string $sql
     * @param int $fetch_style
     * @param int $cursor_orientation
     * @param int $cursor_offset
     *
     * @return mixed
     */
    public function fetchOne(
        string $sql, int $fetch_style = PDO::FETCH_ASSOC,
        int $cursor_orientation = PDO::FETCH_ORI_NEXT,
        int $cursor_offset = 0
    )
    {
        try {
            return $this->pdo->query($sql)->fetch($fetch_style, $cursor_orientation, $cursor_offset);
        } catch (Exception $e) {
            new EE($e);
        }
        return false;
    }

    /**
     * 执行sql 并返回所有结果
     *
     * @param string $sql
     * @param int $fetch_style
     * @param null $fetch_argument
     * @param array $ctor_args
     * <pre>
     * 当fetch_style为PDO::FETCH_CLASS时, 自定义类的构造函数的参数。
     * </pre>
     *
     * @return array
     */
    public function fetchAll(
        string $sql, int $fetch_style = PDO::FETCH_ASSOC, $fetch_argument = null, array $ctor_args = array()
    )
    {
        try {
            $data = $this->pdo->query($sql);
            if (null !== $fetch_argument) {
                switch ($fetch_style) {
                    case PDO::FETCH_CLASS:
                        return $data->fetchAll($fetch_style, $fetch_argument, $ctor_args);

                    default:
                        return $data->fetchAll($fetch_style, $fetch_argument);
                }

            } else {
                return $data->fetchAll($fetch_style);
            }
        } catch (Exception $e) {
            new EE($e);
        }
        return [];
    }

    /**
     * 执行sql 用于无返回值的操作
     *
     * @param string $sql
     *
     * @return int
     */
    public function execute(string $sql): int
    {
        try {
            return $this->pdo->exec($sql);
        } catch (Exception $e) {
            new EE($e);
        }
        return 0;
    }

    /**
     * 链式查询以select开始,跟原生的sql语句保持一致
     *
     * @param string $fields
     * @param string $modifier
     *
     * @return PDOSqlDriver
     */
    function select(string $fields = '*', string $modifier = '')
    {
        $this->generateQueryID();
        $this->querySQL[$this->qid] = $this->SQLAssembler->select($fields, $modifier);
        $this->queryParams[$this->qid] = array();
        return $this;
    }

    /**
     * insert
     *
     * @param string $table
     * @param array $data
     * @param string $modifier
     *
     * @return PDOSqlDriver
     */
    function insert($table, array $data = array(), $modifier = '')
    {
        $params = array();
        $this->generateQueryID();
        $this->querySQL[$this->qid] = $this->SQLAssembler->insert($table, $modifier, $data, $params);
        $this->queryParams[$this->qid] = $params;
        return $this;
    }

    /**
     * replace
     *
     * @param string $table
     * @param string $modifier
     *
     * @return PDOSqlDriver
     */
    function replace($table, $modifier = '')
    {
        $this->generateQueryID();
        $this->querySQL[$this->qid] = $this->SQLAssembler->replace($table, $modifier);
        $this->queryParams[$this->qid] = array();
        return $this;
    }

    /**
     * @param $table
     *
     * @return PDOSqlDriver
     */
    function from($table)
    {
        $this->querySQL[$this->qid] .= $this->SQLAssembler->from($table);
        return $this;
    }

    /**
     * @param $where
     *
     * @return PDOSqlDriver
     */
    function where($where)
    {
        $params = &$this->queryParams[$this->qid];
        $this->querySQL[$this->qid] .= $this->SQLAssembler->where($where, $params);

        return $this;
    }

    /**
     * @return $this
     */
    function forUpdate()
    {
        if (!$this->queryParams[$this->qid]) {
            ELog::error('进行行锁查询，必须用->where()设置条件');
        }
        $this->querySQL[$this->qid] .= $this->SQLAssembler->forUpdate();
        return $this;
    }

    /**
     * @return $this
     */
    function lockInShareMode()
    {
        if (!$this->queryParams[$this->qid]) {
            ELog::error('进行行锁查询，必须用->where()设置条件');
        }
        $this->querySQL[$this->qid] .= $this->SQLAssembler->lockInShareMode();
        return $this;
    }

    /**
     * @param $start
     * @param bool $end
     *
     * @return PDOSqlDriver
     */
    function limit($start, $end = false)
    {
        $this->querySQL[$this->qid] .= $this->SQLAssembler->limit($start, $end);
        return $this;
    }

    /**
     * @param $offset
     *
     * @return PDOSqlDriver
     */
    function offset($offset)
    {
        $this->querySQL[$this->qid] .= $this->SQLAssembler->offset($offset);
        return $this;
    }

    /**
     * @param $order
     *
     * @return PDOSqlDriver
     */
    function orderBy($order)
    {
        $this->querySQL[$this->qid] .= $this->SQLAssembler->orderBy($order);
        return $this;
    }

    /**
     * @param $group
     *
     * @return PDOSqlDriver
     */
    function groupBy($group)
    {
        $this->querySQL[$this->qid] .= $this->SQLAssembler->groupBy($group);
        return $this;
    }

    /**
     * @param $having
     *
     * @return PDOSqlDriver
     */
    function having($having)
    {
        $this->querySQL[$this->qid] .= $this->SQLAssembler->having($having);
        return $this;
    }

    /**
     * @param $procedure
     *
     * @return PDOSqlDriver
     */
    function procedure($procedure)
    {
        $this->querySQL[$this->qid] .= $this->SQLAssembler->procedure($procedure);
        return $this;
    }

    /**
     * @param $var_name
     *
     * @return PDOSqlDriver
     */
    public function into($var_name)
    {
        $this->querySQL[$this->qid] .= $this->SQLAssembler->into($var_name);
        return $this;
    }

    /**
     * @param $set
     *
     * @return PDOSqlDriver
     */
    public function set($set)
    {
        $params = &$this->queryParams[$this->qid];
        $this->querySQL[$this->qid] .= $this->SQLAssembler->set($set, $params);

        return $this;
    }

    /**
     * @param string $on
     *
     * @return PDOSqlDriver
     */
    function on($on)
    {
        $this->querySQL[$this->qid] .= $this->SQLAssembler->on($on);
        return $this;
    }

    /**
     * 返回链式查询当前生成的prepare语句
     *
     * @param bool $only_sql
     *
     * @return mixed
     */
    function getSQL($only_sql = false)
    {
        $this->sql = &$this->querySQL[$this->qid];
        if ($only_sql) {
            return $this->sql;
        }

        $params = $this->queryParams[$this->qid];
        return array('sql' => $this->sql, 'params' => $params);
    }

    /**
     * 获取表前缀
     * @return string
     */
    function getPrefix()
    {
        return $this->SQLAssembler->getPrefix();
    }

    /**
     * 绑定链式查询生成的sql语句并返回stmt对象
     *
     * @param bool $execute 是否调用stmt的execute
     * @param array $prepare_params prepare时的参数
     *
     * @return PDOStatement|false
     */
    public function stmt($execute = true, $prepare_params = array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY))
    {
        if ($this->qid == 0) {
            ELog::error('链式风格的查询必须以->select()开始');
        }

        $this->sql = &$this->querySQL[$this->qid];
        try {
            $stmt = $this->pdo->prepare($this->sql, $prepare_params);
            if ($execute) {
                $execute_params = $this->queryParams[$this->qid];
                $stmt->execute($execute_params);
            }

            unset($this->querySQL[$this->qid], $this->queryParams[$this->qid]);
            return $stmt;
        } catch (Exception $e) {
            ELog::error($e->getMessage());
        }
        return false;
    }

    /**
     * 链式执行操作
     *
     * @param array $prepare_params
     *
     * @return bool
     */
    public function stmtExecute($prepare_params = array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY))
    {
        if ($this->qid == 0) {
            ELog::error('无效的执行语句');
        }

        $this->sql = &$this->querySQL[$this->qid];
        try {
            $stmt = $this->pdo->prepare($this->sql, $prepare_params);
            $execute_params = $this->queryParams[$this->qid];

            unset($this->querySQL[$this->qid], $this->queryParams[$this->qid]);
            return $stmt->execute($execute_params);
        } catch (Exception $e) {
            new EE($e, $e->getMessage());
        }
        return false;
    }

    /**
     * 参数绑定
     *
     * @param string $statement
     * @param array $params
     *
     * @return $this
     */
    public function prepare($statement, $params = array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY))
    {
        try {
            $this->stmt = $this->pdo->prepare($statement, $params);
            if (!$this->stmt) {
                ELog::error('PDO prepare failed!');
            }
        } catch (PDOException $e) {
            throw new EE($e, $e->getMessage());
        }
        return $this;
    }

    /**
     * 执行参数绑定
     *
     * @param array $args
     * @param bool $row_count
     *
     * @return int|$this
     */
    public function exec($args = array(), $row_count = false)
    {
        try {
            $this->stmt->execute($args);
            if ($row_count) {
                return $this->stmt->rowCount();
            }
        } catch (PDOException $e) {
            throw new EE($e, $e->getMessage());
        }
        return $this;
    }

    /**
     * 返回参数绑定结果
     *
     * @param bool $_fetchAll
     * @param int $fetch_style
     *
     * @return mixed
     */
    public function stmtFetch($_fetchAll = false, $fetch_style = PDO::FETCH_ASSOC)
    {
        if (!$this->stmt) {
            ELog::error('stmt init failed!');
        }

        if (true === $_fetchAll) {
            return $this->stmt->fetchAll($fetch_style);
        }

        return $this->stmt->fetch($fetch_style);
    }

    /**
     * 获取表的字段信息
     *
     * @param string $table
     * @param bool $fields_map
     *
     * @return mixed
     */
    public function getMetaData($table, $fields_map = true)
    {
        return $this->connector->getMetaData($table, $fields_map);
    }

    /**
     * 获取自增字段名
     *
     * @param string $table_name
     *
     * @return bool
     */
    public function getAutoIncrementName($table_name)
    {
        return $this->connector->getPK($table_name);
    }

    /**
     * @return PDOConnector
     */
    public function getConnector()
    {
        return $this->connector;
    }

    /**
     * 设置PDOConnector对象
     *
     * @param PDOConnector $connector
     */
    public function setConnector(PDOConnector $connector)
    {
        $this->connector = $connector;
    }

    /**
     * @return SQLAssembler
     */
    public function getSQLAssembler()
    {
        return $this->SQLAssembler;
    }

    /**
     * 设置SQLAssembler对象
     *
     * @param SQLAssembler $SQLAssembler
     */
    public function setSQLAssembler(SQLAssembler $SQLAssembler)
    {
        $this->SQLAssembler = $SQLAssembler;
    }

    /**
     * 解析fields
     *
     * @param string $fields
     *
     * @return string
     */
    public function parseFields($fields)
    {
        return $this->SQLAssembler->parseFields($fields);
    }

    /**
     * 解析where
     *
     * @param array $where
     * @param array $params
     *
     * @return string
     */
    public function parseWhere(array $where, array &$params)
    {
        return $this->SQLAssembler->parseWhere($where, $params);
    }

    /**
     * 解析order
     *
     * @param string $order
     *
     * @return int|string
     */
    public function parseOrder($order)
    {
        return $this->SQLAssembler->parseOrder($order);
    }

    /**
     * 解析group
     *
     * @param string $group_by
     *
     * @return int
     */
    public function parseGroup($group_by)
    {
        return $this->SQLAssembler->parseGroup($group_by);
    }

    /**
     * 开启事务
     * @return bool
     */
    public function commit()
    {
        return $this->pdo->commit();
    }

    /**
     * @return bool
     */
    public function beginTA()
    {
        return $this->pdo->beginTransaction();
    }

    /**
     * 回滚
     * @return bool
     */
    public function rollBack()
    {
        return $this->pdo->rollBack();
    }

    /**
     * 返回最后操作的id
     * @return string
     */
    public function insertId()
    {
        return $this->connector->lastInsertID();
    }

    /**
     * 绑定sql语句,执行后给出返回结果
     *
     * @param bool $_fetchAll
     * @param int $fetch_style
     *
     * @return mixed
     */
    protected function getPrepareResult($_fetchAll = false, $fetch_style = PDO::FETCH_ASSOC)
    {
        $this->sql = $this->SQLAssembler->getSQL();
        $this->params = $this->SQLAssembler->getParams();

        return $this->prepare($this->sql)->exec($this->params)->stmtFetch($_fetchAll, $fetch_style);
    }

    /**
     * 生成qid
     */
    private function generateQueryID()
    {
        do {
            $qid = mt_rand(1, 99999);
            if (!isset($this->querySQL[$qid])) {
                $this->qid = $qid;
                break;
            }
        } while (true);
    }
}