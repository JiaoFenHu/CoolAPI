<?php

/**
 * 作者：陈志平
 * 日期：2016-12-30
 * 电邮：235437507@qq.com
 * 版本：V1.18
 * 更新时间：2019/9/16
 * 更新日志：
 * V1.1 新增自动处理数据库参数函数：
 * V1.2 新增json示例数据返回
 * v1.3 修改liux下api文档展示错误的问题
 * v1.4 修改配置参数#2展示json数据不正确的问题
 * v1.5 新增jsonp返回格式，callback作为保留字段
 * v1.6 修复getcontent的不传送data数据的bug
 * v1.7 修复在低分辨率下栏目过多无法看全的bug
 * v1.8 新增入参数组可非必填功能
 * v1.9 调整测试输出数据
 * v1.10 解决class不存在接口输出文档报错的bug
 * v1.11 新增接口访问记录
 * v1.12 接口访问记录新增OTHER类型
 * v1.13 增加命令行调试模式
 * v1.14 api目录可放置在二级目录
 * v1.15 增加网关调用模式
 * v1.16 修改上传文件类型image为file
 * v1.17 添加网关例外模块
 * v1.18 升级兼容PHP7.2
 * v1.19 增加了对数值/时间/数组类型的入参校验
 * v1.20 数组元素输出测试字段，增加了【0】
 * v1.21 修正IPOST入参无法获取到的bug
 * v1.22 修正数字类型判断错误的bug
 * v1.23 修正xml转数组无法获取CDATA数据的bug
 * v1.24 api文档更新返回json格式数据
 * v1.25 增加文档是否开启关闭功能（分为两个配置项，常量配置项优先于数据表配置项）
 *  - 1.常量"SHOW_API"：boolean
 *  - 2.数据表"tb_show_api"配置
 */
header('Access-Control-Allow-Origin:*');
require_once('globals.php');
require_once(INC_DIR . 'config.php');

$api = new api("info", 1);

class api
{
    #接口文档数组
    public $api;
    #接口文档单个接口数组
    public $info;
    #接口文档预配置参数数组
    public $infoarr;
    #接口文档模块数组
    public $subset_api;
    #接口文档模块输入参数
    public $parameter;
    #接口文档模块输出参数
    public $fields;
    #输入参数
    public $param;
    #返回数据
    public $data;
    #接口req参数
    public $req;
    #模块名
    public $modulename;
    #模块类
    public $module;
    #debug模式
    public $debug;
    #日志id
    public $log_id;
    #db
    public $db;
    #接口目录
    public $infodir;
    #是否开启api文档
    public $show_api;

    function __construct($info_dir, $debug = 0)
    {
        global $db;
        $this->db = $db;
        $this->infodir = API_DIR . $info_dir;
        $this->show_api = SHOW_API;
        $this->debug = $debug;
        if ($this->debug == 1) {
            $options = getopt("q:");
            if (!empty($options)) {
                $_REQUEST['req'] = $options['q'];
            }
            ini_set('display_errors', 1);
            error_reporting(E_ERROR);
        } else {
            ini_set('display_errors', 0);
        }

        if (!empty($_REQUEST['req']) && $_REQUEST['req'] !== 'doc') {
            $this->req = explode('.', $_REQUEST['req']);
            $this->modulename = $this->req[0];
            if ($this->debug == 1) {
                $this->log_id = $this->db->insert('insterface_log', array('ip' => getip(), 'url' => $_SERVER['SCRIPT_NAME'], 'req' => $_REQUEST['req'], '#createtime' => 'now()', '#edittime' => 'now()'));
            }
            unset($this->req[0]);
            $this->req = implode('.', $this->req);
            $this->infoarr = array();
            $this->module = includeclass($this, $this->modulename);
            $this->includefile($this->infodir . "/" . $this->modulename . '.php');
            if ($this->debug == 1) {
                $this->dataerror('请确认接口类型' . $this->modulename . '-' . $this->req);
            }
        } else if ($_REQUEST['req'] == 'doc') {
            $this->api = array(
                'api_name' => PLATFORM . '接口文档',
            );
            if ($this->debug == 1) {
                $this->listdir();
            }
            $this->listapi();
        } else {
            $this->dataerror('No permission to access interface documents!');
        }
    }

