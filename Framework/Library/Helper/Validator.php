<?php
/**
 * Created by PhpStorm.
 * User: JH
 * Date: 2017/12/3
 * Time: 20:21
 */
declare(strict_types=1);

namespace EP\Library\Helper;


use EP\Exception\ELog;

class Validator
{
    /**
     * 标记当前key状态
     * @var bool
     */
    private $valid = true;

    /**
     * 标记全部Key是否有效
     * @var bool
     */
    public $hasInValid = false;

    /**
     * @var array
     */
    private $group = array();
    private $errors = array();
    /**
     * 全部数据容器
     * @var array|null
     */
    private $data = array();

    /**
     * 安全数据，即被询问（get）后的数据存放在此
     * @var array
     */
    private $safe_data = array();

    /**
     * @var array
     */
    private $data_key = array();

    /**
     * 标记当前key
     * @var string
     */
    private $n = '';
    private $n_is_array = array();
    private $n_allow_undeclared_key = array();

    private $or = array();


    protected $t_mobile = array(
        //(/^(17951)?(13[0-9]|15[012356789]|166|17[3678]|18[0-9]|19[0-9]|14[57])[0-9]{8}
        'cn_zh' => '/^(13[0-9]|14[0-9]|15[0-9]|166|17[0-8]|18[0-9]|19[0-9])\d{8}$/', //中国大陆
        'cn_tw' => '/^(09)\d{8}$/', //中国台湾
        'cn_hk' => '/^([6|9])\d{7}$/', //中国香港
        'cn_mo' => '/^[6]([8|6])\d{5}$/',//中国澳门
        've' => '/^04(12|14|24|16|26)\d{7}$/' //委内瑞拉
    );

    protected $text_pwd = array(
        '必须含有数字及字母组合及不能小于6位',
        '必须含有大写、小写字母及数字组合及不能小于6位',
        '必须含有字母数字及符号组合及不能小于6位'
    );

    private $illegal_tags = array(
        'script',
        'iframe',
        'style',
        'link'
    );

    /**
     * Validator constructor.
     *
     * @param array $data
     * @param array $config
     */
    function __construct(array $data = array(), array $config = [])
    {
        $this->init($data, $config);
    }

    function init(array $data, array $config = [])
    {
        $this->setData($data);
        if (!empty($config)) {
            if (isset($config['mobile'])) {
                $this->t_mobile += $config['mobile'];
            }
        }
    }

    /**
     * 提审数据
     *
     * @param string $name
     * @param bool $allow_undeclared_key
     *
     * @return $this
     */
    function get($name, $allow_undeclared_key = false)
    {
        $this->n = $name;
        $this->n_allow_undeclared_key[$name] = $allow_undeclared_key;
        if (!isset($this->data[$name])) {
            $this->valid = $this->group[$name] = false;
            $this->data[$name] = '';
        } else {
            $this->valid = $this->group[$name] = true;
        }
        //提审此key，将数据标记为安全数据
        $this->n_is_array[$name] = false;
        if (is_array($this->data[$name])) {
            $this->n_is_array[$name] = true;
            $this->safe_data[$name] = array_map('trim', $this->data[$name]);
        } else {
            $this->safe_data[$name] = trim($this->data[$name]);
        }
        return $this->isError('缺少参数：' . $name);
    }

    /**
     * 标记返回数据组的KEY
     * @param $as
     * @return $this
     */
    function keyNeeds($as = null)
    {
        if ($as) {
            $this->data[$as] = $this->data[$this->n];
            $this->get($as);
        }
        $this->data_key[] = $this->n;
        return $this;
    }


    /**
     * @param $variable
     * @param null|string|array $call
     *
     * @return $this
     */
    final function toVariable(&$variable, $call = null)
    {
        if (null !== $call) {
            if (!is_array($call)) {
                $call = array($call);
            }
            foreach ($call as $func) {
                if (is_callable($func)) {
                    $this->safe_data[$this->n] = call_user_func($func, $this->safe_data[$this->n]);
                }
            }
        }
        $variable = $this->safe_data[$this->n];
        return $this;
    }

    /**
     * 插入验证数据
     *
     * @param $data
     * @param $key
     *
     * @return $this
     */
    function addData(array $data, $key)
    {
        if (!isset($data[$key])) {
            $data[$key] = '';
        }
        $this->data[$key] = $data[$key];
        return $this;
    }

    /**
     * 获取标记需求的Key数据
     * @return array
     * @see getData()
     */
    function getNeedsDatas()
    {
        $keys = array_unique($this->data_key);
        return $this->getData($keys);
    }

