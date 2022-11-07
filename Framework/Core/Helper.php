<?php
/**
 * Created by PhpStorm.
 * User: JH
 * Date: 2017/10/19
 * Time: 15:41
 */
declare(strict_types=1);

namespace EP\Core;


class Helper
{

    /**
     * 获取中文字符串首字母
     * @param $str
     * @return string
     */
    static function getStringFirstChar($str)
    {
        $str = iconv("UTF-8", "gb2312", $str);
        if (preg_match("/^[\x7f-\xff]/", $str)) {
            $fchar = ord($str{0});
            if ($fchar >= ord("A") and $fchar <= ord("z")) return strtoupper($str{0});
            $a = $str;
            $val = ord($a{0}) * 256 + ord($a{1}) - 65536;
            if ($val >= -20319 and $val <= -20284) return "A";
            if ($val >= -20283 and $val <= -19776) return "B";
            if ($val >= -19775 and $val <= -19219) return "C";
            if ($val >= -19218 and $val <= -18711) return "D";
            if ($val >= -18710 and $val <= -18527) return "E";
            if ($val >= -18526 and $val <= -18240) return "F";
            if ($val >= -18239 and $val <= -17923) return "G";
            if ($val >= -17922 and $val <= -17418) return "H";
            if ($val >= -17417 and $val <= -16475) return "J";
            if ($val >= -16474 and $val <= -16213) return "K";
            if ($val >= -16212 and $val <= -15641) return "L";
            if ($val >= -15640 and $val <= -15166) return "M";
            if ($val >= -15165 and $val <= -14923) return "N";
            if ($val >= -14922 and $val <= -14915) return "O";
            if ($val >= -14914 and $val <= -14631) return "P";
            if ($val >= -14630 and $val <= -14150) return "Q";
            if ($val >= -14149 and $val <= -14091) return "R";
            if ($val >= -14090 and $val <= -13319) return "S";
            if ($val >= -13318 and $val <= -12839) return "T";
            if ($val >= -12838 and $val <= -12557) return "W";
            if ($val >= -12556 and $val <= -11848) return "X";
            if ($val >= -11847 and $val <= -11056) return "Y";
            if ($val >= -11055 and $val <= -10247) return "Z";
        } else {
            return '';
        }
        return '';
    }

    /**
     * 根据文件名创建文件
     *
     * @param string $file_name
     * @param int $mode
     * @param int $dir_mode
     *
     * @return bool
     */
    static function mkFile($file_name, $mode = 0644, $dir_mode = 0755): bool
    {
        if (!file_exists($file_name)) {
            $file_path = dirname($file_name);
            self::createFolders($file_path, $dir_mode);

            $fp = fopen($file_name, 'w+');
            if ($fp) {
                fclose($fp);
                chmod($file_name, $mode);
                return true;
            }
            return false;
        }
        return true;
    }

    /**
     * 取得文件扩展名
     *
     * @param string $file 文件名
     *
     * @return string
     */
    static function getExt($file): string
    {
        return pathinfo($file, PATHINFO_EXTENSION);
    }

    /**
     * 创建文件夹
     *
     * @param string $path
     * @param int $mode
     *
     * @return bool
     */
    static function createFolders($path, $mode = 0755): bool
    {
        if (!is_dir($path)) {
            return mkdir($path, $mode, true);
        }
        return true;
    }

    /**
     * @param string $path
     * @param int $type
     * @return string
     */
    static function formatFoldersByDate($type = 1)
    {
        $dir_name = '';
        switch ($type) {
            case 1:
                #2020/01
                $dir_name = date("Y/m");
                break;
            case 2:
                #2020/01/01
                $dir_name = date("Y/m/d");
                break;
            case 3:
                #2020/0101
                $dir_name = date("Y/md");
                break;
            default:
                #20200101
                $dir_name = date("Ymd");
        }
        return $dir_name;
    }

