<?php
/**
 * Author: OXIVO
 * QQ: 2502212233
 * Date: 2020/2/29
 * Time: 8:47
 */

namespace EP\Library\VPS;


use EP\Library\Curl\HttpRequest;

class kiwivm
{
    private $vid = '';
    private $api_key = '';

    private $methods = 'getServiceInfo';
    private $api = 'https://api.64clouds.com/v1/';

    function __construct(array $config = [])
    {
        if ($config) {
            $this->setVPN($config['vid'], $config['api_key']);
        }
    }

    function setVPN($vid, $api_key)
    {
        $this->vid = $vid;
        $this->api_key = $api_key;
    }

    /**
     * VPS 基本信息
     * @return array
     */
    function getServiceInfo()
    {
        $this->methods = 'getServiceInfo';
        return $this->exec();
    }

    /**
     * VPS 基本信息及系统信息
     * @return array
     */
    function getLiveServiceInfo()
    {
        $this->methods = 'getLiveServiceInfo';
        return $this->exec();
    }

    /**
     * 重置ROOT 密码
     * @return array
     */
    function resetRootPassword()
    {
        $this->methods = 'resetRootPassword';
        return $this->exec();
    }

    /**
     * 重启
     * @return array
     */
    function restart()
    {
        $this->methods = 'restart';
        return $this->exec();
    }

    /**
     * 启动
     * @return array
     */
    function start()
    {
        $this->methods = 'start';
        return $this->exec();
    }

    /**
     * 关机
     * @return array
     */
    function stop()
    {
        $this->methods = 'stop';
        return $this->exec();
    }

    /**
     * 创建快照
     * @return array
     */
    function CreateSnapshot()
    {
        $this->methods = 'snapshot/create';
        return $this->exec();
    }

    /**
     * 更换新IP
     * @param $old_ip
     * @param $host_name
     * @return array
     */
    function resetIp($old_ip, $host_name)
    {
        $this->methods = 'setPTR';
        return $this->exec();
    }

    /**
     * @return array
     */
    private function exec()
    {
        $url = $this->api . $this->methods;
        $params = ['veid' => $this->vid, 'api_key' => $this->api_key];
        $http = new HttpRequest();
        $http->timeout(20);
        $result = $http::get($url, $params, $erro_msg);
        if ($erro_msg) {
            $result = ['error' => 1000, 'msg' => $erro_msg];
            return $result;
        }
        $result = json_decode($result, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $result;
        }
        return $result = ['error' => 1000, 'msg' => '未知错误'];;
    }


}