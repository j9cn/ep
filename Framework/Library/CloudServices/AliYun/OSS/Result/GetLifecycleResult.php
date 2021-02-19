<?php

namespace EP\Library\CloudServices\AliYun\OSS\Result;


use EP\Library\CloudServices\AliYun\OSS\Model\LifecycleConfig;

/**
 * Class GetLifecycleResult
 * @package EP\Library\CloudServices\AliYun\OSS\\Result
 */
class GetLifecycleResult extends Result
{
    /**
     * @return LifecycleConfig|mixed
     * @throws \Library\CloudServices\AliYun\OSS\Core\OssException
     */
    protected function parseDataFromResponse()
    {
        $content = $this->rawResponse->body;
        $config = new LifecycleConfig();
        $config->parseFromXml($content);
        return $config;
    }

    /**
     * Check if the response is OK according to the http status.
     * [200-299]: OK, and the LifecycleConfig could be got; [404] The Life cycle config is not found.
     *
     * @return bool
     */
    protected function isResponseOk()
    {
        $status = $this->rawResponse->status;
        if ((int)(intval($status) / 100) == 2 || (int)(intval($status)) === 404) {
            return true;
        }
        return false;
    }
}
