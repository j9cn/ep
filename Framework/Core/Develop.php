<?php
/**
 * Author: OXIVO
 * QQ: 2502212233
 * Date: 2021/2/18
 * Time: 下午 12:49
 */

namespace EP\Core;


class Develop
{

    static $status = false;
    static private $dev;
    static private $config = [
        # 是否开启在模版中调用打印开发数据
        'debug' => false,
        # 是否在开发环境中自动创建模版
        'auto_create_tpl' => false,
        # 是否能自动在控制器(Controllers/Views)中创建请求的方法(action)
        'auto_add_action' => false,
        # 是否能自动创建控制器
        'auto_add_controller' => false
    ];

    private static function init()
    {
        $debug_config_file = PROJECT_REAL_PATH . 'Config/DEV.php';
        if (is_file($debug_config_file)) {
            self::$status = true;
            $debug_config = Loader::read($debug_config_file);
            self::$config = array_merge(self::$config, $debug_config);
        }
    }

    /**
     * @param string $key
     * @return bool
     */
    static function isDev(string $key = '')
    {
        if (!self::$status) {
            self::init();
        }
        if (!self::$status) {
            return false;
        }
        if (!$key) {
            return self::$status;
        }
        if (!isset(self::$config[$key])) {
            return false;
        }
        return self::$config[$key];
    }

    static function createTpl($file)
    {
        if (!self::isDev('auto_create_tpl')) {
            return false;
        }
        $status = Helper::mkFile($file, 0777);
        file_put_contents($file, 'created by the EP framework script at ' . date('Y.m.d H:i:s'));
        return $status;
    }

    /**
     * @return bool
     */
    static function createMethod()
    {
        if (self::isDev('auto_add_action')) {
            if (!isset($_ENV['EP.method'], $_ENV['EP.controller'])) {
                return false;
            } else {
                $method = $_ENV['EP.method'];
                $controller = $_ENV['EP.controller'];
                $_ENV['EP.autoAdd'] = true;
                $_ENV['EP.autoAddMethodName'] = "{$controller}::{$method}";
                if (isset($_GET['t']) && $_GET['t'] === '```WRITE```') {
                    $controller_file = PROJECT_REAL_PATH . $controller . '.php';
                    $datetime = date('Y.m.d H:i:s');
                    $old_content = trim(Loader::read($controller_file, true));

                    $method_ptl = <<<ptl

    /**
     * @action $method
     * @return void
     * @author This method is automatically created by the EP framework script at $datetime
     *                这个方法由EP框架脚本自动创建于 $datetime
     */
    function {$method}()
    {
        
    }
    
}
ptl;
                    $new_content = substr($old_content, 0, -1) . $method_ptl;
                    file_put_contents($controller_file, $new_content);
                    $url = $_ENV['EP.urlReload'];
                    Header("Location: $url");
                    exit();
                }
                return true;
            }
        }
        return false;
    }

    static function createController()
    {
        if (self::isDev('auto_add_controller')) {
            if (!isset($_ENV['EP.controller'], $_ENV['EP.controllerType'])) {
                return false;
            } else {
                $_ENV['EP.autoAdd'] = true;
                $_ENV['EP.autoAddMethodName'] = $_ENV['EP.controller'];
                $controller_file = PROJECT_REAL_PATH . $_ENV['EP.controller'] . '.php';
                if (isset($_GET['t']) && $_GET['t'] === '```WRITE```') {
                    $controller_file = PROJECT_REAL_PATH . $_ENV['EP.controller'] . '.php';
                    $ctr = end(explode('/', str_replace(DS, '/', $_ENV['EP.controller'])));
                    $appName = $_ENV['EP.appName'];
                    $datetime = date('Y.m.d H:i:s');
                    if ($_ENV['EP.controllerType'] === 1) {
                        $tplCtr = <<<tpl
<?php
/**
 * Author: created by the EP framework script at $datetime
 */
namespace App\\$appName\Controllers;


class $ctr extends $appName
{
    function __construct()
    {
        parent::__construct();
    }


}
tpl;

                    } else {
                        $tplCtr = <<<tpl
<?php
/**
 * Author: created by the EP framework script at $datetime
 */

namespace App\\$appName\Views;

use EP\MVC\View;

class $ctr extends View
{
    
    function __construct()
    {
        parent::__construct();

    }
    
}
tpl;

                    }
                    Helper::mkFile($controller_file);
                    file_put_contents($controller_file, $tplCtr);
                    $url = $_ENV['EP.urlReload'];
                    Header("Location: $url");
                    exit();
                }
            }
            return true;
        }
        return false;
    }


    /**
     * @return string
     */
    static function console()
    {
        if (!self::isDev('debug')) {
            return '';
        }
        $data = var_export($_ENV['EP.console']['data'], true);
        $params = var_export($_ENV['EP.console']['params'], true);
        $session = isset($_SESSION) ? $_SESSION : [];
        $session = var_export($session, true);
        $cookies = var_export($_COOKIE, true);
        $ctr = $_ENV['EP.console']['controller'];
        $act = $_ENV['EP.console']['action'];
        $rand = uniqid();
        $incFile = get_included_files();
        $countIncFile = count($incFile);
        $incFileList = '';
        foreach ($incFile as $i => $file) {
            $incFileList .= "<li>" . self::hiddenFileRealPath($file) . "</li>";
        }
        $tpl = <<<tpl
        
<div id="console_view_body_{$rand}" style="position: fixed;z-index:8640000;padding-left: 5px;right: 0;bottom: 5px;height: 400px;max-height: 500px;max-width:100%;width: 600px;overflow: auto;border-radius:10px;box-shadow:0px 0px 0px 3px #bb0a0a,0px 0px 0px 6px #2e56bf,0px 0px 0px 9px #ea982e;">
<span onclick="console_view_min_max_{$rand}(this)" STYLE="position: fixed;z-index:8640001;background-color:#c0a16b;border: 1px saddlebrown solid;cursor: pointer;right: 15px;padding: 0 5px">-</span>
<h2>Controller: {$ctr}</h2>
<h2>Action: {$act}</h2>
<h2>Data: </h2>
<pre>{$data}</pre>
<h2>\$this->params: </h2>
<pre>{$params}</pre>
<h2>session:</h2>
<pre>{$session}</pre>
<h2>cookies:</h2>
<pre>{$cookies}</pre>
<h2>Inc. Files{$countIncFile}</h2>
<ol>{$incFileList}</ol>
</div>
<script>
function console_view_min_max_{$rand}(dom) {
  var b = document.getElementById('console_view_body_{$rand}');
  if (dom.innerText === '-') {
      dom.innerText = '+';
      b.style.bottom = '-370px'
  }else {
      dom.innerText = '-';
      b.style.bottom = '5px'
  }
}
</script>
tpl;
        echo $tpl;
        return '';
    }

    /**
     * 隐藏异常中的真实文件路径
     *
     * @param $path
     *
     * @return mixed
     */
    private static function hiddenFileRealPath($path)
    {
        return str_replace(
            [
                PROJECT_REAL_PATH,
                EP_PATH,
                str_replace('/', DS, $_SERVER['DOCUMENT_ROOT']),
                EP_HOME_PATH
            ],
            ['Project->', 'EP->', 'Index->', 'EP.dir->'], $path);
    }

}