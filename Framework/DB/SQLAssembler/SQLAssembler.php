<?php
/**
 * Created by PhpStorm.
 * User: JH
 * Date: 2017/11/27
 * Time: 20:03
 */
declare(strict_types=1);

namespace EP\DB\SQLAssembler;


use EP\Exception\ELog;

class SQLAssembler
{
    /**
     * @var string
     */
    protected $sql;

    /**
     * @var string
     */
    protected $params;

    /**
     * 表前缀
     * @var string
     */
    protected $table_prefix;

    /**
     * offset()在limit()中已经传递了第二个参数时不再生效
     * @var bool
     */
    protected $offset_is_valid = true;

    /**
     * 初始化时可以指定表前缀
     *
     * @param string $table_prefix
     */
    function __construct($table_prefix = '')
    {
        $this->table_prefix = $table_prefix;
    }

    /**
     * 插入
     *
     * @param string $table 表名称
     * @param array $data 要处理的数据关联数组
     * @param bool $multi 是否批量插入数据
     * <pre>
     *  批量插入数据时$data的结构如下:
     *      $data = array(
     *          'fields' => array(字段1,字段2,...),
     *          'values' => array(
     *                      array(字段1的值, 字段2的值),
     *                      array(字段1的值, 字段2的值))
     *      );
     * OR
     *      $data = array(
     *          array( 字段1 => 字段1的值, 字段2 => 字段2的值,...)
     *      );
     * </pre>
     */
    public function add(string $table, array &$data, bool $multi = false)
    {
        $params = array();
        $field_str = $value_str = '';

        if (true === $multi) {
            if (empty($data['fields']) || empty($data['values'])) {
                $data = $this->parseMultiData($data);
            }

            foreach ($data ['fields'] as $d) {
                $field_str .= "{$d},";
                $value_str .= '?,';
            }

            $params = array();
            foreach ($data ['values'] as $p) {
                $params[] = $p;
            }

            $fields = trim($field_str, ',');
            $values = trim($value_str, ',');
            $into_fields = "({$fields}) VALUES ({$values})";
        } else {
            $into_fields = $this->parseData($data, $params, 'insert');
        }

        $this->setSQL("INSERT INTO {$table} {$into_fields}");
        $this->setParams($params);
    }

    /**
     * 从批量插入数据中获取对应的 fields、values
     *
     * @param array $data
     *
     * @return array
     */
    private function parseMultiData(array $data)
    {
        $fields = $values = [];
        if (!empty($data)) {
            while ($d = array_shift($data)) {
                $keys = array_keys($d);
                if (empty($fields)) {
                    $fields = $keys;
                } elseif ($keys !== $fields) {
                    continue;
                }
                $values[] = array_values($d);
            }
        }
        return ['fields' => $fields, 'values' => $values];
    }


    /**
     * 带分页功能的查询
     *
     * @param string $table 表名称, 复杂情况下, 以LEFT JOIN为例: table_a a LEFT JOIN table_b b ON a.id=b.aid
     * @param string $fields 要查询的字段 所有字段的时候为'*'
     * @param array $where 查询条件
     * @param int|string $order 排序
     * @param array $page 分页参数 默认返回50条记录
     * @param int|string $group_by
     *
     * @return mixed|void
     */
    public function find(
        string $table, string $fields, array $where, $order = '', array &$page = array('p' => 1, 'limit' => 50),
        $group_by = 1
    ) {
        $params = array();
        $field_str = $this->parseFields($fields);
        $where_str = $this->parseWhere($where, $params);
        $order_str = $this->parseOrder($order);

        $p = ($page['p'] - 1) * $page['limit'];
        if (1 !== $group_by) {
            $group_str = $this->parseGroup($group_by);
            $sql = "SELECT {$field_str} FROM {$table} WHERE {$where_str} GROUP BY {$group_str} ORDER BY {$order_str} LIMIT {$p}, {$page['limit']}";
        } else {
            $sql = "SELECT {$field_str} FROM {$table} WHERE {$where_str} ORDER BY {$order_str} LIMIT {$p}, {$page['limit']}";
        }

        $this->setSQL($sql);
        $this->setParams($params);
    }