    /**
     * 返回处理后的数据
     *
     * @param null $key
     *
     * @return array|string
     */
    function getData($key = null)
    {
        if ($key) {
            if (is_array($key)) {
                $data = array_map(function ($k) {
                    return isset($this->safe_data[$k]) ? $this->safe_data[$k] : '';
                }, $key);
                return array_combine($key, $data);
            }
            return isset($this->safe_data[$key]) ? $this->safe_data[$key] : '';
        }
        return $this->safe_data;
    }

    /**
     * 设置验证数据
     *
     * @param array $data
     *
     * @return $this
     */
    function setData(array $data)
    {
        $this->data = $data;
        return $this;
    }

    /**
     * 返回一个参数，并将key从安全数据中消耗
     * @param string $key
     *
     * @return string
     * @see Validator::getData()
     *
     */
    function popData($key)
    {
        $data = $this->getData($key);
        unset($this->safe_data[$key]);
        return $data;
    }

    function appendCurrentTime($key)
    {
        $this->safe_data[$key] = TIME;
        $this->data_key[] = $key;
        return $this;
    }

    /**
     * 验证一条
     *
     * @param string $name
     *
     * @return bool
     */
    function isValid($name = '')
    {
        $this->or = array();
        if ($name && isset($this->safe_data[$name])) {
            return !isset($this->errors[$name]);
        }
        return $this->valid;
    }

    /**
     * 必须全部通过验证
     * @return bool
     */
    function isValidAll()
    {
        $this->or = array();
        return !in_array(false, $this->group);
    }

    /**
     * 返回最先不通过验证的数据
     *
     * @param bool $only_content
     *
     * @return array|string
     */
    function getCurrentError($only_content = true)
    {
        $error = array_slice($this->errors, 0);
        if ($only_content) {
            if (0 !== count($error)) {
                list($error) = array_values($error);
            } else {
                $error = '';
            }
        }
        return $error;
    }

    /**
     * 返回全部不通过验证的数据
     * @return array
     */
    function getErrors()
    {
        return $this->errors;
    }

    /**
     * 验证参数是否为JSON数据格式及将数据转换
     * @param bool $toArray
     * @return Validator
     */
    function jsonData($toArray = true)
    {
        if ($this->valid) {
            $verify_data = json_decode($this->safe_data[$this->n], true);
            if ($toArray) {
                $this->safe_data[$this->n] = $verify_data;
            }
            $this->valid = (JSON_ERROR_NONE === json_last_error());
        }
        return $this->isError('JSON数据格式错误');
    }

    /**
     * 如果为空值设置指定值
     *
     * @param $set_default_value
     *
     * @return $this
     */
    function ifEmpty($set_default_value)
    {
        if (empty($this->safe_data[$this->n]) && $this->safe_data[$this->n] === '') {
            $this->safe_data[$this->n] = $set_default_value;
        }
        return $this;
    }

    /**
     * 当前KEY不能为空
     *
     * @param string $key_name
     * @param string $error_text
     *
     * @return Validator
     */
    function notEmpty($key_name = '', $error_text = '不能为空')
    {
        if ($this->valid) {
            $this->valid = !empty($this->safe_data[$this->n]) || $this->safe_data[$this->n] === '0';
        }
        if (!$key_name) {
            $key_name = $this->n;
        }
        return $this->isError($key_name . $error_text);
    }

    /**
     * 如果非{m}即{n}
     *
     * @param mixed $val
     * @param mixed $that
     *
     * @return $this
     */
    function isNot($val, $that = '')
    {
        if ($this->safe_data[$this->n] !== $val) {
            $this->safe_data[$this->n] = $that;
        }
        return $this;
    }

    /**
     * @param array $haystack
     * @param string $error_text
     *
     * @return Validator
     * @see in_array()
     *
     */
    function in(array $haystack, $error_text = '不在指定数据范围内')
    {
        $error_text = $this->defaultErrorText($error_text, '不在指定数据范围内');
        if ($this->valid) {
            $this->valid = in_array($this->safe_data[$this->n], $haystack);
        }
        return $this->isError($error_text);
    }

    /**
     * 逻辑运算，跟原生 OR 逻辑一样
     * @return $this
     */
    function _or()
    {
        if ($this->valid) {
            $this->or[$this->n] = true;
        } else {
            $this->valid = true;
            $this->group[$this->n] = true;
            unset($this->errors[$this->n]);
        }
        return $this;
    }

    /**
     * 将指定key不通过验证时，设置为默认值（指定值）
     *
     * @param string $val
     *
     * @return $this
     */
    function inValidSetValue($val = '')
    {
        if (!$this->isValid($this->n)) {
            $this->safe_data[$this->n] = $val;
            $this->group[$this->n] = true;
            unset($this->errors[$this->n]);
        }
        return $this;
    }

