<?php
/**
 * Created by PhpStorm.
 * User: JH
 * Date: 2017/10/8
 * Time: 20:29
 */
declare(strict_types=1);

namespace EP\Http;


use EP\Exception\ELog;

class Request
{
    private $baseUrl;
    private $hostInfo = '';
    private $scriptUrl = '';
    private static $instance;

    /**
     * 实例化类
     * @return Request
     */
    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * 初始化URL
     */
    private function initScriptUrl()
    {
        if (($scriptName = $this->S('SCRIPT_FILENAME')) == null) {
            ELog::error('determine the entry script URL failed!!!', 404);
        }
        $scriptName = basename($scriptName);
        if (($_scriptName = $this->S('SCRIPT_NAME')) != null && basename($_scriptName) === $scriptName) {
            $this->scriptUrl = $_scriptName;
        } elseif (($_scriptName = $this->S('PHP_SELF')) != null && basename($_scriptName) === $scriptName) {
            $this->scriptUrl = $_scriptName;
        } elseif (($_scriptName = $this->S('ORIG_SCRIPT_NAME')) != null && basename($_scriptName) === $scriptName) {
            $this->scriptUrl = $_scriptName;
        } elseif (($pos = strpos($this->S('PHP_SELF'), '/' . $scriptName)) !== false) {
            $this->scriptUrl = substr($this->S('SCRIPT_NAME'), 0, $pos) . '/' . $scriptName;
        } elseif (($_documentRoot = $this->S('DOCUMENT_ROOT')) != null && ($_scriptName = $this->S('SCRIPT_FILENAME')) != null && strpos($_scriptName,
                $_documentRoot) === 0
        ) {
            $this->scriptUrl = str_replace('\\', '/', str_replace($_documentRoot, '', $_scriptName));
        } else {
            ELog::error('determine the entry script URL failed!!!', 404);
        }
    }

    /**
     * 取得$S全局变量的值
     *
     * @param string $name $S的名称
     *
     * @return string
     */
    private function S(string $name): string
    {
        return $_SERVER[$name] ?? '';
    }

    /**
     * 取得script的URL
     * @return string
     */
    public function getScriptUrl(): string
    {
        if (!$this->scriptUrl) {
            $this->initScriptUrl();
        }
        return $this->scriptUrl;
    }

    /**
     * 设置基础路径
     *
     * @param  string $url 设置基础路径
     */
    public function setBaseUrl(string $url)
    {
        $this->baseUrl = $url;
    }

    /**
     * 返回当前URL绝对路径
     *
     * @param  boolean $absolute 是否返回带HOST的绝对路径
     *
     * @return string 当前请求的url
     */
    public function getBaseUrl(bool $absolute = false): string
    {
        if ($this->baseUrl === null) {
            $this->baseUrl = rtrim(dirname($this->getScriptUrl()), DS) . '/';
        }
        return $absolute ? $this->getHostInfo() . $this->baseUrl : $this->baseUrl;
    }

    /**
     * 当前执行的脚本名
     * @return string
     */
    public function getIndexName(): string
    {
        return basename($this->getScriptName());
    }

    /**
     * 取得Host信息
     * @return string
     */
    public function getHostInfo(): string
    {
        if (!$this->hostInfo) {
            return $this->hostInfo = $this->initHostInfo();
        }
        return $this->hostInfo;
    }

    /**
     * 获取当前页面URL路径
     *
     * @param bool $absolute
     *
     * @return string
     */
    public function getCurrentUrl(bool $absolute = true): string
    {
        if ($absolute) {
            return $this->getHostInfo() . $this->S('REQUEST_URI');
        }

        return $this->S('REQUEST_URI');
    }

    /**
     * 设置Host信息
     * @return string
     */
    private function initHostInfo(): string
    {
        if (PHP_SAPI === 'cli') {
            return '';
        }

        $protocol = 'http';
        if (strcasecmp($this->S('HTTPS'), 'on') === 0) {
            $protocol = 'https';
        }

        if (($host = $this->S('HTTP_HOST')) != null) {
            $this->hostInfo = $protocol . '://' . $host;
        } elseif (($host = $this->S('SERVER_NAME')) != null) {
            $this->hostInfo = $protocol . '://' . $host;
            $port = $this->getServerPort();
            if (($protocol == 'http' && $port != 80) || ($protocol == 'https' && $port != 443)) {
                $this->hostInfo .= ':' . $port;
            }
        } else {
            ELog::error('determine the entry script URL failed!!!', 404);
        }

        return $this->hostInfo;
    }

    /**
     * 取得服务器端口
     * @return int 当前服务器端口号
     */
    public function getServerPort(): int
    {
        return (int)$this->S('SERVER_PORT');
    }

    /**
     * 当前scriptFile的路径
     * @return string
     */
    public function getScriptFilePath(): string
    {
        if (($scriptName = $this->S('SCRIPT_FILENAME')) == null) {
            ELog::error('determine the entry script URL failed!!!', 404);
        }

        return dirname($scriptName);
    }

    /**
     * @return string
     */
    public function getUserHost(): string
    {
        return $this->S('REMOTE_HOST');
    }

    /**
     * @return string
     */
    function getRequestURI(): string
    {
        return $this->S('REQUEST_URI');
    }

