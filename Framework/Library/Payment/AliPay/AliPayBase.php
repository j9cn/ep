<?php
/**
 * Created by PhpStorm.
 * User: J.H
 * Date: 2018/2/10
 * Time: 7:48
 */

namespace EP\Library\Payment\AliPay;



abstract class AliPayBase extends AliPaySdk
{

    /**
     * 金额
     * @var float
     */
    protected $amount;

    /**
     * 订单号
     * @var string
     */
    protected $order_num;

    /**
     * 订单名称
     * @var string
     */
    protected $order_name;

    /**
     * 商品地址
     * @var string
     */
    protected $goods_url = '';

    /**
     * 订单描述
     * @var string
     */
    protected $order_desc = '暂无描述';

    /**
     * 获取发送支付请求数据
     * @return array
     */
    abstract function queryData();

    /**
     * 获取发送支付请求表单
     *
     * @param string $method
     * @param bool $auto_submit
     *
     * @return string
     */
    abstract function queryForm($method, $auto_submit = false);

    /**
     * 处理回调
     */
    abstract function acceptNotify();

    /**
     * 处理同步回调
     */
    abstract function acceptReturn();

    /**
     * 设置商户单号
     *
     * @param string $order_num
     *
     * @return $this
     */
    function setOrderNum(string $order_num): self
    {
        $this->order_num = $order_num;
        return $this;
    }

    /**
     * 设置金额
     *
     * @param $amount
     *
     * @return $this
     */
    function setAmount(float $amount): self
    {
        $this->amount = $amount;
        return $this;
    }

    /**
     * 设置订单名称
     *
     * @param string $order_name
     *
     * @return $this
     */
    function setOrderName(string $order_name): self
    {
        $this->order_name = $order_name;
        return $this;
    }

    /**
     * 设置订单描述
     *
     * @param string $order_desc
     *
     * @return $this
     */
    function setOrderDesc(string $order_desc): self
    {
        $this->order_desc = $order_desc;
        return $this;
    }

}