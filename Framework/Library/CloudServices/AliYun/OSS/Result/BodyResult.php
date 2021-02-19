<?php

namespace EP\Library\CloudServices\AliYun\OSS\Result;

/**
 * Class BodyResult
 * @package EP\Library\CloudServices\AliYun\OSS\\Result
 */
class BodyResult extends Result
{
    /**
     * @return string
     */
    protected function parseDataFromResponse()
    {
        return empty($this->rawResponse->body) ? "" : $this->rawResponse->body;
    }
}