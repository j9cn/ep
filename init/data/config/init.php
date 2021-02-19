<?php
/**
 * Created by PhpStorm.
 * User: JH
 * Date: 2017/10/9
 * Time: 21:37
 */

return [
    'config' => 'global',
    'sys' => array(
        'auth' => 'COOKIE',
        'default_tpl_dir' => 'default',
        'display' => 'HTML'
    ),
    'url' => array(
        '*' => 'Main.index',
        //1、原生的参数形式: /index.php/controller.action?foo=bar
        //2、友好的参数形式: /index.php/controller.action/foo/bar
        //3、QUERY_STRING形式: /?/controller.action/foo/bar
        'type' => 1,
        'rewrite' => false,
        'dot' => '/',
        'ext' => '',
        'index' => 'index.php'
    ),
    /***************************************************************
     * uri和auth加解密key
     ****************************************************************/
    'encrypt' => array(
        'uri' => '<(￣oo,￣)/',
        'auth' => '\(0^◇^0)/',
        'cookie' => '(.=^・ェ・^=)'
    ),
    'router' => array(
        'ab.bb' => 'abc.ddd'
    ),
    'cache' => []
];