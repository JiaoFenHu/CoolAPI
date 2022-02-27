<?php

class encrypt extends base
{
    private $key_resource;

    function __construct(api $api)
    {
        $this->api = $api;
    }

    /**
     * 加密的2进制转16进制
     * @param $bin
     * @param bool $str_upper
     * @return string
     */
    public function hexEncode($bin, $str_upper = true)
    {
        if ($str_upper) {
            return strtoupper(bin2hex($bin));
        }
        return bin2hex($bin);
    }

    /**
     * 16进制转2进制秘闻
     * @param $hex
     * @param bool $str_lower
     * @return false|string
     */
    public function hexDecode($hex, $str_lower = true)
    {
        if ($str_lower) {
            return hex2bin(strtolower($hex));
        }
        return hex2bin($hex);
    }

    /**
     * 加密转base64编码
     * @param $encrypt
     * @param false $url_sale
     * @return string|string[]
     */
    public function encodeBase64($encrypt, $url_sale = false)
    {
        $base64 = base64_encode($encrypt);
        if ($url_sale) {
            $base64 = str_replace(['+', '/', '='], ['-', '_', ''], $base64);
        }
        return $base64;
    }

    /**
     * base64解码
     * @param $base64
     * @param false $url_sale
     * @return false|string
     */
    public function decodeBase64($base64, $url_sale = false)
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
    public function desEncrypt($string, $key)
    {
        return openssl_encrypt((string) $string, 'DES-EDE3', $key, OPENSSL_RAW_DATA);
    }

    /**
     * 3DES解密
     * @param string $string
     * @param string $key
     * @return string
     */
    public function desDecrypt($string, $key)
    {
        return openssl_decrypt((string) $string, 'DES-EDE3', $key, OPENSSL_RAW_DATA);
    }

    /**
     * AES 加密
     * @param string $string
     * @param string $key
     * @return string
     */
    public function aesEcbEncrypt($string, $key)
    {
        return openssl_encrypt($string, "AES-128-ECB", $key, OPENSSL_RAW_DATA);
    }

    /**
     * AES 解密
     * @param string $string
     * @param string $key
     * @return string
     */
    public function aesEcbDecrypt($string, $key)
    {
        return openssl_decrypt($string, "AES-128-ECB", $key, OPENSSL_RAW_DATA);
    }

    /**
     * rsaWithSha256签名
     * @param $toSign
     * @param $mode
     * @param string $pem_path
     * @return mixed
     * @throws Exception
     */
    function signRsaWithSha256($toSign, $mode, $pem_path = '')
    {
        $this->checkRsaCert($mode, $pem_path);
        openssl_sign($toSign, $signature, $this->key_resource, OPENSSL_ALGO_SHA256);
        openssl_free_key($this->key_resource);
        return base64_encode($signature);
    }

    /**
     * 验签
     * @param string $string
     * @param string $sign
     * @param string $mode
     * @param string $pem_path
     * @return integer
     * @throws Exception
     */
    function verifySignRsaWithSha256($string, $sign, $mode, $pem_path = '')
    {
        $this->checkRsaCert($mode, $pem_path);
        return openssl_verify($string, base64_decode($sign), $this->key_resource, OPENSSL_ALGO_SHA256);
    }

    /**
     * RSA加密（分段加密）
     * @param $str
     * @param string $mode
     * @param string $pem_path
     * @return string
     */
    public function encrypt($str, $mode = 'public', $pem_path = '')
    {
        $encrypted = array();
        $dataArray = str_split($str, 117);

        $this->checkRsaCert($mode, $pem_path);

        foreach ($dataArray as $subData) {
            if ($mode == 'public') {
                openssl_public_encrypt($subData, $subEncrypted, $this->key_resource);
            } else {
                openssl_private_encrypt($subData, $subEncrypted, $this->key_resource);
            }
            $encrypted[] = $subEncrypted;
            unset($subEncrypted);
        }

        $encrypted = implode('', $encrypted);
        return $encrypted;
    }

