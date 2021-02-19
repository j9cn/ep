<?php
/**
 * Created by PhpStorm.
 * User: J.H
 * Date: 2018/5/5
 * Time: 4:55
 */

namespace EP\DB\Drivers;

use EP\Exception\EE;
use EP\Exception\ELog;
use Memcache,Exception;

class MemcacheDriver
{
    /**
     * @var Memcache
     */
    public $memcache;

    function __construct(array $option)
    {
        if (!extension_loaded('memcache')) {
            ELog::error('Not support memcache extension !');
        }

        try {
            $mc = new Memcache();
            $mc->addserver($option['host'], $option['port']);
            $this->memcache = $mc;
        } catch (Exception $e) {
            new EE($e);
        }
    }

    /**
     * 调用Memcache类提供的方法
     *
     * @param $method
     * @param $argv
     * @return mixed|null
     */
    public function __call($method, $argv)
    {
        $result = null;
        if (method_exists($this->memcache, $method)) {
            $result = ($argv == null)
                ? $this->memcache->$method()
                : call_user_func_array(array($this->memcache, $method), $argv);
        }

        return $result;
    }

}