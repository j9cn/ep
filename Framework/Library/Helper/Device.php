<?php
/**
 * Created by PhpStorm.
 * User: J.H
 * Date: 2018/1/29
 * Time: 19:54
 */

namespace EP\Library\Helper;


use EP\Library\Ip\IpRegion;

class Device
{
    private static $mobile = false;
    private static $tablet = false;
    private static $os = '';
    private static $os_version = '';
    private static $browser = '';
    private static $browser_version = '';
    private static $browser_miniProgram = false;
    private static $browser_alias = array(
        'Firefox' => '火狐',
        'Chrome' => '谷歌',
        'Baidu' => '百度',
        'SouGou' => '搜狗',
        'TaoBao' => '淘宝',
        'XiaoMi' => '小米',
        'LieBao' => '猎豹',
        'Wechat' => '微信'
    );

    /**
     * 设备硬件
     * @return string
     */
    static function divice()
    {
        if (!self::$os) {
            self::os();
        }
        if (self::$tablet) {
            return 'tablet';
        }
        if (self::$mobile) {
            return 'mobile';
        }
        return 'pc';
    }

    /**
     * 获取设备基本信息key
     * @param string $separator
     * @return string
     */
    static function deviceKey($separator = '-')
    {
        self::guestInfo();
        $base = [self::$os, self::$os_version, self::$browser, self::$browser_version];
        $deviceCookieId = $_COOKIE['deviceId'] ?? '';
        $deviceId = $_SERVER['HTTP_DEVICE_ID'] ?? '';
        if ($deviceId) {
            $base[] = $deviceId;
        } elseif ($deviceCookieId) {
            $base[] = $deviceCookieId;
        }
        return implode($separator, $base);
    }

    /**
     * 微信小程序
     * @return bool
     */
    static function isMiniProgram()
    {
        return self::$browser_miniProgram;
    }

