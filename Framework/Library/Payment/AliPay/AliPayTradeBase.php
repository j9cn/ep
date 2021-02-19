<?php
/**
 * Created by PhpStorm.
 * User: J.H
 * Date: 2018/2/14
 * Time: 1:11
 */

namespace EP\Library\Payment\AliPay;


abstract class AliPayTradeBase extends AliPaySDKnv
{

    /**
     * 请求数据
     * @return mixed
     */
    abstract function pay();

    /**
     * 统一收单交易退款接口
     *
     * @param string $order_num
     * @param string $trade_num
     * @param float $refund_amount
     * @param string $refund_reason
     * @param string $out_request_no
     *
     * @return mixed
     */
    abstract function refund(
        string $order_num = '',
        string $trade_num = '',
        float $refund_amount,
        string $refund_reason = '',
        string $out_request_no = ''
    );

    /**
     * 统一收单交易退款查询接口
     * @return mixed
     */
    abstract function refundQuery();

    /**
     * 统一收单线下交易查询接口
     * @return mixed
     */
    abstract function query();

    /**
     * 统一收单交易关闭接口
     * @return mixed
     */
    abstract function close();

    /**
     * 查询对账单下载地址
     * @return mixed
     */
    abstract function getDownloadUrl();
}