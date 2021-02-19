<?php
/**
 * Created by PhpStorm.
 * User: JH
 * Date: 2017/10/8
 * Time: 19:22
 */
declare(strict_types=1);

namespace EP\Core;


//外部定义的项目路径
use EP\Exception\{
    EE, EN
};
use EP\Http\{
    Request, Response
};
use EP\Runtime\ClosureContainer;
use EP\I\RouterInterface;
use Closure;

defined('PROJECT_PATH') or die('undefined PROJECT_PATH');

//项目路径
define('PROJECT_REAL_PATH', rtrim(PROJECT_PATH, DS) . DS);

//项目APP路径
define('APP_PATH_DIR', PROJECT_REAL_PATH . 'App' . DS);

//框架路径
define('EP_PATH', dirname(__DIR__) . DS);

class Delegate
{
    /**
     * @var string
     */
    public $app_name;

    /**
     * @var Application
     */
    private $app;

    /**
     * @var Router
     */
    private $router;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var Loader
     */
    private $loader;

    /**
     * 运行时配置 (高于配置文件)
     * @var array
     */
    private $runtime_config;

    /**
     * 运行时匿名函数容器
     * @var ClosureContainer
     */
    private $action_container;

    /**
     * Delegate的实例
     * @var Delegate
     */
    private static $instance;

    /**
     * 初始化框架
     *
     * @param string $app_name 要加载的app名称
     * @param array $runtime_config 运行时指定的配置
     */
    private function __construct(string $app_name, array $runtime_config)
    {
        $this->app_name = $app_name;
        $this->runtime_config = $runtime_config;

        $this->loader = Loader::init();
        $this->config = self::initConfig($app_name, $runtime_config);
        $this->action_container = new ClosureContainer();
        $this->router = new Router($this);
        $this->app = new Application($app_name, $this);
    }

    /**
     * 实例化框架
     *
     * @param string $app_name app名称
     * @param array $runtime_config 运行时加载的设置
     *
     * @return self
     */
    static function loadApp($app_name, array $runtime_config = array()): self
    {
        if (!isset(self::$instance[$app_name])) {
            self::$instance[$app_name] = new Delegate($app_name, $runtime_config);
        }

        return self::$instance[$app_name];
    }

    /**
     * 直接调用控制器类中的方法
     * <pre>
     * 忽略路由别名相关配置和URL参数, @cp_params注释不生效
     * </pre>
     *
     * @param string $controller "控制器:方法"
     * @param string|array $args 参数
     * @param bool $return_content 是输出还是直接返回结果
     *
     * @return array|mixed|string
     */
    public function get(string $controller, $args = array(), $return_content = false)
    {
        return $this->app->dispatcher($controller, $args, $return_content);
    }

    /**
     * 解析url并运行
     * @throws EE
     */
    public function run()
    {
        try {
            $this->app->dispatcher($this->router->getRouter());
            if ($notice_error = error_get_last()) {
                new EN($notice_error);
            }
        } catch (\Throwable $exception) {
            throw new EE($exception);
        }

    }

    /**
     * 自定义router运行
     *
     * @param RouterInterface $router
     */
    public function rRun(RouterInterface $router)
    {
        $this->app->dispatcher($router);
    }

    /**
     * 处理REST风格的请求
     * <pre>
     * $app = EP\Core\Delegate::loadApp('web')->rest();
     * $app->get("/", function(){
     *    echo "hello";
     * });
     * </pre>
     * @return Rest
     */
    public function rest(): Rest
    {
        return Rest::getInstance($this);
    }

    /**
     * CLI模式下运行方式
     * <pre>
     * 在命令行模式下的调用方法如下:
     * php /path/index.php controller.action params1=value params2=value ... $paramsN=value
     * 第一个参数用来指定要调用的控制器和方法
     * 格式如下:
     *      控制器名称:方法名称
     * 在控制器:方法后加空格来指定参数,格式如下:
     *      参数1=值, 参数2=值, ... 参数N=值
     * 控制器中调用$this->params来获取并处理参数
     * </pre>
     *
     * @param int|bool $run_argc
     * @param array|bool $run_argv
     */
    public function cliRun($run_argc = false, $run_argv = false)
    {
        if (PHP_SAPI !== 'cli') {
            die("This app is only running from CLI\n");
        }

        if (false === $run_argc) {
            $run_argc = $_SERVER['argc'];
        }

        if (false === $run_argv) {
            $run_argv = $_SERVER['argv'];
        }

        if ($run_argc == 1) {
            die("Please specify params: controller.action params\n");
        }

        //去掉argv中的第一个参数
        array_shift($run_argv);
        $controller = array_shift($run_argv);
        //使用get调用指定的控制器和方法,并传递参数
        $this->get($controller, $run_argv);
    }

    /**
     * 注册运行时匿名函数
     *
     * @param string $name
     * @param Closure $f
     *
     * @return $this
     */
    function on($name, Closure $f)
    {
        $this->action_container->add($name, $f);
        return $this;
    }

    /**
     * application对象
     * @return Application
     */
    function getApplication()
    {
        return $this->app;
    }

    /**
     * app配置对象
     * @return Config
     */
    function getConfig()
    {
        return $this->config;
    }

    /**
     * Loader
     * @return Loader
     */
    function getLoader()
    {
        return $this->loader;
    }

    /**
     * 获取运行时指定的配置
     * @return array
     */
    function getRuntimeConfig()
    {
        return $this->runtime_config;
    }

    /**
     * @return Router
     */
    function getRouter()
    {
        return $this->router;
    }

    /**
     * 返回当前app的aspect容器实例
     * @return ClosureContainer
     */
    function getClosureContainer()
    {
        return $this->action_container;
    }

    /**
     * @return Request
     */
    function getRequest()
    {
        return Request::getInstance();
    }

    /**
     * @return Response
     */
    function getResponse()
    {
        return Response::getInstance();
    }

    /**
     * 初始化App配置
     *
     * @param string $app_name
     * @param array $runtime_config
     *
     * @return Config
     */
    private static function initConfig(string $app_name, array $runtime_config)
    {
        $request = Request::getInstance();
        $host = $request->getHostInfo();
        $index_name = $request->getIndexName();

        $request_url = $request->getBaseUrl();
        $script_path = $request->getScriptFilePath();
        //app名称和路径
        $runtime_config['app'] = array(
            'name' => $app_name,
            'path' => APP_PATH_DIR . $app_name . DS
        );

        $env_config = array(
            //url相关设置
            'url' => array(
                'host' => $host,
                'index' => $index_name,
                'request' => $request_url,
                'full_request' => $host . $request_url
            ),

            //配置和缓存的绝对路径
            'path' => array(
                'cache' => PROJECT_REAL_PATH . 'cache' . DS,
                'config' => PROJECT_REAL_PATH . 'Config' . DS,
                'script' => $script_path . DS,
            ),

            //静态文件url和绝对路径
            'static' => array(
                'url' => $host . $request_url . 'static/',
                'path' => $script_path . DS . 'static' . DS
            )
        );

        foreach ($env_config as $key => $value) {
            if (isset($runtime_config[$key]) && is_array($runtime_config[$key])) {
                $runtime_config[$key] = array_merge($value, $runtime_config[$key]);
            } elseif (!isset($runtime_config[$key])) {
                $runtime_config[$key] = $value;
            }
        }
        $app_init = [];
        $app_init_file = APP_PATH_DIR  . $app_name . '.init.php';
        if (is_file($app_init_file)) {
            $app_init = Loader::read($app_init_file);
        }

        return Config::load(APP_PATH_DIR  . 'init.php')->combine($app_init)->combine($runtime_config);

    }

}