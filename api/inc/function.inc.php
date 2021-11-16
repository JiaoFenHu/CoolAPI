<?php

/**
 * 对变量进行 JSON 编码
 * @param mixed value 待编码的 value ，除了resource 类型之外，可以为任何数据类型，该函数只能接受 UTF-8 编码的数据
 * @return string 返回 value 值的 JSON 形式
 */
function json_encode_ex($value)
{
    if (version_compare(PHP_VERSION, '5.4.0', '<')) {
        $str = json_encode($value);
        $str = preg_replace_callback("#\\\u([0-9a-f]{4})#i", function ($matches) {
            return iconv('UCS-2BE', 'UTF-8', pack('H4', $matches[1]));
        }, $str);
        return $str;
    } else {
        return json_encode($value, JSON_UNESCAPED_UNICODE);
    }
}

/**
 * 处理数据库图片返回给前端
 *
 * @param string $img
 * @param integer $mode
 * @param integer $s_mode
 * @return void
 */
function get_img($img, $mode = 0, $s_mode = 0)
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
function get_avatar($avatar)
{
    if (empty($avatar)) {
        return get_img('libs/avatar.jpg', 1);
    }

    return get_img($avatar, 1);
}

/**
 * 入库图片处理
 * @param $img
 * @return string
 */
function remove_domain($img)
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
 * api加载class并初始化
 * @param object $api
 * @param string $class
 * @return stdClass
 */
function include_class($api, $class)
{
    $api->include_file($api->infodir . DIRECTORY_SEPARATOR . $class . ".class.php");
    return $$class = new $class($api);
}


/**
 * 创建文件目录
 * @param $dir
 */
