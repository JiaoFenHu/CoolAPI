<?php
declare(strict_types = 1);

/**
 * 对变量进行 JSON 编码
 * @param $value
 * @return false|string
 */
function jsonEncodeExtend($value)
{
    return json_encode($value, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
}

/**
 * 读取配置文件信息
 * @param string $conf_name
 * @return mixed
 */
function getProEnv(string $conf_name)
{
    static $env_configs = [];
    if (empty($env_configs)) {
        require LIB_DIR . 'spyc/Spyc.php';
        $env_configs = Spyc::YAMLLoad(API_DIR . 'env.' . PROJECT_ENV . '.yaml');
    }

    $conf_names = explode('.', $conf_name);
    switch (count($conf_names)) {
        case 1:
            $each_configs = $env_configs[$conf_names[0]];
            break;
        case 2:
            $each_configs = $env_configs[$conf_names[0]][$conf_names[1]];
            break;
        default:
            $each_configs = $env_configs;
            foreach ($conf_names as $name) {
                $each_configs = $each_configs[$name];
            }
            break;
    }
    return $each_configs;
}

/**
 * 处理数据库图片返回给前端
 *
 * @param string $img
 * @param integer $mode
 * @param integer $s_mode
 * @return void
 */
function getImg(string $img, $mode = 0, $s_mode = 0)
{
    $img = explode(FILE_SPLIT, $img);
    foreach ($img as &$value) {
        if (!empty($value)) {
            if (substr($value, 0, 4) != 'http') {
                if ($s_mode == 1) {
                    $value = explode('/', $value);
                    $file_arr = explode('.', $value[count($value) - 1]);
                    $file_arr[count($file_arr) - 2] = $file_arr[count($file_arr) - 2] . '_s';
                    $value[count($value) - 1] = implode('.', $file_arr);;
                    $value = implode('/', $value);
                }
                if (defined('ALIOSS_URL') && substr($value, -10) !== 'avatar.jpg') {
                    $site = ALIOSS_URL;
                } else {
                    $site = API_DOMAIN_REAL;
                }
                $value = $site . $value;
            }
        }
    }
    unset($value);

    $return_img = $mode == 1 ? $img[0] : $img;
    if (empty($return_img)) {
        $return_img = $mode == 1 ? '' : [];
    }
    return $return_img;
}

/**
 * 获取用户头像方法
 * @param string $avatar
 * @return array|string
 */
function getAvatar(string $avatar)
{
    if (empty($avatar)) {
        return getImg('libs/avatar.jpg', 1);
    }

    return getImg($avatar, 1);
}

/**
 * 入库图片处理
 * @param mixed $img
 * @return string
 */
function removeDomain($img)
{
    if (!empty($img)) {
        if (!is_array($img)) {
            $img = explode(FILE_SPLIT, $img);
        }

        $domain_strlen = strlen(API_DOMAIN_REAL);
        $oss_domain_strlen = strlen(ALIOSS_URL);
        $new_imgs = [];
        foreach ($img as $img_v) {
            if (substr($img_v, 0, $domain_strlen) === API_DOMAIN_REAL || (defined('ALIOSS_URL') && substr($img_v, 0, $oss_domain_strlen) === ALIOSS_URL)) {
                $img_arr = explode('//', $img_v);
                unset($img_arr[0]);
                $img_arr = explode('/', implode('//', $img_arr));
                unset($img_arr[0]);
                $img_v = implode('/', $img_arr);
            }
            $new_imgs[] = $img_v;
        }
        return implode(FILE_SPLIT, $new_imgs);
    }

    return '';
}


/**
 * 创建文件目录
 * @param string $dir
 */
function createDirs(string $dir)
{
    if (!is_dir($dir)) {
        $temp = explode(DIRECTORY_SEPARATOR, $dir);
        $cur_dir = '';
        for ($i = 0; $i < count($temp); $i++) {
            $cur_dir .= $temp[$i] . DIRECTORY_SEPARATOR;
            if (!is_dir($cur_dir) && !empty($temp[$i])) {
                @mkdir($cur_dir, 0777);
            }
        }
    }
}

/**
 * 创建唯一编号
 * @param string $pre
 * @param string $end
 * @return string
 */
function generateUniqueCode(string $pre, string $end = '')
{
    $yCode = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P');
    $orderSn = $yCode[intval(date('Y')) - 2017] . strtoupper(dechex(date('m'))) . date('d') . substr(time(), -5) . substr(microtime(), 2, 5) . sprintf('%02d', rand(0, 99));
    return $pre . $orderSn . $end;
}


/**
 * 获取远程数据
 * @param string $url
 * @param string $method
 * @param array $data
 * @param array $header
 * @param integer $time_out
 * @return string
 */
function getCurlContent(string $url, string $method = 'GET', array $data = [], array $header = [], int $time_out = 10)
{
    $method = strtoupper($method);
    if (!empty($data) && $method == 'GET') {
        $url .= strpos($url, '?') == false ? '?' : '&';
        $url .= http_build_query($data);
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_TIMEOUT, $time_out);
    curl_setopt($ch, CURLOPT_URL, $url); //抓取指定网页
    curl_setopt($ch, CURLOPT_HEADER, 0); //设置header
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //要求结果为字符串且输出到屏幕上
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // https请求 不验证证书和hosts
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);

    if (!empty($header)) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
    }
    if ($method == 'POST') {
        if (in_array('Content-Type:application/json', $header)) {
            $data = json_encode_ex($data);
        }
        curl_setopt($ch, CURLOPT_POST, 1); //post提交方式
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    }
    $response_data = curl_exec($ch); //运行curl
    if ($response_data === false) {
        $response_data = curl_error($ch);
    }
    curl_close($ch);
    return $response_data;
}

