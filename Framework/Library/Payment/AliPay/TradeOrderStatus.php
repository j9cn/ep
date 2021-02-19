<?php
/**
 * Created by PhpStorm.
 * User: J.H
 * Date: 2018/2/16
 * Time: 11:06
 */

namespace EP\Library\Payment\AliPay;


use EP\Exception\EE;

class TradeOrderStatus
{

    private $data = [];

    /**
     * 网关返回码
     * <pre>
     * 10000(接口调用成功)
     * 20000(服务不可用)
     * 20001(授权权限不足)
     * 40001(缺少必选参数)
     * 40002(非法的参数)
     * 40004(业务处理失败)
     * 40006(权限不足)
     * </pre>
     * @link https://docs.open.alipay.com/common/105806
     * @var string
     */
    public $code;
    /**
     * 对应$this->code信息
     * @link https://docs.open.alipay.com/common/105806
     * @var string
     */
    public $msg;
    /**
     * @link https://docs.open.alipay.com/common/105806
     * @var string
     */
    public $sub_code;
    /**
     * @link https://docs.open.alipay.com/common/105806
     * @var string
     */
    public $sub_msg;
    /**
     * 签名
     * @var string
     */
    public $sign;


    /**
     * 支付宝交易号
     * 必有
     * @var string
     */
    public $trade_no;
    /**
     * 商家订单号
     * 必有
     * @var string
     */
    public $out_trade_no;
    /**
     * 买家支付宝账号
     * 必有
     * @var string
     */
    public $buyer_logon_id;
    /**
     * 交易状态
     * 必有
     * <pre>
     * WAIT_BUYER_PAY（交易创建，等待买家付款）
     * TRADE_CLOSED（未付款交易超时关闭，或支付完成后全额退款）
     * TRADE_SUCCESS（交易支付成功）
     * TRADE_FINISHED（交易结束，不可退款）
     * </pre>
     * @var string
     */
    public $trade_status;
    /**
     * 交易的订单金额
     * 必有
     * @var float
     */
    public $total_amount;
    /**
     * 实收金额
     * @var float
     */
    public $receipt_amount;
    /**
     * 买家实付金额
     * @var float
     */
    public $buyer_pay_amount;
    /**
     * 积分支付的金额
     * @var float
     */
    public $point_amount;
    /**
     * 交易中用户支付的可开具发票的金额
     * @var float
     */
    public $invoice_amount;
    /**
     * 本次交易打款给卖家的时间
     * @var string
     */
    public $send_pay_date;
    /**
     * 商户门店编号
     * @var string
     */
    public $store_id;
    /**
     * 商户机具终端编号
     * @var string
     */
    public $terminal_id;
    /**
     * 交易支付使用的资金渠道
     * 必有
     * @var array
     */
    public $fund_bill_list;
    /**
     * 请求交易支付中的商户店铺的名称
     * @var string
     */
    public $store_name;
    /**
     * 买家在支付宝的用户id
     * 必有
     * @var string
     */
    public $buyer_user_id;
    /**
     * 买家用户类型。CORPORATE:企业用户；PRIVATE:个人用户。
     * @var string
     */
    public $buyer_user_type;


    function __construct($data)
    {
        $this->data = $data;
        $this->init();
    }

    protected function init()
    {
        foreach ($this->data as $key => $val) {
            $this->{$key} = $val;
        }
    }

    function getData()
    {
        return $this->data;
    }
}