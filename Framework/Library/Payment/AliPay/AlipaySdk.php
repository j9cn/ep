<?php
/**
 * Created by PhpStorm.
 * User: J.H
 * Date: 2018/2/7
 * Time: 15:21
 */
declare(strict_types=1);

namespace EP\Library\Payment\AliPay;


use EP\Exception\EE;
use EP\Library\Curl\HttpRequest;

class AliPaySdk
{
    /**
     * 合作身份者id，以2088开头的16位纯数字
     * @var string
     */
    protected $partner;

    /**
     * 一般情况下合作身份者id就是seller_id
     * @var string
     */
    protected $seller_id;
    /**
     * 商户帐号
     * @var string
     */
    protected $seller_email;

    /**
     * 安全检验码，以数字和字母组成的32位字符
     * @var string
     */
    protected $key;

    /**
     * 商户的私钥文件相对路径/密匙文本
     * @var string
     */
    protected $private_key;

    /**
     * 支付宝公钥文件相对路径/公钥文本
     * @var string
     */
    protected $ali_public_key;

    /**
     *签名方式
     * @var string
     */
    protected $sign_type = 'MD5';

    /**
     * 字符编码格式 目前支持 gbk 或 utf-8
     * @var string
     */
    protected $input_charset = 'utf-8';

    protected $service = 'create_direct_pay_by_user';

    /**
     * ca证书路径地址，用于curl中ssl校验
     * @var string
     */
    protected $ca_cert;

    /**
     * 访问模式,根据自己的服务器是否支持ssl访问，若支持请选择https；若不支持请选择http
     * @var string
     */
    protected $transport = 'http';

    protected $api = 'https://mapi.alipay.com/gateway.do?';

    protected $notify_url = 'http://商户网关地址/alipay/notify_url.php';

    protected $return_url = 'http://商户网关地址/alipay/return_url.php';

    protected $payment_type = 1;

    protected $api_version = 'old';

    function __construct(array $config)
    {
        if (!isset($config['partner']) || empty($config['partner'])) {
            throw new EE(EE::ERROR, '无效合作身份者id[partner]');
        }
        $this->partner = $this->seller_id = $config['partner'];

        if (!isset($config['seller_email']) || empty($config['seller_email'])) {
            throw new EE(EE::ERROR, '无效支付宝帐号[seller_email]');
        }
        $this->seller_email = $config['seller_email'];


        if (!empty($config['sign_type'])) {
            $this->sign_type = strtoupper($config['sign_type']);
            if (!in_array($this->sign_type, ['MD5', 'RSA', 'DSA'])) {
                throw new EE(EE::ERROR, "不支持{$this->sign_type}方式签名，[sign_type仅支持：MD5,RSA,DSA]");
            }
            if ('MD5' !== $this->sign_type) {
                if (empty($config['private_key'])) {
                    throw new EE(EE::ERROR, '无效商户的私钥[private_key]');
                }
                if (is_file(($private_key = trim($config['private_key'])))) {
                    $this->private_key = file_get_contents($private_key);
                } else {
                    $this->private_key = $private_key;
                }
                $begin_private = "-----BEGIN {$this->sign_type} PRIVATE KEY-----";
                $end_private = "-----END {$this->sign_type} PRIVATE KEY-----";
                if ($begin_private !== substr($this->private_key, 0, strlen($begin_private))) {
                    $this->private_key = $begin_private . "\n" . $this->private_key;
                }
                if ($end_private !== substr($this->private_key, -strlen($end_private), strlen($end_private))) {
                    $this->private_key .= "\n" . $end_private;
                }

                if (empty($config['public_key'])) {
                    throw new EE(EE::ERROR, '无效支付宝公钥[public_key]');
                }
                if (is_file(($public_key = trim($config['public_key'])))) {
                    $this->ali_public_key = file_get_contents($public_key);
                } else {
                    $this->ali_public_key = $public_key;
                }
                $begin_public = '-----BEGIN PUBLIC KEY-----';
                $end_public = '-----END PUBLIC KEY-----';
                if ($begin_public !== substr($this->ali_public_key, 0, strlen($begin_public))) {
                    $this->ali_public_key = $begin_public . "\n" . $this->ali_public_key;
                }
                if ($end_public !== substr($this->ali_public_key, -strlen($end_public), strlen($end_public))) {
                    $this->ali_public_key .= "\n" . $end_public;
                }
            }
        }

        if ('MD5' === $this->sign_type) {
            if (!isset($config['key']) || empty($config['key'])) {
                throw new EE(EE::ERROR, '无效安全检验码[key]');
            }
            $this->key = $config['key'];
        }


        $this->ca_cert = (!empty($config['cacert']) && is_file($config['cacert'])) ? $config['cacert'] : dirname(__FILE__) . DS . 'cacert.pem';

        if (!empty($config['transport']) && 'https' === strtolower($config['transport'])) {
            $this->transport = 'https';
        }

        if (!empty($config['notify_url'])) {
            $this->notify_url = $config['notify_url'];
        }
        if (!empty($config['return_url'])) {
            $this->return_url = $config['return_url'];
        }
    }

