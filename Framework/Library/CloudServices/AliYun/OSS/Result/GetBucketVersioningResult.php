<?php

namespace EP\Library\CloudServices\AliYun\OSS\Result;


use EP\Library\CloudServices\AliYun\OSS\Model\VersioningConfig;

/**
 * Class GetBucketVersioningResult
 * @package EP\Library\CloudServices\AliYun\OSS\\Result
 */
class GetBucketVersioningResult extends Result
{
    /**
     * @return mixed|string
     * @throws \Library\CloudServices\AliYun\OSS\Core\OssException
     */
    protected function parseDataFromResponse()
    {
        $content = $this->rawResponse->body;
        $config = new VersioningConfig();
        $config->parseFromXml($content);
        return $config->getStatus();
    }
}