/**
 * 转换为中文数字
 * @param $num
 * @return string|null
 */
function toChineseNumber($num)
{
    $char = array("零", "一", "二", "三", "四", "五", "六", "七", "八", "九");
    $dw = array("", "十", "百", "千", "万", "亿", "兆");
    $ret_val = "";
    $proZero = false;
    for ($i = 0; $i < strlen($num); $i++) {
        if ($i > 0) $temp = (int)(($num % pow(10, $i + 1)) / pow(10, $i));
        else $temp = (int)($num % pow(10, 1));

        if ($proZero == true && $temp == 0) continue;

        if ($temp == 0) $proZero = true;
        else $proZero = false;

        if ($proZero) {
            if ($ret_val == "") continue;
            $ret_val = $char[$temp] . $ret_val;
        } else $ret_val = $char[$temp] . $dw[$i] . $ret_val;
    }
    if ($ret_val == "一十") $ret_val = "十";
    return $ret_val;
}

/**
 * 获取内网IP
 * @param boolean $is_long 0返回IP地址，1返回IPV4数字地址
 * @return mixed
 */
function getClientIp($is_long = false)
{
    $type = $is_long ? 1 : 0;
    static $ip = NULL;
    if ($ip !== NULL) {
        return $ip[$type];
    }
    if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $arr = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $pos = array_search('unknown', $arr);
        if (false !== $pos) unset($arr[$pos]);
        $ip = trim($arr[0]);
    } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (isset($_SERVER['REMOTE_ADDR'])) {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    // IP地址合法验证
    $long = sprintf("%d", ip2long($ip));
    $ip = $long ? array($ip, $long) : array('0.0.0.0', 0);
    return $ip[$type];
}

/**
 * utf8 转 gb2312
 * @param $_C
 * @return false|string
 */
function _U2_Utf8_Gb($_C)
{
    $_String = '';
    if ($_C < 0x80) {
        $_String .= $_C;
    } elseif ($_C < 0x800) {
        $_String .= chr(0xC0 | $_C >> 6);
        $_String .= chr(0x80 | $_C & 0x3F);
    } elseif ($_C < 0x10000) {
        $_String .= chr(0xE0 | $_C >> 12);
        $_String .= chr(0x80 | $_C >> 6 & 0x3F);
        $_String .= chr(0x80 | $_C & 0x3F);
    } elseif ($_C < 0x200000) {
        $_String .= chr(0xF0 | $_C >> 18);
        $_String .= chr(0x80 | $_C >> 12 & 0x3F);
        $_String .= chr(0x80 | $_C >> 6 & 0x3F);
        $_String .= chr(0x80 | $_C & 0x3F);
    }
    return iconv('UTF-8', 'GB2312', $_String);
}


