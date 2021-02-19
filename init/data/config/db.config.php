<?php

/**
 * mysql
 */
$mysql_link = array(
    'host' => 'localhost',
    'port' => '3306',
    'user' => 'root',
    'pass' => '123456',
    'prefix' => '',
    'charset' => 'utf8',
);

/**
 * redis
 */
$redis_link = array(
    'host' => '127.0.0.1',
    'port' => 6379,
    'auth' => '',
    'timeout' => 2.5
);

#默认数据库配置
$db = $mysql_link;
$db['name'] = 'ep';


$redis_api = $redis_user = $redis_platform = $redis_order = $redis_tasks = $redis_link;


$redis_api['db'] = 1;
$redis_user['db'] = 2;
$redis_platform['db'] = 3;
$redis_order['db'] = 4;
$redis_tasks['db'] = 5;

return array(
    'mysql' => array(
        'db' => $db,
    ),

    'default' => array('mysql' => 'db'),

    /**
     * redis 配置
     */
    'redis' => array(
        'default' => $redis_link,
        'api' => $redis_api,
        'user' => $redis_user,
        'platform' => $redis_platform,
        'order' => $redis_order,
        'tasks' => $redis_tasks
    ),
);


