<?php
/**
 * Author: OXIVO
 * QQ: 2502212233
 * Date: 2020/1/10
 * Time: 15:51
 */

namespace EP\Library\CloudServices\BaiDu\Translate;

use EP\Library\Curl\HttpRequest;

/**
 * Class TranslateText
 * @package EP\Library\CloudServices\BaiDu\Translate
 * @link https://api.fanyi.baidu.com/api/trans/product/apidoc#joinFile
 */
class TranslateText extends Lang
{

    private $query = '';
    private $from = 'auto';
    private $to = 'en';
    private $appid = '';
    private $salt = '';
    private $sign_key = '';
    private $tts = '1'; # 是否显示语音合成资源 tts=0显示，tts=1不显示
    private $dict = '1'; # 是否显示词典资源 dict=0显示，dict=1不显示
    private $action = '1'; #是否需使用自定义术语干预通用翻译API 1=是，0=否

    private $api = 'http://api.fanyi.baidu.com/api/trans/vip/translate';

    function __construct(string $app_id, string $sign_key, array $config = [])
    {
        $this->appid = $app_id;
        $this->sign_key = $sign_key;
        $this->salt = md5(TIME_FLOAT);
        foreach ($config as $key => $value) {
            $this->{$key} = $value;
        }
    }

    function trans(string $query, string $to = Lang::EN, string $from = 'auto')
    {
        return $this->query($query)->from($from)->to($to)->translate();
    }

    function query(string $query)
    {
        $this->query = $query;
        return $this;
    }

    function from(string $from)
    {
        $this->from = $from;
        return $this;
    }

    function to(string $to)
    {
        $this->to = $to;
        return $this;
    }


    function translate()
    {
        $query_data = [
            'q' => $this->query,
            'from' => $this->from,
            'to' => $this->to,
            'appid' => $this->appid,
            'salt' => $this->salt,
            'action' => $this->action,
            'tts' => $this->tts,
            'dict' => $this->dict,
            'sign' => $this->sign()
        ];
        $result = HttpRequest::get($this->api, $query_data);
        $parse = json_decode($result, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            if (isset($parse['trans_result'][0]['dst'])) {
                return $parse['trans_result'][0]['dst'];
            }
        }
        return '';
    }

    private function sign()
    {
        return md5($this->appid . $this->query . $this->salt . $this->sign_key);
    }

}