    /**
     * 只能是中文
     *
     * @param string $error_text
     *
     * @return $this
     */
    function onlyChinese($error_text = '只能是中文')
    {
        return $this->match("/^[\x7f-\xff]+$/", $error_text);
    }


    /**
     * 只能是中文，字母，数字
     *
     * @param string $error_text
     *
     * @return Validator
     */
    function onlyAlnumChinese($error_text = '只能是中文、字母、数字')
    {
        return $this->match("/^[\x7f-\xff|a-z0-9]+$/i", $error_text);
    }

    /**
     * 只能是字母、数字(开头)及下划线
     *
     * @param string $error_text
     *
     * @return Validator
     */
    function alnumUnderline($error_text = '只能是字母、数字(开头)及下划线')
    {
        return $this->match("/^[^_]\w+$/", $error_text);
    }

    /**
     * 验证邮箱
     *
     * @param string $error_text
     *
     * @return Validator
     */
    function email($error_text = 'Email格式错误')
    {
        return $this->match('/^([a-z0-9]*[-_\.]?[a-z0-9]+)*@([a-z0-9]*[-_]?[a-z0-9]+)+[\.][a-z]{2,3}([\.][a-z]{2})?$/i',
            $error_text);
    }

    /**
     * 验证手机号码
     *
     * @param string $location
     * @param string $error_text
     *
     * @return Validator
     */
    function mobile($location = 'cn_zh', $error_text = '手机号码格式错误')
    {
        if (!isset($this->t_mobile[$location])) {
            ELog::error('只支持验证手机号码区域：' . implode('|', array_keys($this->t_mobile)));
        }
        return $this->match($this->t_mobile[$location], $error_text);
    }

    /**
     * 验证网址
     *
     * @param string $error_text
     *
     * @return Validator
     */
    function url($error_text = '网址格式不合法')
    {
        return $this->match('/https?:\/\/[\w.]+[\w\/]*[\w.]*\??[\w=&\+\%]*/', $error_text);
    }

    /**
     * 密码强度组合,长度验证6位起
     *
     * @param int $mode 0|1|2
     * @param string $error_text
     *
     * @return Validator
     */
    function password($mode = 0, $error_text = '登录密码')
    {
        switch ($mode) {
            case 1: //必须含有大写、小写字母及数字组合
                return $this->match('/(?=.*[A-Z])(?=.*[a-z])(?=.*[0-9])[a-zA-Z0-9\W]{6,}$/',
                    $error_text . $this->text_pwd[$mode]);
            case 2: //必须含有字母数字及符号组合
                return $this->match('/(?=.*[\W])(?=.*[a-z])(?=.*[0-9])[a-zA-Z0-9\W]{6,}$/',
                    $error_text . $this->text_pwd[$mode]);
            default://必须含有数字及字母组合
                return $this->match('/(?=.*[a-zA-Z])(?=.*[0-9])[a-zA-Z0-9\W]{6,}$/',
                    $error_text . $this->text_pwd[$mode]);
        }
    }

    /**
     * 简单验证动态密码|验证码
     * @param string $format
     *
     * @return Validator
     * @see date()
     *
     */
    function datePwd($format = 'md')
    {
        if ($this->valid) {
            $this->valid = $this->safe_data[$this->n] === date($format);
        }
        return $this->isError('动态密码错误');
    }

    /**
     * 检测两个数据是否一致
     *
     * @param $key
     * @param string $error_text
     *
     * @return Validator
     */
    function equal($key, $error_text = '两个数据不一致')
    {
        if ($this->valid) {
            $this->valid = (isset($this->data[$key]) && $this->data[$key] === $this->data[$this->n]);
        }
        return $this->isError($error_text);
    }

    /**
     * 检测最小字符串长度或 数字对比
     *
     * @param string|int|float $min
     * @param bool $is_string //是否检测字符串
     * @param string $error_text
     *
     * @return Validator
     */
    function min($min, $error_text = '不能小于%MIN%', $is_string = true)
    {
        if ($this->valid) {
            if ($min) {
                $error_text = $this->defaultErrorText($error_text, '不能小于%MIN%');
                $this->valid = $min <= ($is_string ? mb_strlen($this->safe_data[$this->n]) : floatval($this->safe_data[$this->n]));
                return $this->isError(str_replace('%MIN%', $min, $error_text));
            }
        }
        return $this;
    }

