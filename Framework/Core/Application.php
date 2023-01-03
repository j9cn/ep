<?php
/**
 * Created by PhpStorm.
 * User: JH
 * Date: 2017/10/9
 * Time: 11:38
 */
declare(strict_types=1);

namespace EP\Core;


use EP\Cache\{
    FileCache, Memcache, RedisCache, Cache
};
use EP\Exception\{
    EE, ELog, EN
};
use EP\I\RouterInterface;
use ReflectionMethod, ReflectionClass, Exception, Closure;

class Application
{
    /**
     * action 名称
     * @var string
     */
    protected $action;

    /**
     * 运行时的参数
     * @var array
     */
    protected $params = [];

    /**
     * 控制器名称
     * @var string
     */
    protected $controller;

    /**
     * 当前app名称
     * @var string
     */
    private $app_name;

    /**
     * 输出缓冲状态
     * @var bool
     */
    private $ob_cache_status = true;

    /**
     * @var Delegate
     */
    private $delegate;

    private $config;

    /**
     * 实例化Application
     *
     * @param string $app_name
     * @param Delegate $delegate
     */
    function __construct(string $app_name, Delegate &$delegate)
    {
        $this->app_name = $app_name;
        $this->delegate = $delegate;
        $this->config = $delegate->getConfig();
    }

    /**
     * 运行框架
     *
     * @param object|string $router
     * @param array|string $args 指定参数
     * @param bool $return_response_content 是否输出执行结果
     *
     * @return array|mixed|string
     */
    public function dispatcher($router, $args = array(), $return_response_content = false)
    {
        $init_prams = true;
        $router = $this->parseRouter($router, $args, $init_prams);
        $cr = $this->initController($router['controller'], $router['action']);
        $closureContainer = $this->delegate->getClosureContainer();

        $action_params = array();

        if ($init_prams) {
            $this->initParams($router['params'], $action_params);
        } elseif (is_array($router['params'])) {
            $params = $router['params'] + $action_params;
            $this->setParams($params);
        } else {
            $this->setParams($router['params']);
        }

        $closureContainer->run('dispatcher');

        $ca_key = rtrim(strtolower("{$this->getController()}.{$this->getAction()}"), '.');

        $config_basicAuth = $this->config->get('basicAuth', $ca_key);
        if ($config_basicAuth) {
            $this->delegate->getResponse()->basicAuth($config_basicAuth);
        }
        $config_cache = $this->config->get('cache', $ca_key);
        $cache = false;
        $cached = [];
        if ($config_cache) {
            $cache = $this->initRequestCache($config_cache);
            if (false !== $cache) {
                $cached = $cache->getConfig();
            }
        }

        if (false !== $cache && $cache->isValid()) {
            if ($cached['type'] === Cache::FILE_TYPE_STATIC) {
                $response_content = $cache->getStatic();
            } else {
                $response_content = $cache->get();
            }

        } else {
            $action = $this->getAction();
            $controller_name = $this->getController();

            $runtime_config = array(
                'view_controller_namespace' => $this->getViewControllerNameSpace($controller_name),
                'controller' => $controller_name,
                'action' => $action,
                'params' => $this->getParams(),
            );

            $closureContainer->add('~controller~runtime~', function () use (&$runtime_config) {
                return $runtime_config;
            });
            try {
                $cr->setStaticPropertyValue('app_delegate', $this->delegate);
                $controller = $cr->newInstance();
            } catch (Exception $e) {
                new EE($e);
                return false;
            }

            if ($this->delegate->getResponse()->isEndFlush()) {
                return true;
            }

            if ($this->ob_cache_status) {
                ob_start();
                $response_content = $controller->$action();
                if (!$response_content) {
                    $response_content = ob_get_contents();
                }
                ob_end_clean();
            } else {
                $response_content = $controller->$action();
            }

            if ($cache) {
                if (!empty($cached['type']) && $cached['type'] === Cache::FILE_TYPE_STATIC) {
                    $cache->setStatic($cache->getKey(), $response_content);
                } else {
                    $cache->set('', $response_content);
                }
            }
        }

        if ($return_response_content) {
            return $response_content;
        } else {
            $this->delegate->getResponse()->display($response_content);
        }

        return true;
    }

    /**
     * 设置controller
     *
     * @param $controller
     */
    function setController(string $controller)
    {
        $this->controller = $controller;
    }

    /**
     * 设置action
     *
     * @param $action
     */
    function setAction(string $action)
    {
        $this->action = $action;
    }

    /**
     * 设置params
     *
     * @param array|string $params
     */
    function setParams($params)
    {
        $paramsChecker = $this->delegate->getClosureContainer()->has('setParams', $closure);
        if ($paramsChecker && is_array($params)) {
            array_walk($params, $closure);
        } elseif ($paramsChecker) {
            call_user_func($closure, $params);
        }

        $this->params = $params;
    }

