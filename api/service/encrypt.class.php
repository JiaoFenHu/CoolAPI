<?php

class encrypt
{

    public $db;
    public $api;

    private $url;
    private $appId;                 // 分配的AppId
    private $appSecret;             // 分配的AppSecret
    private $token;                 // 分配的token
    private $ver = '1.0.0';         // 版本号


    public $key_resource;

    function __construct($api)
    {
        $this->api = $api;
    }

    /**
     * 3DES加密转16进制
     * @param $string
     * @param $key
     * @return string
     */
    public function DesToHexEncrypt($string, $key)
    {
        return strtoupper(bin2hex($this->des3_encrypt($string, $key)));
    }

    /**
     * 3DES解密
     * @param $string
     * @param $key
     * @return string
     */
    public function DesToBinDecrypt($string, $key)
    {
        return $this->des3_decrypt(hex2bin(strtolower($string)), $key);
    }

    /**
     * AES-ECB加密并转16进制
     * @param $string
     * @param $key
     * @return string
     */
    public function AesToHexEncrypt($string, $key)
    {
        return strtoupper(bin2hex($this->aes_ecb_encrypt($string, $key)));
    }

    /**
     * AES-ECB解密
     * @param $string
     * @param $key
     * @return string
     */
    public function AesToBinDecrypt($string, $key)
    {
        return $this->aes_ecb_decrypt(hex2bin(strtolower($string)), $key);
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
     * rsaWithSha256加密
     * @param $toSign
     * @param $mode
     * @param string $pem_path
     * @return mixed
     */
    function generateSign($toSign, $mode, $pem_path = '')
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
     */
    function signVerify($string, $sign, $mode, $pem_path = '')
    {
        $this->checkRsaCert($mode, $pem_path);
        return openssl_verify($string, base64_decode($sign), $this->key_resource, OPENSSL_ALGO_SHA256);
    }

    /**
     * 生成待签名字符串
     *
     * @param array $param
     * @return string
     */
    function toSignString($param)
    {
        ksort($param);
        $num = 0;
        $signString = '';
        $filters = ['sign', 'req', 'respCode', 'respDesc'];
        foreach ($param as $key => $value) {
            if (!in_array($key, $filters)) {
                if (is_array($value)) {
                    $value = json_encode_ex($value);
                }

                if ($num === 0) {
                    $signString .= "{$key}={$value}";
                } else {
                    $signString .= "&{$key}={$value}";
                }
                $num++;
            }
        }
        return $signString;
    }


    /**
     *  私钥加密（分段加密）
     *  emptyStr    需要加密字符串
     */
    public function encrypt($str, $mode = 'public', $pem_path = '')
    {
        $crypted = array();
        $dataArray = str_split($str, 117);

        $this->checkRsaCert($mode, $pem_path);

        foreach ($dataArray as $subData) {
            if ($mode == 'public') {
                openssl_public_encrypt($subData, $subCrypted, $this->key_resource);
            } else {
                openssl_private_encrypt($subData, $subCrypted, $this->key_resource);
            }
            $crypted[] = $subCrypted;
            unset($subCrypted);
        }

        $crypted = implode('', $crypted);
        // return base64_encode($crypted);
        return $crypted;
    }

    /**
     *  公钥解密（分段解密）
     *  @encrypstr  加密字符串
     */
    public function decrypt($encryptstr, $mode = 'private', $pem_path = '')
    {
        // $encryptstr = base64_decode($encryptstr);
        $decrypted = array();
        $dataArray = str_split($encryptstr, 256);
        // $dataArray = str_split($encryptstr, 128);

        $this->checkRsaCert($mode, $pem_path);

        foreach ($dataArray as $subData) {
            if ($mode == 'public') {
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
                $this->api->dataerror('加密选项错误！');
                break;
        }


        $this->loadingCert($cert_path, $mode);
        if (empty($this->key_resource)) {
            $this->api->dataerror('密钥读取失败！');
        }
    }



    /**
     * RSA constructor.
     * @param string $KeyPath
     * @param string $cert_type 证书类型: pub  pri
     * @throws Exception
     */
    public function loadingCert($KeyPath, $cert_type)
    {
        switch ($cert_type) {
            case "public":
                if ($this->checkKeyFile($KeyPath)) {
                    $pubkey_str = file_get_contents($KeyPath);
                    $pubkey_str = $this->formatterPublicKey($pubkey_str);
                    $this->key_resource = openssl_pkey_get_public($pubkey_str);
                }
                break;
            case "private":
                if ($this->checkKeyFile($KeyPath)) {
                    $prikey_str = file_get_contents($KeyPath);
                    $prikey_str = $this->formatterPrivateKey($prikey_str);
                    $this->key_resource = openssl_pkey_get_private($prikey_str);
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
     * @param $privateKey string 公钥
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
