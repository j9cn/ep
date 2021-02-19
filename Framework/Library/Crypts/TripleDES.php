<?php
/**
 * Created by PhpStorm.
 * User: J.H
 * Date: 2018/1/28
 * Time: 2:19
 */
declare(strict_types=1);

namespace EP\Library\Crypts;


class TripleDES
{

    private $c;
    private $default_method = 'DES-EDE3';

    const DATA_TYPE_HEX = 2;
    const DATA_TYPE_BASE64 = 1;

    /**
     * TripleDES constructor.
     *
     * @param string $crypt_key
     * @param string|null $method
     * @param string $vi
     */
    function __construct(string $crypt_key, $method = 'DES-EDE3', $vi = '')
    {
        if (null === $method) {
            $method = $this->default_method;
        }
        $this->c = new OpenSSLCrypt($crypt_key, $method, $vi);
    }

    /**
     * 设置密匙
     *
     * @param string $crypt_key
     *
     * @return $this
     */
    function setCryptKey(string $crypt_key)
    {
        $this->c->setCryptKey($crypt_key);
        return $this;
    }

    /**
     * 加密
     *
     * @param string $data
     * @param int $return_data
     *
     * @return string
     */
    function encrypt(string $data, $return_data = self::DATA_TYPE_HEX)
    {
        return $this->c->encrypt($this->c->padding($data), $return_data, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING);
    }

    /**
     * 解密
     *
     * @param string $data
     * @param int $input_data
     *
     * @return string
     */
    function decrypt(string $data, $input_data = self::DATA_TYPE_HEX)
    {
        $des_data = $this->c->decrypt($data, $input_data);
        if (!$des_data) {
            return '';
        }
        return $this->c->padding($des_data, false);
    }

    /**
     * 测试加解密类型
     *
     * @param string $data
     * @param bool $is_encrypt
     * @param int $type_data
     *
     * @return array
     */
    function testCrypt(string $data, $is_encrypt = true, $type_data = self::DATA_TYPE_HEX)
    {
        $test = [];
        foreach (openssl_get_cipher_methods() as $method) {
            if ('DES' === substr($method, 0, 3)) {
                $test[$method] = $is_encrypt ? $this->encrypt($data, $type_data) : $this->decrypt($data, $type_data);
            }
        }
        return $test;
    }
}