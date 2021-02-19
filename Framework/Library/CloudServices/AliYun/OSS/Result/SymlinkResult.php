<?php

namespace EP\Library\CloudServices\AliYun\OSS\Result;
use EP\Library\CloudServices\AliYun\OSS\Core\OssException;
use EP\Library\CloudServices\AliYun\OSS\OssClient;

/**
 *
 * @package Library\CloudServices\AliYun\OSS\Result
 */
class SymlinkResult extends Result
{
    /**
     * @return array|mixed
     */
    protected function parseDataFromResponse()
    {
        $this->rawResponse->header[OssClient::OSS_SYMLINK_TARGET] = rawurldecode($this->rawResponse->header[OssClient::OSS_SYMLINK_TARGET]);
        return $this->rawResponse->header;
    }
}

