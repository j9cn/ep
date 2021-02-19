<?php
/**
 * Created by PhpStorm.
 * User: J.H
 * Date: 2018/2/14
 * Time: 0:55
 */

namespace EP\Library\Payment;


use EP\Exception\EE;
use EP\Library\Curl\HttpRequest;
use EP\Library\Payment\AliPay\AliPayTradeBase;
use EP\Library\Payment\AliPay\TradeOrder;
use EP\Library\Payment\AliPay\TradeOrderStatus;

/**
 * Class AliPayTradePayNewVersion
 * @package EP\Library\Payment
 * @property TradeOrder $createOrder
 * @property TradeOrder $refundOrder
 */
class AliPayTradePayNewVersion extends AliPayTradeBase
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

    /**
     * 接口版本
     */
    const API_VER = '1.0';

    const PAY_FORM_STRING = 1;
    const PAY_FORM_DATA = 2;

    /**
     * 支付宝账单类型
     */
    const BILL_TYPE_TRADE = 'trade'; //trade指商户基于支付宝交易收单的业务账单
    const BILL_TYPE_SIGNCUSTOMER = 'signcustomer'; //signcustomer是指基于商户支付宝余额收入及支出等资金变动的帐务账单

    /**
     * 请求客户端
     * @var int
     */
    private $client_type;


    private $pay;

    private $sdk_delegate;

    function __construct(array $config, $client_type = self::CLIENT_COMPUTER)
    {
        $this->sdk_delegate = parent::__construct($config);
        $this->setApiVersion(self::API_VER);
        $this->client_type = $client_type;
    }


    function pay($type = self::PAY_FORM_STRING, $auto_submit = true)
    {
        switch ($this->client_type) {
            case self::CLIENT_COMPUTER:
                $this->createOrder->order_info['product_code'] = 'FAST_INSTANT_TRADE_PAY';
                break;
            case self::CLIENT_WAP:
                $this->createOrder->order_info['product_code'] = 'QUICK_WAP_WAY';
                break;
        }

        if ($this->createOrder->disable_pay_channels) {
            $this->createOrder->order_info['enable_pay_channels'] = implode(',',
                $this->createOrder->enable_pay_channels);
        }

        if ($this->createOrder->enable_pay_channels) {
            $this->createOrder->order_info['enable_pay_channels'] = implode(',',
                $this->createOrder->enable_pay_channels);
        }

        $this->setBizContent($this->createOrder->order_info);
        $params = [
            'return_url' => $this->getReturnUrl(),
            'notify_url' => $this->getNotifyUrl()
        ];
        $common_params = $this->getCommonParams();
        $parameter = array_merge($common_params, $params);
        $submit_params = $this->filterParams($parameter);
        $submit_params['sign'] = $this->signature($submit_params);
        $form_data = [
            'action' => $this->getAPI(),
            'data' => $submit_params
        ];

        if ($type === self::PAY_FORM_STRING) {
            $auto_submit = $auto_submit ? "<script>document.forms['form_aliPay'].submit();</script>" : '';
            $form_str = <<<tpl
<form id="form_aliPay" name="form_aliPay" action="%s" method="post" style="display: none">
%s
</form>
%s
tpl;
            $input_str = '';
            foreach ($form_data['data'] as $key => $val) {
                $input = <<<input
<input type="hidden" name="%s" value="%s">\n
input;
                $input_str .= sprintf($input, $key, $val);
            }
            return sprintf($form_str, $form_data['action'], $input_str, $auto_submit);
        }
        return $form_data;

    }

    function query(string $order_num = '', string $trade_num = '')
    {
        $this->method = 'alipay.trade.query';
        $biz_content = [];
        if ($order_num) {
            $biz_content['out_trade_no'] = $order_num;
        }
        if ($trade_num) {
            $biz_content['trade_no'] = $trade_num;
        }
        if (!$biz_content) {
            throw new EE(EE::ERROR, '查询商户单号或支付宝交易单号不能为空');
        }
        $this->setBizContent($biz_content);

        $params = $this->getCommonParams();

        $info = $this->request($params);
        $data = $this->parseResponse($info);
        $order = new TradeOrderStatus($data);
        if (!$this->verify($info['_response_'])) {
            return false;
        }
        return $order;
    }

    function close(string $order_num = '', string $trade_num = '', string $operator_id = '')
    {
        $this->method = 'alipay.trade.close';
        $biz_content = [];
        if ($order_num) {
            $biz_content['out_trade_no'] = $order_num;
        }
        if ($trade_num) {
            $biz_content['trade_no'] = $trade_num;
        }
        if (!$biz_content) {
            throw new EE(EE::ERROR, '商户单号、支付宝交易单号不能同时为空');
        }
        if ($operator_id) {
            $biz_content['operator_id'] = $operator_id;
        }
        $this->setBizContent($biz_content);
        $params = $this->getCommonParams();
        $submit_params = $this->filterParams($params);
        $submit_params['sign'] = $this->signature($submit_params);
        return $this->request($submit_params);
    }

    /**
     * 退款
     *
     * @param string $order_num
     * @param string $trade_num
     * @param float $refund_amount
     * @param string $refund_reason
     * @param string $out_request_no
     *
     * @return bool
     * @throws EE
     */
    function refund(
        string $order_num = '',
        string $trade_num = '',
        float $refund_amount,
        string $refund_reason = '',
        string $out_request_no = ''
    ) {
        $this->method = 'alipay.trade.refund';
        $biz_content = [];
        if ($order_num) {
            $biz_content['out_trade_no'] = $order_num;
        }
        if ($trade_num) {
            $biz_content['trade_no'] = $trade_num;
        }
        if (!$biz_content) {
            throw new EE(EE::ERROR, '商户单号、支付宝交易单号不能同时为空');
        }
        $biz_content['refund_amount'] = $refund_amount;
        if ($refund_reason) {
            $biz_content['refund_reason'] = $refund_reason;
        }
        if ($out_request_no) {
            $biz_content['out_request_no'] = $out_request_no;
        }
        if ($this->refundOrder->order_info) {
            $biz_content = array_merge($biz_content, $this->refundOrder->order_info);
        }
        $this->setBizContent($biz_content);
        $params = $this->getCommonParams();
        $submit_params = $this->filterParams($params);
        $submit_params['sign'] = $this->signature($submit_params);
        $refund_info = $this->request($params);
        $this->parseResponse($refund_info);
        if (!$this->verify($refund_info['_response_'])) {
            return false;
        }
        return $refund_info['_response_'];
    }

    function refundQuery(
        string $order_num = '',
        string $trade_num = '',
        string $out_request_no = ''
    ) {
        $this->method = 'alipay.trade.fastpay.refund.query';
        $biz_content = [];
        if ($order_num) {
            $biz_content['out_trade_no'] = $order_num;
        }
        if ($trade_num) {
            $biz_content['trade_no'] = $trade_num;
        }
        if (!$biz_content) {
            throw new EE(EE::ERROR, '商户单号、支付宝交易单号不能同时为空');
        }
        if ($out_request_no) {
            $biz_content['out_request_no'] = $out_request_no;
        }
        $this->setBizContent($biz_content);
        $params = $this->getCommonParams();
        $submit_params = $this->filterParams($params);
        $submit_params['sign'] = $this->signature($submit_params);
        $refund_info = $this->request($params);
        $this->parseResponse($refund_info);
        if (!$this->verify($refund_info['_response_'])) {
            return false;
        }
        return $refund_info['_response_'];
    }

    /**
     * 获取账单下载地址
     *
     * @param string $bill_type
     * @param string $bill_date
     *
     * @return false|string
     */
    function getDownloadUrl(
        string $bill_type = self::BILL_TYPE_TRADE,
        string $bill_date = 'd'
    ) {
        $this->method = 'alipay.data.dataservice.bill.downloadurl.query';
        switch ($bill_date) {
            case 'm':
            case 'M':
                $date = date('Y-m');
                break;
            case 'd':
            case 'D':
                $date = date('Y-m-d');
                break;
            default:
                $date = $bill_date;
        }
        $biz_content = [
            'bill_type' => $bill_type,
            'bill_date' => $date
        ];
        $this->setBizContent($biz_content);
        $params = $this->getCommonParams();
        $submit_params = $this->filterParams($params);
        $submit_params['sign'] = $this->signature($submit_params);
        $info = $this->request($params);
        $response = $this->parseResponse($info);
        if (!$this->verify($info['_response_'])) {
            return false;
        }
        if (isset($response['code']) && $response['code'] === 10000 && !empty($response['bill_download_url'])) {
            return $response['bill_download_url'];
        }
        return false;
    }

    function __get($property)
    {
        switch ($property) {
            case 'createOrder':
                return $this->createOrder = new TradeOrder();
            case 'refundOrder':
                return $this->refundOrder = new TradeOrder();

        }
    }

}