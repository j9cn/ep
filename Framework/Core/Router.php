<?php
/**
 * Created by PhpStorm.
 * User: JH
 * Date: 2017/10/10
 * Time: 13:38
 */
declare(strict_types=1);

namespace EP\Core;


use EP\Exception\ELog;
use EP\Http\Request;
use EP\I\RouterInterface;

class Router implements RouterInterface
{
    /**
     * Action名称
     * @var string
     */
    private $action;

    /**
     * 控制器名称
     * @var string
     */
    private $controller;

    /**
     * url参数
     * @var array
     */
    private $params = array();

    /**
     * @var string
     */
    private $uriRequest;

    /**
     * @var string
     */
    private $originUriRequest;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var Delegate
     */
    private $delegate;

    /**
     * @var array
     */
    private $defaultRouter = array();

    /**
     * 默认Action名称
     */
    const DEFAULT_ACTION = 'index';

    /**
     * Router constructor.
     *
     * @param Delegate $delegate
     */
    function __construct(Delegate &$delegate)
    {
        $this->delegate = $delegate;
        $this->config = $delegate->getConfig();
    }

    /**
     * 设置URI字符串
     *
     * @param string $request_string
     *
     * @return $this
     */
    public function setUriRequest($request_string)
    {
        $this->originUriRequest = $request_string;

        $uri = parse_url($request_string);
        $this->uriRequest = $uri['path'];
        if (!empty($uri['query'])) {
            parse_str($uri['query'], $addition_params);
            $_GET += $addition_params;
        }

        return $this;
    }

    /**
     * Router
     * @return $this
     */
    public function getRouter()
    {
        $request = $this->getUriRequest('', $url_config);
        if (!empty($request)) {
            $request = $this->parseRequestString($request, $url_config);
            $this->initRouter($request);
        } else {
            $router = $this->parseDefaultRouter($url_config['*']);
            $this->setController($router[0]);
            $this->setAction($router[1]);
        }
        return $this;
    }

    /**
     * 获取默认控制器
     * @return array
     */
    function getDefaultRouter()
    {
        if (empty($this->defaultRouter)) {
            $url_config = $this->config->get('url');
            $this->parseDefaultRouter($url_config['*']);
        }
        return $this->defaultRouter;
    }

    /**
     * 返回控制器名称
     * @return mixed
     */
    public function getController()
    {
        return $this->controller;
    }

    /**
     * 返回action名称
     * @return mixed
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * 返回参数
     * @return mixed
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * 按类型解析请求字符串
     *
     * @param string $prefix
     * @param array $url_config
     * @param bool $clear_ampersand
     * @param bool $convert_html_entities
     *
     * @return string
     */
    public function getUriRequest(
        $prefix = '/', &$url_config = array(), $clear_ampersand = true, $convert_html_entities = true
    ): string
    {
        $url_config = $this->config->get('url');
        if (!empty($this->uriRequest)) {
            return $this->uriRequest;
        }
        $request = Request::getInstance()->getPathInfo();
        $this->originUriRequest = $request;
        switch ($url_config['type']) {
            case 1:
            case 2:
                break;
            case 3:
                if (!$url_config['rewrite']) {
                    $request = Request::getInstance()->getQueryString();
                    if (isset($request[0]) && $request[0] != '&') {
                        array_shift($_GET);
                    }
                } else {
                    $request_uri = Request::getInstance()->getRequestURI();
                    //rewrite下带问号的请求参数追加到$_GET数组
                    if ($url_config['rewrite'] && $request_uri && false !== strpos($request_uri, '?')) {
                        $query_string = parse_url($request_uri, PHP_URL_QUERY);
                        parse_str($query_string, $addition_params);
                        $_GET += $addition_params;
                        if ($query_string == $request) {
                            $request = '';
                        }
                    }
                }
                if ($clear_ampersand && false !== strpos($request, '&')) {
                    list($request,) = explode('&', $request);
                }
                break;
            default:
                ELog::error('不支持URL解析类型!', 404);
        }

        if ($request) {
            $request = str_replace('@@', '&', urldecode(ltrim($request, '/')));
            if ($convert_html_entities) {
                $request = htmlspecialchars($request, ENT_QUOTES);
            }
        }
        return $prefix . $request;
    }

