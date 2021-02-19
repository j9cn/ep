<?php

namespace EP\Library\CloudServices\AliYun\OSS\Result;

use EP\Library\CloudServices\AliYun\OSS\Core\OssUtil;
use EP\Library\CloudServices\AliYun\OSS\Model\ObjectInfo;
use EP\Library\CloudServices\AliYun\OSS\Model\ObjectListInfo;
use EP\Library\CloudServices\AliYun\OSS\Model\PrefixInfo;

/**
 * Class ListObjectsResult
 * @package EP\Library\CloudServices\AliYun\OSS\Result
 */
class ListObjectsResult extends Result
{
    /**
     * @return ObjectListInfo|mixed
     * @throws \Library\CloudServices\AliYun\OSS\Core\OssException
     */
    protected function parseDataFromResponse()
    {
        $xml = new \SimpleXMLElement($this->rawResponse->body);
        $encodingType = isset($xml->EncodingType) ? strval($xml->EncodingType) : "";
        $objectList = $this->parseObjectList($xml, $encodingType);
        $prefixList = $this->parsePrefixList($xml, $encodingType);
        $bucketName = isset($xml->Name) ? strval($xml->Name) : "";
        $prefix = isset($xml->Prefix) ? strval($xml->Prefix) : "";
        $prefix = OssUtil::decodeKey($prefix, $encodingType);
        $marker = isset($xml->Marker) ? strval($xml->Marker) : "";
        $marker = OssUtil::decodeKey($marker, $encodingType);
        $maxKeys = isset($xml->MaxKeys) ? intval($xml->MaxKeys) : 0;
        $delimiter = isset($xml->Delimiter) ? strval($xml->Delimiter) : "";
        $delimiter = OssUtil::decodeKey($delimiter, $encodingType);
        $isTruncated = isset($xml->IsTruncated) ? strval($xml->IsTruncated) : "";
        $nextMarker = isset($xml->NextMarker) ? strval($xml->NextMarker) : "";
        $nextMarker = OssUtil::decodeKey($nextMarker, $encodingType);
        return new ObjectListInfo($bucketName, $prefix, $marker, $nextMarker, $maxKeys, $delimiter, $isTruncated, $objectList, $prefixList);
    }

    /**
     * @param $xml
     * @param $encodingType
     * @return array
     * @throws \Library\CloudServices\AliYun\OSS\Core\OssException
     */
    private function parseObjectList($xml, $encodingType)
    {
        $retList = array();
        if (isset($xml->Contents)) {
            foreach ($xml->Contents as $content) {
                $key = isset($content->Key) ? strval($content->Key) : "";
                $key = OssUtil::decodeKey($key, $encodingType);
                $lastModified = isset($content->LastModified) ? strval($content->LastModified) : "";
                $eTag = isset($content->ETag) ? strval($content->ETag) : "";
                $type = isset($content->Type) ? strval($content->Type) : "";
                $size = isset($content->Size) ? intval($content->Size) : 0;
                $storageClass = isset($content->StorageClass) ? strval($content->StorageClass) : "";
                $retList[] = new ObjectInfo($key, $lastModified, $eTag, $type, $size, $storageClass);
            }
        }
        return $retList;
    }

    /**
     * @param $xml
     * @param $encodingType
     * @return array
     * @throws \Library\CloudServices\AliYun\OSS\Core\OssException
     */
    private function parsePrefixList($xml, $encodingType)
    {
        $retList = array();
        if (isset($xml->CommonPrefixes)) {
            foreach ($xml->CommonPrefixes as $commonPrefix) {
                $prefix = isset($commonPrefix->Prefix) ? strval($commonPrefix->Prefix) : "";
                $prefix = OssUtil::decodeKey($prefix, $encodingType);
                $retList[] = new PrefixInfo($prefix);
            }
        }
        return $retList;
    }
}