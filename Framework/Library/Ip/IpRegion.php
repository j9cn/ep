<?php
/**
 * Created by PhpStorm.
 * User: JH
 * Date: 2017/12/29
 * Time: 14:45
 */
declare(strict_types=1);

namespace EP\Library\Ip;


use EP\Exception\EE;

class IpRegion
{

    const INDEX_BLOCK_LENGTH = 12;
    const TOTAL_HEADER_LENGTH = 8192;

    /**
     * super block index info
     */
    private $firstIndexPtr = 0;
    private $lastIndexPtr = 0;
    private $totalBlocks = 0;

    /**
     * for memory mode only
     *  the original db binary string
     */
    private $dbBinStr = null;
    private $db_file_path;

    /**
     * IpRegion constructor.
     *
     * @param null $db_file_path
     *
     * @throws EE
     */
    function __construct($db_file_path = null)
    {
        if (null !== $db_file_path) {
            $this->db_file_path = $db_file_path;
        } else {
            $this->db_file_path = dirname(__FILE__) . '/data/ip2region.db';
        }
        $this->openDb();
    }

    /**
     * @throws EE
     */
    private function openDb()
    {
        if ($this->dbBinStr == null) {
            $this->dbBinStr = file_get_contents($this->db_file_path);
            if (false === $this->dbBinStr) {
                throw new EE(EE::ERROR, "Fail to open the IPdb file {$this->dbFile}");
            }

            $this->firstIndexPtr = self::getLong($this->dbBinStr, 0);
            $this->lastIndexPtr = self::getLong($this->dbBinStr, 4);
            $this->totalBlocks = ($this->lastIndexPtr - $this->firstIndexPtr) / self::INDEX_BLOCK_LENGTH + 1;
        }
    }

    /**
     * @param $ip
     *
     * @return int
     */
    function searchCityID($ip)
    {
        $data = $this->search($ip);
        return $data['city_id'];
    }

    /**
     * @param $ip
     *
     * @return string
     */
    function searchRegion($ip)
    {
        $data = $this->search($ip);
        return $data['region'];
    }

    /**
     * @param $ip
     *
     * @return array
     */
    public function search($ip)
    {
        $ip = trim((string)$ip);
        if (!ctype_digit($ip)) {
            $ip = self::safeIp2long($ip);
        }
        $l = 0;
        $h = $this->totalBlocks;
        $dataPtr = 0;
        while ($l <= $h) {
            $m = (($l + $h) >> 1);
            $p = $this->firstIndexPtr + $m * self::INDEX_BLOCK_LENGTH;
            $sip = self::getLong($this->dbBinStr, $p);
            if ($ip < $sip) {
                $h = $m - 1;
            } else {
                $eip = self::getLong($this->dbBinStr, $p + 4);
                if ($ip > $eip) {
                    $l = $m + 1;
                } else {
                    $dataPtr = self::getLong($this->dbBinStr, $p + 8);
                    break;
                }
            }
        }

        if ($dataPtr == 0) {
            return array('city_id' => 0, 'region' => '');
        }

        $dataLen = (($dataPtr >> 24) & 0xFF);
        $dataPtr = ($dataPtr & 0x00FFFFFF);

        return array(
            'city_id' => self::getLong($this->dbBinStr, $dataPtr),
            'region' => str_replace(['0|', '|0'], '', substr($this->dbBinStr, $dataPtr + 4, $dataLen - 4))
        );
    }


    /**
     * @param $ip
     *
     * @return int|string
     */
    private static function safeIp2long($ip)
    {
        $ip = ip2long($ip);
        if ($ip < 0 && PHP_INT_SIZE == 4) {
            $ip = sprintf("%u", $ip);
        }

        return $ip;
    }


    /**
     * read a long from a byte buffer
     *
     * @param $b
     * @param $offset
     *
     * @return int|string
     */
    private static function getLong($b, $offset)
    {
        $val = (
            (ord($b[$offset++])) |
            (ord($b[$offset++]) << 8) |
            (ord($b[$offset++]) << 16) |
            (ord($b[$offset]) << 24)
        );

        // convert signed int to unsigned int if on 32 bit operating system
        if ($val < 0 && PHP_INT_SIZE == 4) {
            $val = sprintf("%u", $val);
        }

        return $val;
    }

}