    /**
     * 解析router别名配置
     *
     * @param array $request
     *
     * @internal param $router
     */
    private function initRouter(array $request)
    {
        $closure = $this->delegate->getClosureContainer();
        if ($closure->has('initRouter')) {
            $request = $closure->run('initRouter', array($request));
        }
        $controller_action = $ori_controller_action = array_shift($request);
        $ori_action = null;
        if (false !== strpos($ori_controller_action, '.')) {
            list($ori_controller, $ori_action) = explode('.', $ori_controller_action);
        } else {
            $ori_controller = $ori_controller_action;
        }

        $controller_action_alias = $this->config->get('router', strtolower($controller_action));
        if ($controller_action_alias) {
            $controller_action = $controller_action_alias;
        }
        if (strpos($controller_action, '.')) {
            list($controller, $action) = explode('.', $controller_action);
            $controller_alias = $this->config->get('router', strtolower($controller));
            if($controller_alias){
                $controller = $controller_alias;
            }
        } else {
            $controller = $controller_action;
            $action = self::DEFAULT_ACTION;
        }
        $this->config->set('ori_router', array(
            'request' => $this->originUriRequest,
            'controller' => $ori_controller,
            'action' => $ori_action,
            'params' => $request
        ));
        $this->setController($controller);
        $this->setAction($action);
        $this->setParams($request);
    }

    /**
     * 将字符串参数解析成数组
     *
     * @param string $query_string
     * @param array $url_config
     *
     * @return array
     */
    private static function parseRequestString($query_string, $url_config)
    {
        $url_suffix = &$url_config['ext'];
        if (isset($url_suffix[0]) && ($url_suffix_length = strlen(trim($url_suffix))) > 0) {
            if (0 === strcasecmp($url_suffix, substr($query_string, -$url_suffix_length))) {
                $query_string = substr($query_string, 0, -$url_suffix_length);
            } else {
                ELog::error('Page not found !', 404);
            }
        }

        $url_dot = &$url_config['dot'];
        if ($url_dot && false !== strpos($query_string, $url_dot)) {
            $router_params = explode($url_dot, $query_string);
            $end_params = array_pop($router_params);
        } else {
            $router_params = array();
            $end_params = $query_string;
        }
        $params_dot = &$url_config['params_dot'];
        if ($params_dot && $params_dot != $url_dot && false !== strpos($end_params, $params_dot)) {
            $params_data = explode($params_dot, $end_params);
            foreach ($params_data as $p) {
                $router_params[] = $p;
            }
        } else {
            $router_params[] = $end_params;
        }
        return $router_params;
    }

    /**
     * 解析默认控制器和方法
     *
     * @param string $default_router
     *
     * @return array
     */
    private function parseDefaultRouter($default_router)
    {
        if (empty($default_router)) {
            ELog::error("未配置有效控制器和方法[{$default_router}]");
        }

        if (empty($this->defaultRouter)) {
            if (false !== strpos($default_router, '.')) {
                list($controller, $action) = explode('.', $default_router);
            } else {
                $controller = $default_router;
                $action = self::DEFAULT_ACTION;
            }

            $this->defaultRouter = array($controller, $action);
        }

        return $this->defaultRouter;
    }

    /**
     * 设置controller
     *
     * @param $controller
     */
    private function setController($controller)
    {
        $this->controller = $controller;
    }

    /**
     * 设置Action
     *
     * @param $action
     */
    private function setAction($action)
    {
        $this->action = $action;
    }

    /**
     * 设置参数
     *
     * @param $params
     */
    private function setParams($params)
    {
        $this->params = $params;
    }
}