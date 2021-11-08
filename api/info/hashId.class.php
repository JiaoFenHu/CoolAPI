<?php

require_once(COMPOSER_DIR . 'autoload.php');

use Hashids\Hashids;

class hashId
{
    public $db;
    public $api;

    private static $salt = '1one_' . PRO_KEY;

    function __construct($api)
    {
        global $db;
        $this->db = $db;
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
        $string = "abcdefghijklmnopqrstuvwxyz";
        $string .= strtoupper($string);
        $string .= '0123456789';
        return $string;
    }

    /**
     * 加密转换appid
     * @param $id
     * @param int $len
     * @param int $mode
     * @param int $param
     * @return false|mixed|string
     * @throws Exception
     */
    public function getHashIdString($id, $len = 23, $mode = 1, $param = 1)
    {
        switch ($mode) {
            case 1:return generateOrderNo('');break;
            case 2:$alphabet = $this->getEnglishAlphabet($param);break;
            default:$alphabet = $this->getMixtureAlphabet();break;
        }

        $hashids = new Hashids(self::$salt, $len, $alphabet);
        return $hashids->encode($id);
    }
}