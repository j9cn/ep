<?php
/**
 * Created by PhpStorm.
 * User: JH
 * Date: 2017/11/27
 * Time: 18:44
 */

namespace EP\I;


interface SqlInterface
{
    /**
     * 获取一条数据
     *
     * @param string $table 表名
     * @param string $fields 字段
     * @param array $where 条件(建议只使用字符串常量,包含变量时请使用数组)
     * @return mixed
     */
    function get(string $table,string $fields,array $where);

    /**
     * 批量获取表中的数据
     *
     * @param string $table 表名
     * @param string $fields 要获取的字段名
     * @param array $where 条件(建议只使用字符串常量,包含变量时请使用数组)
     * @param int|string $order 排序
     * @param int|string $group_by 分组
     * @param int|string $limit 0表示无限制
     * @return mixed
     */
    function getAll(string $table, string $fields,array $where = [], $order = 1, $group_by = 1, $limit = 0);

    /**
     * 带分页的查询
     *
     * @param string $table 表名
     * @param string $fields 字段名
     * @param string|array $where 条件(建议只使用字符串常量,包含变量时请使用数组)
     * @param string|int $order 排序
     * @param array $page array('p', 'limit'); p表示当前页数, limit表示要取出的条数
     * @param int $group_by
     * @return mixed
     */
    function find(string $table,string $fields,array $where, $order = 1, array &$page = array('p', 'limit'), $group_by = 1);

    /**
     * 添加数据
     *
     * @param string $table 表名
     * @param array $data 要插入的数据
     * @param bool $multi 是否批量插入
     * @return mixed
     */
    function add(string $table,array $data, bool $multi = false);

    /**
     * 更新数据
     *
     * @param string $table 表名
     * @param array $data 要更新的数据
     * @param array $where 筛选条件
     * @return mixed
     */
    function update(string $table, array $data, array $where);

    /**
     * 删除数据
     *
     * @param string $table 表名
     * @param array $where 条件
     * @return mixed
     */
    function del(string $table, array $where);
}