    /**
     * 设置同步回跳URL
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

    /**
     * 设置回调通知地址
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

    protected function signature(array $params): string
    {
        if (empty($this->notify_url)) {
            throw new EE(EE::ERROR, '无效异步通知URL[notify_url]');
        }
        if (empty($this->return_url)) {
            throw new EE(EE::ERROR, '无效同步回跳URL[return_url]');
        }
        switch ($this->sign_type) {
            case 'MD5':
                return md5($this->paramsToString($params) . $this->key);
            case 'RSA':
            case 'DSA':
                $private_key_id = openssl_get_privatekey($this->private_key);
                if (false === $private_key_id) {
                    throw new EE(EE::ERROR, $this->sign_type . '私钥格式错误');
                }
                openssl_sign($this->paramsToString($params), $sign, $private_key_id,
                    ('RSA' === $this->sign_type) ? OPENSSL_ALGO_SHA1 : OPENSSL_ALGO_DSS1);
                openssl_free_key($private_key_id);
                return base64_encode($sign);

            default:
                return '';
        }
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
            case 'MD5':
                return ($sign === md5($data . $this->key));
            case 'RSA':
            case 'DSA':
                $public_key_id = openssl_get_publickey($this->ali_public_key);
                if (false === $public_key_id) {
                    throw new EE(EE::ERROR, $this->sign_type . '公钥格式错误');
                }
                $verify = (bool)openssl_verify($data, base64_decode($sign), $public_key_id,
                    ('RSA' === $this->sign_type) ? OPENSSL_ALGO_SHA1 : OPENSSL_ALGO_DSS1);
                openssl_free_key($public_key_id);
                return $verify;
                break;
            default:
                return false;
        }
    }

    /**
     * 验证请求合法性
     *
     * @param array $params
     *
     * @return bool
     */
    protected function verify(array $params): bool
    {
        if (empty($params) && empty($params['sign'])) {
            return false;
        }
        $data = $this->paramsToString($this->filterParams($params));
        if (!$this->verifySignature($data, (string)$params['sign'])) {
            return false;
        }
        if (!empty($params['notify_id']) && !$this->checkNotifyId($params['notify_id'])) {
            return false;
        }
        return true;
    }

    /**
     * 远程检验支付宝通知ID
     *
     * @param string $notify_id
     *
     * @return bool
     */
    private function checkNotifyId(string $notify_id): bool
    {
        $params = [
            'service' => 'notify_verify',
            'partner' => $this->partner,
            'notify_id' => $notify_id
        ];

        $status = (new HttpRequest())
            ->setOptions(CURLOPT_SSL_VERIFYPEER, true)//打开SSL证书认证
            ->setOptions(CURLOPT_SSL_VERIFYHOST, 2)//使用严格认证
            ->setOptions(CURLOPT_CAINFO, $this->ca_cert)//证书地址
            ->request($this->api, $params);
        if (false !== $status) {
            $status = ('true' === strtolower(trim($status)));
        }
        return $status;
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
    protected function filterParams(array $params): array
    {
        $filter_data = [];
        if (1 == $this->payment_type) {
            foreach ($params as $k => $v) {
                if (!empty($v) && !in_array($k, ['sign', 'sign_type'])) {
                    $filter_data[$k] = $v;
                }
            }
            ksort($filter_data);
        }

        return $filter_data;
    }

}