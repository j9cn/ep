<?php
/**
 * Created by PhpStorm.
 * User: JH
 * Date: 2017/11/27
 * Time: 21:09
 */
declare(strict_types=1);

namespace EP\MVC;


use EP\Core\{
    Develop, FrameBase, Helper, Loader, Router
};
use EP\Exception\EE;
use EP\Exception\ELog;
use function Sodium\add;
use Throwable;

class View extends FrameBase
{


    /**
     * 默认模板目录
     * @var string
     */
    private $tpl_dir;

    /**
     * 资源配置
     * @var array
     */
    private $res_list;

    /**
     * 默认模板路径
     * @var string
     */
    private $tpl_base_path;

    /**
     * 默认url
     * @var string
     */
    private $link_base = null;


    /**
     * 模版数据
     * @var array
     */
    protected $data;

    protected $title;
    protected $keywords;
    protected $description;

    /**
     * 初始化布局文件中的变量
     * <pre>
     * title 标题
     * keywords 关键词
     * description 页面描述
     * layer 布局模板名称
     * load_layer 是否加载布局模板
     * </pre>
     * @var array
     */
    protected $set = array(
        'title' => '',
        'keywords' => '',
        'description' => '',

        'layer' => 'default',
        'load_layer' => true,
    );

    /**
     * 模版扩展文件名
     * @var string
     */
    protected $tpl_file_suffix = '.phtml';
    protected $tpl_file_content_html = <<<tpl
<div class="">

</div>
tpl;

    /**
     * 渲染模板
     *
     * @param null $method
     */
    function display($method = null)
    {
        if ($method === null) {
            $display_type = $this->config->get('sys', 'display');
            if ($display_type && strcasecmp($display_type, 'html') !== 0) {
                $this->set['load_layer'] = false;
                $method = trim($display_type);
            } else {
                if ($this->action) {
                    $method = $this->action;
                } else {
                    $method = Router::DEFAULT_ACTION;
                }
            }
        }
        $this->obRenderAction($method);
    }

    /**
     * 加载指定名称的模板文件
     *
     * @param string $tpl_name
     * @param array|mixed $data
     */
    function renderTpl($tpl_name, $data = array())
    {
        $file = $this->tpl($tpl_name);
        if (is_file($file)) {
            include $file;
        } else {
            if (Develop::createTpl($file)) {
                include $file;
            }else{
                ELog::error("缺少模版文件：{$file}");
            }
        }
    }

    /**
     * 加载指定绝对路径的文件
     *
     * @param string $file 文件绝对路径
     * @param array $data
     */
    function renderFile($file, $data = array())
    {
        include $file;
    }

    /**
     * 带缓存的renderTpl
     *
     * @param string $tpl_name
     * @param array $data
     * @param bool $encode
     *
     * @return string
     */
    function obRenderTpl($tpl_name, array $data = array(), $encode = false)
    {
        ob_start();
        $this->renderTpl($tpl_name, $data);
        return ob_get_clean();
    }

    /**
     * 带缓存的renderFile
     *
     * @param string $file
     * @param array $data
     * @param bool $encode
     *
     * @return string
     */
    function obRenderFile($file, $data = array(), $encode = false)
    {
        ob_start();
        $this->renderFile($file, $data);
        return ob_get_clean();
    }


    /**
     * 模板的绝对路径
     *
     * @param $tpl_name
     * @param bool $get_content 是否读取模板内容
     * @param bool $auto_append_suffix 是否自动添加模版后缀
     *
     * @return string
     */
    function tpl($tpl_name, $get_content = false, $auto_append_suffix = true)
    {
        $file_path = $this->getTplPath() . $tpl_name;
        if ($auto_append_suffix) {
            $file_path .= $this->tpl_file_suffix;
        }

        if (true === $get_content) {
            return file_get_contents($file_path, true);
        }

        return $file_path;
    }

    /**
     * 输出JSON
     */
    function JSON()
    {
        $this->set['load_layer'] = false;
        $this->delegate->getResponse()->setContentType('json');
        echo json_encode($this->data, JSON_UNESCAPED_UNICODE);
        exit();
    }

    /**
     * 安全返回数据数组中的值
     *
     * @param string $key
     * @param mixed $default
     * @param bool $if_empty_return_default
     * @param bool $use_cache
     *
     * @return string
     */
    function ed($key, $default = '', $if_empty_return_default = false, $use_cache = true)
    {
        static $map;
        if (isset($map[$key])) {
            if (!$if_empty_return_default) {
                return $map[$key];
            }
            if (empty($map[$key])) {
                return $default;
            }
        }
        $map[$key] = $this->e($this->data, $key, $default, $use_cache);
        if ($if_empty_return_default && empty($map[$key])) {
            return $default;
        }
        return $map[$key];
    }

