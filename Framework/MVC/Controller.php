<?php
/**
 * Created by PhpStorm.
 * User: JH
 * Date: 2017/10/11
 * Time: 22:15
 */
declare(strict_types=1);

namespace EP\MVC;


use EP\Core\Develop;
use EP\Core\FrameBase;

class Controller extends FrameBase
{

    protected $smartDisplay = true;

    /**
     * 设置data
     * @param string $key
     * @param $val
     */
    function data(string $key, $val)
    {
        $this->data[$key] = $val;
    }

    /**
     * @param $key
     * @return false|mixed
     */
    function get($key)
    {
        if (isset($_GET[$key])) {
            return trim($_GET[$key]);
        }
        return false;
    }

    /**
     * @param $key
     * @return false|mixed
     */
    function post($key)
    {
        if (isset($_POST[$key])) {
            return trim($_POST[$key]);
        }
        return false;
    }

    /**
     * 是否POST请求
     * @return bool
     */
    function isPost(): bool
    {
        return $this->delegate->getRequest()->isPostRequest();
    }

    /**
     * 是否get请求
     * @return bool
     */
    function isGet(): bool
    {
        return $this->delegate->getRequest()->isGetRequest();
    }

    /**
     * 是否AJAX请求
     *
     * @param string $method
     * @param bool $check_params
     *
     * @return bool
     */
    function isAjax($method = AJAX, $check_params = true): bool
    {
        switch ($method) {
            case GET:
                $status = $this->isGet();
                $params = array_values($_GET);
                break;
            case POST:
                $status = $this->isPost();
                $params = array_values($_POST);
                break;
            default:
                $status = true;
                $params = !empty($_POST) ? array_values($_POST) : array_values($_GET);
        }
        $result = $status && $this->request->isAjaxRequest();
        if ($result && $check_params) {
            $signature = $_SERVER['HTTP_ACCESS_SIGNATURE'] ?? '';
            $params_signature = md5(urlencode(implode('', $params)) . $this->request->getUserAgent());
            if (!($signature === $params_signature)) {
                $result_data = [];
                $r_params = [];
                foreach ($params as $v) {
                    $r_params[] = urlencode($v);
                }
                if (Develop::isDev()) {
                    $result_data = [
                        'query_signture' => $signature,
                        'checkd_signture' => $params_signature,
                        'params_value' => implode('|', $r_params),
                        'query_agent' => $this->request->getUserAgent()
                    ];
                }
                $this->response->setStatus(403);
                $this->json(-1000, '非法请求', $result_data);
            }
        }

        return $result;
    }

    /**
     * 获取请求IP
     *
     * @param bool $return_long_ip
     *
     * @return string
     */
    function requestIp(bool $return_long_ip = false)
    {
        $ip = $this->delegate->getRequest()->getClientIPAddress();
        if ($return_long_ip) {
            $ip = ip2long($ip);
        }
        return $ip;
    }

    /**
     * 跳转到站内其它控制器
     *
     * @param null $controller
     * @param null $params
     * @param bool $s_url 是否为加密参数
     */
    function to($controller = null, $params = null, $s_url = false)
    {
        $url = $this->url($controller, $params, $s_url);
        $this->redirect($url);
    }

    /**
     * 返回上一页
     */
    function toReferer()
    {
        $this->redirect($this->request->getUrlReferrer());
    }

    /**
     * 终止运行，跳转到指定URL
     *
     * @param $url
     */
    function redirect($url)
    {
        $this->getDelegate()->getResponse()->redirect($url);
        exit();
    }

    /**
     * 下载一个文件
     *
     * @param $file
     * @param int $buffer
     * @param null $file_name
     */
    protected function sendDownloadFile($file, $buffer = 0, $file_name = null)
    {
        if (!file_exists($file)) {
            die('文件不存在');
        }
        if (null === $file_name) {
            $file_name = basename($file);
        }

        $file_name = rawurlencode($file_name);
        $download_header = array(
            "Content-Type: application/octet-stream",
            "Accept-Ranges: bytes",
            "Accept-Length: " . filesize($file),
            "Content-Disposition:attachment;filename={$file_name}",
            "Content-Transfer-Encoding:binary"
        );
        $this->delegate->getResponse()->setHeaders($download_header);
        $fs = fopen($file, "rb");
        if (0 === (int)$buffer) {
            $buffer = 1024;
        }
        while (!feof($fs)) {
            $file_data = fread($fs, $buffer);
            echo $file_data;
        }
        fclose($fs);
    }

    /**
     * 下载内容
     *
     * @param string $file_name
     * @param string $content
     */
    protected function sendDownloadContent(string $file_name, string $content)
    {
        $download_header = array(
            "Content-Type: application/octet-stream",
            "Accept-Ranges: bytes",
            "Content-Disposition:attachment;filename={$file_name}",
            "Content-Transfer-Encoding:binary"
        );
        $this->delegate->getResponse()->setHeaders($download_header)->displayOver($content);
    }

    /**
     * 设置视图控制器数据
     *
     * @param array $data
     */
    protected function setViewData(array $data)
    {
        $this->view_data = $data;
    }

    /**
     * 使用视图控制器输出
     *
     * @param null $method
     * @param int $http_response_status
     */
    protected function display($method = null, $http_response_status = 200)
    {
        if (null === $method && $this->smartDisplay && $this->request->isAjaxRequest()) {
            $method = 'JSON';
        }
        $this->delegate->getResponse()->setStatus($http_response_status);
        $this->view->display($method);
    }

    protected function displayFile($file_path, $http_response_status = 200)
    {
        $this->delegate->getResponse()->setStatus($http_response_status)->displayOver('', $file_path);
    }

    /**
     * 无需通过视图层直接输出JSON数据
     *
     * @param int $status
     * @param string $message
     * @param array $data
     * @param array $errors
     */
    protected function json($status = 1, $message = 'ok', array $data = [], array $errors = [])
    {
        $json_data = [
            'status' => $status,
            'message' => $message,
            'data' => $data,
            'errors' => $errors
        ];
        $content = json_encode($json_data, JSON_UNESCAPED_UNICODE);
        $this->delegate->getResponse()->setStatus($this->response->getHttpStatus())->setContentType('json')->displayOver($content);
    }

    /**
     * 配合前端autovalid.js使用，效果很好
     * @see json()
     *
     * @param array $errors
     */
    protected function validErrors(array $errors = [])
    {
        if (empty($errors)) {
            $errors = $this->valid->getErrors();
        }
        $this->json(-1, null, [], $errors);
    }

    /**
     * 用于JSON输出数据
     * @see json()
     * @param array $data
     */
    protected function success(array $data = [])
    {
        $this->json(1, 'Success', $data);
    }
}