    /**
     * 检测最大字符串长度或 数字对比
     *
     * @param $max
     * @param bool $is_string
     * @param string $error_text
     *
     * @return Validator
     */
    function max($max, $error_text = '不能大于%MAX%', $is_string = true)
    {
        if ($this->valid) {
            if ($max) {
                $error_text = $this->defaultErrorText($error_text, '不能大于%MAX%');
                $this->valid = $max >= ($is_string ? mb_strlen($this->safe_data[$this->n]) : floatval($this->safe_data[$this->n]));
                return $this->isError(str_replace('%MAX%', $max, $error_text));
            }
        }
        return $this;
    }

    /**
     * @param string $error_text
     *
     * @return Validator
     * @see ctype_alnum()
     *
     */
    function typeAlnum($error_text = '只能是字母和数字')
    {
        if ($this->valid && !isset($this->or[$this->n])) {
            $this->valid = ctype_alnum($this->safe_data[$this->n]);
        }
        return $this->isError($error_text);
    }

    /**
     * @param string $error_text
     *
     * @return Validator
     * @see ctype_digit()
     *
     */
    function typeDigit($error_text = '只能是数字')
    {
        if ($this->valid && !isset($this->or[$this->n])) {
            $this->valid = ctype_digit($this->safe_data[$this->n]);
        }
        return $this->isError($error_text);
    }

    /**
     *  是否合法中国企业名称
     *  分3部分验证：
     *  1. 必须2汉字开头
     *  2. 可以有括号，特殊企业有“（中国）”，有括号情况下，括号中必须最少2位
     *  3. 后面必须多少位汉字结尾，可传参数，默认后面最少2位，（2+2）最少4位，为了兼容 如：支付宝，可以传参数$min_final = 1
     *
     * @param int $final_len //后面最少N位
     * @param string $error_text
     *
     * @return Validator
     */
    function companyName($final_len = 2, $error_text = '企业名称格式错误')
    {
        return $this->match(
            "/^[\x{4e00}-\x{9fa5}]{2,}((\(|（)?([\x{4e00}-\x{9fa5}]{2,})(\)|）)?)?[\x{4e00}-\x{9fa5}]{{$final_len},}$/u",
            $error_text
        );
    }

    /**
     * 是否为合法工商执照号码或统一社会信用代码
     * 统一社会信用代码编码规则
     * @link http://qyj.saic.gov.cn/wjfb/201509/t20150929_162430.html
     *
     * @param string $error_text
     *
     * @return Validator
     */
    function companyLicense($error_text = '营业执照号码格式错误')
    {
        if ($this->valid && !isset($this->or[$this->n])) {
            $this->valid = (bool)(
                //工商执照号码
                preg_match('/(^\d{15}$)/', $this->safe_data[$this->n]) ||
                //统一社会信用代码
                preg_match('/^[159yY]{1}([1239]{1})\d{6}([a-zA-Z0-9]{10})$/', $this->safe_data[$this->n])
            );
        }
        return $this->isError($error_text);
    }

    /**
     * 身份证合法名字验证
     *
     * @param string $error_text
     *
     * @return Validator
     */
    function idCardName($error_text = '姓名格式错误')
    {
        return $this->match("/^[\x{4e00}-\x{9fa5}]{2,}(?:[\·\•\.][\x{4e00}-\x{9fa5}]{1,})|^[\x{4e00}-\x{9fa5}]{2,}$/u",
            $error_text);
    }

    /**
     * 合法身份证号码验证
     *
     * @param string $error_text
     *
     * @return Validator
     */
    function idCard($error_text = '身份证格式错误')
    {
        $num = strtoupper($this->safe_data[$this->n]);
        $this->valid = preg_match('/^[\d]{17}[X\d]$/', $num) === 1;
        if ($this->valid) {
            $city_code = array(
                11 => true, 12 => true, 13 => true, 14 => true, 15 => true,
                21 => true, 22 => true, 23 => true,
                31 => true, 32 => true, 33 => true, 34 => true, 35 => true, 36 => true, 37 => true,
                41 => true, 42 => true, 43 => true, 44 => true, 45 => true, 46 => true,
                50 => true, 51 => true, 52 => true, 53 => true, 54 => true,
                61 => true, 62 => true, 63 => true, 64 => true, 65 => true,
                71 => true,
                81 => true, 82 => true,
                91 => true,
            );
            $this->valid = isset($city_code[$num[0] . $num[1]]);
            if ($this->valid) {
                //生成校验码
                $make_verify_bit = function ($id_card) {
                    if (strlen($id_card) != 17) {
                        return null;
                    }

                    $factor = array(7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2);
                    //校验码对应值
                    $verify_number_list = array('1', '0', 'X', '9', '8', '7', '6', '5', '4', '3', '2');
                    $checksum = 0;
                    for ($i = 0; $i < 17; $i++) {
                        $checksum += $id_card[$i] * $factor[$i];
                    }

                    $mod = $checksum % 11;
                    $verify_number = $verify_number_list[$mod];
                    return $verify_number;
                };

                //校验最后一位
                $this->valid = strcasecmp($num[17], $make_verify_bit(substr($num, 0, 17))) === 0;
                if ($this->valid) {
                    //校验出生日期
                    $birth_day = substr($num, 6, 8);
                    $d = new \DateTime($birth_day);
                    if ($d->format('Y') > date('Y') || $d->format('m') > 12 || $d->format('d') > 31) {
                        $this->valid = false;
                    }
                }
            }
        }
        return $this->isError($error_text);
    }