function create_dirs($dir)
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
function generate_unique_code($pre, $end = '')
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
function get_curl_content($url, $method = 'GET', $data = [], $header = [], $time_out = 10)
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
        if (in_array('Content-Type:application/json', $header) && $method == 'POST') {
            $data = json_encode_ex($data);
        }
    }
    if ($method == 'POST') {
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
function to_chinese_number($num)
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
function get_ip($is_long = false)
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
 * 获取中文拼音
 * @param $_String
 * @param string $_Code
 * @return array|string|string[]|null
 */
function pinyin($_String, $_Code = 'utf-8')
{
    $_DataKey = "a|ai|an|ang|ao|ba|bai|ban|bang|bao|bei|ben|beng|bi|bian|biao|bie|bin|bing|bo|bu|ca|cai|can|cang|cao|ce|ceng|cha" .
        "|chai|chan|chang|chao|che|chen|cheng|chi|chong|chou|chu|chuai|chuan|chuang|chui|chun|chuo|ci|cong|cou|cu|" .
        "cuan|cui|cun|cuo|da|dai|dan|dang|dao|de|deng|di|dian|diao|die|ding|diu|dong|dou|du|duan|dui|dun|duo|e|en|er" .
        "|fa|fan|fang|fei|fen|feng|fo|fou|fu|ga|gai|gan|gang|gao|ge|gei|gen|geng|gong|gou|gu|gua|guai|guan|guang|gui" .
        "|gun|guo|ha|hai|han|hang|hao|he|hei|hen|heng|hong|hou|hu|hua|huai|huan|huang|hui|hun|huo|ji|jia|jian|jiang" .
        "|jiao|jie|jin|jing|jiong|jiu|ju|juan|jue|jun|ka|kai|kan|kang|kao|ke|ken|keng|kong|kou|ku|kua|kuai|kuan|kuang" .
        "|kui|kun|kuo|la|lai|lan|lang|lao|le|lei|leng|li|lia|lian|liang|liao|lie|lin|ling|liu|long|lou|lu|lv|luan|lue" .
        "|lun|luo|ma|mai|man|mang|mao|me|mei|men|meng|mi|mian|miao|mie|min|ming|miu|mo|mou|mu|na|nai|nan|nang|nao|ne" .
        "|nei|nen|neng|ni|nian|niang|niao|nie|nin|ning|niu|nong|nu|nv|nuan|nue|nuo|o|ou|pa|pai|pan|pang|pao|pei|pen" .
        "|peng|pi|pian|piao|pie|pin|ping|po|pu|qi|qia|qian|qiang|qiao|qie|qin|qing|qiong|qiu|qu|quan|que|qun|ran|rang" .
        "|rao|re|ren|reng|ri|rong|rou|ru|ruan|rui|run|ruo|sa|sai|san|sang|sao|se|sen|seng|sha|shai|shan|shang|shao|" .
        "she|shen|sheng|shi|shou|shu|shua|shuai|shuan|shuang|shui|shun|shuo|si|song|sou|su|suan|sui|sun|suo|ta|tai|" .
        "tan|tang|tao|te|teng|ti|tian|tiao|tie|ting|tong|tou|tu|tuan|tui|tun|tuo|wa|wai|wan|wang|wei|wen|weng|wo|wu" .
        "|xi|xia|xian|xiang|xiao|xie|xin|xing|xiong|xiu|xu|xuan|xue|xun|ya|yan|yang|yao|ye|yi|yin|ying|yo|yong|you" .
        "|yu|yuan|yue|yun|za|zai|zan|zang|zao|ze|zei|zen|zeng|zha|zhai|zhan|zhang|zhao|zhe|zhen|zheng|zhi|zhong|" .
        "zhou|zhu|zhua|zhuai|zhuan|zhuang|zhui|zhun|zhuo|zi|zong|zou|zu|zuan|zui|zun|zuo";

    $_DataValue = "-20319|-20317|-20304|-20295|-20292|-20283|-20265|-20257|-20242|-20230|-20051|-20036|-20032|-20026|-20002|-19990" .
        "|-19986|-19982|-19976|-19805|-19784|-19775|-19774|-19763|-19756|-19751|-19746|-19741|-19739|-19728|-19725" .
        "|-19715|-19540|-19531|-19525|-19515|-19500|-19484|-19479|-19467|-19289|-19288|-19281|-19275|-19270|-19263" .
        "|-19261|-19249|-19243|-19242|-19238|-19235|-19227|-19224|-19218|-19212|-19038|-19023|-19018|-19006|-19003" .
        "|-18996|-18977|-18961|-18952|-18783|-18774|-18773|-18763|-18756|-18741|-18735|-18731|-18722|-18710|-18697" .
        "|-18696|-18526|-18518|-18501|-18490|-18478|-18463|-18448|-18447|-18446|-18239|-18237|-18231|-18220|-18211" .
        "|-18201|-18184|-18183|-18181|-18012|-17997|-17988|-17970|-17964|-17961|-17950|-17947|-17931|-17928|-17922" .
        "|-17759|-17752|-17733|-17730|-17721|-17703|-17701|-17697|-17692|-17683|-17676|-17496|-17487|-17482|-17468" .
        "|-17454|-17433|-17427|-17417|-17202|-17185|-16983|-16970|-16942|-16915|-16733|-16708|-16706|-16689|-16664" .
        "|-16657|-16647|-16474|-16470|-16465|-16459|-16452|-16448|-16433|-16429|-16427|-16423|-16419|-16412|-16407" .
        "|-16403|-16401|-16393|-16220|-16216|-16212|-16205|-16202|-16187|-16180|-16171|-16169|-16158|-16155|-15959" .
        "|-15958|-15944|-15933|-15920|-15915|-15903|-15889|-15878|-15707|-15701|-15681|-15667|-15661|-15659|-15652" .
        "|-15640|-15631|-15625|-15454|-15448|-15436|-15435|-15419|-15416|-15408|-15394|-15385|-15377|-15375|-15369" .
        "|-15363|-15362|-15183|-15180|-15165|-15158|-15153|-15150|-15149|-15144|-15143|-15141|-15140|-15139|-15128" .
        "|-15121|-15119|-15117|-15110|-15109|-14941|-14937|-14933|-14930|-14929|-14928|-14926|-14922|-14921|-14914" .
        "|-14908|-14902|-14894|-14889|-14882|-14873|-14871|-14857|-14678|-14674|-14670|-14668|-14663|-14654|-14645" .
        "|-14630|-14594|-14429|-14407|-14399|-14384|-14379|-14368|-14355|-14353|-14345|-14170|-14159|-14151|-14149" .
        "|-14145|-14140|-14137|-14135|-14125|-14123|-14122|-14112|-14109|-14099|-14097|-14094|-14092|-14090|-14087" .
        "|-14083|-13917|-13914|-13910|-13907|-13906|-13905|-13896|-13894|-13878|-13870|-13859|-13847|-13831|-13658" .
        "|-13611|-13601|-13406|-13404|-13400|-13398|-13395|-13391|-13387|-13383|-13367|-13359|-13356|-13343|-13340" .
        "|-13329|-13326|-13318|-13147|-13138|-13120|-13107|-13096|-13095|-13091|-13076|-13068|-13063|-13060|-12888" .
        "|-12875|-12871|-12860|-12858|-12852|-12849|-12838|-12831|-12829|-12812|-12802|-12607|-12597|-12594|-12585" .
        "|-12556|-12359|-12346|-12320|-12300|-12120|-12099|-12089|-12074|-12067|-12058|-12039|-11867|-11861|-11847" .
        "|-11831|-11798|-11781|-11604|-11589|-11536|-11358|-11340|-11339|-11324|-11303|-11097|-11077|-11067|-11055" .
        "|-11052|-11045|-11041|-11038|-11024|-11020|-11019|-11018|-11014|-10838|-10832|-10815|-10800|-10790|-10780" .
        "|-10764|-10587|-10544|-10533|-10519|-10331|-10329|-10328|-10322|-10315|-10309|-10307|-10296|-10281|-10274" .
        "|-10270|-10262|-10260|-10256|-10254";
    $_TDataKey = explode('|', $_DataKey);
    $_TDataValue = explode('|', $_DataValue);

    $_Data = (PHP_VERSION >= '5.0') ? array_combine($_TDataKey, $_TDataValue) : _Array_Combine($_TDataKey, $_TDataValue);
    arsort($_Data);
    reset($_Data);

    if ($_Code != 'gb2312') {
        $_String = _U2_Utf8_Gb($_String);
    }
    $_Res = '';
    for ($i = 0; $i < strlen($_String); $i++) {
        $_P = ord(substr($_String, $i, 1));
        if ($_P > 160) {
            $_Q = ord(substr($_String, ++$i, 1));
            $_P = $_P * 256 + $_Q - 65536;
        }
        $_Res .= _Pinyin($_P, $_Data);
    }
    return preg_replace("/[^a-z0-9]*/", '', $_Res);
}

function _Pinyin($_Num, $_Data)
{
    if ($_Num > 0 && $_Num < 160) {
        return chr($_Num);
    } elseif ($_Num < -20319 || $_Num > -10247) {
        return '';
    } else {
        foreach ($_Data as $k => $v) {
            if ($v <= $_Num) break;
        }
        return $k;
    }
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
function is_https()
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
function int2bool($int)
{
    return $int != 0;
}


/**
 * 验证手机号是否合法
 * @param string $mobile
 * @return bool
 */
function check_mobile($mobile)
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
function string_fuzzy($string, $be = 3, $en = 4, $repeat_mi = 4)
{
    $return_str = '';
    $return_str .= substr($string, 0, $be);
    $return_str .= str_repeat('*', $repeat_mi);
    $return_str .= substr($string, -$en);
    return $return_str;
}

/**
 * 删除数组数字下标
 * @param $db_data
 * @return array
 */
function remove_key_number($db_data)
{
    $return_data = [];
    if (!empty($db_data) && is_array($db_data)) {
        foreach ($db_data as $db_key => $db_val) {
            if (is_array($db_val)) {
                $return_data[$db_key] = remove_db_data_num($db_val);
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
 * @param integer $length	随机字符串长度
 * @param integer $mode	选项
 * @return string
 */
function generate_random_code($length = 16, $mode = 2)
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
        $nonceStr .= $codeSet[mt_rand(0, $codeLen)];
    }
    return $nonceStr;
}

/**
 * 对二维数组的一/多个字段进行排序
 * @return bool|mixed|null
 */
function multi_array_sort()
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
function page_array($data, $from, $limit)
{
    return array_slice($data, $from, $limit);
}

/**
 * 根据ip获取地理地址信息
 *
 * @param string $ipv4
 * @return array|string
 */
function get_ip_address($ipv4)
{
    include_once(LIB_DIR . "ip2region/Ip2Region.class.php");

    $ip2region  = new Ip2Region(LIB_DIR . 'ip2region/data/ip2region.db');
    try {
        $ipInfos = $ip2region->btreeSearch($ipv4);
        return $ipInfos;
    } catch (Exception $e) {
        return $e->getMessage();
    }
}