    /**
     * 更新
     *
     * @param string $table
     * @param array $data
     * @param array $where
     *
     * @return mixed|void
     */
    public function update(string $table, array $data, array $where)
    {
        $params = array();
        $fields = $this->parseData($data, $params);
        $where_str = $this->parseWhere($where, $params);

        $fields = trim($fields, ',');
        $this->setSQL("UPDATE {$table} SET {$fields} WHERE {$where_str}");
        $this->setParams($params);
    }

    /**
     * 删除
     *
     * @param string $table
     * @param string|array $where
     * @param bool $multi 是否批量删除数据
     *      $where = array(
     *          'fields' => array(字段1,字段2,...),
     *          'values' => array(
     *                      array(字段1的值, 字段2的值),
     *                      array(字段1的值, 字段2的值))
     *      );
     *
     * @return mixed|void
     */
    public function del($table, $where, $multi = false)
    {
        $params = array();
        if (true === $multi) {
            if (empty($where ['fields']) || empty($where ['values'])) {
                ELog::error('data format error!');
            }

            $where_condition = array();
            foreach ($where ['fields'] as $d) {
                $where_condition[] = "{$d} = ?";
            }

            $where_str = implode(' AND ', $where_condition);
            foreach ($where ['values'] as $p) {
                $params[] = $p;
            }

        } else {
            $where_str = $this->parseWhere($where, $params);
        }

        $this->setSQL("DELETE FROM {$table} WHERE {$where_str}");
        $this->setParams($params);
    }

    /**
     * select
     *
     * @param string $fields
     * @param string $modifier
     *
     * @return string
     */
    public function select($fields = '*', $modifier = '')
    {
        return "SELECT {$modifier} {$this->parseFields($fields)} ";
    }

    /**
     * insert
     *
     * @param string $table
     * @param string $modifier
     * @param array $data
     * @param array $params
     *
     * @return string
     */
    public function insert($table, $modifier = '', $data, array &$params = array())
    {
        return "INSERT {$modifier} INTO {$table} {$this->parseData($data, $params, 'insert')} ";
    }

    /**
     * replace
     *
     * @param string $table
     * @param string $modifier
     *
     * @return string
     */
    public function replace($table, $modifier = '')
    {
        return "REPLACE {$modifier} {$table} ";
    }

    /**
     * @param $table
     *
     * @return string
     */
    public function from($table)
    {
        return "FROM {$table} ";
    }

    /**
     * @param string|array $where
     * @param array $params
     *
     * @return string
     */
    public function where($where, array &$params)
    {
        return "WHERE {$this->parseWhere($where, $params)} ";
    }

    /**
     * @return string
     */
    function forUpdate()
    {
        return " FOR UPDATE";
    }

    /**
     * @return string
     */
    function lockInShareMode()
    {
        return " LOCK IN SHARE MODE";
    }

    /**
     * @param int $start
     * @param bool|int $end
     *
     * @return string
     */
    public function limit($start, $end = false)
    {
        if ($end) {
            $end = (int)$end;
            $this->offset_is_valid = false;
            return "LIMIT {$start}, {$end} ";
        }

        $start = (int)$start;
        return "LIMIT {$start} ";
    }

    /**
     * @param int $offset
     *
     * @return string
     */
    public function offset($offset)
    {
        if ($this->offset_is_valid) {
            return "OFFSET {$offset} ";
        }

        return "";
    }

    /**
     * @param $order
     *
     * @return string
     */
    public function orderBy($order)
    {
        return "ORDER BY {$this->parseOrder($order)} ";
    }

    /**
     * @param $group
     *
     * @return string
     */
    public function groupBy($group)
    {
        return "GROUP BY {$this->parseGroup($group)} ";
    }

    /**
     * @param $having
     *
     * @return string
     */
    public function having($having)
    {
        return "HAVING {$having} ";
    }

    /**
     * @param $procedure
     *
     * @return string
     */
    public function procedure($procedure)
    {
        return "PROCEDURE {$procedure} ";
    }

    /**
     * @param $var_name
     *
     * @return string
     */
    public function into($var_name)
    {
        return "INTO {$var_name} ";
    }

    /**
     * @param string $data
     * @param array $params
     *
     * @return string
     */
    public function set($data, array &$params = array())
    {
        return "SET {$this->parseData($data, $params)} ";
    }

    /**
     * @param string $on
     *
     * @return string
     */
    public function on($on)
    {
        return "ON {$on} ";
    }

