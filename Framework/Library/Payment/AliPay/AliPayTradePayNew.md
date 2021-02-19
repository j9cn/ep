# 支付宝收单新接口
```php
        //配置文件
        $config = [
            'app_id' => '17083249892389',
            'private_key' => 'MIIEpAIBAAKCAQEAp672UJH4+HIZGIto992s5JJ09Hm9RiLUYhZ0FOkVQAAyOlYwC9PGWOebHoHHOmRjSThew///oAOfWQS1dNBecYF7K6Nr+o5Dfgf9DCFIXQ2z4lfheOL4Iv+ipb6DGgCp0DNoDqsT0e3RogNegXib/S15ysOlcYkrhRnAjkZx2MJc6DYLvtyJ2q9zkMGEk7EayPKheTFgCQAgV3xaaUYxNvZLgQDt3Kk3VhjmlxuqX9IDhWVC0PLKsESJZtprriDu8VnDXepAJTrOadWzF7AtyhWUerYGfMsuJf4CjxT4tzfYdGH7vTVZ3h1WiXLtbU04AW0w5JfIxU/t98Fp2JMhvwIDAQABAoIBAQCerAo6vZaJinZC6pCek+5psEjpmlVHi+fLFZIsw06vbEAbQblfR7tWH3uCh629jIcDH0tVTuZWRXdA5hrK+e2UnMCvz4l646nsFaUXGFuAaloA4cXi/WtuutXu3vLx5RTLhgl+b9ZmfRM0qtl/zGBXV/P6sd3ZLMK4xWCXgQNz5xeJyRZEPXRATXYCUuMekhIq/foIytWodkF+9cgWkOaNsmN7nRLEw32Sf4YS2VdmI+U8Wg6hsln2LB/rLFn9tj2erAVEa002SfXO55eO8rkNvaVQQn4q1tsQf5J8EQMPFshUNa0TJHD2wWw28zcffmwecWsN9BSBtidGGXVMDyNRAoGBANP/JhTRkRRekW9tPQjdeEQHhoIvHB3QRwgHRWrZDfxTxBJorODiHATigwN6twqU63zl9wep8x7zFJWdgW7Gp+1G2Ow8iJ8VbiI1nxv5uM5Xx5TKEWOnJ2P0denE00UlmzxCfEjP2m9JSX0cgdQ6t3zsgZbRcKBhVpJhlAmBQYfJAoGBAMp9JQzjACc/SakbtSvsXEM7VLIN57VF1HOdxVxrDQCJlLimuPimGWNJQ7ZP3BN5f6MgNi5B44gN22OfOVqmzUyxgpH76Kq6JW7pVlqGB1tIlXm4HZs4l8HKnfvWaFOx3m4OUEeKj5f+v0Vs/mHT0cqIk8z3wj8AKNvuJqAXoTFHAoGBAJVqvP1pY3bW3GyLsrv/1JcmMrCo4YlF1fqbnVqcl1Xj+Er9SfPKifMLb0nRgRdNNNi4AK9/IiMLMtPsymA4Vf1PtO50D9sIMLKd1oHSNWYBymJdNXpmQsYZc84K8tlGky4ashxjm1JadhhfMkZSCCddTkztWxM+59SOP9efKX+5AoGAVBbEXCWo2qOdo2yuQB9X8VOiSI8dulnVcG4El+yc6aw45rXV+ux/foveYsenTS8XolauKWeTZdzbTvPAjbTXbRIKdzV12fCTuwuLoOwoAfCSoomjQeKuovvRv9O6X4duJ6YqEIuqNiTEqcApo9ajOMifGG+Laz5VuX+c6r7lYeECgYANygDVHypktH9tcUAzF9JeeqF84InTWuR3Z76QAEBxKpceCfEWUUhM85RYoRbvRAk+KM4zCuAVMruyMNJbEchyMRiF4xa3/TcbzMZVbHyx6v69x0Ihs6DTXqVlIT4zRq3AmVaIhSIbaQUPucaJSwAnMG3GOER6QAzR4Bq0uhNwfw==',
            'public_key' => 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAp672UJH4+HIZGIto992s5JJ09Hm9RiLUYhZ0FOkVQAAyOlYwC9PGWOebHoHHOmRjSThew///oAOfWQS1dNBecYF7K6Nr+o5Dfgf9DCFIXQ2z4lfheOL4Iv+ipb6DGgCp0DNoDqsT0e3RogNegXib/S15ysOlcYkrhRnAjkZx2MJc6DYLvtyJ2q9zkMGEk7EayPKheTFgCQAgV3xaaUYxNvZLgQDt3Kk3VhjmlxuqX9IDhWVC0PLKsESJZtprriDu8VnDXepAJTrOadWzF7AtyhWUerYGfMsuJf4CjxT4tzfYdGH7vTVZ3h1WiXLtbU04AW0w5JfIxU/t98Fp2JMhvwIDAQAB'
        ];
        // 实例化SDK
        $ali = new AliPayTradePayNewVersion($config);
```
> 电脑网站收款
```php
        // 设置通知地址
        $ali->setReturnUrl('http://x.com/alipay/return.php');
        $ali->setNotifyUrl('http://x.com/alipay/notify.php');
        //订单信息
        $order_num = '111111111111111111111';
        $order_total_amount = 0.01;
        $subject = '测试订单';
        $body = '订单详细';
        //商品信息
        $goods_id = 'p-01';
        $goods_name = 'Iphone X';
        $goods_url = 'http://xx.com/prod?id=1';
        $goods_price = 8999.99;
        $goods_category = 'Apple';
        //创建订单
        $ali->createOrder
            ->setOrderInfo($order_num, $order_total_amount, $subject, $body)
            ->setProductInfo($goods_id, $goods_name, $goods_url, $goods_price,$goods_category)
            //允许花呗分期
            ->allowInstallment();
        $ali->pay();
```

> 查询订单
```php
        $order_num = '111111111111111111111';
        //OR
        $trade_num = '2018112611001004680073956707';
        $order = $ali->query($order_num, $trade_num);
```

> 关闭未付款订单
```php
        $order_num = '111111111111111111111';
        //OR
        $trade_num = '2018112611001004680073956707';
        $status = $ali->close($order_num, $trade_num);

```

> 退款
```php
        $order_num = '111111111111111111111';
        $trade_num = '2017112611001004680073956707';
        $refund_amount = 8000; //退款金额
        
        $goods_id = 'p-01';
        $goods_name = 'Iphone X';
        $goods_url = 'http://xx.com/prod?id=1';
        $goods_price = 8999.99;
        $goods_category = 'Apple';
        // 可不设置订单信息
        // $ali->refundOrder->setProductInfo($goods_id, $goods_name, $goods_url, $goods_price, $goods_category);
        
        $ali->refund($order_num, $trade_num, $refund_amount);
```

> 退款查询
```php
        $order_num = '111111111111111111111';
        $trade_num = '2017112611001004680073956707';
        $ali->refundQuery($order_num, $trade_num);
```

> 获取流水账单下载地址
```php
        $ali->getDownloadUrl($ali::BILL_TYPE_TRADE, 'm');
```