    /**
     * @return string
     */
    function getPathInfo(): string
    {
        return $this->S('PATH_INFO');
    }

    /**
     * @return string
     */
    public function getQueryString(): string
    {
        return $this->S('QUERY_STRING');
    }

    /**
     * @return string
     */
    public function getScriptName(): string
    {
        return $this->S('SCRIPT_NAME');
    }

    /**
     * HTTP_REFERER;
     * @return string
     */
    public function getUrlReferrer(): string
    {
        return $this->S('HTTP_REFERER');
    }

    /**
     * @return string userAgent
     */
    public function getUserAgent(): string
    {
        return $this->S('HTTP_USER_AGENT');
    }

    /**
     * @param string $default_lang
     * @return string
     */
    public function getLanguage($default_lang = 'zh')
    {
        $accept_language = $this->S('HTTP_ACCEPT_LANGUAGE');
        if (!$accept_language) {
            return $default_lang;
        }
        list($current_lang) = explode(';', $accept_language);
        $lang = substr($current_lang, 0, 5);
        if (strpos($lang, '-')) {
            list($lang) = explode('-', $lang);
        }
        return $lang;
    }

    /**
     * @return string ACCEPT TYPE
     */
    public function getAcceptTypes(): string
    {
        return $this->S('HTTP_ACCEPT');
    }

    /**
     * 是否是PUT请求
     * @return bool
     */
    public function isPutRequest(): bool
    {
        return ($this->S('REQUEST_METHOD')
                && !strcasecmp($this->S('REQUEST_METHOD'), 'PUT')) || $this->isPutViaPostRequest();
    }

    /**
     * 判断一个链接是否为post请求
     * @return bool
     */
    public function isPostRequest(): bool
    {
        return $this->S('REQUEST_METHOD') && !strcasecmp($this->S('REQUEST_METHOD'), 'POST');
    }

    /**
     * 判断请求类型是否为get
     * @return bool
     */
    public function isGetRequest(): bool
    {
        return $this->S('REQUEST_METHOD') && !strcasecmp($this->S('REQUEST_METHOD'), 'GET');
    }

    /**
     * 判断请求类型是否为delete
     * @return bool
     */
    public function isDeleteRequest(): bool
    {
        return $this->S('REQUEST_METHOD') && !strcasecmp($this->S('REQUEST_METHOD'), 'DELETE');
    }

    /**
     * 是否是通过POST的PUT请求
     * @return bool
     */
    protected function isPutViaPostRequest(): bool
    {
        return isset($_POST['_method']) && !strcasecmp($_POST['_method'], 'PUT');
    }

    /**
     * 是否是ajax请求
     * @return bool
     */
    public function isAjaxRequest(): bool
    {
        return 0 === strcasecmp($this->S('HTTP_X_REQUESTED_WITH'), 'XMLHttpRequest');
    }

    /**
     * 是否是flash请求
     * @return bool
     */
    public function isFlashRequest(): bool
    {
        return stripos($this->S('HTTP_USER_AGENT'), 'Shockwave') !== false
            || stripos($this->S('HTTP_USER_AGENT'), 'Flash') !== false;
    }

    /**
     * 是否苹果
     * @return bool
     */
    function isIphone()
    {
        return stripos($this->S('HTTP_USER_AGENT'), 'ios') !== false;
    }

    /**
     * 是否苹果系统
     * @return bool
     */
    public function isIOS()
    {
        return $this->isIphone() || stripos($this->S('HTTP_USER_AGENT'), 'iPad') !== false;
    }

    /**
     * 是否安卓系统
     * @return bool
     */
    public function isAndroid()
    {
        return stripos($this->S('HTTP_USER_AGENT'), 'android') !== false;
    }

    /**
     * 是否Windows系统
     * @return bool
     */
    public function isWindows()
    {
        return stripos($this->S('HTTP_USER_AGENT'), 'windows nt') !== false;
    }

    /**
     * 是否Mac系统
     * @return bool
     */
    public function isMacOs()
    {
        return stripos($this->S('HTTP_USER_AGENT'), 'Macintosh') !== false;
    }

    /**
     * 获取请求类型
     * @return string
     */
    public function getMethod(): string
    {
        if ($this->isAjaxRequest()) {
            return AJAX;
        }
        if ($this->isPostRequest()) {
            return POST;
        }
        return GET;
    }

    /**
     * 获取客户端IP地址
     *
     * @param array $env_keys
     *
     * @return string userIP
     */
    public function getClientIPAddress(array $env_keys = array()): string
    {
        static $ip = null;
        if (null === $ip) {
            if (empty($env_keys)) {
                $env_keys = array(
                    'HTTP_CLIENT_IP',
                    'HTTP_CF_CONNECTING_IP',
                    'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED',
                    'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED',
                    'REMOTE_ADDR'
                );
            }

            $ip = '0.0.0.0';
            foreach ($env_keys as $env) {
                $env_info = $this->S($env);
                if (!empty($env_info) && 0 !== strcasecmp($env_info, 'unknown')) {
                    $ips = explode(',', $env_info);
                    foreach ($ips as $ip) {
                        $ip = trim($ip);
                        if (false !== ip2long($ip)) {
                            break 2;
                        }
                    }
                }
            }
        }

        return $ip;
    }
}