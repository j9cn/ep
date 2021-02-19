<?php
/**
 * Created by PhpStorm.
 * User: JH
 * Date: 2017/10/11
 * Time: 9:37
 */
declare(strict_types=1);

namespace EP\Core;

use EP\Exception\EE;
use EP\Exception\ELog;
use EP\Exception\EPE;
use EP\Http\Response;
use EP\Http\Request;
use EP\Library\Helper\Cookies;
use EP\Library\Helper\Sessions;
use EP\Library\Helper\Validator;
use EP\MVC\Model;
use EP\MVC\View;
use Throwable, Exception;

/**
 * Class FrameBase
 * @package EP\Core
 * @property Config $config
 * @property Request $request
 * @property Response $response
 * @property View $view
 * @property Cookies $cookie
 * @property Sessions $session
 * @property Validator $valid
 * @property \Redis $redis
 */
class FrameBase
{
    /**
     * action名称
     * @var string
     */
    protected $action;

    /**
     * 参数列表
     * @var array
     */
    protected $params;
    protected $s_params;

    /**
     * 控制器名称
     * @var string
     */
    protected $controller;

    /**
     * @var Delegate
     */
    protected $delegate;

    /**
     * 视图控制器命名空间
     * @var string
     */
    protected $view_controller;

    /**
     * 当前方法的注释配置
     * @var array
     */
    protected $action_annotate;

    /**
     * @var Delegate
     */
    public static $app_delegate;

    private static $url_config_cache = [];

    /**
     * 数据
     * @var array
     */
    protected $data = [];
    /**
     * 视图数据，当设置此数据，优先于控制器数据
     * @var array
     */
    protected $view_data = [];

    public function __construct()
    {
        $this->delegate = self::$app_delegate;
        $runtime_config = $this->delegate->getClosureContainer()->run('~controller~runtime~');

        $this->view_controller = &$runtime_config['view_controller_namespace'];
        $this->action_annotate = &$runtime_config['action_annotate'];
        $this->controller = &$runtime_config['controller'];
        $this->action = &$runtime_config['action'];
        $this->params = &$runtime_config['params'];
    }

    /**
     * @return Config
     */
    function getConfig()
    {
        return $this->delegate->getConfig();
    }

    /**
     * @return Delegate
     */
    function getDelegate()
    {
        return $this->delegate;
    }

    /**
     * 读取配置文件
     *
     * @param string $config_file
     * @param string $file_ext
     *
     * @return Config
     */
    function loadConfig($config_file, $file_ext = '.config.php')
    {
        return Config::load($this->config->get('path', 'config') . $config_file . $file_ext);
    }

    /**
     * @see Loader::read()
     *
     * @param string $name
     * @param bool $get_file_content
     *
     * @return mixed
     */
    function parseGetFile($name, $get_file_content = false)
    {
        return Loader::read($this->getFilePath($name), $get_file_content);
    }

