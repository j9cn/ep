<?php
/**
 * Created by PhpStorm.
 * User: J.H
 * Date: 2018/6/3
 * Time: 0:45
 */

namespace EP\Library\CloudServices\AliYun\SMS;


use EP\Exception\ELog;
use EP\Library\CloudServices\AliYun\Common\BaseApi;

class AliDaYuSms extends BaseApi
{
    private $method = 'alibaba.aliqin.fc.sms.num.send';
    private $app_key = '';
    private $app_secret = '';

    private $sign_method = 'md5';
    private $sign_string = '';
    private $timestamp = '';
    private $format = 'json';
    private $v = '2.0';
    private $simplify = 'true';

    function __construct(array $config = array('appKey' => '', 'appSecret' => ''))
    {
        $this->api = 'https://eco.taobao.com/router/rest';
        $this->timestamp = date('Y-m-d H:i:s');
        $this->Format = strtoupper($this->format);

        if (empty($config['appKey']) || empty($config['appSecret'])) {
            ELog::error('invalid parameter:appKey or appSecret, format: array("appKey" => "xxx", "appSecret" => "xxx")');
        }
        $this->app_key = $config['appKey'];
        $this->sign_string = $this->app_secret = $config['appSecret'];
        parent::__construct();
    }

    /**
     * 设置模版短信
     * @link https://api.alidayu.com/doc2/apiDetail.htm?apiId=25450
     *
     * @param string $sms_template_code
     * @param string|array $rec_num
     * @param array $sms_param
     * @param string $sign_name
     *
     * @return $this
     */
    function setTemplate($rec_num, $sms_template_code, array $sms_param, $sign_name)
    {
        $this->setParam('sms_type', 'normal');
        $this->setParam('sms_free_sign_name', $sign_name);
        $this->setParam('sms_template_code', $sms_template_code);
        if (is_array($rec_num)) {
            $rec_num = implode(',', $rec_num);
        }
        $this->setParam('rec_num', $rec_num);
        $this->setParam('sms_param', json_encode($sms_param, 256));
        $this->buildParams();
        return $this;
    }

    /**
     * @return bool
     */
    function send()
    {
        $result = parent::send();
        if (isset($result['result']['success']) && $result['result']['success'] == true) {
            return true;
        }
        return false;
    }

    protected function buildParams()
    {
        $common_params = array(
            'method' => $this->method,
            'app_key' => $this->app_key,
            'sign_method' => $this->sign_method,
            'timestamp' => $this->timestamp,
            'format' => $this->format,
            'v' => $this->v,
            'simplify' => $this->simplify
        );
        $this->params = array_merge($this->params, $common_params);
        $this->setParam('sign', $this->buildSign());
    }

    /**
     * 签名算法
     * @link http://open.taobao.com/docs/doc.htm?articleId=101617&docType=1&treeId=1#s4
     * @return string
     */
    private function buildSign()
    {
        ksort($this->params);
        foreach ($this->params as $key => $value) {
            $this->sign_string .= $key . $value;
        }
        $this->sign_string .= $this->app_secret;
        return strtoupper(md5($this->sign_string));
    }
}