    /**
     * 设置控制器结果是否使用输出缓冲
     *
     * @param mixed $status
     */
    public function setObStatus($status)
    {
        $this->ob_cache_status = (bool)$status;
    }

    /**
     * 获取控制器名称
     * @return mixed
     */
    function getController(): string
    {
        return $this->controller;
    }

    /**
     * 获取action名称
     * @return string
     */
    function getAction(): string
    {
        return $this->action;
    }

    /**
     * 获取参数
     * @return mixed
     */
    function getParams(): array
    {
        return $this->params;
    }


    /**
     * 实例化内部类
     * <pre>
     * 判断类中是否包含静态成员变量app_delegate并赋值
     * 主要用于实例化Cross\MVC\Module, Cross\MVC\View命名空间下的派生类
     * 不能实例化控制器, 实例化控制器请调用本类中的get()方法
     * </pre>
     *
     * @param string $class 类名或命名空间
     * @param array $args
     *
     * @return object
     * @throws \ReflectionException
     */
    public function instanceClass($class, $args = array())
    {
        $rc = new ReflectionClass($class);
        if ($rc->hasProperty('app_delegate')) {
            $rc->setStaticPropertyValue('app_delegate', $this->delegate);
        }

        if ($rc->hasMethod('__construct')) {
            if (!is_array($args)) {
                $args = array($args);
            }

            return $rc->newInstanceArgs($args);
        }

        return $rc->newInstance();
    }


    /**
     * 字符类型的参数转换为一个关联数组
     *
     * @param string $stringParams
     * @param string $separator
     *
     * @return array
     */
    public static function stringParamsToAssociativeArray($stringParams, $separator)
    {
        return self::oneDimensionalToAssociativeArray(explode($separator, $stringParams));
    }

    /**
     * 一维数组按顺序转换为关联数组
     *
     * @param array $oneDimensional
     *
     * @return array
     */
    public static function oneDimensionalToAssociativeArray(array $oneDimensional): array
    {
        $result = array();
        while ($p = array_shift($oneDimensional)) {
            $result[$p] = array_shift($oneDimensional);
        }

        return $result;
    }

    /**
     * 解析router
     * <pre>
     * router类型为字符串时, 第二个参数生效
     * 当router类型为数组或字符串时,dispatcher中不再调用initParams()
     * </pre>
     *
     * @param RouterInterface|string $router
     * @param array $params
     * @param bool $init_params
     *
     * @return array
     */
    private function parseRouter($router, $params = array(), &$init_params = true): array
    {
        if ($router instanceof RouterInterface) {
            $controller = $router->getController();
            $action = $router->getAction();
            $params = $router->getParams();
        } elseif (is_array($router)) {
            $init_params = false;
            $controller = $router['controller'];
            $action = $router['action'];
        } else {
            $init_params = false;
            if (strpos($router, '.')) {
                list($controller, $action) = explode('.', $router);
            } else {
                $controller = $router;
                $action = Router::DEFAULT_ACTION;
            }
        }

        return ['controller' => ucfirst($controller), 'action' => $action, 'params' => $params];
    }

    /**
     * 获取控制器的命名空间
     *
     * @param string $controller_name
     *
     * @return string
     */
    protected function getControllerNamespace($controller_name): string
    {
        return 'App\\' . str_replace('/', '\\', $this->app_name) . '\\Controllers\\' . $controller_name;
    }

    /**
     * 默认的视图控制器命名空间
     *
     * @param string $controller_name
     *
     * @return string
     */
    protected function getViewControllerNameSpace($controller_name): string
    {
        return 'App\\' . str_replace('/', '\\', $this->app_name) . '\\Views\\' . $controller_name . 'View';
    }

    /**
     * 初始化控制器
     *
     * @param string $controller 控制器
     * @param string $action 动作
     *
     * @return ReflectionClass
     */
    private function initController(string $controller, string $action = ''): ReflectionClass
    {
        $controller_name_space = $this->getControllerNamespace($controller);
        $_ENV['EP.controller'] = $controller_name_space;
        $_ENV['EP.controllerType'] = 1;
        $_ENV['EP.appName'] = $this->app_name;
        $_ENV['EP.method'] = $action;
        $_ENV['EP.urlReload'] = $this->delegate->getRequest()->getHostInfo() . $this->delegate->getRequest()->getPathInfo();
        try {
            $class_reflection = new ReflectionClass($controller_name_space);
            if ($class_reflection->isAbstract()) {
                ELog::error("{$controller_name_space} 不允许访问的控制器", 404);
            }
        } catch (Exception $e) {
            try {
                Develop::createController();
            } catch (Exception $exception) {
                new EN((array)$exception);
            }
            ELog::error($e->getMessage(), 404);
        }
        $this->setController($controller);

        if ($action) {
            try {
                $is_callable = new ReflectionMethod($controller_name_space, $action);
            } catch (Exception $e) {
                try {
                    $is_callable = new ReflectionMethod($controller_name_space, '__call');
                } catch (Exception $e) {

                    try {
                        Develop::createMethod();
                    } catch (\Throwable $exception) {
                        ELog::error($exception->getMessage());
                    }

                    ELog::error("{$controller_name_space}->{$action} 不能解析的请求", 404);
                }
            }

            if (isset($is_callable) && $is_callable->isPublic() && true !== $is_callable->isAbstract()) {
                $this->setAction($action);
            } else {
                ELog::error("{$controller_name_space}->{$action} 不允许访问的方法", 404);
            }
        }

        return $class_reflection;
    }

