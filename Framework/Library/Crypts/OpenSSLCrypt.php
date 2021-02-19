<?php
/**
 * Created by PhpStorm.
 * User: J.H
 * Date: 2018/1/28
 * Time: 2:46
 */
declare(strict_types=1);

namespace EP\Library\Crypts;


use EP\Exception\EE;
use EP\Exception\ELog;

class OpenSSLCrypt
{

    private $crypt_key = 'openssl_crypt';
    private $crypt_method = 'AES-128-CBC';
    private $iv = '';
    private $iv_len;

    const DATA_TYPE_BASE64 = 1;
    const DATA_TYPE_HEX = 2;
    const DATA_TYPE_NO_ENCODE = 3;

    /**
     * OpenSSLCrypt constructor.
     *
     * @param string $crypt_key
     * @param string $method
     * @param string $iv
     */
    function __construct(string $crypt_key, string $method = 'AES-128-CBC', $iv = '')
    {
        if (!in_array($method, openssl_get_cipher_methods())) {
            ELog::error("openssl不支持{$method}加解密方式");
        }
        $this->crypt_key = $crypt_key;
        $this->crypt_method = $method;
        $this->iv_len = openssl_cipher_iv_length($this->crypt_method);
        if ($iv && $this->iv_len) {
            $this->iv = substr(str_pad((string)$iv, $this->iv_len, pack('a', null)), 0, $this->iv_len);
        }
    }

    /**
     * @param string $crypt_key
     */
    function setCryptKey(string $crypt_key)
    {
        $this->crypt_key = $crypt_key;
    }

    /**
     * 加密
     *
     * @param string $data
     * @param int $return_data
     * @param int $options
     *
     * @return string
     */
    function encrypt(string $data, $return_data = self::DATA_TYPE_BASE64, $options = OPENSSL_RAW_DATA)
    {
        $result = @openssl_encrypt(
            $data,
            $this->crypt_method,
            $this->crypt_key,
            $options,
            $this->iv
        );
        switch ($return_data) {
            case self::DATA_TYPE_HEX:
                return bin2hex($result);
            case self::DATA_TYPE_BASE64:
                return base64_encode($result);
            case self::DATA_TYPE_NO_ENCODE:
            default:
                return $result;
        }
    }

    /**
     * 解密
     *
     * @param string $data
     * @param int $input_data
     * @param int $options
     *
     * @return string
     */
    function decrypt(string $data, $input_data = self::DATA_TYPE_BASE64, $options = OPENSSL_RAW_DATA)
    {
        switch ($input_data) {
            case self::DATA_TYPE_HEX:
                $data = hex2bin($data);
                break;
            case self::DATA_TYPE_BASE64:
                $data = base64_decode($data);
                break;
        }
        $decode = '';
        try {
            $decode = openssl_decrypt(
                $data,
                $this->crypt_method,
                $this->crypt_key,
                $options,
                $this->iv
            );
        } catch (\Throwable $exception) {
            ELog::log('lib', $exception->getMessage());
        }
        return $decode;
    }

    /**
     * 补位或退位
     *
     * @param string $data
     * @param bool $is_padding
     * @param int $block_size
     *
     * @return string
     */
    function padding(string $data, $is_padding = true, int $block_size = 8)
    {
        $padding_char = $block_size - (strlen($data) % $block_size);
        if ($is_padding) {
            $data .= str_repeat(chr($padding_char), $padding_char);
        } else {
            $tail = substr($data, -1);
            if (ord($tail) <= $block_size) {
                return rtrim($data, $tail);
            }
        }
        return $data;
    }
}