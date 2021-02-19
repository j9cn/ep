<?php
/**
 * Created by PhpStorm.
 * User: JH
 * Date: 2017/11/27
 * Time: 21:26
 */
declare(strict_types=1);

namespace EP\DB\Drivers;

use EP\Exception\ELog;
use Redis, Exception;


class RedisDriver
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var Redis
     */
    protected $link;

    /**
     * @var array
     */
    protected $option;

    /**
     * 连接redis
     * <pre>
     * unixsocket设置
     * unixsocket /tmp/redis.sock
     * unixsocketperm 777
     * </pre>
     *
     * @param $option
     */
    function __construct(array $option)
    {
        if (!extension_loaded('redis')) {
            ELog::error('Not support redis extension !');
        }
        if (strcasecmp(PHP_OS, 'linux') == 0 && !empty($option['unix_socket'])) {
            $id = $option['unix_socket'];
            $use_unix_socket = true;
        } else {
            $id = "{$option['host']}:{$option['port']}:{$option['timeout']}";
            $use_unix_socket = false;
        }

        static $connects;
        if (!isset($connects[$id])) {
            $redis = new Redis();
            if ($use_unix_socket) {
                $connect_status = $redis->connect($option['unix_socket']);
            } else {
                $connect_status = $redis->connect($option['host'], $option['port'], $option['timeout']);
            }
            if (!$connect_status) {
                ELog::error('Redis connection fail', 500, ELog::ALERT);
            }

            if (!empty($option['auth'])) {
                $authStatus = $redis->auth($option['auth']);
                if (!$authStatus) {
                    ELog::error('Redis auth failed !');
                }
            }

            $connects[$id] = $redis;
        } else {
            $redis = &$connects[$id];
        }

        $this->id = $id;
        $this->link = $redis;
        $this->option = $option;
    }

    /**
     * 选择当前数据库
     * @return int
     */
    function selectCurrentDatabase()
    {
        static $selected = null;
        $db = &$this->option['db'];
        $current = $this->id . ':' . $db;
        if ($selected !== $current) {
            try {
                $select_ret = $this->link->select($db);
                if ($select_ret) {
                    $selected = $current;
                } else {
                    ELog::error("Redis select DB($current) failed!");
                }
            } catch (Exception $e) {
                ELog::error($e->getMessage(), $e->getCode(), ELog::ALERT);
            }
        }
        return $db;
    }

    /**
     * 重构Redis Set方法
     * @see Redis::set()
     *
     * @param $key
     * @param string|array $value
     * @param int $timeout
     *
     * @return bool
     */
    function set($key, $value, $timeout = 0)
    {
        $this->selectCurrentDatabase();
        if (is_array($value)) {
            $value = json_encode($value, JSON_UNESCAPED_UNICODE);
        }
        if (0 === $timeout) {
            return $this->link->set($key, $value);
        }
        return $this->link->set($key, $value, $timeout);
    }

    /**
     * 重构Redis Get方法
     * @see Redis::get()
     *
     * @param string|array $key
     *
     * @return array|bool|mixed
     */
    function get($key)
    {
        $this->selectCurrentDatabase();
        $dejson = function ($data) {
            $result = json_decode($data, true);
            if (0 === json_last_error()) {
                return $result;
            }
            return $data;
        };

        if (is_array($key)) {
            $res = $this->link->mGet($key);
            $resDecode = array();
            foreach ($res as $v) {
                $resDecode[] = $v === false ? false : $dejson($v);
            }
            return $resDecode;
        } else {
            $res = $this->link->get($key);
        }
        if (false === $res) {
            return false;
        }
        return $dejson($res);
    }


    /**
     * 调用redis类提供的方法
     *
     * @param $method
     * @param $argv
     *
     * @return mixed|null
     */
    public function __call($method, $argv)
    {
        $result = null;
        if (method_exists($this->link, $method)) {
            $this->selectCurrentDatabase();
            $result = ($argv == null)
                ? $this->link->$method()
                : call_user_func_array(array($this->link, $method), $argv);
        }

        return $result;
    }
}