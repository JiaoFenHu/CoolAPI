<?php
declare(strict_types=1);

namespace app\admin\service;

use Hashids\Hashids;
use repository\BaseService;

class HashIdService extends BaseService
{
    private static string $salt = '1one_';

    /**
     * 英文字母字符串
     * @param int $_mode
     * @return string
     */
    private function getEnglishAlphabet(int $_mode = 1): string
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
    private function getMixtureAlphabet(): string
    {
        return $this->getEnglishAlphabet(3) . '0123456789';
    }

    /**
     * 加密转换appid
     * @param $id
     * @param int $len
     * @param int $mode
     * @return string
     */
    public function getHashId($id, int $len = 8, int $mode = 1): string
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