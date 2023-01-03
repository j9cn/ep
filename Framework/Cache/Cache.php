<?php
/**
 * Created by PhpStorm.
 * User: JH
 * Date: 2017/10/19
 * Time: 7:43
 */
declare(strict_types=1);

namespace EP\Cache;



class Cache
{
    const FILE_TYPE = 1;
    const FILE_TYPE_STATIC = 11;
    const MEMCACHE_TYPE = 2;
    const MEMCACHE_TYPE_STATIC = 21;
    const REDIS_TYPE = 3;
    const REDIS_TYPE_STATIC = 31;

    /**
     * @var RedisCache|Memcache|FileCache
     */
    static $instance;

    /**
     *  RequestCache
     * @param int $cache_type
     * @param array $cache_config
     *
     * @return FileCache|Memcache|RedisCache
     */
    static function factory($cache_type, array $cache_config)
    {
        if (!self::$instance) {
                switch ($cache_type) {

                    case self::MEMCACHE_TYPE:
                    case self::MEMCACHE_TYPE_STATIC:
                        self::$instance = new Memcache($cache_config);
                        break;

                    case self::REDIS_TYPE:
                    case self::REDIS_TYPE_STATIC:
                        self::$instance = new RedisCache($cache_config);
                        break;

                    default :
                        self::$instance = new FileCache($cache_config);
                }
        }

        return self::$instance;
    }
}