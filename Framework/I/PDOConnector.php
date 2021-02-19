<?php
/**
 * Created by PhpStorm.
 * User: JH
 * Date: 2017/11/27
 * Time: 18:36
 */

namespace EP\I;


interface PDOConnector
{
    /**
     * 获取一个单例实例
     *
     * @param string $dsn
     * @param string $user
     * @param string $password
     * @param array $options
     * @return mixed
     */
    static function getInstance($dsn, $user, $password, array $options);

    /**
     * 获取表的主键名
     *
     * @param string $table_name
     * @return mixed
     */
    function getPK($table_name);

    /**
     * 获取表的字段信息
     *
     * @param string $table_name
     * @param bool $fields_map
     * @return mixed
     */
    function getMetaData($table_name, $fields_map = true);

    /**
     * 返回一个PDO连接对象的实例
     *
     * @return mixed
     */
    function getPDO();

    /**
     * 最后插入时自增ID的值
     *
     * @return mixed
     */
    function lastInsertId();
}