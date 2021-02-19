<?php
/**
 * Created by PhpStorm.
 * User: JH
 * Date: 2017/11/27
 * Time: 21:43
 */
declare(strict_types=1);

namespace EP\Cache;


use EP\DB\Drivers\RedisDriver;
use EP\Exception\ELog;
use EP\I\CacheInterface;

class RedisCache extends RedisDriver implements CacheInterface
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
     * 设置缓存key和缓存有效期
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
        $this->expire_time = $option['expire_time'];
    }

    /**
     * 设置request请求
     *
     * @param $key
     * @param $value
     *
     * @return mixed|void
     */
    function set(string $key, $value)
    {
        if (!$key) {
            $key = $this->cache_key;
        }
        $this->link->setex($key, $this->expire_time, $value);
    }

    /**
     * 检查缓存key是否有效
     * @return bool
     */
    function isValid()
    {
        return $this->link->ttl($this->cache_key) > 0;
    }

    /**
     * 返回request的内容
     *
     * @param string $key
     *
     * @return bool|mixed|string
     */
    function get(string $key = '')
    {
        if (!$key) {
            $key = $this->cache_key;
        }

        return $this->link->get($key);
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