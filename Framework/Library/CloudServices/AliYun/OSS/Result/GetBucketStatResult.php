<?php

namespace EP\Library\CloudServices\AliYun\OSS\Result;


use EP\Library\CloudServices\AliYun\OSS\Model\BucketStat;

/**
 * Class GetRefererResult
 * @package EP\Library\CloudServices\AliYun\OSS\\Result
 */
class GetBucketStatResult extends Result
{
    /**
     * Parse bucket stat data
     *
     * @return BucketStat
     */
    protected function parseDataFromResponse()
    {
        $content = $this->rawResponse->body;
        $stat = new BucketStat();
        $stat->parseFromXml($content);
        return $stat;
    }
}