    /**
     * 解析字段
     *
     * @param string $fields
     *
     * @return string
     */
    public function parseFields($fields)
    {
        if (empty($fields)) {
            $field_str = '*';
        } else {
            $field_str = $fields;
        }

        return $field_str;
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
        if (!empty($where)) {
            if (is_array($where)) {
                if (isset($where[1])) {
                    $where_str = $where[0];
                    if (!is_array($where[1])) {
                        $params[] = $where[1];
                    } else {
                        foreach ($where[1] as $w) {
                            $params[] = $w;
                        }
                    }
                } else {
                    $where_str = $this->parseWhereFromHashMap($where, $params);
                }
            } else {
                $where_str = $where;
            }
        } else {
            $where_str = '1=1';
        }
        return $where_str;
    }

    /**
     * 解析order
     *
     * @param string $order
     *
     * @return string
     */
    public function parseOrder($order)
    {
        if (!empty($order)) {
            $order_str = $order;
        } else {
            $order_str = '1';
        }

        return $order_str;
    }

    /**
     * 解析group by
     *
     * @param string $group_by
     *
     * @return string
     */
    public function parseGroup($group_by)
    {
        if (!empty($group_by)) {
            $group_str = $group_by;
        } else {
            $group_str = '1';
        }

        return $group_str;
    }

    /**
     * @return string
     */
    public function getSQL()
    {
        return $this->sql;
    }

    /**
     * @param $sql
     */
    protected function setSQL($sql)
    {
        $this->sql = $sql;
    }

    /**
     * @return string|array
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * 获取表前缀
     * @return string
     */
    public function getPrefix()
    {
        return $this->table_prefix;
    }

    /**
     * @param $params
     */
    protected function setParams($params)
    {
        $this->params = $params;
    }

    /**
     * 解析where条件
     *
     * @param string $operator 字段和值之间的操作符
     * @param string $field 字段名
     * @param string|array $field_config 字段值配置
     * @param bool $is_mixed_field 区别默认字段和复合字段(带括号的字段)
     * @param string $condition_connector 每个条件之间的连接符
     * @param string $connector 每个字段之间的连接符
     * @param array $params 包含字段值的数组(prepare之后传递的参数)
     *
     * @return array
     */
    protected function parseCondition(
        $operator, $field, $field_config, $is_mixed_field, $condition_connector, $connector, array &$params
    ) {
        $condition = array();
        switch ($connector) {
            case 'OR':
                if (!is_array($field_config)) {
                    $field_config = array($field_config);
                }
                foreach ($field_config as $field_single_config) {
                    if (is_array($field_single_config)) {
                        list($operator, $single_field_value) = $field_single_config;
                        $params [] = $single_field_value;
                    } else {
                        $params [] = $field_single_config;
                    }
                    $condition[' OR '][] = "{$field} {$operator} ?";
                }
                break;

            case 'AND':
                if ($is_mixed_field) {
                    $condition[" {$condition_connector} "][] = $field;
                    if (is_array($field_config)) {
                        foreach ($field_config as $f) {
                            $params [] = $f;
                        }
                    } else {
                        $params[] = $field_config;
                    }
                } else {
                    if (is_array($field_config)) {
                        foreach ($field_config as $and_exp_val) {
                            $ex_operator = '=';
                            if (is_array($and_exp_val)) {
                                list($ex_operator, $n_value) = $and_exp_val;
                                $and_exp_val = $n_value;
                            }
                            $condition[' AND '][] = "{$field} {$ex_operator} ?";
                            $params [] = $and_exp_val;
                        }
                    } else {
                        $params [] = $field_config;
                        $condition[' AND '][] = "{$field} {$operator} ?";
                    }
                }
                break;

            case 'IN':
            case 'NOT IN':
                if (!is_array($field_config)) {
                    ELog::error('IN or NOT IN need a array parameter');
                }

                $in_where_condition = array();
                foreach ($field_config as $in_field_val) {
                    $params[] = $in_field_val;
                    $in_where_condition [] = '?';
                }

                $in_where_condition_string = implode(',', $in_where_condition);
                $condition[" {$condition_connector} "][] = "{$field} {$connector} ($in_where_condition_string)";
                break;

            case 'BETWEEN':
            case 'NOT BETWEEN':
                if (!is_array($field_config)) {
                    ELog::error('BETWEEN need a array parameter');
                }

                if (!isset($field_config[0]) || !isset($field_config[1])) {
                    ELog::error('BETWEEN parameter error!');
                }

                $condition[" {$condition_connector} "][] = "{$field} {$connector} ? AND ?";
                $params[] = $field_config[0];
                $params[] = $field_config[1];
                break;

            case '@':
                if (is_array($field_config)) {
                    $field_config = implode(' ', $field_config);
                }
                $condition[" {$condition_connector} "][] = "{$field} {$field_config}";
                break;

            default:
                $operator = $connector;
                $condition[" {$condition_connector} "][] = "{$field} {$operator} ?";
                $params [] = $field_config;
        }
        return $condition;
    }

