<?php
/**
 * Created by PhpStorm.
 * User: J.H
 * Date: 2018/5/5
 * Time: 11:29
 */

namespace EP\DB\Drivers;

use EP\Exception\EE;
use MongoClient,Exception;
use EP\Exception\ELog;

class MongoDriver
{
    /**
     * @var MongoClient
     */
    public $db;

    /**
     * 创建MongoDB实例
     *
     * @param $link_params
     */
    function __construct(array $link_params)
    {
        if (!class_exists('MongoClient')) {
            ELog::error('Class MongoClient not found!');
        }

        try {
            $mongoClient = new MongoClient($link_params['dsn'], $link_params['options']);
            $this->db = $mongoClient->$link_params['db'];
        } catch (Exception $e) {
            new EE($e);
        }
    }
}