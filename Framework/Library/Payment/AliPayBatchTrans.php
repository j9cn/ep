<?php
/**
 * Created by PhpStorm.
 * User: J.H
 * Date: 2018/2/12
 * Time: 10:01
 */

namespace EP\Library\Payment;


use EP\Library\Curl\HttpRequest;
use EP\Library\Payment\AliPay\AliPaySdk;

class AliPayBatchTrans extends AliPaySdk
{

    /**
     * 转账帐号
     * @var string
     */
    private $email;

    /**
     * 转账名称
     * @var string
     */
    private $account_name;

    /**
     * 转账日期
     * @var string
     */
    private $pay_date;

    /**
     * 转账批次
     * @var string
     */
    private $batch_num;

    /**
     * 总转账笔数
     * @var int
     */
    private $batch_count = 0;

    /**
     * 总转账金额
     * @var int
     */
    private $batch_fee = 0;

    /**
     * 付款详细数据
     * @var array
     */
    private $detail_data = [];

    /**
     * 业务扩展参数
     * @var string
     */
    private $extend_param;

    function __construct(array $config)
    {
        parent::__construct($config);
        $this->pay_date = date('Ymd');
    }

    /**
     * 设置转账帐号信息
     *
     * @param string $account_email
     * @param string $account_name
     * @param int $batch_num
     *
     * @return $this
     */
    function setTransAccount(string $account_email, string $account_name, int $batch_num): self
    {
        $this->service = 'batch_trans_notify';
        $this->email = $account_email;
        $this->account_name = $account_name;
        $this->batch_num = $this->pay_date . str_pad($batch_num, 4, '0', STR_PAD_LEFT);
        return $this;
    }

    /**
     * 设置业务扩展参数
     * 参数格式：参数名1^参数值1|参数名2^参数值2|……
     *
     * @param string $extend
     *
     * @return AliPayBatchTrans
     */
    function setExtendParam(string $extend): self
    {
        $this->extend_param = $extend;
        return $this;
    }

    /**
     * 设置收款信息
     * 流水号1^收款方账号1^收款账号姓名1^付款金额1^备注说明1
     *
     * @param string $order 转账单号
     * @param string $account 收款帐号
     * @param string $name 收款帐号姓名
     * @param float $amount 转账金额
     * @param string $note 转账备注
     *
     * @return $this
     */
    function to(string $order, string $account, string $name, float $amount, string $note = '转账备注'): self
    {
        $this->batch_count++;
        $this->batch_fee += $amount;
        $trans_detail = [
            $order,
            $account,
            $name,
            $amount,
            $note
        ];
        $this->detail_data[] = implode('^', $trans_detail);
        return $this;
    }

    function trans()
    {
        $form_data = $this->buildData();
        $status = HttpRequest::post($this->api . '_input_charset=' . $this->input_charset, $form_data);
        if (false === $status) {
            return 'error';
        }
        return mb_convert_encoding($status, $this->input_charset, 'gb2312, utf-8');
        return 'success';
    }

    function queryData(): array
    {
        $form_data = [
            'form_action' => $this->api . '_input_charset=' . $this->input_charset,
            'form_data' => $this->buildData()
        ];
        return $form_data;
    }

    function queryForm(): string
    {
        $form = $this->queryData();
        $form_str = <<<tpl
<form id="form_aliPay" name="form_aliPay" action="%s" method="post" style="display: none">
%s
</form>
tpl;
        $input_str = '';
        foreach ($form['form_data'] as $key => $val) {
            $input = <<<input
<input type="hidden" name="%s" value="%s">\n
input;
            $input_str .= sprintf($input, $key, $val);
        }
        return sprintf($form_str, $form['form_action'], $input_str);
    }

    /**
     * 编译必须参数
     * @return array
     */
    private function buildData(): array
    {
        $parameter = [
            'service' => $this->service,
            'partner' => $this->partner,
            'notify_url' => $this->notify_url,
            'email' => $this->email,
            'account_name' => $this->account_name,
            'pay_date' => $this->pay_date,
            'batch_no' => $this->batch_num,
            'batch_fee' => $this->batch_fee,
            'batch_num' => $this->batch_count,
            'detail_data' => implode('|', $this->detail_data),
            '_input_charset' => $this->input_charset
        ];
        $submit_params = $this->filterParams($parameter);
        $submit_params['sign'] = $this->signature($submit_params);
        $submit_params['sign_type'] = $this->sign_type;
        return $submit_params;
    }
}