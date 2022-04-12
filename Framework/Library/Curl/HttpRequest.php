<?php
/**
 * Created by PhpStorm.
 * User: J.H
 * Date: 2018/2/12
 * Time: 8:14
 */

namespace EP\Library\Curl;


use EP\Exception\ELog;

class HttpRequest
{
    private $cookies = '';
    private $userAgent = '';
    private $timeout = 10;
    private $credentials = '';
    private $header = array();
    private $customRequest = '';
    private $options = array();


    const METHOD_GET = 'GET';
    const METHOD_POST = 'POST';

    function timeout($time = 5)
    {
        $this->timeout = $time;
    }

    function cookies($cookies = array())
    {
        if (!empty($cookies)) {
            $this->cookies = '';
            if (is_array($cookies)) {
                foreach ($cookies as $key => $val) {
                    $this->cookies .= "{$key}={$val}; ";
                }
            } else {
                $this->cookies = $cookies;
            }
        }
    }

    function userAgent($agent = '')
    {
        if ($agent) {
            $this->userAgent = $agent;
        }
    }

    function useAgentChrome()
    {
        $this->userAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/88.0.4324.150 Safari/537.36');
        return $this;
    }

    /**
     * @link http://us.php.net/manual/en/function.curl-setopt.php
     *
     * @param $option
     * @param string $value
     *
     * @return $this
     */
    function setOptions($option, $value)
    {
        $this->options[$option] = $value;
        return $this;
    }

    function setHeader(array $header)
    {
        $this->header = array_values($header);
    }

    /**
     * @param string $username
     * @param string $pwd
     */
    function auth(string $username, string $pwd)
    {
        $this->credentials = "{$username}:{$pwd}";
    }

    /**
     * @see multiRequest()
     *
     * @param array $data
     * @param string $url
     * @param string $method
     * @param $http_code
     * @param array $error_msg
     *
     * @return array
     */
    static function multiHttp(
        array &$data, $url = '', $method = self::METHOD_GET, &$error_msg = array(), &$http_code = false
    )
    {
        return (new self())->multiRequest($data, $url, $method, $error_msg, $http_code);
    }

    /**
     * @param array $data
     * @param string $url
     * @param string $method
     * @param $http_code
     * @param array $error_msg
     *
     * @return array
     */
    function multiRequest(array &$data, $url = '', $method = self::METHOD_GET, &$error_msg = [], &$http_code = false)
    {
        $req_list = curl_multi_init();
        $ch_list = $ri = array();
        foreach ($data as $index => $node) {

            $q_url = $url;
            if (isset($node['url'])) {
                $q_url = $node['url'];
            }

            $params = array();
            if (isset($node['params'])) {
                $params = $node['params'];
            }

            $q_method = $method;
            if (isset($node['method'])) {
                $q_method = $node['method'];
            }

            if ($q_method === self::METHOD_GET && !empty($params)) {
                $q_url = rtrim($q_url, '?') . '?' . http_build_query($params);
                $params = array();
            }
            $data[$index]['request_url'] = $q_url;

            $this->credentials = '';
            if (isset($node['auth']) && is_array($node['auth'])) {
                $user_pwd = array_values($node['auth']);
                $this->auth($user_pwd[0], $user_pwd[1]);
            }

            if (isset($node['header'])) {
                $this->header = $node['header'];
            }

            if (isset($node['cookies'])) {
                $this->cookies = $node['cookies'];
            }

            if (isset($node['ua'])) {
                $this->userAgent = $node['ua'];
            }

            $ch_list[$index] = $this->getCurlObject($q_url, $params);
            $ri[(int)$ch_list[$index]] = $index;
            curl_multi_add_handle($req_list, $ch_list[$index]);
        }

        $result_data = $result_code = $result_error = array();

        do {
            while (($exec = curl_multi_exec($req_list, $running)) === CURLM_CALL_MULTI_PERFORM) {
                if ($exec != CURLM_OK) {
                    break;
                }
            }

            while ($node_complete = curl_multi_info_read($req_list)) {

                $nci = (int)$node_complete['handle'];
                // 从已完成请求中获取状态、错误、内容
                if (false !== $http_code) {
                    $result_code[$ri[$nci]] = curl_getinfo($node_complete['handle'], CURLINFO_HTTP_CODE);
                }
                $result_data[$ri[$nci]] = $data[$ri[$nci]]['result_content'] = curl_multi_getcontent($node_complete['handle']);
                if (curl_errno($node_complete['handle']) !== CURLE_OK) {
                    $result_error[$ri[$nci]] = curl_error($node_complete['handle']);
                    $result_data[$ri[$nci]] = $data[$ri[$nci]]['result_content'] = false;
                }
                curl_close($node_complete['handle']);
                curl_multi_remove_handle($req_list, $node_complete['handle']);
            }

            if ($running) {
                $rel = curl_multi_select($req_list, 0.5);
                if ($rel == -1) {
                    usleep(1000);
                }
            }

            if (false == $running) {
                break;
            }
        } while (true);

        curl_multi_close($req_list);
        $http_code = $result_code;
        $error_msg = $result_error;
        return $result_data;
    }

