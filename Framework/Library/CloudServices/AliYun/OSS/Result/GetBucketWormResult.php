<?php

namespace EP\Library\CloudServices\AliYun\OSS\Result;


use EP\Library\CloudServices\AliYun\OSS\Model\WormConfig;

/**
 * Class GetBucketWormResult
 * @package EP\Library\CloudServices\AliYun\OSS\\Result
 */
class GetBucketWormResult extends Result
{
    /**
     * @return WormConfig|mixed
     * @throws \Library\CloudServices\AliYun\OSS\Core\OssException
     */
    protected function parseDataFromResponse()
    {
        $content = $this->rawResponse->body;
        $config = new WormConfig();
        $config->parseFromXml($content);
        return $config;
    }
}