    /**
     * @param string $path
     * @param int $type
     * @return array
     */
    static function loadFolders(string $path, $type = 0)
    {
        $resource = null;
        $current_path = rtrim($path, DS) . DS;
        if (is_dir($current_path)) {
            $resource = opendir($current_path);
        }
        $list = [];
        if ($resource) {
            $i = 0;
            while (false !== ($filename = readdir($resource))) {
                if ($filename{0} == '.') {
                    continue;
                }
                $file = $current_path . $filename;
                switch ($type) {
                    case 2:
                        if (!is_dir($file)) {
                            continue;
                        }
                        break;
                    case 1:
                        if (!is_file($file)) {
                            continue;
                        }
                }
                if (is_dir($file)) {
                    $list[$i]['is_dir'] = true; //是否文件夹
                    $list[$i]['has_file'] = (count(scandir($file)) > 2); //文件夹是否包含文件或者下级目录
                    $list[$i]['filesize'] = 0; //文件大小
                    $list[$i]['filetype'] = ''; //文件类别，用扩展名判断
                } else {
                    $list[$i]['is_dir'] = false;
                    $list[$i]['has_file'] = false;
                    $list[$i]['filesize'] = round(filesize($file) / 1024, 3) . ' kb';
                    $list[$i]['filetype'] = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                }
                $list[$i]['dir_path'] = $file;
                $list[$i]['filename'] = $filename; //文件名，包含扩展名
                $list[$i]['datetime'] = date('Y-m-d H:i:s', filemtime($file)); //文件最后修改时间
                $i++;
            }
            closedir($resource);
        }
        return $list;
    }

    /**
     * 数据递归
     *
     * @param array $data
     * @param string $parent_key
     * @param string $sub_key
     * @param string $node_name
     * @param int $parent_id
     *
     * @return array
     */
    static function treeData($data, $parent_key = 'parent', $sub_key = 'sub', $node_name = 'nodes', $parent_id = 0)
    {
        $tree_data = array();
        $tem = array();
        foreach ($data as $value) {
            if ($value[$parent_key] == $parent_id) {
                $tem = self::treeData($data, $parent_key, $sub_key, $node_name, $value[$sub_key]);
                //判断是否存在子数组
                if ($tem) {
                    $value[$node_name] = $tem;
                    $value['isParent'] = true;
                } else {
                    $value[$node_name] = [];
                    $value['isParent'] = false;
                }
                //$tem && $value[$node_name] = $tem;
                $tree_data[] = $value;
            }
        }
        return $tree_data;
    }

    /**
     * 数字万位格式化
     *
     * @param float $num
     * @param int $decimals
     * @param string $thousands_sep
     *
     * @return string
     */
    static function formatNumThousands($num, $decimals = 2, $thousands_sep = ',')
    {
        if (!is_numeric($num)) {
            return '0.00';
        }
        if ($num >= 10000) {
            $_decimals = $decimals + 1;
            $num = sprintf("%.{$_decimals}f", $num);
            list($int, $decimal) = explode('.', $num);
            $int = strrev(preg_replace('/(\d{4})/', "$1{$thousands_sep}", strrev($int)));
            if ($decimal) {
                $decimal = substr($decimal, 0, $decimals);
            }
            return "{$int}.{$decimal}";
        } else {
            return number_format((float)$num, $decimals, '.', ' ');
        }
    }

    /**
     * 格式化数据大小(单位byte)
     *
     * @param int $size
     * @param string $ori_unit
     *
     * @return string
     */
    static function sizeConvert($size, $ori_unit = 'B')
    {
        $size = (int)$size;
        if (1 > $size) {
            return 0 . ' B';
        }
        $unit = ['B', 'KB', 'M', 'G', 'TB', 'PB'];
        $shift_init = 0;
        if (in_array(strtoupper($ori_unit), $unit)) {
            list($shift_init) = array_keys($unit, strtoupper($ori_unit));
        }
        $s = floor(log($size, 1024));
        $i = (int)$s + $shift_init;

        if (isset($unit[$i])) {
            return sprintf('%.2f ' . $unit[$i], $size / pow(1024, $s));
        }

        return $size . ' ' . $unit[0];
    }


