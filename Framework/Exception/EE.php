<?php
/**
 * Created by PhpStorm.
 * User: JH
 * Date: 2017/11/28
 * Time: 16:45
 */

namespace EP\Exception;

use EP\Core\Helper;
use EP\Http\Request;
use Throwable, Exception, PDOException;

class EE extends Exception
{

    private $elog;

    /**
     * EE constructor.
     *
     * @param Throwable|Exception|PDOException $e
     * @param string $message
     * @param string $error_level
     * @param int $code
     */
    function __construct($e, $message = '', $code = 0, $error_level = ELog::ERROR)
    {
        parent::__construct($message, $code, $e);
        $this->elog = new ELog($e, $message, $error_level, $e->getCode(), true);
        if (PHP_SAPI === 'cli') {
            set_exception_handler(array($this, 'cliErrorHandler'));
        } else {
            set_exception_handler(array($this, 'errorHandler'));
        }
    }

    /**
     * cli模式下的异常处理
     *
     * @param Throwable $e
     *
     * @throws \ReflectionException
     */
    function cliErrorHandler(Throwable $e)
    {
        $trace_table = array();
        $trace = $e->getTrace();
        $this->getCliTraceInfo($trace, $trace_table);

        $previous_trace = array();
        if ($e->getPrevious()) {
            $previous_trace = $e->getPrevious()->getTrace();
            $this->getCliTraceInfo($previous_trace, $trace_table);
        }

        $result['line'] = $e->getLine();
        $result['file'] = $e->getFile();
        $result['message'] = $e->getMessage();

        $result['trace'] = $trace;
        $result['trace_table'] = $trace_table;
        $result['previous_trace'] = $previous_trace;

        $this->elog->display(__DIR__ . '/tpl/cli_error.tpl.php');
    }


    /**
     * CLI trace
     *
     * @param $trace
     * @param $trace_table
     *
     * @throws \ReflectionException
     */
    protected function getCliTraceInfo(&$trace, &$trace_table)
    {
        if (!empty($trace)) {
            $this->elog->alignmentTraceData($trace);
            foreach ($trace as &$t) {
                foreach ($t as $type_name => &$trace_content) {
                    switch ($type_name) {
                        case 'file':
                        case 'line':
                        case 'function':
                            $line_max_width = max(strlen($type_name), strlen($trace_content));
                            if (($line_max_width % 2) != 0) {
                                $line_max_width += 5;
                            } else {
                                $line_max_width += 4;
                            }

                            if (!isset($trace_table[$type_name]) || $line_max_width > $trace_table[$type_name]) {
                                $trace_table[$type_name] = $line_max_width;
                            }
                            break;
                        default:
                            unset($t[$type_name]);
                    }
                }
            }
        }
    }


    /**
     * @param Throwable $e
     *
     * @throws \ReflectionException
     */
    function errorHandler(Throwable $e)
    {
        $this->elog->display(__DIR__ . '/tpl/front_error.tpl.php');
    }


    /**
     * @return int
     */
    private function getHttpCode()
    {
        $url = strtolower(Request::getInstance()->getCurrentUrl());
        $ext = Helper::getExt($url);
        if (in_array($ext, ['css', 'js', 'png', 'jpg', 'jpeg', 'gif', 'bmp', 'map'])) {
            $this->log = false;
            return 404;
        }
        return 500;
    }
}