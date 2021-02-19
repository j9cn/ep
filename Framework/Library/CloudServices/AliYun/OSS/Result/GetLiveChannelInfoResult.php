<?php

namespace EP\Library\CloudServices\AliYun\OSS\Result;

use EP\Library\CloudServices\AliYun\OSS\Model\GetLiveChannelInfo;

class GetLiveChannelInfoResult extends Result
{
    /**
     * @return GetLiveChannelInfo|mixed
     */
    protected function parseDataFromResponse()
    {
        $content = $this->rawResponse->body;
        $channelList = new GetLiveChannelInfo();
        $channelList->parseFromXml($content);
        return $channelList;
    }
}
