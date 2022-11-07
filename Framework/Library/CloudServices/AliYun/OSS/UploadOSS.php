<?php
/**
 * Author: OXIVO
 * QQ: 2502212233
 * Date: 2021/1/7
 * Time: 下午 7:02
 */

namespace EP\Library\CloudServices\AliYun\OSS;


use EP\Core\Helper;
use EP\Exception\ELog;
use EP\Library\CloudServices\AliYun\OSS\Core\OssException;

class UploadOSS
{
    /**
     * @var OssClient
     */
    private static $oss;
    private static $bucket;


    /**
     * @param array $config
     */
    static function init(array $config)
    {
        if (!empty($config['bucket'])) {
            self::$bucket = $config['bucket'];
        }
        try {
            self::$oss = new OssClient(
                $config['accessKeyId'],
                $config['accessKeySecret'],
                $config['endpoint'],
                $config['isCName']
            );
        } catch (OssException $exception) {
            ELog::error($exception->getMessage());
        }
    }

    static function setBucket(string $bucket)
    {
        self::$bucket = $bucket;
    }

    static function OssClient()
    {
        if (!self::$oss) {
            ELog::ERROR('未初始化OSS,需先使用 UploadOSS::init()');
        }
        return self::$oss;
    }

    /**
     * @param string $save_path
     * @param string file|file_key $file
     * @param null $file_name
     * @param bool $ori_name
     * @return array
     * @throws Http\RequestCore_Exception
     */
    static function upFile(string $save_path, string $file, $file_name = null, $ori_name = false)
    {
        if (!self::$oss) {
            ELog::ERROR('未初始化OSS,需先使用 UploadOSS::init()');
        }
        $name = Helper::timeCodeId();
        if (is_file($file)) {
            $ext = pathinfo($file, PATHINFO_EXTENSION);
            if (null === $file_name) {
                if ($ori_name) {
                    $name = pathinfo($file, PATHINFO_FILENAME);
                }
            }
        } elseif (!empty($_FILES[$file]) && $_FILES[$file]['error'] === 0) {
            $up_file = $_FILES[$file];
            $file = $up_file['tmp_name'];
            $ext = pathinfo($up_file['name'], PATHINFO_EXTENSION);
            if (null === $file_name) {
                if ($ori_name) {
                    $name = pathinfo($up_file['name'], PATHINFO_FILENAME);
                }
            }
        } else {
            return self::result(-1, '缺少上传文件');
        }
        $saved = "{$save_path}/{$name}.{$ext}";
        try {
            $res = self::$oss->uploadFile(self::$bucket, $saved, $file);
        } catch (OssException $exception) {
            return self::result(0, $exception->getMessage());
        }
        return self::result(1, '上传成功', $res['oss-request-url']);
    }

    private static function result($code, $msg, $data = [])
    {
        return ['status' => $code, 'message' => $msg, 'data' => $data];
    }
}