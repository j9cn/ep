<?php
/**
 * Created by PhpStorm.
 * User: J.H
 * Date: 2018/5/5
 * Time: 21:40
 */

namespace EP\Core;

use Closure, ReflectionFunction, ReflectionException;
use EP\Exception\{
    EE, ELog
};
use EP\Http\Request;

class Rest
{
    /**
     * @var array
     */
    protected $rules;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var Delegate
     */
    protected $delegate;

    /**
     * @var string
     */
    protected $request_type;

    /**
     * @var string
     */
    protected $request_string;

    /**
     * @var array
     */
    protected $custom_router_config = array();

    /**
     * @var Rest
     */
    private static $instance;

    /**
     * 初始化request
     *
     * @param Delegate $delegate
     */
    private function __construct(Delegate &$delegate)
    {
        $this->delegate = $delegate;
        $this->request = $delegate->getRequest();
        $this->request_type = $this->getRequestType();
        $this->request_string = $delegate->getRouter()->getUriRequest('/', $useless, false, false);
    }

    /**
     * 创建rest实例
     *
     * @param Delegate $delegate
     *
     * @return Rest
     */
    static function getInstance(Delegate &$delegate)
    {
        if (!self::$instance) {
            self::$instance = new Rest($delegate);
        }

        return self::$instance;
    }

    /**
     * GET
     *
     * @param string $custom_router
     * @param callable|Closure $process_closure
     */
    function get($custom_router, Closure $process_closure)
    {
        $this->addCustomRouter('get', $custom_router, $process_closure);
    }

    /**
     * POST
     *
     * @param string $custom_router
     * @param callable|Closure $process_closure
     */
    function post($custom_router, Closure $process_closure)
    {
        $this->addCustomRouter('post', $custom_router, $process_closure);
    }

    /**
     * PUT
     *
     * @param string $custom_router
     * @param callable|Closure $process_closure
     */
    function put($custom_router, Closure $process_closure)
    {
        $this->addCustomRouter('put', $custom_router, $process_closure);
    }

    /**
     * DELETE
     *
     * @param string $custom_router
     * @param callable|Closure $process_closure
     */
    function delete($custom_router, Closure $process_closure)
    {
        $this->addCustomRouter('delete', $custom_router, $process_closure);
    }

    /**
     * GET, POST, PUT, DELETE
     *
     * @param string $custom_router
     * @param callable|Closure $process_closure
     */
    function any($custom_router, Closure $process_closure)
    {
        $this->addCustomRouter($this->request_type, $custom_router, $process_closure);
    }

    /**
     * @see Delegate::on()
     *
     * @param string $name
     * @param Closure $f
     */
    function on($name, Closure $f)
    {
        $this->delegate->on($name, $f);
    }

    /**
     * 参数正则验证规则
     *
     * @param array $rules
     */
    function rules(array $rules)
    {
        $this->rules = $rules;
    }

    /**
     * 处理请求
     */
    function run()
    {
        $match = false;
        $params = array();
        $process_closure = null;
        if (!empty($this->custom_router_config[$this->request_type])) {
            $custom_routers = $this->custom_router_config[$this->request_type];
            if (!empty($custom_routers['high']) && isset($custom_routers['high'][$this->request_string])) {
                $match = true;
                $process_closure = $custom_routers['high'][$this->request_string];
            } elseif (!empty($custom_routers['current'])) {
                $match = $this->matchProcess($custom_routers['current'], $process_closure, $params);
            } elseif (!empty($custom_routers['global'])) {
                $match = $this->matchProcess($custom_routers['global'], $process_closure, $params);
            }
        }

        if ($match && $process_closure !== null) {
            $this->response($process_closure, $params);
        } else {
            $closure_container = $this->delegate->getClosureContainer();
            if ($closure_container->has('mismatching')) {
                $closure_container->run('mismatching');
            } else {
                ELog::error('Not match uri', 404);
            }
        }
    }

    /**
     * 循环匹配(参数多的优先)
     *
     * @param array $routers
     * @param $process_closure
     * @param array $params
     *
     * @return bool
     */
    private function matchProcess(array $routers, & $process_closure, & $params)
    {
        uasort($routers, function ($a, $b) {
            return $a['params_count'] < $b['params_count'];
        });

        foreach ($routers as $router => $router_config) {
            $params = array();
            if (true === $this->matchCustomRouter($router, $router_config['params_key'], $params)) {
                $process_closure = $router_config['process_closure'];
                return true;
            }
        }

        return false;
    }

