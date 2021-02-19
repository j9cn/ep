<?php
/**
 * Created by PhpStorm.
 * User: J.H
 * Date: 2018/5/5
 * Time: 4:54
 */

namespace EP\Cache;


use EP\DB\Drivers\MemcacheDriver;
use EP\Exception\ELog;
use EP\I\CacheInterface;

class Memcache extends MemcacheDriver implements CacheInterface
{

    /**
     * @var array
     */
    protected $config;

    /**
     * 缓存key
     * @var string
     */
    protected $cache_key;

    /**
     * 有效时间
     * @var int
     */
    protected $expire_time;

    /**
     * @var array
     */
    static private $value_cache = [];

    /**
     * 初始化key和过期时间
     *
     * @param array $option
     */
    function __construct(array $option)
    {
        parent::__construct($option);
        $this->setConfig($option);
        if (empty($option['key']) || empty($option['expire_time'])) {
            ELog::error('请指定缓存key和过期时间');
        }

        $this->cache_key = $option['key'];
        $this->expire_time = time() + min(2592000, (int)$option['expire_time']);
    }

    /**
     * 获取request缓存
     *
     * @param string $key
     *
     * @return mixed
     */
    function get(string $key = '')
    {
        if (isset(self::$value_cache[$key])) {
            return self::$value_cache[$key];
        }

        if (!$key) {
            $key = $this->cache_key;
        }

        return $this->memcache->get($key);
    }

    /**
     * 设置request缓存
     *
     * @param string $key
     * @param mixed $value
     *
     * @return mixed set
     */
    function set(string $key, $value)
    {
        if (!$key) {
            $key = $this->cache_key;
        }
        return $this->memcache->set($key, $value, MEMCACHE_COMPRESSED, $this->expire_time);
    }

    /**
     * 查看key是否有效
     * @return bool
     */
    function isValid()
    {
        if (isset(self::$value_cache[$this->cache_key])) {
            return true;
        }

        $value = $this->memcache->get($this->cache_key);
        if (!empty($value)) {
            self::$value_cache[$this->cache_key] = $value;
            return true;
        }

        return false;
    }

    /**
     * 设置配置
     *
     * @param array $config
     */
    function setConfig(array $config = array())
    {
        $this->config = $config;
    }

    /**
     * 获取缓存配置
     * @return mixed
     */
    function getConfig()
    {
        return $this->config;
    }
}