    //遍历加载api文件;
    function listdir()
    {
        if (is_dir($this->infodir)) {
            if ($dh = opendir($this->infodir)) {
                while (($file = readdir($dh)) !== false) {
                    if (substr($file, -3) == 'php' && !is_dir($this->infodir . "/" . $file)) {
                        if (substr($file, -9, -4) != 'class') {
                            $this->classname = substr($file, 0, -4);
                            include $this->infodir . "/" . $file;
                            $this->api['api_list'][$this->classname] = $this->subset_api;
                        }
                    }
                }
                closedir($dh);
            } else {
                $this->dataerror('无法读取接口文件目录！');
            }
        } else {
            $this->dataerror('接口文件目录不正确！');
        }
    }

    //加载单个文件;
    function includefile($file)
    {
        if (file_exists($file)) {
            require_once($file);
        } else {
            if ($this->debug == 1) {
                $this->dataerror('文件路径:' . $file . '不存在');
            } else {
                $this->dataerror('文件缺失或不存在该接口');
            }
        }
    }

    /**
     * db error log
     * @param object $e
     * @param integer $status
     * @return void
     */
    function dberror($e, $status = 1)
    {
        $message = $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine() . '<br>Stack trace:<br>';
        $trace = $e->getTrace();
        foreach ($trace as $key => $value) {
            $message .= '#' . $key . ' ' . $value['file'] . '(' . $value['line'] . '): ' . $value['class'] . $value['type'] . $value['function'] . '()<br>';
        }

        $this->db->insert('interface_db_err_log', [
            'interface' => $this->modulename . '.' . $this->info['req'],
            'err_content' => $message,
            '#createtime' => 'now()',
            '#edittime' => 'now()'
        ]);
        $this->dataerror('系统繁忙，请稍后重试！', $status);
    }

    //输出error数据
    function dataerror($error, $status = 1)
    {
        if ($this->db->inTransaction()) {
            $this->db->rollBack();
        }
        $this->data['error'] = $error;
        $this->data['status'] = $status;
        $this->echodata();
    }

    //输出data数据
    function echodata($data = [], $mode = 1)
    {
        if ($mode == 1) {
            if (!empty($data)) {
                $this->checkdates($this->fields, $data);
                foreach ($data as $key => $value) {
                    $this->data['data'][$key] = $value;
                }
            }
            if (!isset($this->data['error'])) {
                $this->data['error'] = '';
            }
            if (!isset($this->data['status'])) {
                $this->data['status'] = 1;
            }
            if (!empty($_GET['callback'])) {
                echo $_GET['callback'] . '(' . json_encode($this->data) . ')';
            } else {
                $this->data = json_encode_ex($this->data);
            }
        } else {
            $this->data = $data;
        }
        echo $this->data;
        if ($this->debug == 1) {
            $this->db->update('insterface_log', array('returndata' => $this->data, '#edittime' => 'now()'), array('tbid' => $this->log_id));
        }
        exit;
    }

    //校验输出数据是否完整
    function checkdates($fields, $data)
    {
        foreach ($fields as $key => $value) {
            if (is_array($value)) {
                $this->checkfields($data, $key);
                if (!empty($data[$key])) {
                    if (is_array($data[$key])) {
                        foreach ($data[$key] as $datas) {
                            $this->checkdates($value, $datas);
                        }
                    } else {
                        $this->dataerror('返回参数:' . $key . '格式不正确');
                    }
                }
            } else {
                $this->checkfields($data, $value);
            }
        }
    }

    function checkfields($data, $field)
    {
        $field = $this->removehashtag($field);
        if (!is_array($data) || !array_key_exists($field, $data)) {
            if ($this->debug == 1) {
                $this->dataerror('返回参数:' . $field . '缺失');
            } else {
                $this->dataerror('返回参数缺失');
            }
        }
    }

    //接口预处理方法
    function apiinit()
    {
        //添加参数
        $this->addparam();
        //是否为查看文档模式
        $this->checkdoc();
        //获取传输数据
        $this->getparam();
        //检测参数是否完整
        $this->param = $this->checkparam($this->info['parameter']);
        //检测是否模拟数据
        $this->checksimulate();
        return $this->param;
    }

    //添加接口文档参数
    function addparam()
    {
        if ($this->parameter) {
            $this->info['parameter'] = $this->addparameter($this->parameter);
        }
        if ($this->fields) {
            $this->info['fields'] = $this->addfields($this->fields);
        }
    }

    //添加接口参数
    function addsubset()
    {
        foreach ($this->info as $key => $value) {
            $tempapi[$key] = $value;
        }
        $this->subset_api['kind'][] = $tempapi;
    }