    /**判断是否为移动端访问
     * @return bool  返回true为移动端访问
     */
    static function isMobile()
    {
        if (self::$mobile) {
            return true;
        }
        // 如果有HTTP_X_WAP_PROFILE则一定是移动设备
        if (isset ($_SERVER['HTTP_X_WAP_PROFILE'])) {
            return true;
        }
        //如果via信息含有wap则一定是移动设备,部分服务商会屏蔽该信息
        if (isset ($_SERVER['HTTP_VIA'])) {
            //找不到为false,否则为true
            return stristr($_SERVER['HTTP_VIA'], "wap") ? true : false;
        }
        //判断手机发送的客户端标志,兼容性有待提高
        if (isset ($_SERVER['HTTP_USER_AGENT'])) {
            $client_keywords = array(
                'nokia', 'sony', 'ericsson', 'mot', 'samsung', 'htc', 'sgh', 'lg', 'sharp', 'sie-', 'philips',
                'panasonic', 'alcatel', 'lenovo', 'iphone', 'ipod', 'blackberry', 'meizu', 'android', 'netfront',
                'symbian', 'ucweb', 'windowsce', 'palm', 'operamini', 'operamobi', 'openwave', 'nexusone', 'cldc',
                'midp', 'wap', 'mobile'
            );
            // 从HTTP_USER_AGENT中查找手机浏览器的关键字
            if (preg_match("/(" . implode('|', $client_keywords) . ")/i", strtolower($_SERVER['HTTP_USER_AGENT']))) {
                return true;
            }
        }
        if (isset ($_SERVER['HTTP_ACCEPT'])) {
            // 如果只支持wml并且不支持html那一定是移动设备
            if ((strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') !== false) && (stripos($_SERVER['HTTP_ACCEPT'],
                        'text/html') === false || (strpos($_SERVER['HTTP_ACCEPT'],
                            'vnd.wap.wml') < strpos($_SERVER['HTTP_ACCEPT'], 'text/html')))
            ) {
                return true;
            }
        }
        return false;
    }

    /**
     * 返回访客详细信息
     *
     * @param string $user_agent
     *
     * @return array
     */
    static function guestInfo($user_agent = '')
    {
        $agent = $user_agent ? $user_agent : $_SERVER['HTTP_USER_AGENT'];
        self::osVersion($agent);
        self::browser($agent);
        return array(
            'os' => self::$os,
            'os_ver' => self::$os_version,
            'browser' => self::$browser,
            'browser_alia' => isset(self::$browser_alias[self::$browser]) ? self::$browser_alias[self::$browser] : self::$browser,
            'browser_ver' => self::$browser_version,
            'mobile' => $user_agent ? self::$mobile : self::isMobile(),
            'tablet' => self::$tablet,
            'ip' => self::getIp(),
            'long_ip' => self::getLongIp(),
            'area' => (new IpRegion())->searchRegion(self::getIp())
        );
    }

    /**
     * 获取系统类型及版本(型号)
     *
     * @param string $user_agent
     *
     * @return mixed|string
     */
    static function osVersion($user_agent = '')
    {
        $agent = $user_agent ? $user_agent : $_SERVER['HTTP_USER_AGENT'];
        self::os($user_agent);
        $ov = '';
        if (true === self::$mobile) {
            if (in_array(self::$os, array('iPhone', 'iPad', 'iPod'))) {
                preg_match('/OS (\d[_\d]*)/i', $agent, $version);

            } else {

                switch (self::$os) {
                    case 'Android':
                        preg_match('/Android (\d[.\d]+)/i', $agent, $version);
                        break;
                    case 'Windows Phone';
                        preg_match('/(Phone|Phone OS) (\d[.\d]+)/i', $agent, $version) ||
                        preg_match('/Tablet PC[\s?\d[.\d]*/i', $agent, $version);
                        break;
                    case 'Nokia':
                        //分两个正则验证，优先取Nokia机型
                        preg_match('/Nokia\s?([A-Z\d-]+)/i', $agent, $version) ||
                        preg_match('/Series\s?(\d+)/i', $agent, $version);
                        break;
                    case 'BlackBerry':
                        preg_match('/(BlackBerry\s|BB)(\d+)/i', $agent, $version);
                        break;
                    case 'BaDa':
                        preg_match('/SAMSUNG-([-A-Z\d]+)/i', $agent, $version);
                        break;
                    case 'Tizen':
                        preg_match('/SMART-TV|TIZEN ([A-Z]+[-A-Z\d]+)|Tizen\s?([.\d]+)/i', $agent, $version);
                        break;

                    default:
                        preg_match('/Sailfish|Ubuntu/i', $agent, $version);

                }

            }

        } else {
            // desktop

            switch (self::$os) {
                case 'Windows':
                    preg_match('/Windows\s?(NT|ME)\s?([.\d]*)/i', $agent, $version);
                    $version[] = self::parseWinOs(end($version));
                    break;
                case 'Chrome OS':
                    preg_match('/CrOS\s[\S]+\s([.\d]*)/i', $agent, $version);
                    break;
                case 'Macintosh':
                    preg_match('/OS\s([X\s_.\d]*)/i', $agent, $version);
                    break;
                case 'Linux':
                    $type_linux = array(
                        'Ubuntu', 'Debian', 'Fedora', 'FreeBSD', 'Gentoo', 'CentOS', 'CaixaMagica', 'Mint', 'Red Hat',
                        'RHEL', 'SUSE', 'Mandriva', 'MeeGo', 'NetBSD', 'OpenBSD', 'Slackware', 'StartOS', 'SunOS',
                        'webOS'
                    );
                    $ver = '[-\/\w.]{0,6}';
                    $type = array_map(function ($t) use ($ver) {
                        return $t . $ver;
                    }, $type_linux);
                    preg_match('/' . implode('|', $type) . '/i', $agent, $version);
                    break;
                default:
                    preg_match('/webOS|hp-tablet/i', $agent, $version);

            }

        }
        if (is_array($version)) {
            $ov = end($version);
        }
        return self::$os_version = (string)$ov;
    }

    private static function parseWinOs($ver)
    {
        switch ($ver) {
            case '6.3':
                return '8.1';
            case '6.2':
                return '8';
            case '6.1':
                return '7';
            case '6.0':
                return 'Vista';
            case '5.2':
                return '2003';
            case '5.1':
                return 'xp';
            case '5.0':
                return '2000';
            default:
                return $ver;
        }
    }

    /**
     * 获取系统类型
     *
     * @param string $user_agent
     *
     * @return string
     */
    static function os($user_agent = '')
    {
        $agent = $user_agent ? $user_agent : $_SERVER['HTTP_USER_AGENT'];
        $os = 'unknown';
        $mobile = $tablet = false;

        if (false === $agent) {
            return self::$os = $os;
        }
        if (preg_match('/Mobile|Phone|Tablet|opera\s*mobi|Opera\sMini/i', $agent)) {
            $tablet = (bool)(stripos($agent, 'Pad') !== false || stripos($agent, 'Tablet') !== false);
            $mobile = true;

        }
        if (stripos($agent, 'windows') !== false) {

            $os = 'Windows';
            if ($mobile) {
                $os = 'Windows Phone';
            }

        } elseif (stripos($agent, 'Tizen') !== false) {
            //SAMSUNG
            $mobile = true;
            $os = 'Tizen';

        } elseif (stripos($agent, 'android') !== false) {
            $mobile = true;
            $os = 'Android';

        } elseif (stripos($agent, 'iPhone') !== false) {

            $os = 'iPhone';

        } elseif (stripos($agent, 'iPad') !== false) {

            $mobile = true;
            $os = 'iPad';

        } elseif (stripos($agent, 'iPod') !== false) {

            $mobile = true;
            $os = 'iPod';

        } elseif (stripos($agent, 'mac') !== false) {

            $os = 'Macintosh';

        } elseif (stripos($agent, 'CrOS') !== false) {

            $os = 'Chrome OS';
        } elseif (stripos($agent, 'KFAPWI') !== false) {

            $os = 'Kindle';
            $tablet = true;

        } elseif (stripos($agent, 'linux') !== false || stripos($agent, 'X11') !== false) {

            $os = 'Linux';

        } elseif (stripos($agent, 'Nokia') !== false || stripos($agent, 'Series') !== false) {

            $mobile = true;
            $os = 'Nokia';

        } elseif (
            stripos($agent, 'BlackBerry') !== false ||
            stripos($agent, 'BB10') !== false ||
            stripos($agent, 'PlayBook') !== false
        ) {

            $mobile = true;
            $os = 'BlackBerry';

        } elseif (stripos($agent, 'BaDa') !== false) {
            //SAMSUNG
            $mobile = true;
            $os = 'BaDa';

        } elseif (stripos($agent, 'FreeBSD') !== false) {

            $os = 'FreeBSD';

        } elseif (stripos($agent, 'OpenBSD') !== false) {

            $os = 'OpenBSD';

        } elseif (stripos($agent, 'NetBSD') !== false) {

            $os = 'NetBSD';

        } elseif (stripos($agent, 'OpenSolaris') !== false) {

            $os = 'OpenSolaris';

        } elseif (stripos($agent, 'SunOS') !== false) {

            $os = 'SunOS';

        } elseif (stripos($agent, 'OS\/2') !== false) {

            $os = 'OS2';

        } elseif (stripos($agent, 'BeOS') !== false) {

            $os = 'BeOS';

        } elseif (stripos($agent, 'win') !== false) {

            $os = 'Windows';
        } else {
            $os = 'unknown';
        }
        self::$mobile = $mobile;
        self::$tablet = $tablet;
        self::$os = $os;
        return $os;
    }

    /**
     * 获取浏览器信息
     *
     * @param string $user_agent
     *
     * @return string
     */
    static function browser($user_agent = '')
    {
        $agent = $user_agent ? $user_agent : $_SERVER['HTTP_USER_AGENT'];
        $bn = $bv = '';
        if (stripos($agent, 'MicroMessenger') !== false) {
            preg_match('/MicroMessenger\/([.\d]{0,5})/i', $agent, $version);
            $bn = 'Wechat';
            if (stripos($agent, 'miniProgram') !== false) {
                self::$browser_miniProgram = true;
            }

        } elseif (stripos($agent, "Maxthon") !== false) {//双内核，即包含了IE一致特性，又含有chrome
            preg_match('/Maxthon\/([.\d]+)/', $agent, $version);
            $bn = "Maxthon";

        } elseif (stripos($agent, "Edge") !== false) {
            preg_match('/Edge\/([.\d]+)/', $agent, $version);
            $bn = "Edge";

        } elseif (stripos($agent, "MSIE") !== false || (stripos($agent, 'Trident') !== false &&
                preg_match('/;\srv:(11.[\d.]+)/i', $agent, $version))
        ) {
            if (empty($version)) {
                preg_match('/MSIE\s+([.\d]+)/i', $agent, $version);
            }
            $bn = "IE";

        } elseif (stripos($agent, 'Opera') !== false || stripos($agent, 'OPR') !== false) {
            preg_match('/OPR?(era(\sMini))?\/([.\d]{1,8})/i', $agent, $version);
            $bn = "Opera";

        } elseif (stripos($agent, "Chrome") !== false || stripos($agent, 'CriOS') !== false) {
            $other = str_replace(array('Mozilla/', 'AppleWebKit/', 'Safari/', 'Chrome/'), '', $agent);
            $other_browser = array(
                'CriOS', 'QQBrowser', 'QtWebEngine',
                'baidubrowser', 'BIDUBrowser', 'Spark', //百度
                '360Browser', '360EE', //360
                'LBBROWSER', //猎豹
                'MetaSr', 'Amigo', 'Dragon', 'CoolNovo', 'Epiphany', 'FaBrowser', 'Iridium', 'Kinza', 'SmartTV',
                'luakit', 'MxNitro', 'MxBrowser', 'Perk', 'Puffin', 'Polarity', 'SamsungBrowser', 'Sleipnir',
                'SlimBoat', 'SparkSafe', 'Iron', 'Surf', 'Swing', 'TaoBrowser', 'UCBrowser', 'Vivaldi',
                'WhiteHat Aviator', 'MiuiBrowser', 'Yowser', 'YaBrowser'
            );
            $pattern = '/(' . implode('|', $other_browser) . ')[\/\s]?([.\d]{1,8})?/i';
            preg_match($pattern, $other, $version);
            if (!empty($version[1])) {
                $bn = self::parseBrowserName($version[1]);
                if (!isset($version[2])) {
                    $version[2] = '';
                }
            } else {
                preg_match('/Chrome\/([.\d]{1,8})/i', $agent, $version);
                $bn = "Chrome";
            }


        } elseif (stripos($agent, 'Firefox') !== false || stripos($agent, 'FxiOS') !== false) {
            $other = str_replace(array('Mozilla/', 'Gecko/', 'Firefox/'), '', $agent);
            $other_browser = array(
                'Dragon', 'CometBird', 'conkeror', 'Cunaguaro', 'Cunaguaro', 'Cyberfox', 'Flock', 'Iceweasel', 'Meleon',
                'Light', 'Lunascape', 'Maemo Browser', 'Midori', 'Orca', 'PaleMoon', 'SailfishBrowser', 'SeaMonkey',
                'Sundial', 'TenFourFox', 'Waterfox', 'Wyzo', 'FxiOS'
            );
            $pattern = '/(' . implode('|', $other_browser) . ')[\/\s]?([.\d]{1,8})/i';
            preg_match($pattern, $other, $version);
            if (!empty($version[1])) {
                $bn = self::parseBrowserName($version[1]);
            } else {
                preg_match('/Firefox\/([.\d]{1,8})/i', $agent, $version);
                $bn = "Firefox";
            }

        } elseif (stripos($agent, 'Safari/') !== false) {
            $other = str_replace(array('Mozilla/', 'AppleWebKit/', 'Safari/'), '', $agent);
            $other_browser = array(
                'Coast', 'CoolBrowser', 'Epiphany', 'iCab', 'konqueror', 'SmartTV', 'Mercury', 'Midori', 'OmniWeb',
                'OneBrowser', 'Otter', 'QML', 'QupZilla', 'rekonq', 'SamsungBrowser', 'Stainless', 'Surf', 'UCBrowser',
                'MQQBrowser', 'Qt', 'MiuiBrowser', '360browser'
            );
            $pattern = '/(' . implode('|', $other_browser) . ')[\/\s]?([.\d]{1,8})?/i';
            preg_match($pattern, $other, $version);
            if (!empty($version[1])) {
                $bn = self::parseBrowserName($version[1]);
                if (!isset($version[2])) {
                    $version[2] = '';
                }
            } else {
                preg_match('/Safari\/([.\d]{1,8})/i', $agent, $version);
                $bn = "Safari";
            }
        } else {

            $other = array(
                'QtWeb', 'MQQBrowser', 'QQBrowser', 'conkeror', 'SeaMonkey', 'Monyq', 'Minimo', 'NintendoBrowser',
                'NetPositive', 'NetSurf', 'Polaris', 'Twitter', 'TizenBrowser', 'Ubuntu', 'UCWEB', 'WhatsApp', 'Opera',
                'MiuiBrowser', 'baiduboxapp'
            );
            preg_match('/(' . implode('|', $other) . ')[\s\/]?([.\d]{0,8})?/i', $agent, $version);

            if (!empty($version[1])) {
                $bn = self::parseBrowserName($version[1]);
                if (!isset($version[2])) {
                    $version[2] = '';
                }
            } else {
                $bn = "unknown";
            }
        }
        self::$browser = $bn;
        self::$browser_version = $bv;
        if (is_array($version)) {
            self::$browser_version = trim(end($version), ".-\0");
        }
        return $bn . '-' . $bv;
    }

    static function getLongIp($ip = '')
    {
        if ('' === $ip) {
            $ip = self::getIp();
        }
        return sprintf("%u", ip2long($ip));
    }

    static function getIp()
    {
        static $user_ip;
        if (!is_null($user_ip)) {
            return $user_ip;
        }
        $key = array(
            'HTTP_CLIENT_IP',
            'HTTP_CF_CONNECTING_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        );

        foreach ($key as $field) {
            if (array_key_exists($field, $_SERVER)) {
                $ips = explode(',', $_SERVER[$field]);
                foreach ($ips as $ip) {
                    $ip = trim($ip);
                    //会过滤掉保留地址和私有地址段的IP，例如 127.0.0.1会被过滤
                    if ((bool)filter_var($ip, FILTER_VALIDATE_IP,
                            FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) &&
                        (false !== ip2long($ip))
                    ) {
                        return $user_ip = $ip;
                    }
                }
            }
        }
        return $user_ip = '0.0.0.0';
    }

    private static function parseBrowserName($key)
    {
        $key = strtolower($key);
        switch ($key) {
            case 'fxios':
                return 'Firefox';
            case 'crios':
                return 'Chrome';
            case 'qqbrowser':
            case 'mqqbrowser':
                return 'QQ';
            case 'qtwebengine':
                return 'QtWeb';
            case 'baidubrowser':
            case 'spark':
            case 'bidubrowser':
            case 'baiduboxapp':
                return 'Baidu';
            case 'metasr':
                return 'SouGou'; //搜狗
            case 'taobrowser':
                return 'TaoBao'; //淘宝
            case 'ucbrowser':
            case 'ucweb':
                return 'UC';
            case 'miuibrowser':
                return 'XiaoMi';
            case '360browser':
            case '360ee':
                return '360';
            case 'LBBROWSER':
                return 'LieBao';//猎豹

            default:
                return $key;
        }
    }
}