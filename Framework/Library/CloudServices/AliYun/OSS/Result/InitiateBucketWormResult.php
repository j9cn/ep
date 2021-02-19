<?php

namespace EP\Library\CloudServices\AliYun\OSS\Result;
use EP\Library\CloudServices\AliYun\OSS\Core\OssException;

/**
 * Class InitiateBucketWormResult
 * @package EP\Library\CloudServices\AliYun\OSS\\Result
 */
class InitiateBucketWormResult extends Result
{
    /**
     * Get the value of worm-id from response headers
     *
     * @return int
     * @throws OssException
     */
    protected function parseDataFromResponse()
    {
        $header = $this->rawResponse->header;
        if (isset($header["x-oss-worm-id"])) {
            return strval($header["x-oss-worm-id"]);
        }
        throw new OssException("cannot get worm-id");
    }
}