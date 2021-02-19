<?php
/**
 * Created by PhpStorm.
 * User: JH
 * Date: 2017/12/5
 * Time: 12:39
 */
declare(strict_types=1);

namespace App\project\Controllers;


use EP\Core\FrameBase;
use EP\Core\Helper;

class Main extends FrameBase
{

    private $project_path;
    private $app_path;
    private $status = false;

    function index()
    {
        $project = $this->getParam('make');
        if (!$project) {
            $this->printMsg('使用参数 [make] => PROJECT_NAME.APP_NAME', true);
        }
        @list($project_name, $app_name) = explode('.', $project);
        if (!isset($app_name) && empty($app_name)) {
            $app_name = 'Admin';
        }
        $app_name = ucfirst($app_name);
        $this->printMsg("正在创建[ {$project} ]项目...");
        $this->project_path = dirname(dirname(PROJECT_REAL_PATH)) . DS . $project_name . DS;

        if (Helper::createFolders($this->project_path)) {
            $this->printMsg($this->project_path . '---> Ok');
            if (!is_file($this->project_path . '.gitignore')) {
                file_put_contents($this->project_path . '.gitignore', $this->getFileContent('.gitignore.tpl'));
            }
        } else {
            $this->printMsg($this->project_path . '---> Fail', true);
        }

        $this->app_path = $this->project_path . DS . 'App' . DS . $app_name;
        if (is_dir($this->app_path)) {
            $this->printMsg("项目{$project_name}->{$app_name}[{$this->app_path}]已存在,请手动删除！", true);
        }
        $skeleton = [
            'dirApp' => $this->project_path . 'App' . DS . $app_name,
            'dirControllers' => $this->project_path . 'App' . DS . $app_name . DS . 'Controllers',
            'dirTemplates' => $this->project_path . 'App' . DS . $app_name . DS . 'Templates' . DS . 'default',
            'dirViews' => $this->project_path . 'App' . DS . $app_name . DS . 'Views',
            'dirConfig' => $this->project_path . 'Config',
            'dirAppRoot' => $this->project_path . 'htdocs' . DS . strtolower($app_name),
            'dirModules' => $this->project_path . 'Modules'
        ];
        foreach ($skeleton as $dir) {
            if (Helper::createFolders($dir)) {
                $this->printMsg("创建目录[ {$dir} ] ---> [Ok]");
            } else {
                $this->printMsg("创建目录[ {$dir} ] ---> [Fail]");
            }
        }

        //创建开发环境配置文件
        if (!is_file($skeleton['dirConfig']. DS . 'DEV.php')) {
            if (Helper::mkFile($skeleton['dirConfig'] . DS . 'DEV.php')) {
                file_put_contents($skeleton['dirConfig'] . DS . 'DEV.php', $this->getFileContent('DEV.php'));
            }
        }

        $config_path = $this->project_path . 'App' . DS;
        //global config
        if (!is_file($config_path . 'init.php')) {
            if (Helper::mkFile($config_path . 'init.php')) {
                $status = file_put_contents($config_path . 'init.php', $this->getFileContent('init.php'));
                if ($status) {
                    $this->printMsg("创建全局配置文件 [ {$config_path}init.php ] ---> [Ok]");
                } else {
                    $this->printMsg("创建全局配置文件 [ {$config_path}init.php ] ---> [Fail]", true);
                }
            }
        }


        //app config
        if (!is_file($config_path . "{$app_name}.init.php")) {
            if (Helper::mkFile($config_path . "{$app_name}.init.php")) {
                $status = file_put_contents($config_path . "{$app_name}.init.php", $this->getFileContent('app.php'));
                if ($status) {
                    $this->printMsg("创建APP配置文件 [ {$app_name}.init.php ] ---> [Ok]");
                } else {
                    $this->printMsg("创建APP配置文件 [ {$app_name}.init.php ] ---> [Fail]", true);
                }
            }
        }

        //入口文件
        $index = str_replace('%app_name%', $app_name, $this->getFileContent('index.tpl', 6));
        $this->writer($skeleton['dirAppRoot'] . DS . 'index.php', $index);
        $this->writer($skeleton['dirAppRoot'] . DS . '.htaccess', $this->getFileContent('htaccess.tpl', 6));

        // 框架连接
        $this->writer($this->project_path . 'project.php', $this->getFileContent('project.php'));

        // controller
        $controller_main = str_replace('%app_name%', $app_name, $this->getFileContent('Main.tpl', 2));
        $controller_app = str_replace('%app_name%', $app_name, $this->getFileContent('app.tpl', 2));
        $this->writer($skeleton['dirControllers'] . DS . 'Main.php', $controller_main);
        $this->writer($skeleton['dirControllers'] . DS . "{$app_name}.php", $controller_app);

        // views
        $views = str_replace('%app_name%', $app_name, $this->getFileContent('MainView.tpl', 4));
        $this->writer($skeleton['dirViews'] . DS . 'MainView.php', $views);

        // Templates
        $this->writer($skeleton['dirTemplates'] . DS . 'default.layer.phtml', $this->getFileContent('layer.tpl', 3));
        $this->writer($skeleton['dirTemplates'] . DS . 'main' . DS . 'ep_info.phtml', $this->getFileContent('info.tpl', 3));

        // 数据库配置
        $db_config_path = $skeleton['dirConfig'] . DS . 'db.config.php';
        if (!is_file($db_config_path)) {
            $this->writer($db_config_path, $this->getFileContent('db.config.php'));
        }


        if ($this->status) {
            $this->printMsg('##############################################');
            $this->printMsg('##               successfully               ##');
            $this->printMsg('##############################################');
            $this->printMsg('');
            $this->printMsg('Directory of Document Root: ' . $skeleton['dirAppRoot']);
        }
    }

    private function writer($file, $content)
    {
        if ($status = Helper::mkFile($file)) {
            $status = file_put_contents($file, $content);
        }
        $this->status = $status;
        if ($status) {
            $this->printMsg("创建文件 [ {$file} ] ---> [Ok]");
        } else {
            $this->printMsg("创建文件 [ {$file} ] ---> [Fail]", true);
        }
    }

    private function getFileContent($file_name, $type = 1)
    {
        switch ($type) {
            case 2:
                $type = 'controllers';
                break;
            case 3:
                $type = 'templates';
                break;
            case 4:
                $type = 'views';
                break;
            case 6:
                $type = 'htdocs';
                break;
            default:
                $type = 'config';
        }
        return file_get_contents(PROJECT_REAL_PATH . 'data' . DS . $type . DS . $file_name);
    }

    private function printMsg($message, $end = false)
    {
        echo '..' . $message . PHP_EOL;
        if ($end) {
            exit();
        }
    }

    private function getParam($params_key)
    {
        static $params;
        if (!$params) {
            $argv = $_SERVER['argv'];
            array_shift($argv);

            $key = '';
            foreach ($_SERVER['argv'] as $k => $arg) {
                if (($k % 2) !== 0) {
                    $key = $arg;
                    $params[$key] = null;
                } else {
                    $params[$key] = $arg;
                }
            }
        }
        if (isset($params[$params_key])) {
            return $params[$params_key];
        }
        return null;
    }
}