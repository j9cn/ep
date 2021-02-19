<?php
/**
 * Created by PhpStorm.
 * User: JH
 * Date: 2018/1/14
 * Time: 16:58
 */
declare(strict_types=1);

namespace EP\Library\Helper;


class Sessions
{

    private $sid;

    function __construct()
    {
        session_name('EPSID');
        if (!isset($_SESSION)) {
            session_start();
        }
        $this->sid = session_id();
    }

    /**
     * @param string $key
     * @param $val
     * @param int $expire
     *
     * @return string
     */
    function set(string $key, $val, $expire = 0)
    {
        $_SESSION[$key] = $val;
        if (0 !== $expire) {
            $_SESSION["{$key}_expire"] = time() + $expire;
        }
        return $this->sid;
    }

    /**
     * @param string $key
     *
     * @return bool|string
     */
    function get(string $key)
    {
        if (isset($_SESSION["{$key}_expire"])) {
            if (time() > $_SESSION["{$key}_expire"]) {
                $this->del($key);
            }
        }
        if (isset($_SESSION[$key])) {
            return $_SESSION[$key];
        }
        return false;
    }

    /**
     * @param string $key
     */
    function del(string $key)
    {
        unset($_SESSION["{$key}_expire"], $_SESSION[$key]);
    }
}