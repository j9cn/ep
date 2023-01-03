<?php
/**
 * Created by PhpStorm.
 * User: JH
 * Date: 2017/10/19
 * Time: 7:58
 */
declare(strict_types=1);

namespace EP\Cache;


use EP\Exception\ELog;
use EP\I\CacheInterface;
use EP\Core\Helper;

class FileCache implements CacheInterface
{

    private $config;

    private $cache_file;

    private $expire_time = 3600;

    /**
     * FileCache constructor.
     *
     * @param array $config
     */
    function __construct(array $config)
    {
        $this->setConfig($config);
        if (empty($config['cache_path']) || empty($config['key'])) {
            ELog::error('请指定缓存文件路径[cache_path]和缓存[key]');
        }

        $this->cache_file = "{$config['cache_path']}{$config['key']}{$config['ext']}";
        $this->expire_time = isset($config['expire_time']) ? $config['expire_time'] : 3600;
    }

    /**
     * 如果缓存文件不存在则创建
     */
    function init()
    {
        if (!file_exists($this->cache_file)) {
            Helper::mkFile($this->cache_file);
        }
    }

    function getKey()
    {
        return $this->cache_file;
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

    function getConfig()
    {
        return $this->config;
    }

    function isValid()
    {
        if (!file_exists($this->cache_file)) {
            return false;
        } elseif ((TIME - filemtime($this->cache_file)) < $this->expire_time) {
            return true;
        }
        return false;
    }

    /**
     * 生成cache文件
     *
     * @param $key
     * @param $data
     * @param int $expire
     *
     * @return bool
     */
    function set(string $key, $data, $expire = 0)
    {
        $this->init();
        return false !== file_put_contents($this->cache_file, $this->cacheFormat($data, $expire), LOCK_EX);
    }

    function setStatic(string $cache_file, string $value)
    {
        if (!file_exists($cache_file)) {
            $this->init();
        }
        $str = str_replace(PHP_EOL, '', $value);
        $str = preg_replace('/>\s+</', '><', $str);
        file_put_contents($cache_file, $str, LOCK_EX);
    }

    /**
     * 读入cache文件
     * @param string $key
     *
     * @return bool|mixed
     */
    function get(string $key = '')
    {
        if (!is_file($this->cache_file)) {
            return false;
        }
        $cache = require "{$this->cache_file}";
        if (isset($cache['EX_TIME']) && ($cache['EX_TIME'] == 0 || $cache['EX_TIME'] > $_SERVER['REQUEST_TIME'])) {
            if (isset($cache['DATA'])) {
                return $cache['DATA'];
            }
        }
        return false;
    }

    function getStatic()
    {
        if (file_exists($this->cache_file)) {
            return file_get_contents($this->cache_file);
        }
        return false;
    }


    /**
     * 设置cache格式
     *
     * @param $data
     * @param $expire
     *
     * @return string
     */
    private function cacheFormat($data, $expire)
    {
        $f = <<<ptl
<?php
return array(
    'DATA' => %s,
    'EX_TIME' => %u
);
ptl;
        return sprintf($f, var_export($data, true), $expire === 0 ? $expire : time() + $expire);
    }
}