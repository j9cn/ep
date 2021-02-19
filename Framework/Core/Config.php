<?php
/**
 * Created by PhpStorm.
 * User: JH
 * Date: 2017/10/9
 * Time: 20:52
 */
declare(strict_types=1);

namespace EP\Core;


class Config
{
    /**
     * @var string
     */
    private $res_file;

    /**
     * @var array
     */
    private $config_data;

    /**
     * @var self
     */
    private static $instance;

    /**
     * @var EPArray
     */
    private $ca;

    /**
     * 查询缓存
     * @var array
     */
    private static $cache;

    /**
     * 读取配置
     * Config constructor.
     *
     * @param string $res_file
     */
    private function __construct(string $res_file)
    {
        $this->res_file = $res_file;
        $this->config_data = Loader::read($res_file);
        $this->ca = EPArray::init($this->config_data, $this->res_file);
    }

    /**
     * 实例化配置类
     *
     * @param string $file
     *
     * @return Config
     */
    static function load($file): self
    {
        if (!isset(self::$instance[$file])) {
            self::$instance[$file] = new self($file);
        }
        return self::$instance[$file];
    }

    /**
     * 合并附加数组到源数组
     *
     * @param array $append_config
     *
     * @return $this
     */
    function combine(array $append_config = array()): self
    {
        if (!empty($append_config)) {
            foreach ($append_config as $key => $value) {
                if (isset($this->config_data[$key]) && is_array($value)) {
                    $this->config_data[$key] = array_merge($this->config_data[$key], $value);
                } else {
                    $this->config_data[$key] = $value;
                }

                $this->clearIndexCache($key);
            }
        }

        return $this;
    }

    /**
     * @see EPArray::get()
     *
     * @param string $index
     * @param string|array $options
     *
     * @return string|array
     */
    function get(string $index, $options = '')
    {
        $key = $this->getIndexCacheKey($index);
        if (is_array($options)) {
            $opk = implode('.', $options);
        } elseif ($options) {
            $opk = $options;
        } else {
            $opk = '-###-';
        }

        if (!isset(self::$cache[$key][$opk])) {
            self::$cache[$key][$opk] = $this->ca->get($index, $options);
        }

        return self::$cache[$key][$opk];
    }

    /**
     * @see EPArray::set()
     *
     * @param string $index
     * @param array|string $values
     *
     * @return bool
     */
    function set(string $index, $values = ''): bool
    {
        $result = $this->ca->set($index, $values);
        $this->clearIndexCache($index);

        return $result;
    }

    /**
     * 返回全部配置数据
     * @return array
     */
    function getAll(): array
    {
        return $this->config_data;
    }

    /**
     * 返回全部配置数据对象
     * @return object
     */
    function getObject()
    {
        return EPArray::arrayToObject($this->config_data);
    }

    /**
     * 获取数组索引缓存key
     *
     * @param string $index
     *
     * @return string
     */
    protected function getIndexCacheKey($index): string
    {
        return $this->res_file . '.' . $index;
    }

    /**
     * 清除缓存
     *
     * @param string $index
     */
    protected function clearIndexCache(string $index)
    {
        $key = $this->getIndexCacheKey($index);
        unset(self::$cache[$key]);
    }
}