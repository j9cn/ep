<?php

namespace EP\Library\CloudServices\AliYun\OSS\Result;

use EP\Library\CloudServices\AliYun\OSS\Model\GetLiveChannelStatus;

class GetLiveChannelStatusResult extends Result
{
    /**
     * @return GetLiveChannelStatus|mixed
     */
    protected function parseDataFromResponse()
    {
        $content = $this->rawResponse->body;
        $channelList = new GetLiveChannelStatus();
        $channelList->parseFromXml($content);
        return $channelList;
    }
}
