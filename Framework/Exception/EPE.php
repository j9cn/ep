<?php
/**
 * Created by PhpStorm.
 * User: J.H
 * Date: 2018/5/3
 * Time: 7:17
 */

namespace EP\Exception;

use SplFileObject, ReflectionClass, ReflectionMethod;

class EPE
{

    /**
     * 根据trace信息分析源码,生成异常处理详细数据
     *
     * @param \Throwable $e
     *
     * @return mixed
     * @throws \ReflectionException
     */
    function exceptionSource(\Throwable $e)
    {
        $file = $e->getFile();
        $exception_line = $e->getLine();
        $trace = $e->getTrace();
        if ('EP->Exception' . DS . 'ELog.php' === $this->hiddenFileRealPath($file)) {
            unset($trace[0]);
            $file = $trace[1]['file'];
            $exception_line = $trace[1]['line'];
        }

        $exception_file_source = array();
        $exception_file_info = new SplFileObject($file);
        foreach ($exception_file_info as $line => $code) {
            $line += 1;
            if ($line <= $exception_line + 6 && $line >= $exception_line - 6) {
                $exception_file_source[$line] = self::highlightCode($code);
            }
        }

        $result['main'] = array(
            'file' => $file,
            'line' => $exception_line,
            'message' => $this->hiddenFileRealPath($e->getMessage()),
            'show_file' => $this->hiddenFileRealPath($file),
            'source' => $exception_file_source,
        );


        $this->getTraceInfo($trace, $result['trace']);
        if ($e->getPrevious()) {
            $this->getTraceInfo($e->getPrevious()->getTrace(), $result['previous_trace']);
        }

        return $result;
    }

    /**
     * 高亮代码
     *
     * @param string $code
     *
     * @return mixed
     */
    private static function highlightCode($code)
    {
        $code = rtrim($code);
        if (0 === strcasecmp(substr($code, 0, 5), '<?php ')) {
            return highlight_string($code, true);
        }

        $highlight_code_fragment = highlight_string("<?php {$code}", true);
        return str_replace('&lt;?php', '', $highlight_code_fragment);
    }

    /**
     * 隐藏异常中的真实文件路径
     *
     * @param $path
     *
     * @return mixed
     */
    protected function hiddenFileRealPath($path)
    {
        return str_replace(array(
            PROJECT_REAL_PATH,
            EP_PATH,
            str_replace('/', DS, $_SERVER['DOCUMENT_ROOT']),
            EP_HOME_PATH
        ),
            array('Project->', 'EP->', 'Index->','EP.dir->'), $path);
    }


    /**
     * @param array $trace
     * @param $content
     *
     * @throws \ReflectionException
     */
    protected function getTraceInfo(array $trace, &$content)
    {
        if (!empty($trace)) {
            $this->alignmentTraceData($trace);
            foreach ($trace as $tn => &$t) {
                if (!isset($t['file'])) {
                    continue;
                }

                $i = 0;
                $trace_file_info = new SplFileObject($t['file']);
                foreach ($trace_file_info as $line => $code) {
                    $line += 1;
                    if (($line <= $t['end_line'] && $line >= $t['start_line']) && $i < 16) {
                        $t['source'][$line] = self::highlightCode($code);
                        $i++;
                    }
                }

                $content[] = $t;
            }
        }
    }

    /**
     * 整理trace数据
     *
     * @param array $trace
     *
     * @throws \ReflectionException
     */
    function alignmentTraceData(array &$trace = array())
    {
        foreach ($trace as &$t) {
            if (isset($t['file'])) {
                $t['show_file'] = $this->hiddenFileRealPath($t['file']);
                $t['start_line'] = max(1, $t['line'] - 6);
                $t['end_line'] = $t['line'] + 6;
            } elseif (isset($t['function']) && isset($t['class'])) {
                $rc = new ReflectionClass($t['class']);
                $t['file'] = $rc->getFileName();
                $t['show_file'] = $this->hiddenFileRealPath($rc->getFileName());

                $rf = new ReflectionMethod($t['class'], $t['function']);
                $t['start_line'] = $rf->getStartLine();
                $t['end_line'] = $rf->getEndLine();
                $t['line'] = sprintf("%s ~ %s", $t['start_line'], $t['end_line']);
            } else {
                continue;
            }
        }
    }

    function errorType($type)
    {
        switch ($type) {
            case E_ERROR: // 1 //
                return 'E_ERROR';
            case E_WARNING: // 2 //
                return 'E_WARNING';
            case E_PARSE: // 4 //
                return 'E_PARSE';
            case E_NOTICE: // 8 //
                return 'E_NOTICE';
            case E_CORE_ERROR: // 16 //
                return 'E_CORE_ERROR';
            case E_CORE_WARNING: // 32 //
                return 'E_CORE_WARNING';
            case E_COMPILE_ERROR: // 64 //
                return 'E_COMPILE_ERROR';
            case E_COMPILE_WARNING: // 128 //
                return 'E_COMPILE_WARNING';
            case E_USER_ERROR: // 256 //
                return 'E_USER_ERROR';
            case E_USER_WARNING: // 512 //
                return 'E_USER_WARNING';
            case E_USER_NOTICE: // 1024 //
                return 'E_USER_NOTICE';
            case E_STRICT: // 2048 //
                return 'E_STRICT';
            case E_RECOVERABLE_ERROR: // 4096 //
                return 'E_RECOVERABLE_ERROR';
            case E_DEPRECATED: // 8192 //
                return 'E_DEPRECATED';
            case E_USER_DEPRECATED: // 16384 //
                return 'E_USER_DEPRECATED';
            default:
                return 'UNKNOWN';
        }
    }
}