/**
 * 预处理数据库字段
 * @param string $table
 * @param integer $mode
 * @param string $columnname
 * @return string
 */
/*function get_column($table, $mode = 0, $columnname = '')
{
    global $db;
    $db->getcolumn($table, 1);
    unset($db->column['tb_insterface_log']);
    $column = current($db->column);
    if (empty($columnname)) {
        $columnname = $table;
    }
    $out = '';
    foreach ($column as $key => $value) {
        if ($mode == 0) {
            switch ($value['type']) {
                case in_array($value['type'], ['varchar', 'char', 'text', 'longtext', 'enum']):
                    $type = 'string';
                    break;
                case in_array($value['type'], ['bigint', 'int', 'tinyint']):
                    $type = 'int';
                    break;
                case "timestamp":
                    $type = 'datetime';
                    break;
                default:
                    $type = $value['type'];
                    break;
            }
            $list = '';
            if ($type == 'int') {
                $comment = explode(' ', $value['comment']);
                if (count($comment) > 1) {
                    $list = ',\'list\'=>array(';
                    foreach ($comment as $c) {
                        if (is_numeric(mb_substr($c, 0, 1, 'utf-8'))) {
                            $list .= mb_substr($c, 0, 1, 'utf-8') . '=>\'' . mb_substr($c, 1, mb_strlen($c, 'utf-8'), 'utf-8') . '\',';
                        }
                    }
                    $list = mb_substr($list, 0, mb_strlen($list, 'utf-8') - 1, 'utf-8') . ')';
                    $value['comment'] = $comment[0];
                }
            } else if ($value['type'] == 'enum') {
                $list = ',\'list\' => array(';
                foreach ($value['list'] as $c) {
                    $list .= $c . ' => \'\',';
                }
                $list = rtrim($list, ',') . ')';
            }
            $out .= '$this->infoarr[\'' . str_replace('_', '', $key) . '\']=array(\'type\' => \'' . $type . '\',\'summary\' => \'' . $value['comment'] . '\'' . $list . ');<br>';
        } else if ($mode == 1) {
            $out .= '\'' . str_replace('_', '', $key) . '\',';
        } else if ($mode == 2) {
            echo '$data[\'' . str_replace('_', '', $key) . '\']=$' . $columnname . '[\'' . $key . '\'];<br>';
        } else if ($mode == 3) {
            if ($key != 'tbid' && $key != 'createtime') {
                $out .= '\'' . $key . '\'=>$param[\'' . str_replace('_', '', $key) . '\'],<br>';
            }
        } else {
            $out .= var_export($value);
        }
    }
    if ($mode == 1 || $mode == 3) {
        $out = substr($out, 0, strlen($out) - 1);
    }
    echo $out;
    exit;
}*/


/**
 * xml转数组
 * @param string $xml
 * @return mixed
 */
function xml2array($xml)
{
    libxml_disable_entity_loader(true);
    $xml = simplexml_load_string($xml, "SimpleXMLElement",  LIBXML_NOCDATA);
    return json_decode(json_encode($xml), true);
}


/**
 * 判断当前协议是否为HTTPS
 * @return string
 */
function isHttps()
{
    $is_https = false;
    if (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off') {
        $is_https = true;
    } elseif (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
        $is_https = true;
    } elseif (!empty($_SERVER['HTTP_FRONT_END_HTTPS']) && strtolower($_SERVER['HTTP_FRONT_END_HTTPS']) !== 'off') {
        $is_https = true;
    }
    return $is_https ? 'https' : 'http';
}


/**
 * int转bool
 * @param int $int
 * @return bool
 */