    /**
     * 执行一条正则
     *
     * @param $pattern
     * @param $error_text
     *
     * @return Validator
     */
    function match($pattern, $error_text)
    {
        if ($this->valid && !isset($this->or[$this->n]) && !$this->n_allow_undeclared_key[$this->n]) {
            $this->valid = (bool)preg_match($pattern, $this->safe_data[$this->n]);
        }
        return $this->isError($error_text);
    }

    /**
     * @param string $key
     * @param string $msg
     *
     * @return $this
     */
    function setError(string $key, string $msg)
    {
        $this->hasInValid = true;
        $this->group[$key] = false;
        $this->errors[$key] = $msg;
        return $this;
    }

    /**
     * @param string $set_msg
     *
     * @return $this
     */
    private function isError($set_msg = '')
    {
        if ($this->n_allow_undeclared_key[$this->n] && empty($this->safe_data[$this->n])) {
            $this->group[$this->n] = $this->valid = true;
            return $this;
        }
        if (!$this->valid) {
            $this->setError($this->n, $set_msg);
        }
        return $this;
    }

    /**
     * 转换html实体编码
     * @return $this
     */
    function convertHtmlTags()
    {
        if ($this->valid) {
            $this->safe_data[$this->n] = str_replace(
                array('<', '>', "'", '"'),
                array('&lt;', '&gt;', '&#039;', '&quot;'),
                $this->safe_data[$this->n]
            );
        }
        return $this;
    }

    /**
     * 过滤非法标签
     *
     * @param array $tags_name
     *
     * @return $this
     */
    function cleanIllegalTags(array $tags_name = array())
    {
        if ($this->valid && !empty($this->safe_data[$this->n])) {
            if (!empty($tags_name)) {
                $tags_name = array_map(
                    function ($tag) {
                        return str_replace('|', '', $tag);
                    },
                    $tags_name
                );
            }
            $tags_name = implode('|', ($this->illegal_tags + $tags_name));
            $str = str_replace(array('&lt;', '&gt;'), array('<', '>'), $this->safe_data[$this->n]);
            $this->safe_data[$this->n] = preg_replace("@<(\/|\s+|\/\s+)?({$tags_name})(.*?)>@is", '', $str);
        }
        return $this;
    }

    /**
     * 强制转换int绝对值
     * @return $this
     */
    function absInt($allowedZero = true)
    {
        $this->safe_data[$this->n] = (int)abs($this->safe_data[$this->n]);
        if ($this->valid && !$allowedZero) {
            $this->valid = $this->safe_data[$this->n] !== 0;
            return $this->isError('不允许为0');
        }
        return $this;
    }

    /**
     * 去除多余的换行符，制表符
     * @return $this
     */
    function trimContent()
    {
        if ($this->valid && !empty($this->safe_data[$this->n])) {
            $this->safe_data[$this->n] = str_replace(
                array("\r\n\t", "\r\n", "\n", "\r", "\t"),
                '',
                $this->safe_data[$this->n]
            );
        }
        return $this;
    }

    /**
     * 去除多余连贯空格
     * @return $this
     */
    function trimOverSpace()
    {
        if ($this->valid && !empty($this->safe_data[$this->n])) {
            $this->safe_data[$this->n] = preg_replace('/\s\s+/', ' ', $this->safe_data[$this->n]);
        }
        return $this;
    }

    /**
     * @param $error_text
     * @param string $default
     *
     * @return string
     */
    private function defaultErrorText($error_text, string $default)
    {
        if (null === $error_text) {
            return "[{$this->n}]{$default}";
        }
        return $error_text;
    }

    function __call($name, $arguments)
    {
        if (is_callable($name)) {
            $this->safe_data[$this->n] = call_user_func($name, $this->safe_data[$this->n]);
        }
        return $this;
    }
}