    /**
     * 随机生成数字字母
     *
     * @param int $len
     * @param null|string $chars
     * @param bool $use_default_chars
     *
     * @return string
     */
    static function randNumString($len, $chars = null, $use_default_chars = true)
    {
        $default_chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        if (!is_null($chars)) {
            if ($use_default_chars) {
                $default_chars = $chars . $default_chars;
            } else {
                $default_chars = $chars;
            }
        }
        $str = '';
        $lc = strlen($default_chars) - 1;
        for ($i = 0; $i < $len; $i++) {
            $str .= $default_chars[mt_rand(0, $lc)];
        }
        return $str;
    }

    static function UUID()
    {
        return sprintf(
            '%04X%04X-%04X-%04X-%04X-%04X%04X%04X',
            mt_rand(0, 65535),
            mt_rand(0, 65535),
            mt_rand(0, 65535),
            mt_rand(16384, 20479),
            mt_rand(32768, 49151),
            mt_rand(0, 65535),
            mt_rand(0, 65535),
            mt_rand(0, 65535)
        );
    }

    /**
     * 生成当前时间转36进制+2位随机36进制字母
     * @return string
     */
    static function timeCodeId()
    {
        return base_convert(time(), 10, 36) . base_convert(mt_rand(100, 999), 10, 36);
    }

    /**
     * 显示友好时间格式
     *
     * @param int $time 时间戳
     * @param string $format
     * @param int $start_time
     * @param string $suffix
     * @return string
     */
    static function fTime($time, $format = 'Y-m-d H:i:s', $start_time = 0, $suffix = '前')
    {
        if ($start_time == 0) {
            $start_time = time();
        }

        $t = $start_time - $time;
        if ($t < 63072000) {
            $f = array(
                '31536000' => '年',
                '2592000' => '个月',
                '604800' => '星期',
                '86400' => '天',
                '3600' => '小时',
                '60' => '分钟',
                '1' => '秒'
            );

            foreach ($f as $k => $v) {
                if (0 != $c = floor($t / (int)$k)) {
                    return $c . $v . $suffix;
                }
            }
        }

        return date($format, $time);
    }

    /**
     * 压缩html
     * 清除换行符,清除制表符,去掉注释标记
     * @param string $string
     * @return string
     * */
    static function compressHtml($string)
    {
        $string = str_replace("\r\n", '', $string); //清除换行符
        $string = str_replace("\n", '', $string); //清除换行符
        $string = str_replace("\t", '', $string); //清除制表符
        $pattern = array(
            "/> *([^ ]*) *</", //去掉注释标记
            "/[\s]+/",
            "/<!--[^!]*-->/",
            "/\" /",
            "/ \"/",
            "'/\*[^*]*\*/'"
        );
        $replace = array(
            ">\\1<",
            " ",
            "",
            "\"",
            "\"",
            ""
        );
        return preg_replace($pattern, $replace, $string);
    }

    /**
     * 获取请求参数的分页数据
     * @param array $data
     * @return array
     */
    static function pageData(array $data = [])
    {
        $default = ['p' => 1, 'limit' => 20];
        $data = array_merge($default, $_POST, $_GET, $data);
        $page = [
            'p' => ((int)$data['p'] < 1) ? 1 : intval($data['p']),
            'limit' => $data['limit']
        ];
        if (!empty($data['limit'])) {
            $page['limit'] = ((int)$data['limit'] > 0) ? (int)$data['limit'] : $default['limit'];
        }
        if (!empty($data['result_count'])) {
            $page['result_count'] = (int)$data['result_count'];
        }
        return $page;
    }

    /**
     * 生成日期+时间+指定长度随机数
     * @param int $randLen
     * @return string
     */
    static function buildDateOrderNum($randLen = 3)
    {
        $randMax = 9;
        if ($randLen > 1) {
            $randMax = (int)str_pad((string)$randMax, (int)$randLen, '9');
        }
        $date = [
            #年月日 8位
            date('Ymd'),
            # 当天第几分钟(4位 0001-1440)
            str_pad((string)round((time() - strtotime(date('y-m-d'))) / 60, 0, PHP_ROUND_HALF_DOWN), 4, '0', STR_PAD_LEFT),
            # 当前秒数 (2位 00-59)
            str_pad(date('s'), 2, '0', STR_PAD_LEFT),
            #随机N位
            str_pad((string)mt_rand(1, $randMax), $randLen, '0', STR_PAD_LEFT)
        ];
        return implode('', $date);
    }

}