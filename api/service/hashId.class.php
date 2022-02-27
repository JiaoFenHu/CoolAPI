<?php

use Hashids\Hashids;

class hashId extends base
{
    private static $salt = '1one_';

    function __construct(api $api)
    {
        $this->api = $api;
    }

    /**
     * 英文字母字符串
     * @param int $_mode
     * @return string
     */
    private function getEnglishAlphabet($_mode = 1)
    {
        $string = 'abcdefghijklmnopqrstuvwxyz';
        switch ($_mode) {
            case 1:
                return $string;
            case 2:
                return strtoupper($string);
            default:
                return $string . strtoupper($string);
        }
    }

    /**
     * 获得混合字符串
     * @return string
     */
    private function getMixtureAlphabet()
    {
        return $this->getEnglishAlphabet(3) . '0123456789';
    }

    /**
     * 加密转换appid
     * @param $id
     * @param int $len
     * @param int $mode
     * @return false|mixed|string
     * @throws Exception
     */
    public function getHashId($id, $len = 8, $mode = 1)
    {
        switch ($mode) {
            case 1:
            case 2:
            case 3:
                $alphabet = $this->getEnglishAlphabet($mode);
                break;
            default:
                $alphabet = $this->getMixtureAlphabet();
                break;
        }

        $hashids = new Hashids(self::$salt, $len, $alphabet);
        return $hashids->encode($id);
    }
}