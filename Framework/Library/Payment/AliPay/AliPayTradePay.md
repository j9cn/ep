#支付宝收款旧DSK

```php
$config = [
            // 无效合作身份者id，partner 如需 seller_id，此项等价于 seller_id
            'partner' => '2088501624560335',
            // 商户帐号
            'seller_email' => 'test@alipay.com',
            // 使用MD5签名时必须
            'key' => '202cb962ac59075b964b07152d234b70',
            // 可用 MD5|RSA|DSA
            'sign_type' => 'RSA',
            // 使用MD5签名可留空，RSA|DSA必须填写正确的对应私匙
            'private_key' => <<<private
-----BEGIN RSA PRIVATE KEY-----
MIICXgIBAAKBgQDRB4YJyOyE2jSy45ceYJlxl4M/Gh+e9ZIWdv9uQzz1U0qvE0rE
VnzwFi7WLyyfC+xaUYowPNiDwVXwxOJOTPDIKxAImddiv9joCTme7XXH+BV9Vpyw
kOIrUqDMPUbptxKEKS1Baz6A9tuvmttAkUjyZCS7x87EVP6KxX2r7hpx2QIDAQAB
AoGAEVJ1SiRLbWsDyPtRT6QjsyUiLD2G905Ub+YmnsWVrKLdYorPvFuKeP7tnLRG
F1wOlyGAuSShsLF55Lz8IA8COP3mdW/5UYsKONiNa66dODVHxkZ+qxU+0BZ6e0TJ
b1LXzpyvQlCFr+Rz2HuPfF9vJ/6JZh3k4aoIw4IdXchpzi0CQQD2MRoxir2952Al
CIS36ZG2d5R+bD9vEVccwzjh1uBitzrIpG6U/fOd6sl7RTkeRfyw3w+4PR6rSKjv
ZRZMfn6vAkEA2VtnVw8PPK/f6Athwt5VJ40ONhahf592BHB3QEDxCnyvZ7SRC1QA
BlKRxsgjf9rfj3oOtE/pfyxDpqKbHAr59wJBALUj07338zuy7g7RgbU/6bJzsZKD
WvkBrTLAgS3JyDdZ0aqnMaX2ZDUg9zX37NrVa+NHfG12qwYj5AigPBP0TokCQQDR
+ktK/3Fo3z8fnF9FMiRxoQMpnZNHB3WrtDqACDzUNL//H6E/oFalxP6vWolw6rEu
mmu6Jbkc8lYolM3juXbhAkEAhGI4brYYBfwKATNVWl+O5SJKdRIJE84Djikr1AhZ
fvCUSYrIXKpKApLf7wYGUHeOKp11eGqzQSrXTMLgo6FvTA==
-----END RSA PRIVATE KEY-----
private
            ,
            // 使用MD5签名可留空，RSA|DSA必须填写正确的对应公匙
            'public_key' => <<<public
-----BEGIN PUBLIC KEY-----
MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDRB4YJyOyE2jSy45ceYJlxl4M/
Gh+e9ZIWdv9uQzz1U0qvE0rEVnzwFi7WLyyfC+xaUYowPNiDwVXwxOJOTPDIKxAI
mddiv9joCTme7XXH+BV9VpywkOIrUqDMPUbptxKEKS1Baz6A9tuvmttAkUjyZCS7
x87EVP6KxX2r7hpx2QIDAQAB
-----END PUBLIC KEY-----
public
            ,
            // 如果不填写外置cacert文件，SDK加载内置cacert文件
            'cacert' => '',
            // 访问模式,根据自己的服务器是否支持ssl访问，若支持请选择https；若不支持请选择http
            'transport' => 'http',
            // 异步服务器通知地址
            'notify_url' => $this->url('payment.notifyAliPay'),
            // 同步跳转通知地址
            'return_url' => $this->url('payment.returnAliPay'),
        ];

        $ali = new AliPayTradePay($config);
        $ali->setOrderNum('20180210224408')
            ->setAmount(0.01)
            ->setOrderName('测试商品名称')
            ->setOrderDesc('商品介绍');
```
>取提交请求数据
```php
        $ali->queryData()
```
>return
```php
        array(
               'form_action' => 'https://mapi.alipay.com/gateway.do?_input_charset=utf-8',
               'form_data' =>
                        array(
                            '_input_charset' => 'utf-8',
                            'body' => '商品介绍',
                            'notify_url' => 'http://23469.com/payment.notifyAliPay',
                            'out_trade_no' => '20180210224408',
                            'partner' => '2088501624560335',
                            'payment_type' => 1,
                            'return_url' => 'http://23469.com/payment.returnAliPay',
                            'seller_email' => 'test@alipay.com',
                            'service' => 'create_direct_pay_by_user',
                            'subject' => '测试商品名称',
                            'total_fee' => 0.01,
                            'sign' => 'So3qiL/LncH4SE9Ls9hf+D4Laf/en914chcruGaeycPa2XzXtvrR6W1CHSiLOjdECLlVvo/AZ8u28ncRG3zWXCRPrOYygHlx6gAqqhHHoTyrSTVDheUFPXDIpzKPXcdkIgzAF2Ubw8Ac3BKVeLWHkuDMbdooyw6eF8L210itxO0=',
                            'sign_type' => 'RSA'
                        )
        )
```

>取生成请求表格
```php
        /**
         * 获取发送支付请求表单
         *
         * @param string $method 表格提交 post|get
         * @param bool $auto_submit 是否自动提交 true|false
         *
         * @return string
         */
        $ali->queryForm()
```
>return
```html
<form id="form_aliPay" name="form_alipay" action="https://mapi.alipay.com/gateway.do?_input_charset=utf-8" method="post" style="display: none">
<input type="hidden" name="_input_charset" value="utf-8">
<input type="hidden" name="body" value="商品介绍">
<input type="hidden" name="notify_url" value="http://23469.com/payment.notifyAliPay">
<input type="hidden" name="out_trade_no" value="20180210224408">
<input type="hidden" name="partner" value="2088501624560335">
<input type="hidden" name="payment_type" value="1">
<input type="hidden" name="return_url" value="http://23469.com/payment.returnAliPay">
<input type="hidden" name="seller_email" value="test@alipay.com">
<input type="hidden" name="service" value="create_direct_pay_by_user">
<input type="hidden" name="subject" value="测试商品名称">
<input type="hidden" name="total_fee" value="0.01">
<input type="hidden" name="sign" value="So3qiL/LncH4SE9Ls9hf+D4Laf/en914chcruGaeycPa2XzXtvrR6W1CHSiLOjdECLlVvo/AZ8u28ncRG3zWXCRPrOYygHlx6gAqqhHHoTyrSTVDheUFPXDIpzKPXcdkIgzAF2Ubw8Ac3BKVeLWHkuDMbdooyw6eF8L210itxO0=">
<input type="hidden" name="sign_type" value="RSA">

</form>
<script>
document.forms['form_aliPay'].submit();
</script>
```
