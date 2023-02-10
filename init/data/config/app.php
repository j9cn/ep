<?php
/**
 * Created by PhpStorm.
 * User: JH
 * Date: 2017/10/14
 * Time: 21:10
 */

define('PAGE_NOT_FOUND','');// 自定义404模版
return [
    'cache' => [
        '#CACHED#' => false, #缓存总开关
        'main.index' => [
            'onCache' => false,
            'method' => GET,  // bool|GET|POST|AJAX
            'config' => [
                'type' => 1,
                'expire_time' => 864000,
//                'ext' => '.json',
                'cache_params_key' => ['c', 'b', 'a']
            ]
        ]
    ],
    'basicAuth' => [
        //'main.index' => ['user' => '111', 'pw' => 'aa']
    ]
];