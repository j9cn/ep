<?php
/**
 * Created by PhpStorm.
 * User: J.H
 * Date: 2018/2/14
 * Time: 1:12
 */

namespace EP\Library\Payment\AliPay;


use EP\Exception\EE;
use EP\Library\Curl\HttpRequest;

class AliPaySDKnv
{
    protected $app_id;

    private $private_key;

    private $public_key;

    protected $sign_type = 'RSA2';

    protected $format = 'json';

    protected $charset = 'UTF-8';

    protected $timestamp;

    public $method = 'alipay.trade.page.pay';

    private $api = 'https://openapi.alipay.com/gateway.do';

    /**
     * 异步通知接口
     * @var string
     */
    private $notify_url;

    /**
     * 同步通知接口
     * @var string
     */
    private $return_url;

    /**
     * JSON FORMAT
     * @var string
     */
    protected $biz_content;

    public $version = '1.0';


    function __construct(array $config)
    {
        if (!isset($config['app_id']) || empty($config['app_id'])) {
            throw new EE(EE::ERROR, '无效应用id[app_id]');
        }
        $this->app_id = $config['app_id'];
        if (!empty($config['sign_type'])) {
            $this->sign_type = strtoupper($config['sign_type']);
        }

        //私钥
        if (empty($config['private_key'])) {
            throw new EE(EE::ERROR, '无效商户的私钥[private_key]');
        }
        $this->private_key = $this->parseKey($config['private_key']);
        $begin_private = "-----BEGIN RSA PRIVATE KEY-----";
        $end_private = "-----END RSA PRIVATE KEY-----";
        if ($begin_private !== substr($this->private_key, 0, strlen($begin_private))) {
            $this->private_key = $begin_private . "\n" . $this->private_key;
        }
        if ($end_private !== substr($this->private_key, -strlen($end_private), strlen($end_private))) {
            $this->private_key .= "\n" . $end_private;
        }
        //公钥
        if (empty($config['public_key'])) {
            throw new EE(EE::ERROR, '无效支付宝公钥[public_key]');
        }
        $this->public_key = $this->parseKey($config['public_key']);
        $begin_public = '-----BEGIN PUBLIC KEY-----';
        $end_public = '-----END PUBLIC KEY-----';
        if ($begin_public !== substr($this->public_key, 0, strlen($begin_public))) {
            $this->public_key = $begin_public . "\n" . $this->public_key;
        }
        if ($end_public !== substr($this->public_key, -strlen($end_public), strlen($end_public))) {
            $this->public_key .= "\n" . $end_public;
        }

        if (isset($config['notify_url'])) {
            $this->notify_url = $config['notify_url'];
        }
        if (isset($config['return_url'])) {
            $this->return_url = $config['return_url'];
        }
        $this->timestamp = date('Y-m-d H:i:s');
        return $this;
    }

    function getCommonParams()
    {
        $parameter = [
            'app_id' => $this->app_id,
            'method' => $this->method,
            'format' => $this->format,
            'charset' => $this->charset,
            'sign_type' => $this->sign_type,
            'timestamp' => $this->timestamp,
            'version' => $this->version,
            'biz_content' => $this->biz_content
        ];
        return $parameter;
    }

    /**
     * 设置服务器通知地址
     *
     * @param string $url
     *
     * @return $this
     */
    function setNotifyUrl(string $url)
    {
        $this->notify_url = $url;
        return $this;
    }

    /**
     * @return string
     * @throws EE
     */
    function getNotifyUrl()
    {
        if (empty($this->notify_url) && false !== $this->notify_url) {
            throw new EE(EE::ERROR, '无效异步通知URL[notify_url]');
        }
        return $this->notify_url;
    }

    /**
     * @return string
     * @throws EE
     */
    function getReturnUrl()
    {
        if (empty($this->return_url) && false !== $this->return_url) {
            throw new EE(EE::ERROR, '无效同步回跳URL[return_url]');
        }
        return $this->return_url;
    }


    /**
     * 设置同步回跳通知地址
     *
     * @param string $url
     *
     * @return $this
     */
    function setReturnUrl(string $url)
    {
        $this->return_url = $url;
        return $this;
    }

