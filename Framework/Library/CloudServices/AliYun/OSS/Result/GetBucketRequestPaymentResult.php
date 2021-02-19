<?php

namespace EP\Library\CloudServices\AliYun\OSS\Result;


use EP\Library\CloudServices\AliYun\OSS\Model\RequestPaymentConfig;

/**
 * Class GetBucketRequestPaymentResult
 * @package EP\Library\CloudServices\AliYun\OSS\\Result
 */
class GetBucketRequestPaymentResult extends Result
{
    /**
     * @return mixed|string
     * @throws \Library\CloudServices\AliYun\OSS\Core\OssException
     */
    protected function parseDataFromResponse()
    {
        $content = $this->rawResponse->body;
        $config = new RequestPaymentConfig();
        $config->parseFromXml($content);
        return $config->getPayer();
    }
}
