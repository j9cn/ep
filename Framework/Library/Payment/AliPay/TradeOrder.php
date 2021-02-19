<?php
/**
 * Created by PhpStorm.
 * User: J.H
 * Date: 2018/2/17
 * Time: 7:47
 */

namespace EP\Library\Payment\AliPay;


class TradeOrder
{

    public $enable_pay_channels = [];
    public $disable_pay_channels = [];
    public $order_info = [];

    function setOrderInfo(
        string $order_num,
        float $order_total_amount,
        string $subject, string $body = '',
        int $goods_type = 1,
        int $order_timeout_express = 0
    ) {
        $express = (0 === $order_timeout_express) ? '1c' : "{$order_timeout_express}m";
        $this->order_info = [
            'out_trade_no' => $order_num,
            'total_amount' => $order_total_amount,
            'timeout_express' => $express,
            'subject' => $subject,
            'body' => ('' === $body) ? $subject : $body,
            'goods_type' => $goods_type
        ];
        return $this;
    }

    /**
     * 设置商品信息
     *
     * @param string $goods_id 商品ID
     * @param string $goods_name 商品名称
     * @param string $goods_show_url 商品展示网址
     * @param float $price 商品价格
     *
     * @return $this
     */
    /**
     * 设置商品信息
     *
     * @param string $goods_id 商品ID
     * @param string $goods_name 商品名称
     * @param string $goods_show_url 商品展示网址
     * @param float $price 商品价格
     * @param string $goods_category 商品类目
     * @param string $goods_body 商品描述信息
     * @param int $quantity 商品数量
     *
     * @return $this
     */
    function setProductInfo(
        string $goods_id = '',
        string $goods_name = '',
        string $goods_show_url = '',
        float $price = 0,
        string $goods_category = '',
        string $goods_body = '',
        int $quantity = 1
    ) {
        $product_info = [];
        if ($goods_id) {
            $product_info['goods_id'] = $goods_id;
        }

        if ($goods_name) {
            $product_info['goods_name'] = $goods_name;
        }
        if ($goods_show_url) {
            $product_info['show_url'] = $goods_show_url;
        }
        if ($price) {
            $product_info['price'] = $price;
        }
        if ($goods_category) {
            $product_info['goods_category'] = $goods_category;
        }
        if ($goods_body) {
            $product_info['goods_body'] = $goods_body;
        }
        if ($quantity) {
            $product_info['quantity'] = $quantity;
        }
        if ($product_info) {
            $this->order_info['goods_detail'] = json_encode($product_info, JSON_UNESCAPED_UNICODE);
        }
        return $this;
    }

    /**
     * 设置商户的终端信息
     *
     * @param string $terminal_id 终端编号
     * @param string $store_id 门店编号
     * @param string $operator_id 操作员编号
     *
     * @return $this
     */
    function setTerminal(string $terminal_id = '', string $store_id = '', string $operator_id = '')
    {
        if ($terminal_id) {
            $this->order_info['terminal_id'] = $terminal_id;
        }
        if ($store_id) {
            $this->order_info['store_id'] = $store_id;
        }
        if ($operator_id) {
            $this->order_info['operator_id'] = $operator_id;
        }
        return $this;
    }

    /**
     * 允许使用花呗分期
     *
     * @param int $installment_count 分期期数
     * @param int $seller_percent 0-用户支付手续费；100-商家支付手续费
     *
     * @return $this
     */
    function allowInstallment(int $installment_count = 3, int $seller_percent = 0)
    {
        $this->enablePayChannels('pcreditpayInstallment');
        $this->order_info['extend_params'] = [
            'hb_fq_num' => $installment_count,
            'hb_fq_seller_percent' => $seller_percent <= 100 ? 0 : 100
        ];
        return $this;
    }

    /**
     * @param array|string $channel
     *
     * @return $this
     */
    function enablePayChannels($channel)
    {
        if (is_array($channel)) {
            $this->enable_pay_channels += $channel;
        } else {
            $this->enable_pay_channels[] = $channel;
        }
        return $this;
    }

    /**
     * @param array|string $channel
     *
     * @return $this
     */
    function disablePayChannels($channel)
    {
        if (is_array($channel)) {
            $this->disable_pay_channels += $channel;
        } else {
            $this->disable_pay_channels[] = $channel;
        }
        return $this;
    }
}