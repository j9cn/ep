<?php
/**
 * Created by PhpStorm.
 * User: J.H
 * Date: 2018/3/13
 * Time: 8:28
 */

namespace EP\Library\CloudServices\BaiDu\PushMessages;


use EP\Library\Curl\HttpRequest;

/**
 * 百度云推送
 * Class PushMsg
 * @package EP\Library\CloudServices\BaiDu\PushMessages
 * @link http://push.baidu.com/doc/restapi/restapi
 */
class PushMsg
{
    const DEVICE_TYPE_ANDROID = 3;
    const DEVICE_TYPE_IOS = 4;

    const MSG_TYPE_MESSAGE = 0;
    const MSG_TYPE_NOTICE = 1;

    const METHOD_POST = 'POST';
    const METHOD_GET = 'GET';

    private $send_status = ['已发送', '未发送', '正在发送', '发送失败'];

    private $api_host = 'http://api.tuisong.baidu.com/rest/3.0/';
    private $api = '';

    private $api_key;
    private $secret_key;
    private $device_type;

    private $deploy_status = 2;

    private $android_msg = [
        'title' => 'hello',
        'description' => 'world',
        'notification_builder_id' => 0,
        'notification_basic_style' => 7,
        'open_type' => 0,
        'url' => '',
        'pkg_content' => '',
        'custom_content' => []
    ];

    private $ios_msg = [
        'aps' => [
            'alert' => 'hello world',
            'sound' => 'default',
            'badge' => 1
        ],
    ];

    private $send_time;

    private $params = [];

    private $error;

    function __construct(string $api_key, string $secret_key, bool $develop = false)
    {
        $this->secret_key = $secret_key;
        $this->params['apikey'] = $this->api_key = $api_key;
        $this->params['timestamp'] = time();
        if ($develop) {
            $this->deploy_status = 1;
        }
    }

    /**
     * 单播消息
     *
     * @param string $channel_id
     * @param int $msg_type
     * @param int $msg_expires
     *
     * @return bool
     */

    function pushByChannelId(string $channel_id, int $msg_type = self::MSG_TYPE_NOTICE, $msg_expires = 86400)
    {
        $this->api = 'push/single_device';
        $this->params['channel_id'] = $channel_id;
        $this->params['msg_type'] = $msg_type;
        $this->params['msg_expires'] = $msg_expires;
        if ($this->device_type === self::DEVICE_TYPE_IOS) {
            $this->params['deploy_status'] = $this->deploy_status;
        }
        $status = $this->push($response);
        if ($status) {
            return $response;
        }
        return $status;
    }

    /**
     * 推送消息到给定的一组设备(批量单播)
     * @link http://push.baidu.com/doc/restapi/restapi/#-post-rest-3-0-push-batch_device
     *
     * @param array $channel_ids 单次最多1W台设别
     * @param int $msg_type
     * @param int $msg_expires
     *
     * @return bool
     */
    function pushByChannelIdBatch(array $channel_ids, int $msg_type = self::MSG_TYPE_NOTICE, $msg_expires = 86400)
    {
        $this->api = 'push/batch_device';
        $this->params['channel_ids'] = json_encode($channel_ids);
        $this->params['msg_type'] = $msg_type;
        $this->params['msg_expires'] = $msg_expires;
        $status = $this->push($response);
        if ($status) {
            return $response;
        }
        return $status;
    }

    /**
     * @param int $msg_type
     * @param int $msg_expires
     * @param int $send_time
     *
     * @return bool
     */
    function pushAll(int $msg_type = self::MSG_TYPE_NOTICE, int $msg_expires = 86400, int $send_time = 0)
    {
        $this->api = 'push/all';
        $this->send_time = $send_time;
        $this->params['msg_type'] = $msg_type;
        $this->params['msg_expires'] = $msg_expires;
        $status = $this->push($response);
        if ($status) {
            return $response;
        }
        return $status;
    }