    //添加请求参数和是否必填
    function addparameter($name, $ismust = 1)
    {
        if (is_array($name)) {
            foreach ($name as $key => $value) {
                if (!is_numeric($key)) {
                    $parameter[$key] = $this->infoarr[$key];
                    $last = end($value);
                    if (is_numeric($last)) {
                        $parameter[$key]['ismust'] = $last;
                        unset($value[key($value)]);
                    } else {
                        $parameter[$key]['ismust'] = 1;
                    }
                    $parameter[$key]['data'] = $this->addparameter($value);
                } else {
                    if (is_array($value)) {
                        $key = key($value);
                        if (!is_numeric($key)) {
                            $parameter[$key] = $this->infoarr[$key];
                            $parameter[$key]['ismust'] = end($value);
                            $parameter[$key]['data'] = $this->addparameter(reset($value));
                        } else {
                            $parameter[$value[0]] = $this->addparameter($value[0], $value[1]);
                        }
                    } else {
                        $parameter[$value] = $this->addparameter($value);
                    }
                }
            }
            return $parameter;
        } else {
            $tempinfo = $this->infoarr[$name];
            $tempinfo['ismust'] = $ismust;
            return $tempinfo;
        }
    }

    //添加返回参数和示例值
    function addfields($fields)
    {
        if (is_array($fields)) {
            foreach ($fields as $key => &$value) {
                $value = $this->addfields($value);
                if (!is_numeric($key)) {
                    $this->addfields($key);
                    $value = array_merge($value, $this->infoarr[$key]);
                }
            }
            return $fields;
        } else {
            if (!empty($this->infoarr[$fields])) {
                $tempinfo = $this->infoarr[$fields];
                $tempinfo['name'] = $fields;
                return $tempinfo;
            } else {
                $this->dataerror("配置的输出参数" . $fields . "没有定义");
            }
        }
    }

    //根据method获得param
    function getparam()
    {
        if ($this->info['method'] == 'POST') {
            $input = file_get_contents('php://input');
            if (empty($input)) {
                $this->param = $_POST;
                $methed = 'POST';
            } else {
                $decodeparam = json_decode($input, 1);
                if (!empty($decodeparam)) {
                    $methed = 'JSON';
                    $this->param = $decodeparam;
                } else {
                    if ($xml = @simplexml_load_string($input, 'SimpleXMLElement', LIBXML_NOCDATA)) {
                        $methed = 'XML';
                        $this->param = $input;
                    } else {
                        parse_str($input, $decodeparam);
                        if (!empty($decodeparam)) {
                            $methed = 'IPOST';
                            $this->param = $decodeparam;
                        } else {
                            $methed = 'OTHER';
                            $this->param = $input;
                        }
                    }
                }
            }
        } else {
            $this->param = $_GET;
            $methed = 'GET';
        }
        if ($this->debug == 1) {
            $this->db->update('insterface_log', array('methed' => $methed, 'data' => json_encode_ex($this->param), '#edittime' => 'now()'), array('tbid' => $this->log_id));
        }
    }

