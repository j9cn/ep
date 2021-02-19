<?php
/**
 * Created by PhpStorm.
 * User: J.H
 * Date: 2018/5/5
 * Time: 10:49
 */

namespace EP\DB\Connector;

use EP\Exception\EE;
use PDO, PDOException;

class SQLiteConnector extends BaseConnector
{
    /**
     * @var PDO
     */
    private static $instance;

    /**
     * 默认连接参数
     * @var array
     */
    private static $options = array();

    /**
     * 创建一个SQLite的PDO连接
     *
     * @param string $dsn
     * @param array $options
     */
    private function __construct($dsn, array $options)
    {
        try {
            $this->pdo = new PDO($dsn, null, null, parent::getOptions(self::$options, $options));
        } catch (PDOException $e) {
            new EE($e);
        }
    }

    /**
     * @param string $dsn
     * @param null $user
     * @param null $pwd
     * @param array $options
     *
     * @return SQLiteConnector|PDO
     */
    static function getInstance($dsn, $user = null, $pwd = null, array $options = array())
    {
        $key = md5($dsn);
        if (empty(self::$instance[$key])) {
            self::$instance[$key] = new self($dsn, $options);
        }

        return self::$instance[$key];
    }

    /**
     * 返回一个PDO连接对象的实例
     * @return mixed
     */
    function getPDO()
    {
        return $this->pdo;
    }

    /**
     * 获取表的主键名
     *
     * @param string $table
     *
     * @return mixed
     */
    function getPK($table)
    {
        $info = $this->getMetaData($table, false);
        if (!empty($info)) {
            foreach ($info as $i) {
                if ($i['pk'] == 1) {
                    return $i['name'];
                }
            }
        }
        return false;
    }

    /**
     * 最后插入的id
     */
    function lastInsertId()
    {
        return $this->pdo->lastInsertId();
    }

    /**
     * 获取表的字段信息
     *
     * @param string $table
     * @param bool $fields_map
     *
     * @return mixed
     */
    function getMetaData($table, $fields_map = true)
    {
        $sql = "PRAGMA table_info('{$table}')";
        try {
            $data = $this->pdo->query($sql);
            if ($fields_map) {
                $result = array();
                $data->fetchAll(PDO::FETCH_FUNC,
                    function ($cid, $name, $type, $notnull, $dflt_value, $pk) use (&$result) {
                        $result[$name] = array(
                            'primary' => $pk == 1,
                            'auto_increment' => (bool)(($pk == 1) && ($type == 'INTEGER')), //INTEGER && PRIMARY KEY.
                            'default_value' => strval($dflt_value),
                            'not_null' => $notnull == 1
                        );
                    });
                return $result;
            } else {
                return $data->fetchAll(PDO::FETCH_ASSOC);
            }
        } catch (PDOException $e) {
            return array();
        }
    }
}