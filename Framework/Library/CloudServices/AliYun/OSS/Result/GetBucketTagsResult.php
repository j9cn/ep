<?php

namespace EP\Library\CloudServices\AliYun\OSS\Result;


use EP\Library\CloudServices\AliYun\OSS\Model\TaggingConfig;

/**
 * Class GetBucketTagsResult
 * @package EP\Library\CloudServices\AliYun\OSS\\Result
 */
class GetBucketTagsResult extends Result
{
    /**
     * @return TaggingConfig|mixed
     * @throws \Library\CloudServices\AliYun\OSS\Core\OssException
     */
    protected function parseDataFromResponse()
    {
        $content = $this->rawResponse->body;
        $config = new TaggingConfig();
        $config->parseFromXml($content);
        return $config;
    }
}
