<?php
declare(strict_types = 1);

namespace repository\encrypt;

use repository\exception\CoolException;

class Encrypt
{
    private $keyResource;

    /**
     * 加密的2进制转16进制
     * @param string $bin
     * @param bool $str_upper
     * @return string
     */
    public function hexEncode(string $bin, bool $str_upper = true) : string
    {
        if ($str_upper) {
            return strtoupper(bin2hex($bin));
        }
        return bin2hex($bin);
    }

    /**
     * 16进制转2进制秘闻
     * @param string $hex
     * @param bool $str_lower
     * @return false|string
     */
    public function hexDecode(string $hex, $str_lower = true) : string
    {
        if ($str_lower) {
            return hex2bin(strtolower($hex));
        }
        return hex2bin($hex);
    }

    /**
     * 加密转base64编码
     * @param string $encrypt
     * @param bool $url_sale
     * @return string
     */
    public function encodeBase64(string $encrypt, bool $url_sale = false) : string
    {
        $base64 = base64_encode($encrypt);
        if ($url_sale) {
            $base64 = str_replace(['+', '/', '='], ['-', '_', ''], $base64);
        }
        return $base64;
    }

    /**
     * base64解码
     * @param string $base64
     * @param bool $url_sale
     * @return false|string
     */
    public function decodeBase64(string $base64, bool $url_sale = false)
    {
        if ($url_sale) {
            $base64 = str_replace(['-', '_'], ['+', '/'], $base64);
            $remainder = strlen($base64) % 4;
            if ($remainder > 0) {
                $base64 .= substr('====', $remainder);
            }
        }
        return base64_decode($base64);
    }

    /**
     * 3DES加密
     * @param string $string
     * @param string $key
     * @return string
     */
    public function desEncrypt(string $string, string $key)
    {
        return openssl_encrypt($string, 'DES-EDE3', $key, OPENSSL_RAW_DATA);
    }

    /**
     * 3DES解密
     * @param string $string
     * @param string $key
     * @return string
     */
    public function desDecrypt(string $string, string $key)
    {
        return openssl_decrypt($string, 'DES-EDE3', $key, OPENSSL_RAW_DATA);
    }

    /**
     * AES 加密
     * @param string $string
     * @param string $key
     * @return string
     */
    public function aesEcbEncrypt(string $string, string $key)
    {
        return openssl_encrypt($string, "AES-128-ECB", $key, OPENSSL_RAW_DATA);
    }

    /**
     * AES 解密
     * @param string $string
     * @param string $key
     * @return string
     */
    public function aesEcbDecrypt(string $string, string $key)
    {
        return openssl_decrypt($string, "AES-128-ECB", $key, OPENSSL_RAW_DATA);
    }

    /**
     * rsaWithSha256签名
     * @param string $toSign
     * @param string $mode
     * @param string $pem_path
     * @return string
     * @throws CoolException
     */
    function signRsaWithSha256(string $toSign, string $mode, string $pem_path = '') : string
    {
        $this->checkRsaCert($mode, $pem_path);
        openssl_sign($toSign, $signature, $this->keyResource, OPENSSL_ALGO_SHA256);
        openssl_free_key($this->keyResource);
        return base64_encode($signature);
    }

    /**
     * 验签
     * @param string $string
     * @param string $sign
     * @param string $mode
     * @param string $pem_path
     * @return int
     * @throws CoolException
     */
    function verifySignRsaWithSha256(string $string, string $sign, string $mode, string $pem_path = '') : int
    {
        $this->checkRsaCert($mode, $pem_path);
        return openssl_verify($string, base64_decode($sign), $this->keyResource, OPENSSL_ALGO_SHA256);
    }

    /**
     * RSA加密（分段加密）支持RSA1 / RSA2
     * @param string $str
     * @param string $mode
     * @param string $pem_path
     * @return string
     * @throws CoolException
     */
    public function encrypt(string $str, string $mode = 'public', string $pem_path = '') : string
    {
        $encrypted = array();
        $dataArray = str_split($str, 117);

        $this->checkRsaCert($mode, $pem_path);

        foreach ($dataArray as $subData) {
            if ($mode == 'public') {
                openssl_public_encrypt($subData, $subEncrypted, $this->keyResource);
            } else {
                openssl_private_encrypt($subData, $subEncrypted, $this->keyResource);
            }
            $encrypted[] = $subEncrypted;
            unset($subEncrypted);
        }

        $encrypted = implode('', $encrypted);
        return $encrypted;
    }