    /**
     * 按标签推送
     *
     * @param string $tag
     * @param int $msg_type
     * @param int $msg_expires
     * @param int $send_time
     *
     * @return bool
     */
    function pushByTags(string $tag, int $msg_type = self::MSG_TYPE_NOTICE, int $msg_expires = 86400, int $send_time = 0
    ) {
        $this->api = 'push/tags';
        $this->send_time = $send_time;
        $this->params['type'] = 1;
        $this->params['tag'] = $tag;
        $this->params['msg_type'] = $msg_type;
        $this->params['msg_expires'] = $msg_expires;
        $status = $this->push($response);
        if ($status) {
            return $response;
        }
        return $status;
    }

    /**
     * 查询消息的发送状态; 不支持pushByChannelId接口单一推送信息查询
     *
     * @param string $msg_id
     *
     * @return array
     */
    function queryMsgStatus(string $msg_id)
    {
        $this->api = 'report/query_msg_status';
        $this->params['msg_id'] = $msg_id;
        $status = $this->query($response);
        if ($status) {
            if (!empty($response['result']['result'])) {
                foreach ($response['result']['result'] as $index => $msg) {
                    $msg['send_status'] = $this->send_status[(int)$msg['status']];
                    $msg['send_date'] = date('Y-m-d H:i:s', $msg['send_time']);
                    $response['result']['result'][$index] = $msg;
                }
            }
            return $response;
        }
        return [];
    }

    /**
     * @param int $start
     * @param int $limit
     * @param string $tag
     *
     * @return array
     */
    function tagsQuery(int $start = 0, int $limit = 100, string $tag = ''): array
    {
        $this->api = 'app/query_tags';
        $this->params['start'] = $start;
        $this->params['limit'] = max(100, $limit);
        if ($tag) {
            $this->params['tag'] = $tag;
        }
        $status = $this->query($response);
        if ($status) {
            return $response;
        }
        return [];
    }

    /**
     * 增加标签
     *
     * @param string $tag_name
     *
     * @return bool
     */
    function tagCreate(string $tag_name)
    {
        $this->api = 'app/create_tag';
        return $this->tag($tag_name);
    }

    /**
     * 删除标签
     *
     * @param string $tag_name
     *
     * @return bool
     */
    function tagDel(string $tag_name)
    {
        $this->api = 'app/del_tag';
        return $this->tag($tag_name);
    }

    /**
     * 查询标签组设备数量
     *
     * @param string $tag_name
     *
     * @return int
     */
    function tagDevicesCount(string $tag_name)
    {
        $this->api = 'tag/device_num';
        $this->params['tag'] = $tag_name;
        $this->post($result);
        if (isset($result['result']['device_num'])) {
            return $result['result']['device_num'];
        }
        return 0;
    }

    /**
     * 标签管理
     *
     * @param string $tag_name
     *
     * @return bool
     */
    private function tag(string $tag_name)
    {
        $this->params['tag'] = $tag_name;
        $status = $this->post($response);
        if ($status) {
            return isset($response['result']['result']) && $response['result']['result'] == 0 ? true : false;
        }
        return false;
    }

    /**
     * 添加设备到标签组
     *
     * @param array $channel_ids
     * @param string $tag_name
     *
     * @return array
     */
    function devicesAddByTag(array $channel_ids, string $tag_name)
    {
        $this->api = 'tag/add_devices';
        return $this->devices($channel_ids, $tag_name);
    }

    /**
     * 将设备从标签组中移除
     *
     * @param array $channel_ids
     * @param string $tag_name
     *
     * @return array
     */
    function devicesDelByTag(array $channel_ids, string $tag_name)
    {
        $this->api = 'tag/del_devices';
        return $this->devices($channel_ids, $tag_name);
    }