    function getParams($key = '', $encrypt = false)
    {
        return parent::getParams($key, $encrypt);
    }

    /**
     * @param mixed $data
     * @return string
     */
    function console($data = '')
    {
        if ('' === $data) {
            $data = $this->data;
        }
        $_ENV['EP.console'] = [
            'data' => $data,
            'params' => $this->params,
            'action' => $this->action,
            'controller' => $this->controller
        ];
        return Develop::console();
    }

    function jsConsole(string $key = '')
    {
        if (!Develop::isDev('debug')) {
            return '';
        }
        $data = $this->data;
        if ($key) {
            $data = $this->e($data, $key);
        }
        return '<script type="text/javascript">console.table(' . json_encode($data, 256) . ')</script>';
    }

    /**
     * 设置layer附加参数
     *
     * @param $name
     * @param null $value
     *
     * @return $this
     */
    final public function set($name, $value = null)
    {
        if (is_array($name)) {
            $this->set = array_merge($this->set, $name);
        } else {
            $this->set[$name] = $value;
        }

        return $this;
    }

    function js($js_url, $is_ui = false, $cdn = false, $use_static_url = true)
    {
        $dot = '.js?t=' . TIME_FLOAT;
        if (!Develop::isDev('debug')) {
            $dot = '.min.js';
        }
        if ($cdn) {
            return $this->resCDN('js/' . $js_url . $dot, $use_static_url);
        }
        return $this->res('js/' . $js_url . $dot, $is_ui, $use_static_url);
    }

    function css($css_url, $is_ui = false, $cdn = false, $use_static_url = true)
    {
        $dot = '.css?t=' . TIME_FLOAT;
        if (!Develop::isDev('debug')) {
            $dot = '.min.css';
        }
        if ($cdn) {
            return $this->resCDN('css/' . $css_url . $dot, $use_static_url);
        }
        return $this->res('css/' . $css_url . $dot, $is_ui, $use_static_url);
    }

    /**
     * 生成资源文件路径
     *
     * @param $res_url
     * @param bool $use_static_url
     *
     * @return string
     */
    function res($res_url, $is_ui = false, $use_static_url = true)
    {
        static $res_base_url = null;
        if (!isset($res_base_url[$use_static_url])) {
            if ($use_static_url) {
                $base_url = $this->config->get('static', 'url');
                if ($is_ui && $ui_dir = $this->config->get('uiDir')) {
                    $base_url = str_replace('static', $ui_dir . '/' . 'static', $base_url);
                }
            } else {
                $base_url = $this->config->get('url', 'full_request');
            }

            $res_base_url[$use_static_url] = rtrim($base_url, '/') . '/';
        }
        return $res_base_url[$use_static_url] . $res_url;
    }

    function resCDN($res_url, $use_static_url = true)
    {
        static $res_base_url = null;
        if (!isset($res_base_url[$use_static_url])) {
            $base_url = rtrim((string)$this->config->get('cdn'), '/');
            if (!$base_url) {
                ELog::error('init OR ' . $this->getAppName() . ' init未设置配置项[cdn]');
            }
            if ($use_static_url) {
                $base_url .= '/static';
            }
            $res_base_url[$use_static_url] = $base_url . '/';
        }
        return $res_base_url[$use_static_url] . $res_url;
    }

    /**
     * 输出资源相对路径
     *
     * @param string $res_url
     * @param string $res_dir
     *
     * @return string
     */
    function relRes($res_url, $res_dir = 'static')
    {
        static $res_base_url = null;
        if (null === $res_base_url) {
            $res_base_url = rtrim($this->config->get('url', 'request'), '/') . '/' . $res_dir . '/';
        }

        return $res_base_url . $res_url;
    }

    /**
     * 设置模板dir
     *
     * @param $dir_name
     */
    function setTplDir($dir_name)
    {
        $this->tpl_dir = $dir_name;
    }

    /**
     * 获取生成连接的基础路径
     * @return string
     */
    function getLinkBase()
    {
        if (null === $this->link_base) {
            $this->setLinkBase($this->config->get('url', 'full_request'));
        }

        return $this->link_base;
    }

    /**
     * 获取当前app名称
     * @return string
     */
    function getAppName()
    {
        static $app_name = null;
        if ($app_name === null) {
            $app_name = $this->config->get('app', 'name');
        }

        return $app_name;
    }