    //校验必须数据是否完整
    function checkparam($parameter, $params = '', $file = '', $tipkey = '')
    {
        if (empty($params)) {
            $params = $this->param;
        }
        if (!empty($parameter)) {
            if (empty($file)) {
                $file = $_FILES;
            }
            foreach ($parameter as $key => $value) {
                if (is_array($value['data'])) {
                    if (!empty($params[$key])) {
                        foreach ($params[$key] as $tipkeys => &$sonparams) {
                            if (empty($tipkey)) {
                                $sonfile = $file[$key]['name'][$tipkeys];
                            } else {
                                $sonfile = $file[$key][$tipkeys];
                            }
                            if (empty($sonfile)) {
                                $sonfile = -1;
                            }
                            $sonparams = $this->checkparam($value['data'], $sonparams, $sonfile, $tipkeys);
                        }
                    }
                }
                if (!array_key_exists($key, $this->infoarr)) {
                    if ($this->debug == 1) {
                        $this->dataerror("参数" . $key . ",未定义");
                    } else {
                        $this->dataerror('参数未定义，请检查');
                    }
                }
                if ($this->infoarr[$key]['type'] == 'file') {
                    $param = $file[$this->removehashtag($key)];
                } else {
                    $param = $params[$this->removehashtag($key)];
                }
                if ($value['ismust'] == true && !isset($param)) {
                    $error[] = $this->removehashtag($key);
                }
                if (isset($param) && is_array($value['list'])) {
                    if (!array_key_exists($param, $value['list'])) {
                        if ($this->debug == 1) {
                            $listhtml = ' 可选值:';
                            foreach ($value['list'] as $listkey => $lists) {
                                $listhtml .= $listkey . '(' . $lists . '),';
                            }
                            $listhtml = substr($listhtml, 0, strlen($listhtml) - 1);
                            $this->dataerror("参数 " . $this->removehashtag($key) . " 数值不正确,请输入正确的值。" . $listhtml);
                        } else {
                            $this->dataerror('参数数值不正确，请检查');
                        }
                    }
                }
                if (isset($param) && $value['type'] == 'array') {
                    if (!is_array($param)) {
                        if ($this->debug == 1) {
                            $this->dataerror("参数 " . $this->removehashtag($key) . " 类型不正确，请传入数组值。");
                        } else {
                            $this->dataerror('参数数值不正确，请检查');
                        }
                    }
                }
                if (isset($param) && $value['type'] == 'int') {
                    if (!is_numeric($param)) {
                        if ($this->debug == 1) {
                            $this->dataerror("参数 " . $this->removehashtag($key) . " 类型不正确，请传入数字值。");
                        } else {
                            $this->dataerror('参数数值不正确，请检查');
                        }
                    }
                }
                if (isset($param) && $value['type'] == 'datetime') {
                    if (!strtotime($param)) {
                        if ($this->debug == 1) {
                            $this->dataerror("参数 " . $this->removehashtag($key) . " 类型不正确，请传入日期值。");
                        } else {
                            $this->dataerror('参数数值不正确，请检查');
                        }
                    }
                }
                if ($value['ismust'] == false && !isset($param)) {
                    $params[$this->removehashtag($key)] = $value['default'];
                }
            }
            if (!empty($error)) {
                if ($this->debug == 1) {
                    $tips = "缺少参数" . implode(',', $error);
                    if ($tipkey !== '') {
                        $tips = '数据组【' . $tipkey . '】' . $tips;
                    }
                    $this->dataerror($tips);
                } else {
                    $this->dataerror('缺少参数，请检查');
                }
            }
        }
        return $params;
    }

    //判断是否为当前接口
    function checkthisapi()
    {
        return $this->req == $this->info['req'];
    }

    //判断是否为查看文档
    function checkdoc()
    {
        if ($_GET['doc'] == true) {
            $this->listinfo();
        }
    }

    //判断是否为模拟数据
    function checksimulate()
    {
        if ($this->param['simulate'] == 1) {
            foreach ($this->info['fields'] as $key => $value) {
                $this->data[$key] = $this->simulate($value);
            }
            $this->echodata();
        }
    }

    //模拟数据
    function simulate($param)
    {
        switch ($param['type']) {
            case 'int':
                $simulate = rand();
                break;
            case 'string':
                $simulate = get_rand_string(rand(0, 10));
                break;
            case 'bool':
                $simulate = rand(0, 1);
                break;
        }
        return $simulate;
    }

    //去除重名参数定义的#后面内容
    function removehashtag($k)
    {
        if (stripos($k, '#')) {
            $k = substr($k, 0, stripos($k, '#'));
        }
        return $k;
    }

    //输出整个api文档
    function listapi()
    {
        if ($this->show_api) {
            $api_list = [];
            foreach ($this->api['api_list'] as $key => $value) {
                if (!empty($value['kind'])) {
                    foreach ($value['kind'] as $kind_k => &$kind_v) {
                        $kind_v['req'] = $key . '/' . str_replace('.', '/', $kind_v['req']);
                    }
                } else {
                    $value['kind'] = [];
                }
                $api_list[] = $value;
            }
            $echo_api = [
                'show_api' => $this->show_api,
                'api_title' => PLATFORM,
                'api_list' => $api_list
            ];
            echo json_encode_ex($echo_api);
        } else {
            echo json_encode_ex([
                'show_api' => $this->show_api,
                'api_title' => PLATFORM,
                'api_list' => []
            ]);
        }
        exit;
    }

    //输出json格式示例返回
    function listjson($list)
    {
        $json_data = [];
        foreach ($list as $k => $v) {
            if (is_numeric($k)) {
                $v = $this->removehashtag($v);
                $json_data[$v] = '';
                unset($list[$k]);
            } else {
                $k = $this->removehashtag($k);
                $json_data[$k][] = $this->listjson($list[$k]);
            }
        }
        return $json_data;
    }

