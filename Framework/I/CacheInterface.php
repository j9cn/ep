<?php
/**
 * Created by PhpStorm.
 * User: JH
 * Date: 2017/10/11
 * Time: 11:11
 */

namespace EP\I;


interface CacheInterface
{
    /**
     * @param string $key
     * @param $value
     * @return mixed set
     */
    function set(string $key, $value);

    /**
     * @param string $key
     * @return mixed get cache
     */
    function get(string $key);

    /**
     * 是否有效
     *
     * @return bool
     */
    function isValid();

    /**
     * 缓存配置
     *
     * @param array $config
     *
     */
    function setConfig(array $config = array());

    /**
     * 获取缓存配置
     *
     * @return mixed
     */
    function getConfig();
}