    /**
     * 解密（分段解密）
     * @param $encrypt
     * @param string $mode
     * @param string $pem_type
     * @param string $pem_path
     * @return string
     * @throws Exception
     */
    public function decrypt($encrypt, $mode = 'RSA2', $pem_type = 'private', $pem_path = '')
    {
        $decrypted = array();
        $split_len = $mode === 'RSA2' ? 256 : 128;
        $dataArray = str_split($encrypt, $split_len);

        $this->checkRsaCert($pem_type, $pem_path);

        foreach ($dataArray as $subData) {
            if ($pem_type == 'public') {
                openssl_public_decrypt($subData, $subDecrypted, $this->key_resource);
            } else {
                openssl_private_decrypt($subData, $subDecrypted, $this->key_resource);
            }
            $decrypted[] = $subDecrypted;
            unset($subDecrypted);
        }
        $decrypted = implode('', $decrypted);
        return $decrypted;
    }


    /**
     * 验证并加载证书资源
     * @param $mode
     * @param string $pem_path
     * @throws Exception
     */
    private function checkRsaCert($mode, $pem_path = '')
    {

        switch ($mode) {
            case 'public':
                $cert_path = $pem_path ?: RSA_PUB_PATH;
                break;
            case 'private':
                $cert_path = $pem_path ?: RSA_PRI_PATH;
                break;
            default:
                throw new Exception("加密选项错误!");
                break;
        }

        $this->loadingCert($cert_path, $mode);
        if (empty($this->key_resource)) {
            throw new Exception("密钥读取失败!");
        }
    }

    /**
     * RSA constructor.
     * @param string $cert_path
     * @param string $cert_type 证书类型: public  private
     * @return bool
     * @throws Exception
     */
    public function loadingCert($cert_path, $cert_type)
    {
        switch ($cert_type) {
            case "public":
                if ($this->checkKeyFile($cert_path)) {
                    $public_key = file_get_contents($cert_path);
                    $public_key = $this->formatterPublicKey($public_key);
                    $this->key_resource = openssl_pkey_get_public($public_key);
                }
                break;
            case "private":
                if ($this->checkKeyFile($cert_path)) {
                    $private_key = file_get_contents($cert_path);
                    $private_key = $this->formatterPrivateKey($private_key);
                    $this->key_resource = openssl_pkey_get_private($private_key);
                }
                break;
            default:
                throw new Exception("错误的证书类型!");
                break;
        }

        return true;
    }

    /**
     * 校验文件是否存在
     * @param $keyPath
     * @return bool
     * @throws Exception
     */
    public function checkKeyFile($keyPath)
    {
        if (!empty($keyPath)) {
            if (!file_exists($keyPath)) {
                throw new Exception("密钥文件不存在!");
            }
            return true;
        }
        return false;
    }

    /**
     * 格式化公钥
     * @param $publicKey string 公钥
     * @return string
     */
    public function formatterPublicKey($publicKey)
    {
        if (strpos($publicKey, '-----BEGIN PUBLIC KEY-----') !== false) {
            return $publicKey;
        }

        $str = chunk_split($publicKey, 64, PHP_EOL); //在每一个64字符后加一个\n
        $publicKey = "-----BEGIN PUBLIC KEY-----" . PHP_EOL . $str . "-----END PUBLIC KEY-----";

        return $publicKey;
    }

    /**
     * 格式化私钥
     * @param $privateKey string 私钥
     * @return string
     */
    public function formatterPrivateKey($privateKey)
    {
        if (strpos($privateKey, '-----BEGIN RSA PRIVATE KEY-----') !== false) {
            return $privateKey;
        }

        $str = chunk_split($privateKey, 64, PHP_EOL); //在每一个64字符后加一个\n
        $privateKey = "-----BEGIN RSA PRIVATE KEY-----" . PHP_EOL . $str . "-----END RSA PRIVATE KEY-----";
        return $privateKey;
    }
}
