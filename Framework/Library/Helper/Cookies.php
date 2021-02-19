<?php
/**
 * Created by PhpStorm.
 * User: JH
 * Date: 2017/12/3
 * Time: 14:23
 */
declare(strict_types=1);

namespace EP\Library\Helper;


use EP\Core\Helper;
use EP\Library\Crypts\TripleDES;

class Cookies
{
    /**
     * 加解密默认key
     * @var string
     */
    private $key = '⁽⁽ƪ(ᵕ᷄≀ ̠˘᷅ )ʃ⁾⁾ᵒᵐᵍᵎᵎ';

    /**
     * 设置为true时，前端不能使用JS读取cookie
     * @var bool
     */
    protected $httponly = false;

    protected $cookie_domain = '';

    /**
     * Cookies constructor.
     *
     * @param string $key
     */
    function __construct($key = '')
    {
        if (trim($key)) {
            $this->setAuthKey($key);
        }
        if (defined('COOKIE_DOMAIN')) {
            $this->cookie_domain = COOKIE_DOMAIN;
        }
    }

    /**
     * 设置跨越Cookie
     * @param $domain
     * @return $this
     */
    function setDomain($domain)
    {
        $this->cookie_domain = $domain;
        return $this;
    }

    /**
     * 设置加密key
     *
     * @param $key
     */
    function setAuthKey($key)
    {
        $this->key = trim($key);
        return $this;
    }

    /**
     * @param bool $httponly
     *
     * @return $this
     */
    function setHttpOnly(bool $httponly = true)
    {
        $this->httponly = $httponly;
        return $this;
    }

    /**
     * 设置cookie
     *
     * @param $name
     * @param $value
     * @param int $expire
     *
     * @return bool
     */
    function set($name, $value, $expire = 0)
    {
        if ($value === '' || $value === null) {
            $expire = time() - 3600;
            $value = '';
        }
//        $cookie_domain = '';
//        if (defined('COOKIE_DOMAIN')) {
//            $cookie_domain = COOKIE_DOMAIN;
//        }
        if ($expire > 0) {
            $expire = time() + $expire;
        }
        $secure = isset($_SERVER['HTTPS']) && strcmp($_SERVER['HTTPS'], 'on') === 0;
        if (setcookie($name, (string)$value, $expire, '/', $this->cookie_domain, $secure, $this->httponly)) {
            return true;
        }
        return false;
    }

    /**
     * 删除cookie
     * @see set()
     *
     * @param $name
     *
     * @return bool
     */
    function del($name)
    {
        return $this->set($name, null);
    }

    /**
     * 生成加密cookie
     *
     * @param string $name
     * @param string|array $params
     * @param int $expire
     *
     * @return bool
     */
    function setAuth($name, $params, $expire = 0)
    {
        $this->httponly = true;
        if ($params === '' || $params === null) {
            $expire = time() - 3600;
            $value = null;
        } else {
            $encryptKey = $this->getEncryptKey($name);
            if (is_array($params)) {
                $params = json_encode($params);
            }
            $value = (new TripleDES($encryptKey))->encrypt($params);
        }
        return $this->set($name, $value, $expire);
    }

    /**
     * 提取一个cookie值
     *
     * @param $name
     *
     * @return bool|mixed
     */
    function get($name)
    {
        if (!isset($_COOKIE[$name])) {
            return false;
        }
        return $_COOKIE[$name];
    }

    /**
     * 从已加密的cookie中取出值
     *
     * @param string $params cookie的key
     * @param bool $deCode
     *
     * @return bool|string|array
     */
    function getAuth($params, $deCode = false)
    {
        if (false !== strpos($params, '.') && $deCode) {
            list($name, $arrKey) = explode('.', $params);
        } else {
            $name = $params;
        }

        $value = $this->get($name);
        if (false === $value) {
            return false;
        }

        $encryptKey = $this->getEncryptKey($name);
        $result = (new TripleDES($encryptKey))->decrypt((string)$value);
        if (!$result) {
            return false;
        }

        if ($deCode) {
            $result = json_decode($result, true);
            if (isset($arrKey)) {
                if (isset($result[$arrKey])) {
                    return $result[$arrKey];
                }
                return false;
            }
        }

        return $result;
    }

    /**
     * 生成密钥
     *
     * @param string $cookieName
     *
     * @return string
     */
    protected function getEncryptKey($cookieName)
    {
        if (isset($_SERVER['HTTP_USER_AGENT'])) {
            $agent = strtolower($_SERVER['HTTP_USER_AGENT']);
        } else {
            $agent = 'agent';
        }

        return md5($agent . $this->key . $cookieName);
    }
}