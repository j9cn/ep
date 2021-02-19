<?php
/**
 * Created by PhpStorm.
 * User: J.H
 * Date: 2018/2/10
 * Time: 7:42
 */

namespace EP\Library\Payment;


use EP\Library\Payment\AliPay\AliPayBase;
use EP\Library\Payment\AliPay\AliPaySdk;

class AliPayTradePay extends AliPayBase
{

    /**
     * 网页支付
     */
    const CLIENT_COMPUTER = 1;

    /**
     * wap 页面支付
     */
    const CLIENT_WAP = 2;

    /**
     * 移动支付
     */
    const CLIENT_MOBILE = 3;

    private $client_type;

    function __construct(array $config, $client_type = self::CLIENT_COMPUTER)
    {
        parent::__construct($config);
        $this->client_type = $client_type;
    }

    function queryData()
    {
        $form_data = [
            'form_action' => $this->api . '_input_charset=' . $this->input_charset,
            'form_data' => []
        ];
        switch ($this->client_type) {
            case self::CLIENT_COMPUTER:
                $this->payment_type = 1;
                $parameter = $this->getParams();
                $submit_data = $this->filterParams($parameter);
                $sign = $this->signature($submit_data);
                $submit_data['sign'] = $sign;
                $submit_data['sign_type'] = $this->sign_type;
                $form_data['form_data'] = $submit_data;
                break;

            case self::CLIENT_WAP:
                $this->service = 'alipay.wap.create.direct.pay.by.user';
                $this->payment_type = 1;
                $parameter = $this->getParams(['seller_id' => $this->partner]);
                $submit_data = $this->filterParams($parameter);
                $sign = $this->signature($submit_data);
                $submit_data['sign'] = $sign;
                $submit_data['sign_type'] = $this->sign_type;
                $form_data['form_data'] = $submit_data;
                break;

            case self::CLIENT_MOBILE:
                $this->service = 'mobile.securitypay.pay';
                $this->payment_type = 1;
                $parameter = $this->getParams(['seller_id' => $this->partner]);
                $submit_data = $this->filterParams($parameter);
                $submit_data['LinkString'] = http_build_query($submit_data);
                $form_data['form_data'] = $submit_data;
                break;

            default:

        }
        return $form_data;
    }


    function queryForm($method = 'post', $auto_submit = false): string
    {
        $form = $this->queryData();
        $auto_submit = $auto_submit ? "document.forms['form_aliPay'].submit();" : '';
        $form_str = <<<tpl
<form id="form_aliPay" name="form_aliPay" action="%s" method="%s" style="display: none">
%s
</form>
<script>
%s
</script>
tpl;
        $input_str = '';
        foreach ($form['form_data'] as $key => $val) {
            $input = <<<input
<input type="hidden" name="%s" value="%s">\n
input;
            $input_str .= sprintf($input, $key, $val);
        }
        return sprintf($form_str, $form['form_action'], $method, $input_str, $auto_submit);
    }

    function acceptNotify()
    {
        return $this->verify($_POST);
    }

    function acceptReturn()
    {
        return $this->verify($_GET);
    }


    private function getParams(array $append_params = []): array
    {
        $parameter = [
            'service' => $this->service,
            'partner' => $this->partner,
            'seller_email' => $this->seller_email,
            'payment_type' => $this->payment_type,
            'notify_url' => $this->notify_url,
            'return_url' => $this->return_url,
            'out_trade_no' => $this->order_num,//商家订单号
            'subject' => $this->order_name, //商品名称
            'total_fee' => $this->amount,//订单金额
            'body' => $this->order_desc, //商品描述
            'show_url' => $this->goods_url,//商品地址
            'anti_phishing_key' => '',
            'exter_invoke_ip' => '',
            '_input_charset' => $this->input_charset
        ];
        return array_merge($parameter, $append_params);
    }

};;