    /**
     * 解析文件路径
     * <pre>
     *  格式如下:
     *  1 ::[path/file_name] 从当前项目根目录查找
     *  2 app::[path/file_name] 当前app路径
     *  3 static::[path/file_name] 静态资源目录
     *  4 cache::[path/file_name] 缓存路径
     *  5 config::[path/file_name] 配置路径
     * </pre>
     *
     * @param string $name
     *
     * @return string
     */
    function getFilePath($name)
    {
        $prefix_name = 'project';
        if (false !== strpos($name, '::')) {
            list($prefix_name, $file_name) = explode('::', $name);
            if (!empty($prefix_name)) {
                $prefix_name = strtolower(trim($prefix_name));
            }
        } else {
            $file_name = $name;
        }

        static $cache = null;
        if (!isset($cache[$prefix_name])) {
            switch ($prefix_name) {
                case 'app':
                    $prefix_path = $this->config->get('app', 'path');
                    break;

                case 'cache':
                case 'config':
                    $prefix_path = $this->config->get('path', $prefix_name);
                    break;

                case 'static':
                    $prefix_path = $this->config->get('static', 'path');
                    break;

                default:
                    $prefix_path = PROJECT_REAL_PATH;
            }
            $cache[$prefix_name] = rtrim($prefix_path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        }

        return $cache[$prefix_name] . str_replace('/', DIRECTORY_SEPARATOR, $file_name);
    }

    /**
     * 生成url
     *
     * @param null|string $controller
     * @param null|string|array $params
     * @param bool $encrypt_params
     *
     * @return string
     */
    function url($controller = null, $params = [], $encrypt_params = false)
    {
        static $link_base = null;
        if ($link_base === null) {
            $link_base = $this->config->get('url', 'full_request');
        }

        if (($controller === null || $controller === '/') && empty($params)) {
            return $link_base;
        } else {
            if (0 === strpos($controller, ':')) {
                list(, $action) = explode(':', $controller, 2);
                if (':' === $action) {
                    $action = $this->action;
                }
                if (0 === strcasecmp('index', $action)) {
                    $action = '';
                }
                $controller = rtrim("{$this->controller}.{$action}", '.');
            }
            $uri = $this->makeUri($this->delegate->app_name, false, $controller, $params, $encrypt_params);
            return $link_base . $uri;
        }
    }

    /**
     *  生成加密连接
     * @see FrameBase::url()
     *
     * @param null $controller
     * @param null $params
     *
     * @return string
     */
    function sUrl($controller = null, $params = null)
    {
        return $this->url($controller, $params, true);
    }

    /**
     * 生成指定app,指定控制器的url
     *
     * @param string $base_link
     * @param string $app_name
     * @param null|string $controller
     * @param null|string|array $params
     * @param null|bool $encrypt_params
     *
     * @return string
     */
    function appUrl($base_link, $app_name, $controller = null, $params = null, $encrypt_params = null)
    {
        $base_link = rtrim($base_link, '/') . '/';
        if ($controller === null && $params === null) {
            return $base_link;
        } else {
            $uri = $this->makeUri($app_name, true, $controller, $params, $encrypt_params);
            return $base_link . $uri;
        }
    }

    /**
     * 生成连接
     *
     * @param string $app_name
     * @param bool $check_app_name
     * @param null|string $controller
     * @param null|array $params
     * @param null|bool $encrypt_params
     *
     * @return string
     */
    protected function makeUri($app_name, $check_app_name, $controller = null, $params = null, $encrypt_params = null)
    {
        $uri = '';
        $enable_controller_cache = false;
        $app_name = ucfirst($app_name);
        //在运行过程中,如果url的配置有变化,需要调用cleanLinkCache()来刷新缓存
        if (!isset(self::$url_config_cache[$app_name])) {
            $this_app_name = $app_name;
            if ($check_app_name) {
                $this_app_name = $this->delegate->app_name;
            }

            if ($check_app_name && $app_name != $this_app_name) {
                $app_config = Loader::read(APP_PATH_DIR . 'initConfig' . DS . $app_name . '.init.php');
                $config = array_merge_recursive($this->config->getAll(), $app_config);
                $url_router_config = array(
                    'url' => $this->e($config, 'url', []),
                    'router' => $this->e($config, 'router', [])
                );
            } else {
                $url_router_config = array(
                    'url' => $this->config->get('url'),
                    'router' => $this->config->get('router')
                );
            }
            self::$url_config_cache[$app_name] = $url_router_config;
        } else {
            $enable_controller_cache = true;
            $url_router_config = self::$url_config_cache[$app_name];
        }

        $url_params = '';
        $has_controller_string = true;
        $url_controller = $this->makeControllerUri($app_name, $enable_controller_cache, $controller,
            $url_router_config);
        if ($url_controller === '') {
            $has_controller_string = false;
        }

        $url_config = &$url_router_config['url'];
        if (!empty($params)) {
            $url_params = $this->makeParams($params, $url_config, $encrypt_params, $has_controller_string);
        }

        if (!empty($url_config['ext'])) {
            switch ($url_config['type']) {
                case 1:
                    if ($has_controller_string) {
                        $uri .= $url_controller . $url_config['ext'];
                    }

                    $uri .= $url_params;
                    break;
                case 2:
                case 3:
                    $uri .= $url_controller . $url_params . $url_config['ext'];
                    break;
            }
        } else {
            $uri .= $url_controller . $url_params;
        }
        return $uri;
    }

    /**
     * 生成控制器连接
     *
     * @param string $app_name
     * @param bool $use_cache 是否使用缓存
     * @param string $controller
     * @param array $url_config
     *
     * @return string
     */
    private function makeControllerUri($app_name, $use_cache, $controller, array $url_config)
    {
        static $path_cache;
        if (isset($path_cache[$app_name][$controller]) && $use_cache) {
            return $path_cache[$app_name][$controller];
        }

        $app_alias_config = $this->parseControllerAlias($app_name, $url_config['router']);
        if (isset($app_alias_config[$controller])) {
            $real_controller = $app_alias_config[$controller];
        } else {
            $real_controller = $controller;
        }

        $action_name = null;
        if ($real_controller && (false !== strpos($real_controller, '.'))) {
            list($controller_name, $action_name) = explode('.', $real_controller);
        } else {
            $controller_name = $real_controller;
        }

        $url = &$url_config['url'];
        $index = $this->makeIndex($url, true);
        $controller_path = $index . $controller_name;
        if (null !== $action_name) {
            $controller_path .= ".{$action_name}";
        }

        $path_cache[$app_name][$controller] = $controller_path;
        return $controller_path;
    }

    /**
     * 解析路由别名配置
     *
     * @param string $app_name
     * @param array $router
     *
     * @return array
     */
    private function parseControllerAlias($app_name, array $router)
    {
        static $router_alias_cache;
        if (!isset($router_alias_cache[$app_name])) {
            $router_alias_cache[$app_name] = array();
            if (!empty($router)) {
                foreach ($router as $controller_alias => $alias_config) {
                    $router_alias_cache[$app_name][$alias_config] = $controller_alias;
                }
            }
        }

        return $router_alias_cache[$app_name];
    }

    /**
     * 生成URL中的索引部分
     *
     * @param array $url_config
     * @param bool $have_controller
     *
     * @return string
     */
    private function makeIndex(array $url_config, $have_controller = false)
    {
        static $cache = array();
        if (isset($cache[$have_controller])) {
            return $cache[$have_controller];
        }

        $index = $url_config['index'];
        $is_default_index = (0 === strcasecmp($index, 'index.php'));
        $index_dot = $addition_dot = '';
        switch ($url_config['type']) {
            case 3:
                $index_dot = '?';
                $addition_dot = '/';
                if ($is_default_index) {
                    $index = '';
                }
                break;

            case 1:
            case 2:
                $index_dot = '/';
                $addition_dot = '';
                break;

            default:
                ELog::error("不支持生成索引类型[{$url_config['type']}]");
        }

        if ($url_config['rewrite']) {
            $index = $index_dot = $addition_dot = '';
        }
        $virtual_path = &$url_config['virtual_path'];
        if ($have_controller) {
            $index .= $index_dot . $addition_dot;
            if ($virtual_path) {
                $index .= $virtual_path . '.';
            }
        } else {
            if ($is_default_index) {
                $index = '';
            }

            if ($virtual_path) {
                $index .= $index_dot . $addition_dot . $virtual_path;
                if ($url_config['ext']) {
                    $index .= $url_config['ext'];
                }
            }
        }

        $cache[$have_controller] = $index;
        return $index;
    }

    /**
     * 生成uri参数字符串
     *
     * @param array $params 当url_type的值不为2时, 值必须是标量(bool型需要在外部转换为int型)
     * @param array $url_config
     * @param bool $encrypt_params
     * @param bool $add_prefix_dot 当控制器字符串为空时,参数不添加前缀
     *
     * @return string
     */
    protected function makeParams(array $params, array $url_config, $encrypt_params = false, $add_prefix_dot = true)
    {
        $params_dot = $url_dot = &$url_config['dot'];
        if ($params_dot) {
            $dot = $params_dot;
        } else {
            $dot = $url_dot;
        }

        $url_params = $hash = '';
        if ($params) {
            switch ($url_config['type']) {
                case 2:
                case 3:
                    if (!$dot) {
                        $dot = '/';
                    }

                    foreach ($params as $key => $param) {
                        if ('#' !== $key) {
                            $url_params .= $key . $dot . str_replace('&', '@@', $param) . $dot;
                        } else {
                            $hash = "#{$param}";
                        }
                    }

                    $url_params = trim($url_params, $dot);
                    break;

                default:
                    $url_dot = '?';
                    $add_prefix_dot = true;
                    if (!empty($params['#'])) {
                        $hash = "#{$params['#']}";
                        unset($params['#']);
                    }
                    $url_params = http_build_query($params);
            }

            if (true === $encrypt_params) {
                $url_params = $this->urlEncrypt($url_params);
            }
        }

        if ($add_prefix_dot) {
            return $url_dot . $url_params . $hash;
        }

        return $url_params . $hash;
    }

    /**
     * 安全的返回数组中的值
     *
     * @param array $data
     * @param string|int $key
     * @param string $default_value
     * @param bool $use_cache
     *
     * @return mixed
     */
    function e(array $data, $key, $default_value = '', $use_cache = false)
    {
        static $cache;
        $data_key = '0';
        if ($use_cache) {
            $data_key = md5(json_encode($data));
            if (isset($cache[$data_key][$key])) {
                return $cache[$data_key][$key];
            }
        }
        if (!is_array($key)) {
            if (strpos($key, '.')) {
                $key_path = explode('.', $key);
            } else {
                $key_path = array($key);
            }
        } else {
            $key_path = $key;
        }
        foreach ($key_path as $k) {
            if (isset($data[$k])) {
                $data = $data[$k];
            } else {
                return $cache[$data_key][$key] = $default_value;
            }
        }
        return $cache[$data_key][$key] = $data;
    }

    /**
     * uri参数加密
     *
     * @param string $params
     * @param string $type
     *
     * @return bool|string
     */
    function urlEncrypt($params, $type = 'encode')
    {
        $result = '';
        static $key_cache;
        $key = $this->getUrlEncryptKey('uri');
        if (!isset($key_cache[$key])) {
            $key_cache[$key] = md5($key);
        }

        $key = $key_cache[$key];
        if ($type == 'encode') {
            $params = (string)$params;
        } else {
            //校验数据完整性
            //省略校验要解密的参数是否是一个16进制的字符串
            $str_head = substr($params, 0, 5);
            $params = substr($params, 5);
            if ($str_head != substr(md5($params . $key), 9, 5)) {
                return $result;
            }

            $params = pack('H*', $params);
        }

        if (!$params) {
            return $result;
        }

        for ($str_len = strlen($params), $i = 0; $i < $str_len; $i++) {
            $result .= chr(ord($params[$i]) ^ ord($key[$i % 32]));
        }

        if ($type == 'encode') {
            $result = bin2hex($result);
            $result = substr(md5($result . $key), 9, 5) . $result;
        }
        return $result;
    }

    function getParams($key = '', $encrypt = false)
    {
        if (false === $encrypt) {
            $params = $this->params;
        } else {

            $url_config = $this->config->get('url');
            $ori_params = $this->params;
            switch ($url_config['type']) {
                case 1:
                case 2:
                    $params = current(array_keys($ori_params));
                    break;

                default:
                    if (is_array($ori_params) && !$url_config['rewrite']) {
                        array_shift($ori_params);
                        $params = current(array_keys($ori_params));
                    } else {
                        $params = current(array_keys($ori_params));
                    }
            }
            if (is_string($params)) {
                $decode_params_str = $this->urlEncrypt($params, 'decode');
                if ($decode_params_str) {
                    switch ($url_config['type']) {
                        case 1:
                            parse_str($decode_params_str, $params);
                            $this->params = $params;
                            break;
                        default:
                            $this->params = $params = Application::stringParamsToAssociativeArray($decode_params_str,
                                $url_config['params_dot']);
                    }
                }
            }

        }

        if ('' === $key) {
            return $params;
        } elseif (isset($params[$key])) {
            return $params[$key];
        }
        return '';
    }

    /**
     * 获取uri加密/解密时用到的key
     *
     * @param string $type
     *
     * @return string
     */
    private function getUrlEncryptKey($type = 'auth')
    {
        $encrypt_key = $this->config->get('encrypt', $type);
        if (empty($encrypt_key)) {
            $encrypt_key = '<(￣oo,￣)/';
        }

        return $encrypt_key;
    }

    /**
     * 初始化视图控制器
     * @return mixed
     */
    protected function initView()
    {
        try {
            $view = new $this->view_controller();
            $view->data = !empty($this->view_data) ? $this->view_data : $this->data;
            $view->config = $this->getConfig();
            $view->params = $this->params;
        } catch (Throwable $e) {
            try {
                $_ENV['EP.controller'] = $this->view_controller;
                $_ENV['EP.controllerType'] = 2;
                Develop::createController();
            } catch (Throwable $exception) {
                ELog::error($exception->getMessage());
            }
            ELog::error($e->getMessage());
            return false;
        }
        return $view;
    }

    protected function getView()
    {
        static $view;
        if (!$view) {
            $view = new View();
        }
        return $view;
    }

    /**
     * @param string $key
     *
     * @return Cookies
     */
    protected function initCookies($key = '')
    {
        if ('' === $key) {
            $key = $this->config->get('encrypt', 'cookie');
        }
        return new Cookies($key);
    }

    protected function initValid(array $data)
    {
        return new Validator($data);
    }

    /**
     * request response view
     *
     * @param string $property
     *
     * @return Response|Request|View|Config|Cookies|Sessions|Validator|null
     */
    function __get($property)
    {
        switch ($property) {
            case 'config':
                return $this->config = $this->delegate->getConfig();

            case 'request' :
                return $this->request = $this->delegate->getRequest();

            case 'response' :
                return $this->response = $this->delegate->getResponse();

            case 'view' :
                return $this->view = $this->initView();

            case 'cookie':
                return $this->cookie = $this->initCookies();

            case 'session':
                return $this->session = new Sessions();

            case 'valid':
                return $this->valid = $this->initValid($this->request->isPostRequest() ? $_POST : $_GET);

            case 'redis':
                return $this->redis = (new Model())->getModel('redis:default');
        }

        return null;
    }
}