    /**
     * 发送一个curl请求
     * $error_msg
     * @link https://curl.haxx.se/libcurl/c/libcurl-errors.html
     *
     * @param string $url
     * @param array|string $params
     * @param string $method
     * @param string $error_msg
     * @param int $http_code
     *
     * @return false|mixed
     */
    function request(string $url, $params = array(), $method = self::METHOD_GET, &$error_msg = '', &$http_code = 0)
    {
        if ($method === self::METHOD_GET) {
            $url = rtrim($url, '?');
            if ($params) {
                if (strpos($url, '?')) {
                    $url .= '&';
                } else {
                    $url .= '?';
                }
                if (is_array($params)) {
                    $params = http_build_query($params);
                }
                $url .= $params;
            }
            $params = array();
        }
        $ch = $this->getCurlObject($url, $params);
        $result = curl_exec($ch);
        if (false === $result && '' !== $error_msg) {
            $error_msg = curl_error($ch);
        }

        if (0 !== $http_code) {
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        }
        curl_close($ch);
        return $result;
    }

    function uploadFile(string $url, string $filePath, string $uploadName = 'file')
    {
        $data[$uploadName] = new \CURLFile($filePath);
        $res = $this->request($url, $data, self::METHOD_POST);
        return $res;
    }

    /**
     * GET请求
     * @see request()
     *
     * @param $url
     * @param array $params
     * @param string $error_msg
     * @param int $http_code
     *
     * @return false|mixed
     */
    static function get($url, array $params = array(), &$error_msg = '', &$http_code = 0)
    {
        return (new self())->request($url, $params, self::METHOD_GET, $error_msg, $http_code);
    }

    /**
     * POST请求
     * @see request()
     *
     * @param $url
     * @param array $params
     * @param string $error_msg
     * @param int $http_code
     *
     * @return false|mixed
     */
    static function post($url, array $params, &$error_msg = '', &$http_code = 0)
    {
        return (new self())->request($url, $params, self::METHOD_POST, $error_msg, $http_code);
    }

    /**
     * PUT请求
     * @see request()
     *
     * @param $url
     * @param array $params
     * @param string $error_msg
     * @param int $http_code
     *
     * @return false|mixed
     */
    static function put($url, array $params, &$error_msg = '', &$http_code = 0)
    {
        return (new self())->setReqMethod('PUT')->request($url, $params, 'put', $error_msg, $http_code);
    }

    /**
     * DELETE请求
     * @see request()
     *
     * @param $url
     * @param array $params
     * @param string $error_msg
     * @param int $http_code
     *
     * @return false|mixed
     */
    static function delete($url, array $params, &$error_msg = '', &$http_code = 0)
    {
        return (new self())->setReqMethod('DELETE')->request($url, $params, 'delete', $error_msg, $http_code);
    }

    private function setReqMethod($method = '')
    {
        $this->customRequest = $method;
        $this->header[] = "X-HTTP-Method-Override: {$method}";
        return $this;
    }

    /**
     * @param $url
     * @param array $params
     * @param int $connect_timeout
     *
     * @return bool
     * @throws \Exception
     */
    static function asyncGet($url, array $params = array(), $connect_timeout = 1)
    {
        return self::asyncRequest($url, $params, self::METHOD_GET, $connect_timeout);
    }

    /**
     * @param $url
     * @param array $params
     * @param int $connect_timeout
     *
     * @return bool
     */
    static function asyncPost($url, array $params = array(), $connect_timeout = 1)
    {
        return self::asyncRequest($url, $params, self::METHOD_POST, $connect_timeout);
    }

