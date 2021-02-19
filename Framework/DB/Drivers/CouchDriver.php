<?php
/**
 * Created by PhpStorm.
 * User: J.H
 * Date: 2018/5/5
 * Time: 11:00
 */

namespace EP\DB\Drivers;

use CouchbaseCluster,Exception;
use EP\Exception\EE;
use EP\Exception\ELog;

class CouchDriver
{
    /**
     * @var \Couchbase\Bucket
     */
    public $link;
    /**
     * @param array $link_params
     */
    function __construct(array $link_params)
    {
        if (!class_exists('CouchbaseCluster')) {
            ELog::error('Class CouchbaseCluster not found!');
        }

        $bucket = isset($link_params['bucket']) ? $link_params['bucket'] : 'default';
        $bucket_password = isset($link_params['bucket_password']) ? $link_params['bucket_password'] : '';

        try {
            $myCluster = new CouchbaseCluster($link_params['dsn']);
            $this->link = $myCluster->openBucket($bucket, $bucket_password);
        } catch (Exception $e) {
            new EE($e);
        }
    }

    /**
     * 调用Couch提供的方法
     *
     * @param $method
     * @param $argv
     * @return mixed|null
     */
    public function __call($method, $argv)
    {
        $result = null;
        if (method_exists($this->link, $method)) {
            try {
                $result = ($argv == null)
                    ? $this->link->$method()
                    : call_user_func_array(array($this->link, $method), $argv);
            } catch (Exception $e) {
                new EE($e);
            }
        }

        return $result;
    }
}