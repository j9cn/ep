<?php

namespace EP\Library\CloudServices\AliYun\OSS\Result;


use EP\Library\CloudServices\AliYun\OSS\Model\ServerSideEncryptionConfig;

/**
 * Class GetBucketEncryptionResult
 * @package EP\Library\CloudServices\AliYun\OSS\\Result
 */
class GetBucketEncryptionResult extends Result
{
    /**
     * @return ServerSideEncryptionConfig|mixed
     * @throws \Library\CloudServices\AliYun\OSS\Core\OssException
     */
    protected function parseDataFromResponse()
    {
        $content = $this->rawResponse->body;
        $config = new ServerSideEncryptionConfig();
        $config->parseFromXml($content);
        return $config;
    }
}