    /**
     * @param string $url
     * @param array $params
     * @param string $method
     * @param int $connect_timeout
     *
     * @return bool
     */
    static function asyncRequest($url, array $params = array(), $method = self::METHOD_GET, $connect_timeout = 1)
    {

        $url_info = parse_url($url);
        if (!isset($url_info['host'])) {
            ELog::error('invalid url');
        }
        $hostname = $url_info['host'];
        if (isset($url_info['port'])) {
            $port = $url_info['port'];
        }
        if (!isset($port) && isset($url_info['scheme'])) {
            $port = strtolower($url_info['scheme']) === 'https' ? 443 : 80;
        }

        $path = isset($url_info['path']) ? $url_info['path'] : '/';

        $params = http_build_query($params);
        $params_length = strlen($params);
        $query = '?';
        if (isset($url_info['query'])) {
            $query .= rtrim($url_info['query'], '&');
        }
        if ($method === self::METHOD_GET && $params_length > 0) {
            $query .= ('?' === $query) ? $params : '&' . $params;
        }
        $path .= $query;

        $fp = fsockopen($hostname, $port, $errno, $errstr, $connect_timeout);

        if ($fp) {
            $headers = array(
                "{$method} {$path} HTTP/1.1",
                "Host: {$hostname}",
                "Content-type: application/x-www-form-urlencoded",
                "Connection: Close",
                "User-Agent: AsyncRequest"
            );
            if ($method === self::METHOD_POST) {
                $headers[] = "Content-Length: {$params_length}\r\n";
                $headers[] = $params;
            }
            if (fwrite($fp, implode("\r\n", $headers)) === false || fclose($fp) === false) {
                return false;
            }
            return true;
        }
        return false;
    }

    /**
     * 建立CURL RESOURCE
     *
     * @param $url
     * @param array $postData
     *
     * @return resource
     */
    private function getCurlObject($url, $postData = array())
    {
        $options = array();
        $url = trim($url);
        $options[CURLOPT_URL] = $url;
        $options[CURLOPT_TIMEOUT] = $this->timeout;
        $options[CURLOPT_RETURNTRANSFER] = true;
        if ($this->userAgent) {
            $options[CURLOPT_USERAGENT] = $this->userAgent;
        }
        if ($this->cookies) {
            $options[CURLOPT_COOKIE] = $this->cookies;
        }
        if ($this->credentials) {
            $options[CURLOPT_HTTPAUTH] = CURLAUTH_BASIC;
            $options[CURLOPT_USERPWD] = $this->credentials;
        }
        if (!empty($this->header) && is_array($this->header)) {
            $options[CURLOPT_HTTPHEADER] = array_values($this->header);
        }
        if ($this->customRequest) {
            $options[CURLOPT_CUSTOMREQUEST] = $this->customRequest;
        }
        if (!empty($postData)) {
            $options[CURLOPT_POST] = true;
            $options[CURLOPT_POSTFIELDS] = $postData;
        }
        if (stripos($url, 'https') === 0) {
            $options[CURLOPT_SSL_VERIFYPEER] = false;
            $options[CURLOPT_SSL_VERIFYHOST] = false;
        }
        foreach ($this->options as $option => $val) {
            $options[$option] = $val;
        }
        $ch = curl_init();
        curl_setopt_array($ch, $options);

        return $ch;
    }

