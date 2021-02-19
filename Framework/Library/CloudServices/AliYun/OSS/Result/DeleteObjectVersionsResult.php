<?php

namespace EP\Library\CloudServices\AliYun\OSS\Result;

use EP\Library\CloudServices\AliYun\OSS\Core\OssUtil;
use EP\Library\CloudServices\AliYun\OSS\Model\DeletedObjectInfo;

/**
 * Class DeleteObjectVersionsResult
 * @package Library\CloudServices\AliYun\OSS\Result
 */
class DeleteObjectVersionsResult extends Result
{
    /**
     * @return array|mixed
     * @throws \Library\CloudServices\AliYun\OSS\Core\OssException
     */
    protected function parseDataFromResponse()
    {
        $xml = simplexml_load_string($this->rawResponse->body); 
        $encodingType = isset($xml->EncodingType) ? strval($xml->EncodingType) : "";
        return $this->parseDeletedList($xml, $encodingType);
    }

    /**
     * @param $xml
     * @param $encodingType
     * @return array
     * @throws \Library\CloudServices\AliYun\OSS\Core\OssException
     */
    private function parseDeletedList($xml, $encodingType)
    {
        $retList = array();
        if (isset($xml->Deleted)) {
            foreach ($xml->Deleted as $content) {
                $key = isset($content->Key) ? strval($content->Key) : "";
                $key = OssUtil::decodeKey($key, $encodingType);
                $versionId = isset($content->VersionId) ? strval($content->VersionId) : "";
                $deleteMarker = isset($content->DeleteMarker) ? strval($content->DeleteMarker) : "";
                $deleteMarkerVersionId = isset($content->DeleteMarkerVersionId) ? strval($content->DeleteMarkerVersionId) : "";
                $retList[] = new DeletedObjectInfo($key, $versionId, $deleteMarker, $deleteMarkerVersionId);
            }
        }
        return $retList;
    }
}
