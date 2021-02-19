<?php
/**
 * Created by PhpStorm.
 * User: J.H
 * Date: 2018/3/10
 * Time: 7:50
 */
declare(strict_types=1);

namespace EP\Library\Helper;


class RunTime
{
    private static $start = null;
    private static $end = null;

    static function start()
    {
        self::$end = null;
        return self::$start = microtime(true);
    }

    static function end()
    {
        return self::$end = microtime(true);
    }

    static function getRunTime($unit = 's', $showUnit = true)
    {
        if (null === self::$start) {
            new \Exception('please call the first start function');
        }

        $duration = self::calcDuration(self::$start, null, $unit);

        if ($showUnit) {
            return $duration . " {$unit}";
        } else {
            return $duration;
        }

    }

    static function calcDuration($start_time, $end_time = null, $unit = 's')
    {
        switch ($unit) {
            case 's':
            case 'S':
                $pre = 0;
                break;
            default:
                $pre = 1;
        }
        if (null === $end_time) {
            $end_time = self::end();
        }
        return round(($end_time - $start_time) * pow(10, $pre * 3), 3);
    }

}