    /**
     * @param $error_code
     *
     * @return string
     */
    function getError($error_code)
    {
        $error_codes = [
            1 => 'CURLE_UNSUPPORTED_PROTOCOL',
            2 => 'CURLE_FAILED_INIT',
            3 => 'CURLE_URL_MALFORMAT',
            4 => 'CURLE_URL_MALFORMAT_USER',
            5 => 'CURLE_COULDNT_RESOLVE_PROXY',
            6 => 'CURLE_COULDNT_RESOLVE_HOST',
            7 => 'CURLE_COULDNT_CONNECT',
            8 => 'CURLE_FTP_WEIRD_SERVER_REPLY',
            9 => 'CURLE_REMOTE_ACCESS_DENIED',
            11 => 'CURLE_FTP_WEIRD_PASS_REPLY',
            13 => 'CURLE_FTP_WEIRD_PASV_REPLY',
            14 => 'CURLE_FTP_WEIRD_227_FORMAT',
            15 => 'CURLE_FTP_CANT_GET_HOST',
            17 => 'CURLE_FTP_COULDNT_SET_TYPE',
            18 => 'CURLE_PARTIAL_FILE',
            19 => 'CURLE_FTP_COULDNT_RETR_FILE',
            21 => 'CURLE_QUOTE_ERROR',
            22 => 'CURLE_HTTP_RETURNED_ERROR',
            23 => 'CURLE_WRITE_ERROR',
            25 => 'CURLE_UPLOAD_FAILED',
            26 => 'CURLE_READ_ERROR',
            27 => 'CURLE_OUT_OF_MEMORY',
            28 => 'CURLE_OPERATION_TIMEDOUT',
            30 => 'CURLE_FTP_PORT_FAILED',
            31 => 'CURLE_FTP_COULDNT_USE_REST',
            33 => 'CURLE_RANGE_ERROR',
            34 => 'CURLE_HTTP_POST_ERROR',
            35 => 'CURLE_SSL_CONNECT_ERROR',
            36 => 'CURLE_BAD_DOWNLOAD_RESUME',
            37 => 'CURLE_FILE_COULDNT_READ_FILE',
            38 => 'CURLE_LDAP_CANNOT_BIND',
            39 => 'CURLE_LDAP_SEARCH_FAILED',
            41 => 'CURLE_FUNCTION_NOT_FOUND',
            42 => 'CURLE_ABORTED_BY_CALLBACK',
            43 => 'CURLE_BAD_FUNCTION_ARGUMENT',
            45 => 'CURLE_INTERFACE_FAILED',
            47 => 'CURLE_TOO_MANY_REDIRECTS',
            48 => 'CURLE_UNKNOWN_TELNET_OPTION',
            49 => 'CURLE_TELNET_OPTION_SYNTAX',
            51 => 'CURLE_PEER_FAILED_VERIFICATION',
            52 => 'CURLE_GOT_NOTHING',
            53 => 'CURLE_SSL_ENGINE_NOTFOUND',
            54 => 'CURLE_SSL_ENGINE_SETFAILED',
            55 => 'CURLE_SEND_ERROR',
            56 => 'CURLE_RECV_ERROR',
            58 => 'CURLE_SSL_CERTPROBLEM',
            59 => 'CURLE_SSL_CIPHER',
            60 => 'CURLE_SSL_CACERT',
            61 => 'CURLE_BAD_CONTENT_ENCODING',
            62 => 'CURLE_LDAP_INVALID_URL',
            63 => 'CURLE_FILESIZE_EXCEEDED',
            64 => 'CURLE_USE_SSL_FAILED',
            65 => 'CURLE_SEND_FAIL_REWIND',
            66 => 'CURLE_SSL_ENGINE_INITFAILED',
            67 => 'CURLE_LOGIN_DENIED',
            68 => 'CURLE_TFTP_NOTFOUND',
            69 => 'CURLE_TFTP_PERM',
            70 => 'CURLE_REMOTE_DISK_FULL',
            71 => 'CURLE_TFTP_ILLEGAL',
            72 => 'CURLE_TFTP_UNKNOWNID',
            73 => 'CURLE_REMOTE_FILE_EXISTS',
            74 => 'CURLE_TFTP_NOSUCHUSER',
            75 => 'CURLE_CONV_FAILED',
            76 => 'CURLE_CONV_REQD',
            77 => 'CURLE_SSL_CACERT_BADFILE',
            78 => 'CURLE_REMOTE_FILE_NOT_FOUND',
            79 => 'CURLE_SSH',
            80 => 'CURLE_SSL_SHUTDOWN_FAILED',
            81 => 'CURLE_AGAIN',
            82 => 'CURLE_SSL_CRL_BADFILE',
            83 => 'CURLE_SSL_ISSUER_ERROR',
            84 => 'CURLE_FTP_PRET_FAILED',
            85 => 'CURLE_RTSP_CSEQ_ERROR',
            86 => 'CURLE_RTSP_SESSION_ERROR',
            87 => 'CURLE_FTP_BAD_FILE_LIST',
            88 => 'CURLE_CHUNK_FAILED'
        ];
        if ($error_code && isset($error_codes[$error_code])) {
            return $error_codes[$error_code];
        }
        return '';
    }

}