function int2bool(int $int)
{
    return $int != 0;
}


/**
 * 验证手机号是否合法
 * @param string $mobile
 * @return bool
 */
function checkMobile(string $mobile)
{
    $preg = '/^1([38][0-9]|4[579]|5[0-3,5-9]|6[6]|7[0135678]|9[89])\d{8}$/';
    if (!preg_match($preg, $mobile)) {
        return false;
    }
    return true;
}

/**
 * 字符串脱敏处理
 * @param string $string 不能是中文
 * @param int $be
 * @param int $en
 * @param int $repeat_mi *号重复数量
 * @return string
 */
function stringFuzzy(string $string, $be = 3, $en = 4, $repeat_mi = 4)
{
    $return_str = '';
    $return_str .= substr($string, 0, $be);
    $return_str .= str_repeat('*', $repeat_mi);
    $return_str .= substr($string, -$en);
    return $return_str;
}

/**
 * 删除数组数字下标
 * @param array $db_data
 * @return array
 */
function removeKeyNumber(array $db_data)
{
    $return_data = [];
    if (!empty($db_data) && is_array($db_data)) {
        foreach ($db_data as $db_key => $db_val) {
            if (is_array($db_val)) {
                $return_data[$db_key] = removeKeyNumber($db_val);
            } else {
                if (!is_numeric($db_key)) {
                    $return_data[$db_key] = $db_val;
                }
            }
        }
    }
    return $return_data;
}

/**
 * 获取随机字符串
 * @param integer $length 随机字符串长度
 * @param integer $mode 选项
 * @return string
 * @throws Exception
 */
function generateRandomCode(int $length = 16, int $mode = 2)
{
    $randoms = [
        '0123456789',
        'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ',
        '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'
    ];

    if ($mode > 2 || !is_int($mode)) {
        $mode = 2;
    }
    $codeSet = str_split($randoms[$mode]);
    $codeLen = count($codeSet) - 1;
    $nonceStr = '';
    for ($i = 0; $i < $length; $i++) {
        $nonceStr .= $codeSet[random_int(0, $codeLen)];
    }
    return $nonceStr;
}

/**
 * 对二维数组的一/多个字段进行排序
 * @return bool|mixed|null
 */
function multiArraySort()
{
    $args = func_get_args();
    if (empty($args)) {
        return null;
    }
    $arr = array_shift($args);
    if (!is_array($arr)) {
        return false;
    }
    foreach ($args as $key => $field) {
        if (is_string($field)) {
            $args[$key] = array_column($arr, $field);
        }
    }
    $args[] = &$arr; //引用值
    call_user_func_array('array_multisort', $args);
    return array_pop($args);
}


/**
 * 对数组进行limit筛选返回
 * @param array $data
 * @param int $from
 * @param int $limit
 * @return array
 */
function pageArray(array $data, int $from, int $limit)
{
    return array_slice($data, $from, $limit);
}

/**
 * 根据ip获取地理地址信息
 * @param string $ipv4
 * @return array|string
 */
function getIpAddress(string $ipv4)
{
    include(LIB_DIR . "ip2region/Ip2Region.class.php");

    $ip2region  = new Ip2Region(LIB_DIR . 'ip2region/data/ip2region.db');
    try {
        return $ip2region->btreeSearch($ipv4);
    } catch (Exception $e) {
        return $e->getMessage();
    }
}

/**
 * 获取http请求头
 * @return array|false
 */
function getRequestHeaders()
{
    if (function_exists('getallheaders')) {
        $headers = getallheaders();
    } elseif (function_exists('http_get_request_headers')) {
        $headers = http_get_request_headers();
    } else {
        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }
    }
    return $headers;
}

/**
 * @param mixed $data
 * @param bool $is_dump
 * @param bool $die
 */
function prints($data, bool $is_dump = true, bool $die = true)
{
    if ($is_dump) {
        var_dump($data);
    }else {
        echo "<pre>";
        print_r($data);
    }

    if ($die) {
        exit();
    }
}