    /**
     * 用户设别统管理
     *
     * @param array $channel_ids
     * @param string $tag_name
     *
     * @return array
     */
    private function devices(array $channel_ids, string $tag_name)
    {
        $this->params['tag'] = $tag_name;
        $this->params['channel_ids'] = json_encode($channel_ids);
        $params = $this->encodePostParams($this->params);
        $content = $this->request($params, self::METHOD_POST, $error, $status);
        $result = $this->parse($content, $status, $id);
        if (!is_string($result) && !empty($result['devices'])) {
            return $result['devices'];
        }
        return [];
    }

    /**
     * 查询定时消息的发送记录
     *
     * @param string $timer_id
     * @param int $start
     * @param int $limit
     *
     * @return array
     */
    function timerRecords(string $timer_id, int $start = 0, int $limit = 100)
    {
        $this->api = 'report/query_timer_records';
        $this->params['timer_id'] = $timer_id;
        $this->params['start'] = $start;
        $this->params['limit'] = max(100, $limit);

        $status = $this->query($response);
        if ($status) {
            if (!empty($response['result']['result'])) {
                foreach ($response['result']['result'] as $index => $msg) {
                    $msg['send_status'] = $this->send_status[(int)$msg['status']];
                    $msg['send_date'] = date('Y-m-d H:i:s', $msg['send_time']);
                    $response['result']['result'][$index] = $msg;
                }
            }
            return $response;
        }
        return [];
    }

    /**
     * @param int $start
     * @param int $limit
     * @param string $timer_id
     *
     * @return array
     */
    function timerList(int $start = 0, int $limit = 10, string $timer_id = '')
    {
        $this->api = 'timer/query_list';
        $this->params['start'] = $start;
        $this->params['limit'] = max(10, $limit);
        if ($timer_id) {
            $this->params['timer_id'] = $timer_id;
        }
        $status = $this->post($response);
        if ($status) {
            $type_msg = ['透传消息', '通知', '带格式的消息', '富媒体消息'];
            $range_msg = ['tag组播', '广播', '批量单播', '标签组合', '精准推送', 'LBS推送', '系统保留', '单播'];
            if (!empty($response['result']['result'])) {
                foreach ($response['result']['result'] as $index => $item) {
                    $item['send_date'] = date('Y-m-d H:i:s', $item['send_time']);
                    $item['msg_type_string'] = $type_msg[$item['msg_type']];
                    $item['msg_range_string'] = $range_msg[$item['range_type']];
                    $response['result']['result'][$index] = $item;
                }
            }
            return $response;
        }
        return [];
    }

    /**
     * 取消定时任务
     *
     * @param string $timer_id
     *
     * @return bool
     */
    function timerCancel(string $timer_id)
    {
        $this->api = 'timer/cancel';
        $this->params['timer_id'] = $timer_id;
        $status = $this->post($response);
        return $status;
    }

    function statistic_device()
    {
        $this->api = 'report/statistic_device';
        $status = $this->query($response);
        var_dump($response);
    }

    /**
     * @param $response
     *
     * @return bool
     */
    private function query(&$response)
    {
        $this->params['sign'] = $this->sign();
        $content = $this->request($this->params, self::METHOD_GET, $error, $status);
        $result = $this->parse($content, $status, $id);
        if (!is_string($result)) {
            $response = ['request_id' => $id, 'result' => $result];
            return true;
        }
        $response = $result;
        return false;
    }

    private function post(&$response)
    {
        $params = $this->encodePostParams($this->params);
        $content = $this->request($params, self::METHOD_POST, $error, $status);
        $result = $this->parse($content, $status, $id);
        if (!is_string($result)) {
            $response = ['request_id' => $id, 'result' => $result];
            return true;
        }
        $response = $result;
        return false;
    }

    /**
     * @param $response
     *
     * @return bool
     */
    private function push(&$response)
    {
        if ($this->device_type === self::DEVICE_TYPE_IOS) {
            $this->params['deploy_status'] = $this->deploy_status;
        }
        if ($this->send_time) {
            $this->params['send_time'] = time() + min(31535999, max(65, $this->send_time));
        }
        $this->params['msg'] = $this->getPushMsg();
        $this->params['device_type'] = $this->device_type;
        $params = $this->encodePostParams($this->params);
        $content = $this->request($params, self::METHOD_POST, $error, $status);
        $result = $this->parse($content, $status, $id);
        if (!is_string($result)) {
            $response = ['request_id' => $id, 'msg_id' => $result['msg_id']];
            if (isset($result['timer_id'])) {
                $response['timer_id'] = $result['timer_id'];
            }
            return true;
        }
        $response = $result;
        return false;
    }