    /**
     * 初始化参数
     *
     * @param array|string $url_params
     * @param array $annotate_params
     */
    private function initParams($url_params, array $annotate_params = array())
    {
        $url_type = $this->config->get('url', 'type');
        switch ($url_type) {
            case 2:
            case 3:
                $params = self::oneDimensionalToAssociativeArray($url_params);
                break;
            default:
                $params = [];
        }
        $params = array_merge($_REQUEST, $_COOKIE, $_GET, $params);
        $this->setParams($params);
    }

    /**
     * 初始化请求缓存
     * <pre>
     * request_cache_config 共接受3个参数
     * 1 缓存开关
     * 2 缓存配置数组
     * 3 是否强制开启请求缓存(忽略HTTP请求类型检查)
     * 请求类型验证优先级大于缓存开关
     * 注册匿名函数cpCache可以更灵活的控制请求缓存
     * </pre>
     *
     * @param array $request_cache_config
     *
     * @return bool|Memcache|RedisCache|FileCache
     */
    private function initRequestCache(array $request_cache_config)
    {
        if (empty($request_cache_config['onCache'])) {
            return false;
        }

        if (!empty($request_cache_config['method']) && $request_cache_config['method'] !== $this->delegate->getRequest()->getMethod()) {
            return false;
        }

        if (!isset($request_cache_config['config']) || !is_array($request_cache_config['config'])) {
            ELog::error('请求缓存配置格式不正确');
        }


        $display_type = $this->config->get('sys', 'display');
        $this->delegate->getResponse()->setContentType($display_type);

        $default_cache_config = [
            'type' => 1,
            'expire_time' => 3600,
            'mode' => 'static',
            'cache_params_key' => false,
            'cache_path' => $this->config->get('path', 'cache') . 'request' . DS,
            'key_suffix' => '',
            'ext' => '.cache'
        ];

        $sys_cache_config = [
            'app_name' => lcfirst($this->app_name),
            'tpl_dir_name' => $this->config->get('sys', 'default_tpl_dir'),
            'ca' => strtolower(rtrim("{$this->getController()}.{$this->getAction()}", '.'))
        ];

        $cache_config = array_merge($default_cache_config, $request_cache_config['config'], $sys_cache_config);

        if (Cache::FILE_TYPE == $cache_config['type'] || Cache::FILE_TYPE_STATIC == $cache_config['type']) {
            $cache_config['key_dot'] = DS;
        } else {
            $cache_config['key_dot'] = ':';
        }

        $params_key = [];
        if (!empty($cache_config['cache_params_key'])) {
            sort($cache_config['cache_params_key'], SORT_STRING);
            foreach ($cache_config['cache_params_key'] as $key) {
                $param = '';
                if (isset($this->params[$key])) {
                    $param = $this->params[$key];
                }
                $params_key[$key] = "{$key}=" . ($param ?? '-');
            }
        }
        $cache_key = $sys_cache_config + $params_key;
        $cache_config['key'] = implode($cache_config['key_dot'], (array)$cache_key) . $cache_config['key_suffix'];

        return Cache::factory($cache_config['type'], $cache_config);
    }

    /**
     * 设置Response
     *
     * @param array $config
     */
    private function setResponseConfig(array $config)
    {
        if (isset($config['content_type'])) {
            $this->delegate->getResponse()->setContentType($config['content_type']);
        }

        if (isset($config['status'])) {
            $this->delegate->getResponse()->setStatus($config['status']);
        }
    }

    /**
     * 调用依赖控制器实例的匿名函数
     *
     * @param Closure $closure
     * @param $controller
     */
    private function callReliesControllerClosure(Closure $closure, $controller)
    {
        $closure($controller);
    }

    /**
     * 设置action注释
     *
     * @param array $annotate
     * @param array $controller_annotate
     */
    private function setAnnotateConfig(array $annotate, array $controller_annotate)
    {
        if (empty($controller_annotate)) {
            $this->action_annotate = $annotate;
        } else {
            $this->action_annotate = array_merge($controller_annotate, $annotate);
        }
    }
}