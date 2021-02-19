<?php
/**
 * Created by PhpStorm.
 * User: J.H
 * Date: 2018/5/4
 * Time: 23:53
 */

namespace EP\Library\CloudServices\AliYun\Common;


use EP\Exception\ELog;
use EP\Library\Curl\HttpRequest;

abstract class BaseApi
{
    protected $Format = 'JSON';
    protected $Version = '';
    protected $AccessKeyId = '';
    protected $AccessKeySecret = '';
    protected $Signature = '';
    protected $SignatureMethod = 'HMAC-SHA1';
    protected $SignatureVersion = '1.0';
    protected $api = 'https://dm.aliyuncs.com/';

    protected $params = array();

    function __construct()
    {

    }

    private function getNonce()
    {
        return md5(str_shuffle($this->AccessKeySecret) . time());
    }

    private function getSignature()
    {
        ksort($this->params);
        $queryString = '';
        foreach ($this->params as $key => $value) {
            $queryString .= '&' . $this->percentEncode($key) . '=' . $this->percentEncode($value);
        }

        $stringToSign = 'POST&%2F&' . $this->percentEncode(substr($queryString, 1));
        return base64_encode(hash_hmac('sha1', $stringToSign, $this->AccessKeySecret . '&', true));
    }

    private function percentEncode($str)
    {
        $res = urlencode($str);
        $res = preg_replace('/\+/', '%20', $res);
        $res = preg_replace('/\*/', '%2A', $res);
        $res = preg_replace('/%7E/', '~', $res);
        return $res;
    }

    final protected function setParam($key, $value)
    {
        $this->params[$key] = $value;
    }

    function send()
    {
        $result = HttpRequest::post($this->api, $this->params, $error);
        if ($error) {
            ELog::log('ali_api', "Curl error: {$error}\n{$this->api}");
            return false;
        }
        return $this->parseResponse($result);
    }

    protected function buildParams()
    {
        date_default_timezone_set("GMT");
        $common_params = array(
            'Format' => $this->Format,
            'Version' => $this->Version,
            'AccessKeyId' => $this->AccessKeyId,
            'SignatureMethod' => $this->SignatureMethod,
            'Timestamp' => date('c'),
            'SignatureVersion' => $this->SignatureVersion,
            'SignatureNonce' => $this->getNonce(),
            'RegionId' => $_SERVER['SERVER_ADDR']
        );
        $this->params = array_merge($this->params, $common_params);
        $this->setParam('Signature', $this->getSignature());
    }

    private function parseResponse($body)
    {
        ELog::log('sss', $body);
        if ($this->Format == 'JSON') {
            return json_decode($body, true);
        }
        $result = @simplexml_load_string($body);
        return (array)$result;
    }

}