    /**
     * 匹配uri和自定义路由
     *
     * @param string $custom_router
     * @param array $params_keys
     * @param array $params
     *
     * @return bool
     */
    private function matchCustomRouter($custom_router, array $params_keys = array(), array & $params = array())
    {
        $request_uri_string = $this->request_string;
        $custom_router_params_token = preg_replace("/\{:(.*?)\}/", '{PARAMS}', $custom_router);
        while (strlen($custom_router_params_token) > 0) {
            $defined_params_pos = strpos($custom_router_params_token, '{PARAMS}');
            if ($defined_params_pos) {
                $compare_ret = substr_compare($custom_router_params_token, $request_uri_string, 0, $defined_params_pos);
            } else {
                $compare_ret = strcmp($custom_router_params_token, $request_uri_string);
            }

            if ($compare_ret !== 0) {
                return false;
            }

            //分段解析
            $custom_router_params_token = substr($custom_router_params_token, $defined_params_pos + 8);
            $request_uri_string = substr($request_uri_string, $defined_params_pos);

            if ($custom_router_params_token) {
                //下一个标识符的位置
                $next_defined_dot_pos = strpos($request_uri_string, $custom_router_params_token[0]);
                $params_value = substr($request_uri_string, 0, $next_defined_dot_pos);
                $request_uri_string = substr($request_uri_string, $next_defined_dot_pos);
            } else {
                $params_value = $request_uri_string;
            }

            $key_name = array_shift($params_keys);
            if ($key_name && isset($this->rules[$key_name]) && !preg_match($this->rules[$key_name], $params_value)) {
                return false;
            }

            if ($key_name) {
                $params[$key_name] = $params_value;
            }
        }

        return true;
    }

    /**
     * 输出结果
     *
     * @param Closure $process_closure
     * @param array $params
     */
    private function response(Closure $process_closure, array $params = array())
    {
        $closure_params = array();
        try {
            $ref = new ReflectionFunction($process_closure);
            $parameters = $ref->getParameters();
        } catch (ReflectionException $e) {
            new EE($e, $e->getMessage(), 404);
        }

        if (!empty($parameters)) {
            foreach ($parameters as $p) {
                if (!isset($params[$p->name])) {
                    ELog::error("未指定的参数: {$p->name}", 404);
                }
                $closure_params[$p->name] = $params[$p->name];
            }
        }

        $content = call_user_func_array($process_closure, $closure_params);
        if (null != $content) {
            $this->delegate->getResponse()->display($content);
        }
    }

    /**
     * 解析自定义路由并保存参数key
     *
     * @param string $request_type
     * @param string $custom_router
     * @param Closure $process_closure
     */
    private function addCustomRouter($request_type, $custom_router, Closure $process_closure)
    {
        if ($this->request_type === $request_type) {
            $custom_router = trim($custom_router);
            $is_contain_params = preg_match_all("/(.*?)\{:(.*?)\}/", $custom_router, $params_keys);
            if ($is_contain_params) {
                $prefix_string_length = strlen($params_keys[1][0]);
                $compare = substr_compare($this->request_string, $custom_router, 0, $prefix_string_length);
                if ($compare === 0) {
                    $level = 'current';
                    if ($prefix_string_length == 1) {
                        $level = 'global';
                    }

                    $this->custom_router_config[$request_type][$level][$custom_router] = array(
                        'process_closure' => $process_closure,
                        'params_count' => count($params_keys[2]),
                        'params_key' => $params_keys[2],
                    );
                }
            } else {
                $this->custom_router_config[$request_type]['high'][$custom_router] = $process_closure;
            }
        }
    }

    /**
     * 获取当前请求类型
     * @return string
     */
    private function getRequestType()
    {
        $request_type = 'get';
        if ($this->request->isPostRequest()) {
            $request_type = 'post';
        } elseif ($this->request->isPutRequest()) {
            $request_type = 'put';
        } elseif ($this->request->isDeleteRequest()) {
            $request_type = 'delete';
        }

        return $request_type;
    }
}