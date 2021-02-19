<?php
/**
 * Created by PhpStorm.
 * User: JH
 * Date: 2017/10/9
 * Time: 20:53
 */
declare(strict_types=1);

namespace EP\Core;


class EPArray
{
    /**
     * @var array 数据
     */
    protected $data;

    /**
     * @var self
     */
    protected static $instance;

    /**
     * CrossArray
     *
     * @param array $data
     */
    private function __construct(array &$data)
    {
        $this->data = &$data;
    }

    /**
     * @param array $data
     * @param string $cache_key
     *
     * @return EPArray
     */
    static function init(array &$data, $cache_key = null): self
    {
        if (null === $cache_key) {
            $cache_key = md5(json_encode($data));
        }

        if (!isset(self::$instance[$cache_key])) {
            self::$instance[$cache_key] = new self($data);
        }

        return self::$instance[$cache_key];
    }

    /**
     * 获取配置参数
     *
     * @param string $config
     * @param string|array $name
     *
     * @return bool|string|array
     */
    function get($config, $name = '')
    {
        if (isset($this->data[$config])) {
            if ($name) {
                if (is_array($name)) {
                    $result = array();
                    foreach ($name as $n) {
                        if (isset($this->data[$config][$n])) {
                            $result[$n] = $this->data[$config][$n];
                        }
                    }
                    return $result;
                } elseif (isset($this->data[$config][$name])) {
                    return $this->data[$config][$name];
                }

                return false;
            }

            return $this->data[$config];
        }
        return false;
    }

    /**
     * 更新成员或赋值
     *
     * @param string $index
     * @param string|array $values
     *
     * @return bool
     */
    function set(string $index, $values = ''): bool
    {
        if (is_array($values)) {
            if (isset($this->data[$index])) {
                $this->data[$index] = array_merge($this->data[$index], $values);
            } else {
                $this->data[$index] = $values;
            }
        } else {
            $this->data[$index] = $values;
        }

        return true;
    }


    /**
     * 数组转对象
     *
     * @param array $data
     *
     * @return object
     */
    static function arrayToObject(array $data)
    {
        return (object)array_map('self::arrayToObject', $data);
    }
}