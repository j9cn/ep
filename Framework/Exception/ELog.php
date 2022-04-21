<?php
/**
 * Created by PhpStorm.
 * User: J.H
 * Date: 2018/5/2
 * Time: 23:08
 */

namespace EP\Exception;


use EP\Core\Develop;
use EP\Http\Response;
use Exception;
use EP\Core\FrameBase;
use EP\Core\Helper;
use EP\Http\Request;

class ELog extends EPE
{
    /**
     * **必须** 立刻采取行动
     */
    const ALERT = 'alert';
    const ERROR = 'error';
    const WARNING = 'warning';


    protected $write = true;
    protected $msg = 'EP EXCEPTION';
    protected $e;
    protected $error_level = '';
    protected $error_code;
    protected $request_time;
    protected $date;

    /**
     * ELog constructor.
     *
     * @param null|Exception $e
     * @param string $error_level
     * @param string $message
     * @param int $code
     * @param bool $write_log
     */
    function __construct($e = null, $message = '', $error_level = self::ERROR, $code = 0, $write_log = true)
    {
        $this->write = $write_log;
        $this->msg = $message;
        $this->error_level = $error_level;
        $this->error_code = $code;
        $this->request_time = date('y-m-d H:i:s');
        $this->date = date('y-m-d');
        if ($e instanceof \Throwable) {
            $this->e = $e;
            if ('' === $this->msg) {
                $this->msg = $e->getMessage();
            }
            if (0 === $code) {
                $this->error_code = $e->getCode();
            }
        }
        if ($e) {
            $this->e = $e;
        }
    }

    static function error(string $message, int $code = 500, $error_level = self::ERROR)
    {
        (new self(null, $message, $error_level, $code))->display();
    }

    static function log(string $type_log, $message)
    {
        if (!is_string($message)) {
            $msg = "---------\n";
            foreach ($message as $key => $value) {
                if (!is_string($value)) {
                    $value = print_r($value, true);
                }
                $msg .= sprintf(".....[%s]:%s\n", $key, $value);
            }
            $message = $msg;
        }
        (new self(null, $message))->writeLog(false, $type_log, $message);
    }

    protected function writeLog($notice = false, $type = '', $content = '')
    {
        if (!$this->write) {
            return false;
        }
        if ('' === $type) {
            if (404 === $this->error_code) {
                $type = '404';
            } else {
                $type = 'exception/' . $this->error_level;
            }
            if ($this->checkErrorLock()) {
                return false;
            }
        }
        $log_error_tpl = <<<tpl
=======================================================================        
Error code: %s
-----------------------------------------------------------------------
REQUEST TIME:   %s
%s
-----------------------------------------------------------------------
+
tpl;
        $log_tpl = <<<tpl
-----------------------------------------------------------------------
REQUEST IP:     %s
REQUEST TIME:   %s
TRACE:          %s
LOG DATA:       %s
-----------------------------------------------------------------------
+
tpl;

        if ('' === $content) {
            $content = sprintf($log_error_tpl,
                $this->getContentCode(),
                $this->request_time,
                $this->getLogContent()
            );
        } else {
            list(, $log_trace) = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
            $log_info = '';
            if (isset($log_trace['file'])) {
                $log_info = $this->hiddenFileRealPath($log_trace['file']) . ' ' . $log_trace['line'];
            }
            $content = sprintf($log_tpl,
                Request::getInstance()->getClientIPAddress(),
                date('m-d h:i'),
                $log_info,
                $content
            );
            if (null === $this->e && $this->checkLogLock($content, $type)) {
                return false;
            }
        }

        $file_name = PROJECT_REAL_PATH . "ep_log/{$type}/{$this->date}.log";
        if (Helper::mkFile($file_name)) {
            file_put_contents($file_name, "{$content}\n", FILE_APPEND | LOCK_EX);
        }
        if ($notice) {
            $n = $this->noticeAdmin($content);
        }
        return true;
    }

    private function checkLogLock($content, $type)
    {
        $lock_file_name = substr(md5($content), -6);
        $file = PROJECT_REAL_PATH . "ep_log/ep_lock/{$type}/{$this->date}/{$lock_file_name}.lock";
        if (is_file($file)) {
            return true;
        }
        Helper::mkFile($file);
        return false;
    }

    /**
     * @return bool
     */
    private function checkErrorLock()
    {
        $file = PROJECT_REAL_PATH . "ep_log/exception/ep_lock/{$this->date}/{$this->getContentCode()}.lock";
        if (is_file($file)) {
            return true;
        }
        Helper::mkFile($file);
        return false;
    }

    /**
     * @return string
     */
    private function getContentCode()
    {
        return substr(strtoupper(md5($this->getLogContent())), -6);
    }