    //输出单个api文档
    function listinfo()
    {
        if ($this->show_api) {
            $api_info = [
                'api_name' => $this->info['summary'],
                'api_module' => $this->subset_api['name'],
                'api_uri' => $this->modulename . '/' . str_replace('.', '/', $this->info['req']),
                'api_url' => SITEROOTURL . 'api' . '/' . $this->modulename . '/' . str_replace('.', '/', $this->info['req']),
                'method' => $this->info['method'],
                'request_param' => [],
                'response_param' => [],
                'json_string' => '{}'
            ];
            $date_url = '';
            if (!empty($this->info['parameter'])) {
                $api_info['request_param'] = $this->list_parameters($this->info['parameter']);
                if ($this->info['method'] == 'GET') {
                    $date_url = '?' . $this->list_test($this->info['parameter'], $this->info['method']);
                } else {
                    $post_test = $this->list_test($this->info['parameter'], $this->info['method']);
                }
            }
            if (!empty($this->info['fields'])) {
                $api_info['response_param'] = $this->list_fields($this->info['fields']);
                $api_info['json_string'] = json_encode($this->listjson($this->fields), JSON_PRETTY_PRINT);
            }
            $api_info['test_url'] = '{{host}}/' . $this->modulename . '/' . str_replace('.', '/', $this->info['req']) . $date_url;
            $api_info['post_test'] = isset($post_test) ? $post_test : '';
            echo json_encode_ex($api_info);
        } else {
            echo json_encode_ex(new stdClass());
        }
        exit;
    }

    /**
     * 输出post的test参数
     * @param $parameter
     * @param $mode
     * @param string $key
     * @return array|string
     */
    function list_test($parameter, $mode, $key = '')
    {
        $dataurl = '';
        $post_param = [];
        $i = 0;
        foreach ($parameter as $k => $value) {
            $k = $this->removehashtag($k);
            if (is_array($value['data'])) {
                if ($mode == 'GET') {
                    $dataurl .= $this->list_test($value['data'], $mode, $k . '[0]');
                } else {
                    $post_param = array_merge($post_param, $this->list_test($value['data'], $mode, $k . '[0]'));
                }
            } else {
                if (!empty($key)) {
                    $dataurl .= $key . '[' . $k . ']';
                } else {
                    $dataurl .= $k;
                    if ($value['type'] == 'array') {
                        $dataurl .= '[0]';
                    }
                }
                $i++;
                if ($mode == 'GET') {
                    if ($i < count($parameter)) {
                        $dataurl .= '=&';
                    }
                } else {
                    $post_param[] = $dataurl;
                    $dataurl = '';
                }
            }
        }
        if ($mode == 'GET') {
            $end = (substr($dataurl, -1) != '=') ? '=' : '';
            return $dataurl . $end;
        }
        return $post_param;
    }

    /**
     * 处理返回参数
     * @param $fields
     * @return array
     * @author hzl
     */
    function list_fields($fields)
    {
        $fields_param = [];
        foreach ($fields as $k => $v) {
            if (is_array($v)) {
                $list = [];
                if (is_numeric($k)) {
                    $v['name'] = $this->removehashtag($v['name']);
                } else if ($v) {
                    $dg = [];
                    $dg['data'] = $this->list_fields($v);
                    $dg['summary'] = $v['summary'];
                    $dg['type'] = $v['type'];
                    $dg['name'] = $this->removehashtag($k);
                    $v = $dg;
                }

                if (isset($v['list'])) {
                    foreach ($v['list'] as $listkey => $lists) {
                        $list[] = [
                            'name' => $listkey,
                            'value' => $lists
                        ];
                        unset($v['list'][$listkey]);
                    }
                }
                $v['list'] = $list;
                $fields_param[] = $v;
                unset($list, $v);
            }
        }
        return $fields_param;
    }

    /**
     * 处理请求参数
     * @param $parameters
     * @return array
     * @author hzl
     */
    function list_parameters($parameters)
    {
        $format_parameters = [];
        foreach ($parameters as $k => $v) {
            if (is_array($v)) {
                $list = [];
                if (isset($v['list'])) {
                    foreach ($v['list'] as $listkey => $lists) {
                        $list[] = [
                            'name' => $listkey,
                            'value' => $lists
                        ];
                    }
                }
                $v['list'] = $list;
                if (!isset($v['default'])) {
                    $v['default'] = '';
                }

                if (is_array($v['data'])) {
                    $v['data'] = $this->list_parameters($v['data']);
                }
                $v['name'] = $this->removehashtag($k);
                $format_parameters[] = $v;
            }
        }
        return $format_parameters;
    }
}