    /**
     * 解析数据
     *
     * @param $data
     * @param array $params
     * @param string $format
     *
     * @return string
     */
    private function parseData($data, array &$params, $format = 'normal')
    {
        if (!empty($data)) {
            if (is_array($data)) {
                if (isset($data[1])) {
                    $sql_segment = $data[0];
                    if (!is_array($data[1])) {
                        $params[] = $data[1];
                    } else {
                        foreach ($data[1] as $d) {
                            $params[] = $d;
                        }
                    }
                } else {
                    if ('insert' === $format) {
                        $data_keys = $data_values = array();
                        foreach ($data as $key => $value) {
                            $data_keys[] = $key;
                            $data_values[] = '?';
                            $params[] = $value;
                        }

                        $fields = implode(',', $data_keys);
                        $values = implode(',', $data_values);
                        $sql_segment = "({$fields}) VALUES ({$values})";
                    } else {
                        $segment = '';
                        foreach ($data as $key => $value) {
                            if (is_array($value)) {
                                if (isset($value[1])) {
                                    if (in_array(trim($value[0]), ['+', '-', '*', '/'])) {
                                        $segment .= ", {$key} = {$key} {$value[0]} {$value[1]}";
                                    } else {
                                        $segment .= ", {$key} = {$value[0]}";
                                        $params[] = $value[1];
                                    }
                                } else {
                                    $segment .= ", {$key} = {$value[0]}";
                                }
                            } else {
                                $segment .= ", {$key} = ?";
                                $params[] = $value;
                            }
                        }

                        $sql_segment = trim($segment, ',');
                    }
                }
            } else {
                $sql_segment = $data;
            }
        } else {
            $sql_segment = '';
        }
        return $sql_segment;
    }

    /**
     * 解析关联数组
     *
     * @param array $where
     * @param array $params
     *
     * @return string
     */
    private function parseWhereFromHashMap(array $where, array &$params)
    {
        $all_condition = array();
        foreach ($where as $field => $field_config) {
            $operator = '=';
            $field = trim($field);
            $is_mixed_field = false;
            $condition_connector = $connector = 'AND';

            if ($field[0] == '(' && $field[strlen($field) - 1] == ')') {
                $is_mixed_field = true;
            }

            if ($is_mixed_field === false && is_array($field_config)) {
                if (count($field_config) == 3) {
                    list($connector, $field_true_value, $condition_connector) = $field_config;
                } else {
                    list($connector, $field_true_value) = $field_config;
                }

                $condition_connector = strtoupper(trim($condition_connector));
                $connector = strtoupper(trim($connector));
                $field_config = $field_true_value;
            }

            $condition = $this->parseCondition($operator, $field, $field_config, $is_mixed_field, $condition_connector,
                $connector, $params);
            $all_condition[] = $condition;
        }
        return $this->combineWhereCondition($all_condition);
    }

    /**
     * 组合where条件
     *
     * @param $where_condition
     *
     * @return string
     */
    private function combineWhereCondition($where_condition)
    {
        $where = '';
        foreach ($where_condition as $condition) {
            foreach ($condition as $where_connector => $where_condition) {
                if (isset($where_condition[1])) {
                    $where_snippet_string = implode($where_connector, $where_condition);
                    $where_snippet = "($where_snippet_string)";
                    $where_connector = ' AND ';
                } else {
                    $where_snippet = $where_condition[0];
                }

                if ('' === $where) {
                    $where = $where_snippet;
                } else {
                    $where .= $where_connector . $where_snippet;
                }
            }
        }
        return $where;
    }
}