    /**
     * @param $params
     * @param string $method
     * @param $error
     * @param $status
     *
     * @return false|mixed
     */
    private function request($params, string $method, &$error, &$status)
    {
        $curl = new HttpRequest();
        $curl->setHeader(
            [
                'User-Agent: BCCS_SDK/3.0',
                'Content-Type: application/x-www-form-urlencoded;charset=utf-8'
            ]
        );
        $content = $curl->request($this->api_host . $this->api, $params, $method, $error, $status);
        return $content;
    }

    private function encodePostParams(array $params)
    {
        $params['sign'] = $this->sign(self::METHOD_POST);
        $result = [];
        foreach ($params as $k => $v) {
            $result[] = urlencode($k) . '=' . urlencode($v);
        }
        return join('&', $result);
    }

    /**
     * @param $content
     * @param $status
     * @param int $request_id
     *
     * @return mixed|string
     */
    private function parse($content, $status, &$request_id)
    {
        $result = json_decode($content, true);
        if ($result !== null && array_key_exists('request_id', $result)) {
            $request_id = $result['request_id'];
            if (200 == $status && array_key_exists('response_params', $result)) {
                return $result['response_params'];
            }
            if (isset($result['error_code'])) {
                return $this->error = "[{$status}.{$result['error_code']}]-{$result['error_msg']}";
            }
            return $result;
        }
        $request_id = 0;
        return $this->error = "[{$status}]unknown error";
    }

    private function sign(string $method = self::METHOD_GET)
    {
        $params_str = $method . $this->api_host . $this->api;
        $params = $this->params;
        ksort($params);
        foreach ($params as $key => $val) {
            $params_str .= "{$key}=$val";
        }
        return md5(urlencode($params_str . $this->secret_key));
    }

    /**
     * 设置android通知信息格式
     *
     * @param string $title
     * @param string $desc
     * @param array $ex_params
     * @param string $url
     *
     * @return $this
     */
    function setAndroidMsg(string $title, string $desc, array $ex_params = [], string $url = '')
    {
        $this->device_type = self::DEVICE_TYPE_ANDROID;
        $this->android_msg['title'] = $title;
        $this->android_msg['description'] = $desc;
        if ('' !== $url) {
            if ('http' === substr(strtolower($url), 0, 4)) {
                $this->android_msg['open_type'] = 1;
                $this->android_msg['url'] = $url;
            } else {
                $this->android_msg['open_type'] = 2;
                $this->android_msg['pkg_content'] = $url;
            }
        }
        $this->android_msg['custom_content'] = $ex_params;
        return $this;
    }

    /**
     * 设置IOS信息格式
     *
     * @param string $alert
     * @param array $ex_params
     * @param int $badge
     * @param string $sound
     *
     * @return $this
     */
    function setIOSMsg(string $alert, array $ex_params = [], $badge = 1, $sound = 'default')
    {
        $this->device_type = self::DEVICE_TYPE_IOS;
        $this->ios_msg = [
                'aps' => [
                    'alert' => $alert,
                    'sound' => $sound,
                    'badge' => $badge
                ]
            ] + $ex_params;
        return $this;
    }

    private function getPushMsg()
    {
        switch ($this->device_type) {
            case self::DEVICE_TYPE_ANDROID:
                $msg = $this->android_msg;
                break;
            case self::DEVICE_TYPE_IOS:
                $msg = $this->ios_msg;
                break;
            default:
                $msg = $this->android_msg;
        }
        return json_encode($msg);
    }

    function getError()
    {
        return $this->error;
    }

}