    /**
     * 设置生成的连接基础路径
     *
     * @param $link_base
     */
    function setLinkBase($link_base)
    {
        $this->link_base = rtrim($link_base, '/') . '/';
    }

    /**
     * 模板路径
     * @return string 要加载的模板路径
     */
    function getTplPath()
    {
        static $tpl_path;
        $app_name = $this->getAppName();
        if (!isset($tpl_path[$app_name])) {
            $tpl_path[$app_name] = $this->getTplBasePath() . $this->getTplDir() . DIRECTORY_SEPARATOR;
        }

        return $tpl_path[$app_name];
    }

    /**
     * 设置模板路径
     *
     * @param $tpl_base_path
     */
    function setTplBasePath($tpl_base_path)
    {
        $this->tpl_base_path = rtrim($tpl_base_path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    }

    /**
     * 获取模板默认路径
     * @return string
     */
    function getTplBasePath()
    {
        if (!$this->tpl_base_path) {
            $this->setTplBasePath($this->config->get('app', 'path') . 'Templates' . DIRECTORY_SEPARATOR);
        }

        return $this->tpl_base_path;
    }

    /**
     * 取得模板路径前缀
     * @return string
     */
    function getTplDir()
    {
        if (!$this->tpl_dir) {
            $default_tpl_dir = $this->config->get('sys', 'default_tpl_dir');
            if (!$default_tpl_dir) {
                $default_tpl_dir = 'default';
            }
            $this->setTplDir($default_tpl_dir);
        }

        return $this->tpl_dir;
    }

    /**
     * 运行时分组添加css/js
     *
     * @param $res_url
     * @param string $location
     * @param bool $convert
     */
    function addRes($res_url, $location = 'header', $convert = true)
    {
        $this->res_list[$location][] = array(
            'url' => $res_url,
            'convert' => $convert
        );
    }

    /**
     * 分组加载css|js
     *
     * @param string $location
     *
     * @return string
     */
    function loadRes($location = 'header')
    {
        $result = '';
        if (empty($this->res_list) || empty($this->res_list[$location])) {
            return $result;
        }

        if (isset($this->res_list[$location]) && !empty($this->res_list[$location])) {
            $data = $this->res_list[$location];
        }

        if (!empty($data)) {
            if (is_array($data)) {
                foreach ($data as $r) {
                    $result .= $this->outputResLink($r['url'], $r['convert']);
                }
            } else {
                $result .= $this->outputResLink($data);
            }
        }

        return $result;
    }

    /**
     * 生成当前URL参数
     *
     * @param array $add_params
     *
     * @return string
     */
    function buildQueryString(array $add_params = [])
    {
        $params = array_merge($_GET, $add_params);
        return urldecode(http_build_query($params));
    }

    /**
     * 输出js/css连接
     *
     * @param $res_link
     * @param bool $make_link
     *
     * @return null|string
     */
    protected function outputResLink($res_link, $make_link = true)
    {
        $t = Helper::getExt($res_link);
        switch (strtolower($t)) {
            case 'js' :
                $tpl = '<script type="text/javascript" src="%s"></script>';
                break;

            case 'css' :
                $tpl = '<link rel="stylesheet" type="text/css" href="%s"/>';
                break;

            default :
                $tpl = null;
        }

        if (null !== $tpl) {
            if ($make_link) {
                $res_link = $this->res($res_link);
            }

            return sprintf("{$tpl}\n", $res_link);
        }

        return null;
    }

    /**
     * 加载布局
     *
     * @param string $content
     * @param string $layer_ext
     */
    protected function loadLayer($content, $layer_ext = '.layer.phtml')
    {
        $layer_file = $this->getTplPath() . $this->set['layer'] . $layer_ext;
        if (!is_file($layer_file)) {
            ELog::error($layer_file . ' layer Not found!');
        }
        extract($this->set, EXTR_PREFIX_SAME, 'USER_DEFINED');
        include "{$layer_file}";
    }

    /**
     * 输出带layer的view
     *
     * @param string $method
     *
     * @return string|void
     */
    private function obRenderAction($method)
    {
        ob_start();
        try {
            $this->$method();
        } catch (Throwable $e) {
            try {
                $_ENV['EP.method'] = $method;
                $_ENV['EP.controller'] = $this->view_controller;
                Develop::createMethod();
            } catch (Throwable $exception) {
                ELog::error($exception->getMessage());
            }
            ELog::error($e->getMessage());
        }

        $content = ob_get_clean();

        if ($this->set['load_layer']) {
            $this->loadLayer($content);
        } else {
            echo $content;
        }
    }


}