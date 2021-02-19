<?php
/**
 * Created by PhpStorm.
 * User: JH
 * Date: 2017/10/8
 * Time: 17:57
 */

// 框架常量
define('EP_VER', '0.1.00');
define('DS', DIRECTORY_SEPARATOR);
define('POST', 'post');
define('GET', 'get');
define('AJAX', 'ajax');
define('TIME', $_SERVER['REQUEST_TIME']);
define('TIME_FLOAT', $_SERVER['REQUEST_TIME_FLOAT']);


define('EP_HOME_PATH', __DIR__ . DS);
require __DIR__ . '/Framework/Core/Loader.php';
require __DIR__ . '/Framework/Core/Delegate.php';

class_alias('EP\Core\Delegate', 'EP');