    function setBizContent($biz_content, $json_encode = true)
    {
        if ($json_encode) {
            $this->biz_content = $this->jsonFormat($biz_content);
        } else {
            $this->biz_content = $biz_content;
        }

    }

    function signature(array $params)
    {
        switch ($this->sign_type) {
            case 'RSA':
            case 'RSA2':
                $private_key_id = openssl_get_privatekey($this->private_key);
                if (false === $private_key_id) {
                    throw new EE(EE::ERROR, $this->sign_type . '私钥格式错误');
                }
                openssl_sign($this->paramsToString($params), $sign, $private_key_id,
                    ('RSA' === $this->sign_type) ? OPENSSL_ALGO_SHA1 : OPENSSL_ALGO_SHA256);
                openssl_free_key($private_key_id);
                return base64_encode($sign);

            default:
                return '';
        }
    }

    /**
     * 验证请求合法性
     *
     * @param array $params
     *
     * @return bool
     */
    function verify(array $params): bool
    {
        if (empty($params) && empty($params['sign'])) {
            return false;
        }
        $data = $this->paramsToString($this->filterParams($params));
        if (!$this->verifySignature($data, (string)$params['sign'])) {
            return false;
        }
        return true;
    }

    /**
     * 数据签名验证
     *
     * @param string $data
     * @param string $sign
     *
     * @return bool
     * @throws EE
     */
    private function verifySignature(string $data, string $sign): bool
    {
        switch ($this->sign_type) {
            case 'RSA':
            case 'RSA2':
                $public_key_id = openssl_get_publickey($this->public_key);
                if (false === $public_key_id) {
                    throw new EE(EE::ERROR, $this->sign_type . '公钥格式错误');
                }
                $verify = (bool)openssl_verify($data, base64_decode($sign), $public_key_id,
                    ('RSA' === $this->sign_type) ? OPENSSL_ALGO_SHA1 : OPENSSL_ALGO_SHA256);
                openssl_free_key($public_key_id);
                return $verify;
                break;
            default:
                return false;
        }
    }

    private function paramsToString(array $params)
    {
        $str_params = [];
        foreach ($params as $k => $v) {
            $str_params[] = "{$k}={$v}";
        }
        return implode('&', $str_params);
    }

    /**
     * 除去待签名参数数组中的空值和签名参数，及重新排序
     *
     * @param array $params
     *
     * @return array
     */
    function filterParams(array $params): array
    {
        $filter_data = [];
        if (1 == 1) {
            foreach ($params as $k => $v) {
                if (!empty($v) && !in_array($k, ['sign'])) {
                    $filter_data[$k] = $v;
                }
            }
            ksort($filter_data);
        }

        return $filter_data;
    }

    function setApiVersion(string $ver)
    {
        $this->version = $ver;
    }

    function getAPI(array $params = [])
    {
        $query = '';
        if ($params) {
            $query = '?' . http_build_query($params);
        }
        return $this->api . $query;
    }

    protected function request($params)
    {
        $info = HttpRequest::post($this->getAPI(), $params, $error_msg, $http_code);
        if ($error_msg) {
            throw new EE(EE::ERROR, $error_msg);
        }
        if (false === $info) {
            return false;
        }
        return $info;
    }

    function jsonFormat(array $data)
    {
        return json_encode($data, JSON_UNESCAPED_UNICODE);
    }

    function parseResponse(&$data)
    {
        $data = mb_convert_encoding($data, 'utf-8', 'gb2312, utf-8');
        $data = json_decode($data, true);
        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new EE(EE::ERROR, json_last_error_msg());
        }
        $api_response = str_replace('.', '_', $this->method) . '_response';
        if (!isset($data[$api_response])) {
            throw new EE(EE::ERROR, 'Error Response');
        }
        $data['_response_'] = $data[$api_response];
        if (isset($data['sign'])) {
            $data['_response_']['sign'] = $data['sign'];
        }
        return $data[$api_response];
    }

    private function parseKey(string $key)
    {
        if (is_file(($str_key = trim($key)))) {
            $str_key = file_get_contents($str_key);
        }
        if (2 > substr_count($str_key, "\n")) {
            $str_key = wordwrap($str_key, 64, "\n", true);
        }
        return $str_key;
    }
}