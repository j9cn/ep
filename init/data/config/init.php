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
    'cache' => [
        '#CACHED#' => false, #缓存总开关
        '*example' => [
            'onCache' => false, # 缓存开关true|false
            'method' => GET,  # bool|GET|POST|AJAX
            'config' => [
                'type' => 11, # see Cache:: * _TYPE
                'expire_time' => 3600, # default 3600s
                'ext' => '.cache', # fileCache ext default .cache
                'cache_params_key' => ['lang'] # cached params
            ]
        ]
    ]
];