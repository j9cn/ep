<?php

namespace EP\Library\CloudServices\AliYun\OSS\Result;
use EP\Library\CloudServices\AliYun\OSS\Core\OssException;

/**
 * Class UploadPartResult
 * @package Library\CloudServices\AliYun\OSS\Result
 */
class UploadPartResult extends Result
{
    /**
     * 结果中part的ETag
     *
     * @return string
     * @throws OssException
     */
    protected function parseDataFromResponse()
    {
        $header = $this->rawResponse->header;
        if (isset($header["etag"])) {
            return $header["etag"];
        }
        throw new OssException("cannot get ETag");

    }
}