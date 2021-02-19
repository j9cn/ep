<?php
/**
 * Created by PhpStorm.
 * User: jh
 * Date: 2018/10/21
 * Time: 下午2:47
 */

namespace EP\Exception;


use EP\Core\Develop;

class EN extends EPE
{
    private $elog;

    public function __construct(array $error)
    {
        $error['file'] = $this->hiddenFileRealPath($error['file']);
        $error['error_type'] = $this->errorType($error['type']);
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        if (!is_array($trace)) {
            $trace = [];
        }
        foreach ($trace as $index => $item) {
            $trace[$index]['file'] = $this->hiddenFileRealPath($item['file']);
        }
        $main_error = [
            'main' => $error,
            'trace' => $trace
        ];
        ob_clean();
        if (Develop::isDev()) {
            $this->elog = new ELog($main_error, $error['message'], $error['error_type'], $error['type']);
            $this->display();
        } else {
            ELog::log(ELog::WARNING, $error);
        }
    }

    private function display()
    {
        $tpl = __DIR__ . '/tpl/warning_error.tpl.php';
        if (PHP_SAPI === 'cli') {
            $tpl = __DIR__ . '/tpl/cli_warning_error.tpl.php';
        }
        $this->elog->display($tpl);
    }

}