<?php
/**
 * Created by PhpStorm.
 * User: JH
 * Date: 2017/11/27
 * Time: 18:16
 */
declare(strict_types=1);

namespace EP\DB;

use EP\DB\Connector\{PgSQLConnector,SQLiteConnector,MySQLConnector};
use EP\DB\Drivers\{MongoDriver,PDOSqlDriver,RedisDriver,CouchDriver,MemcacheDriver};
use EP\DB\SQLAssembler\{PgSQLAssembler,SQLiteAssembler,MySQLAssembler};
use EP\Exception\ELog;
use PDO;


class DBFactory
{
    /**
     * @param string $link
     * @param array $params
     *
     * @return PDOSqlDriver|RedisDriver|MemcacheDriver|CouchDriver|MongoDriver
     */
    static function make(string $link, array $params)
    {
        //配置的数据表前缀
        $prefix = !empty($params['prefix']) ? $params['prefix'] : '';
        $options = isset($params['options']) ? $params['options'] : array();
        if (isset($params['charset'])) {
            $options['charset'] = $params['charset'];
        }
        switch (strtolower($link)) {
            case 'mysql' :
                return new PDOSqlDriver(
                    MySQLConnector::getInstance(self::getDsn($params, 'mysql'), $params['user'], $params['pass'],
                        $options),
                    new MySQLAssembler($prefix)
                );

            case 'sqlite':
                return new PDOSqlDriver(SQLiteConnector::getInstance($params['dsn'], null, null, $options),
                    new SQLiteAssembler($prefix));

            case 'pgsql':
                return new PDOSqlDriver(
                    PgSqlConnector::getInstance(self::getDsn($params, 'pgsql'), $params['user'], $params['pass'],
                        $options),
                    new PgSQLAssembler($prefix)
                );

            case 'mongo':
                return new MongoDriver($params);

            case 'redis':
                return new RedisDriver($params);

            case 'memcache':
                return new MemcacheDriver($params);

            case 'couch':
                return new CouchDriver($params);

            default:
                ELog::error('不支持的数据库扩展!');
        }
    }

    /**
     * @param $params
     * @param string $type
     * @param bool $use_unix_socket
     *
     * @return string
     */
    private static function getDsn($params, $type = 'mysql', $use_unix_socket = true)
    {
        if (!empty($params['dsn'])) {
            return $params['dsn'];
        }

        if (!isset($params['host']) || !isset($params['name'])) {
            ELog::error('连接数据库所需参数不足');
        }

        if ($use_unix_socket && !empty($params['unix_socket'])) {
            $dsn = "{$type}:unix_socket={$params['unix_socket']};dbname={$params['name']};";
        } else {
            $dsn = "{$type}:host={$params['host']};dbname={$params['name']};";
            if (isset($params['port'])) {
                $dsn .= "port={$params['port']};";
            }

            if (isset($params['charset'])) {
                $dsn .= "charset={$params['charset']};";
            }
        }

        return $dsn;
    }
}