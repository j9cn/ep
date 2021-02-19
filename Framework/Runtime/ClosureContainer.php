<?php
/**
 * Created by PhpStorm.
 * User: JH
 * Date: 2017/10/8
 * Time: 20:16
 */
declare(strict_types=1);

namespace EP\Runtime;

use Closure;

class ClosureContainer
{
    /**
     * @var array
     */
    protected $actions;

    /**
     * 注册一个匿名方法
     *
     * @param string $name
     * @param Closure $f
     */
    function add(string $name, Closure $f)
    {
        $this->actions[$name] = $f;
    }

    /**
     * 执行指定的匿名方法
     *
     * @param string $name
     * @param array $params
     *
     * @return mixed
     */
    function run(string $name, array $params = array())
    {
        if (isset($this->actions[$name])) {
            return call_user_func_array($this->actions[$name], $params);
        }
        return false;
    }

    /**
     * 执行指定的匿名方法并缓存执行结果
     *
     * @param string $name
     * @param array $params
     *
     * @return mixed
     */
    function runOnce(string $name, array $params = array())
    {
        static $cache = array();
        if (isset($cache[$name])) {
            return $cache[$name];
        } elseif (isset($this->actions[$name])) {
            if (!is_array($params)) {
                $params = array($params);
            }

            $cache[$name] = call_user_func_array($this->actions[$name], $params);
            return $cache[$name];
        }

        return false;
    }

    /**
     * 检查指定的匿名方法是否已经注册
     *
     * @param string $name
     * @param Closure|null $closure
     *
     * @return bool
     */
    function has(string $name, &$closure = null): bool
    {
        if (isset($this->actions[$name])) {
            $closure = $this->actions[$name];
            return true;
        }

        return false;
    }
}