    /**
     * 解密（分段解密）支持RSA1 / RSA2
     * @param string $encrypt
     * @param string $mode
     * @param string $pem_type
     * @param string $pem_path
     * @return string
     * @throws CoolException
     */
    public function decrypt(
        string $encrypt,
        string $mode = 'RSA2',
        string $pem_type = 'private',
        string $pem_path = ''
    ) : string
    {
        $decrypted = array();
        $split_len = $mode === 'RSA2' ? 256 : 128;
        $dataArray = str_split($encrypt, $split_len);

        $this->checkRsaCert($pem_type, $pem_path);

        foreach ($dataArray as $subData) {
            if ($pem_type == 'public') {
                openssl_public_decrypt($subData, $subDecrypted, $this->keyResource);
            } else {
                openssl_private_decrypt($subData, $subDecrypted, $this->keyResource);
            }
            $decrypted[] = $subDecrypted;
            unset($subDecrypted);
        }
        $decrypted = implode('', $decrypted);
        return $decrypted;
    }


    /**
     * 验证并加载证书资源
     * @param string $mode
     * @param string $pem_path
     * @throws CoolException
     */
    private function checkRsaCert(string $mode, string $pem_path = '') : void
    {

        switch ($mode) {
            case 'private':
            case 'public':
                $cert_path = $pem_path;
                break;
            default:
                throw new CoolException("不支持的加密选项！");
                break;
        }

        $this->loadingCert($cert_path, $mode);
        if (empty($this->keyResource)) {
            throw new CoolException("密钥读取失败!");
        }
    }

    /**
     * RSA constructor.
     * @param string $cert_path
     * @param string $cert_type 证书类型: public  private
     * @return bool
     * @throws CoolException
     */
    public function loadingCert(string $cert_path, string $cert_type) : bool
    {
        switch ($cert_type) {
            case "public":
                if ($this->checkKeyFile($cert_path)) {
                    $public_key = $this->formatterPublicKey(file_get_contents($cert_path));
                    $this->keyResource = openssl_pkey_get_public($public_key);
                }
                break;
            case "private":
                if ($this->checkKeyFile($cert_path)) {
                    $private_key = $this->formatterPrivateKey(file_get_contents($cert_path));
                    $this->keyResource = openssl_pkey_get_private($private_key);
                }
                break;
            default:
                throw new CoolException("错误的证书类型!");
                break;
        }

        return true;
    }

    /**
     * 校验文件是否存在
     * @param $keyPath
     * @return bool
     * @throws CoolException
     */
    public function checkKeyFile($keyPath) : bool
    {
        if (!empty($keyPath)) {
            if (!file_exists($keyPath)) {
                throw new CoolException("密钥文件不存在!");
            }
            return true;
        }
        return false;
    }

    /**
     * 格式化公钥
     * @param string $publicKey 公钥
     * @return string
     */
    public function formatterPublicKey(string $publicKey) : string
    {
        if (strpos($publicKey, '-----BEGIN PUBLIC KEY-----') !== false) {
            return $publicKey;
        }

        $str = chunk_split($publicKey, 64, PHP_EOL); //在每一个64字符后加一个\n
        $publicKey = "-----BEGIN PUBLIC KEY-----" . PHP_EOL;
        $publicKey .= $str;
        $publicKey .= "-----END PUBLIC KEY-----";
        return $publicKey;
    }

    /**
     * 格式化私钥
     * @param string $privateKey 私钥
     * @return string
     */
    public function formatterPrivateKey(string $privateKey) : string
    {
        if (strpos($privateKey, '-----BEGIN RSA PRIVATE KEY-----') !== false) {
            return $privateKey;
        }

        $str = chunk_split($privateKey, 64, PHP_EOL); //在每一个64字符后加一个\n
        $privateKey = "-----BEGIN RSA PRIVATE KEY-----" . PHP_EOL;
        $privateKey .= $str;
        $privateKey .= "-----END RSA PRIVATE KEY-----";
        return $privateKey;
    }
}