    private function getLogContent()
    {
        static $content;
        if ($content) {
            return $content;
        }

        $req = Request::getInstance();

        $content_tpl_404 = <<<tpl
IP:%s   ->REQUEST METHOD: %s    ->REQUEST URL:%s    ->REFERRER:%s
tpl;


        $content_tpl = <<<tpl
IP:             %s
REQUEST METHOD: %s
REQUEST URL:    %s
REFERRER:       %s
USERAGENT:      %s
=== REQUEST DATA ===
GET: %s
POST: %s
COOKIE: %s
SESSION: %s
SERVERS: %s
-----------------------------------------------------------------------
%s
-----------------------------------------------------------------------
%s
tpl;
        $_m = '';
        $cli = false;
        if (PHP_SAPI === 'cli') {
            $_m = 'cli';
            $cli = true;
        } else {
            if ($req->isAjaxRequest()) {
                $_m = AJAX;
            }
            if ($req->isPostRequest()) {
                $_m .= POST;
            } else {
                $_m .= GET;
            }
        }
        $method = strtoupper($_m);
        if (404 === $this->error_code) {
            return $content = sprintf($content_tpl_404,
                $cli ? 'cli' : $req->getClientIPAddress(),
                $method,
                $req->getHostInfo() . ($_SERVER['PATH_INFO'] ?? $req->getIndexName()),
                $cli ? 'cli' : $req->getUrlReferrer()
            );
        }
        $usk = ['HTTP_ACCEPT', 'HTTP_HOST', 'HTTP_PRAGMA', 'PATH', 'LD_LIBRARY_PATH', 'SERVER_SIGNATURE', 'SERVER_SOFTWARE', 'SERVER_NAME', 'SERVER_ADDR', 'SERVER_PORT', 'REMOTE_ADDR', 'DOCUMENT_ROOT', 'REQUEST_SCHEME', 'CONTEXT_PREFIX', 'CONTEXT_DOCUMENT_ROOT', 'SERVER_ADMIN', 'SCRIPT_FILENAME', 'REMOTE_PORT', 'GATEWAY_INTERFACE', 'SERVER_PROTOCOL', 'REQUEST_METHOD', 'REQUEST_URI', 'SCRIPT_NAME', 'PHP_SELF', 'REQUEST_TIME_FLOAT', 'REQUEST_TIME'];
        $S = $_SERVER;
        foreach ($usk as $key) {
            if (isset($S[$key])) {
                unset($S[$key]);
            }
        }
        return $content = sprintf($content_tpl,
            $cli ? 'cli' : $req->getClientIPAddress(),
            $method,
            $cli ? 'cli' : $req->getCurrentUrl(),
            $cli ? 'cli' : $req->getUrlReferrer(),
            $cli ? 'cli' : $req->getUserAgent(),
            !empty($_GET) ? print_r($_GET, true) : '< NULL >',
            !empty($_POST) ? print_r($_POST, true) : '< NULL >',
            !empty($_COOKIE) ? print_r($_COOKIE, true) : '< NULL >',
            !empty($_SESSION) ? print_r($_SESSION, true) : '< NULL >',
            !empty($S) ? print_r($S, true) : '< NULL >',
            !is_null($this->e) ? $this->e->getMessage() : $this->msg,
            !is_null($this->e) ? $this->e->getTraceAsString() : '< NULL >'
        );
    }

    private function noticeAdmin($content)
    {
        $config = FrameBase::$app_delegate->getConfig();
        if ($call = $config->get('admin', 'notify')) {
            if (!is_callable($call)) {
                return false;
            }
            $project_name = $config->get('admin', 'project');
            $server_ip = $_SERVER['SERVER_ADDR'];
            $app_name = FrameBase::$app_delegate->app_name;
            $title = "服务器（{$server_ip} > {$project_name} > {$app_name}）出现异常：{$this->getContentCode()}";
            return call_user_func($call, $title, $content);
        }
        return false;
    }

    /**
     * @param string $tpl
     *
     * @throws \ReflectionException
     */
    function display($tpl = '')
    {
        $response = Response::getInstance();
        if (Develop::isDev()) {
            if (is_array($this->e)) {
                $error = $this->e;
                $this->alignmentTraceData($error);
            } elseif (is_null($this->e)) {
                $error = $this->exceptionSource(new Exception($this->msg, $this->error_code));
                $tpl = __DIR__ . '/tpl/trace_info_error.tpl.php';
            } else {
                $error = $this->exceptionSource($this->e);
                $tpl = __DIR__ . '/tpl/trace_info_error.tpl.php';
            }
            if (!$tpl) {
                $tpl = __DIR__ . '/tpl/trace_info_error.tpl.php';
            }
            $response->setStatus($this->error_code)->displayOver($error, $tpl);
        } else {

            $data = [
                'error' => $this->getContentCode()
            ];
            if ('' === $tpl && !is_file($tpl)) {
                $tpl = __DIR__ . '/tpl/front_error_404.tpl.php';
                if (404 === $this->error_code) {
                    $url = strtolower(Request::getInstance()->getCurrentUrl());
                    $ext = Helper::getExt($url);
                    if (in_array($ext, ['css', 'js', 'png', 'jpg', 'jpeg', 'gif', 'bmp', 'map'])) {
                        $this->write = false;
                    }
                    $tpl = defined('PAGE_NOT_FOUND') && is_file(PAGE_NOT_FOUND) ? PAGE_NOT_FOUND : $tpl;
                } else {
                    $tpl = defined('PAGE_ERROR') && is_file(PAGE_ERROR) ? PAGE_ERROR : __DIR__ . '/tpl/front_error.tpl.php';
                }
            }
            if ($this->write) {
                $this->writeLog(self::ALERT === $this->error_level);
            }
            if (Request::getInstance()->isAjaxRequest()) {
                $data['message'] = "错误代码: {$data['error']}";
                $data['status'] = -1;
                $data = json_encode($data, JSON_UNESCAPED_UNICODE);
                $response->setContentType('json')->setStatus()->displayOver($data);
            }
            $response->setStatus($this->error_code)->displayOver($data, $tpl);
        }
    }


}