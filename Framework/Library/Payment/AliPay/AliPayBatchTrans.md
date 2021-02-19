


#支付宝批量转账

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
            'notify_url' => $this->url('payment.notifyAliPay')
        ];

        $ali = new AliPayBatchTrans($config);
        $ali->setTransAccount('ali@qq.com', '支付宝名称', 1)
            ->to(123, 'a@qq.com', '黄山', 10000,'年终奖金')
            ->setNotifyUrl('https://myurl.com/trans.php');
            
```
>取提交请求数据
```php
        $ali->queryData()
```
>return
```php
        array (
          'form_action' => 'https://mapi.alipay.com/gateway.do?_input_charset=utf-8',
          'form_data' => 
          array (
            '_input_charset' => 'utf-8',
            'account_name' => '支付宝名称',
            'batch_fee' => 10000,
            'batch_no' => '201802140001',
            'batch_num' => 1,
            'detail_data' => '123^a@qq.com^黄山^10000^年终奖金',
            'email' => 'ali@qq.com',
            'notify_url' => 'https://myurl.com/trans.php',
            'partner' => '2088501624560335',
            'pay_date' => '20180214',
            'service' => 'batch_trans_notify',
            'sign' => 'XNS7p1JUoTV4ygi0gWGeazsZjHUjrqm90Ih22mjeWvP1E/HLWAMc074AJwWwz/iwmpbm2YjqB/l+LwZ9+TfmYVWKuwgULqBsRkjLVVV9MKSae+/RcUyaHpja3msr5SlGpEvGA2vww/LJCk/fcAJSpzdtvHBpGEhmmlT1egsETCw=',
            'sign_type' => 'RSA',
          ),
        )
```

>取生成请求表格
```php
        $ali->queryForm()
```
>return
```html
<form id="form_aliPay" name="form_aliPay" action="https://mapi.alipay.com/gateway.do?_input_charset=utf-8" method="post" style="display: none">
<input type="hidden" name="_input_charset" value="utf-8">
<input type="hidden" name="account_name" value="支付宝名称">
<input type="hidden" name="batch_fee" value="10000">
<input type="hidden" name="batch_no" value="201802140001">
<input type="hidden" name="batch_num" value="1">
<input type="hidden" name="detail_data" value="123^a@qq.com^黄山^10000^年终奖金">
<input type="hidden" name="email" value="ali@qq.com">
<input type="hidden" name="notify_url" value="https://myurl.com/trans.php">
<input type="hidden" name="partner" value="2088501624560335">
<input type="hidden" name="pay_date" value="20180214">
<input type="hidden" name="service" value="batch_trans_notify">
<input type="hidden" name="sign" value="XNS7p1JUoTV4ygi0gWGeazsZjHUjrqm90Ih22mjeWvP1E/HLWAMc074AJwWwz/iwmpbm2YjqB/l+LwZ9+TfmYVWKuwgULqBsRkjLVVV9MKSae+/RcUyaHpja3msr5SlGpEvGA2vww/LJCk/fcAJSpzdtvHBpGEhmmlT1egsETCw=">
<input type="hidden" name="sign_type" value="RSA">
</form>
```

> 服务器模拟POST提交转账付款处理请求
```php
        $ali->trans()
```
