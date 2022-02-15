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

$api = new api("controller");

class api
{
    #接口文档数组
    public $api;
    #接口文档单个接口数组
    public $info;
    #接口文档预配置参数数组
    public $doc_params = [];
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
    public $module_name;
    #模块类
    public $module;
    #日志id
    public $log_id;
    #db
    public $db;
    #接口目录
    public $interface_dir;

    #debug
    private $debug;
    #接口访问日志开关
    private $open_api_log;

    function __construct($app)
    {
        // global $db;
        // $this->db = $db;
        $this->debug = get_env('system.open_api_debug');
        $this->interface_dir = API_DIR . $app;
        $this->open_api_log = get_env('system.open_request_log');

        $options = getopt("q:");
        if (!empty($options)) {
            $_REQUEST['req'] = $options['q'];
        }

        if (!empty($_REQUEST['req']) && $_REQUEST['req'] !== 'doc') {
            $this->req = explode('.', $_REQUEST['req']);
            $this->module_name = $this->req[0];
            // if (get_env('system.open_request_log')) {
            //     $this->log_id = $this->db->insert('interface_log', [
            //         'ip' => get_ip(),
            //         'url' => $_SERVER['SCRIPT_NAME'],
            //         'req' => $_REQUEST['req'],
            //         '#createtime' => 'now()',
            //         '#edittime' => 'now()'
            //     ]);
            // }
            unset($this->req[0]);
            $this->req = implode('.', $this->req);
            $this->module = $this->loadService($this->module_name);
            $this->includeFile($this->interface_dir . "/" . $this->module_name . '.php');
            if ($this->debug == 1) {
                $this->outputResponseError('请确认接口类型' . $this->module_name . '-' . $this->req);
            }
        } else if ($_REQUEST['req'] == 'doc') {
            $this->api = ['api_name' => PLATFORM . '接口文档'];
            $this->listDir();
            $this->listApiDoc();
        } else {
            $this->outputResponseError('No permission to access interface documents!');
        }
    }

    /**
     * 加载service方法类
     * @param $service_name
     * @return mixed
     */
    public function loadService($service_name)
    {
        $this->includeFile(SERVICE_DIR . $service_name . ".class.php");
        return $$service_name = new $service_name($this);
    }

    /**
     * 遍历加载接口文件夹，加载接口
     */
    function listDir()
    {
        if (!is_dir($this->interface_dir)) {
            $this->outputResponseError('接口文件目录不正确！');
        }

        $dh = opendir($this->interface_dir);
        if ($dh === false) {
            $this->outputResponseError('无法读取接口文件目录！');
        }

        while (($file = readdir($dh)) !== false) {
            if (substr($file, -3) == 'php' && is_file($this->interface_dir . "/" . $file)) {
                $module_name = substr($file, 0, -4);
                include $this->interface_dir . "/" . $file;
                $this->api['api_list'][$module_name] = $this->subset_api;
            }
        }
        closedir($dh);
    }

    /**
     * 加载单个文件
     * @param $file
     */
    function includeFile($file)
    {
        if (!file_exists($file)) {
            if ($this->debug) {
                $this->outputResponseError('文件路径:' . $file . '不存在');
            }

            $this->outputResponseError('文件缺失或不存在该接口');
        }

        require_once($file);
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

    /**
     * 接口响应错误输出
     * @param $error
     * @param int $status
     */
    function outputResponseError($error, $status = 1)
    {
        if ($this->db->inTransaction()) {
            $this->db->rollBack();
        }
        $this->data['error'] = $error;
        $this->data['status'] = $status;
        $this->outputResponseData();
    }

    /**
     * 接口响应数据输出
     * @param array $data
     * @param int $mode
     */
    function outputResponseData($data = [], $mode = 1)
    {
        if ($mode == 1) {
            if (!empty($data)) {
                $this->checkResponseDataFields($this->fields, $data);
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
        if ($this->open_api_log) {
            $this->db->update('interface_log', array('returndata' => $this->data, '#edittime' => 'now()'), array('tbid' => $this->log_id));
        }
        exit;
    }

    /**
     * 校验输出数据是否完整
     * @param $fields
     * @param $data
     */
    function checkResponseDataFields($fields, $data)
    {
        foreach ($fields as $key => $value) {
            if (is_array($value)) {
                $this->checkFields($data, $key);
                if (!empty($data[$key])) {
                    if (!is_array($data[$key])) {
                        $this->outputResponseError('响应参数:' . $key . '格式不正确');
                    }

                    foreach ($data[$key] as $field) {
                        $this->checkResponseDataFields($value, $field);
                    }
                }
            } else {
                $this->checkFields($data, $value);
            }
        }
    }

    /**
     * 验证输出参数字段
     * @param $data
     * @param $field
     * @return bool
     */
    function checkFields($data, $field)
    {
        $field = $this->removeParamsTag($field);
        if (!is_array($data) || !array_key_exists($field, $data)) {
            if ($this->debug == 1) {
                $this->outputResponseError('响应参数:' . $field . '缺失!');
            }
            $this->outputResponseError('响应参数缺失!');
        }
        return true;
    }

    //接口预处理方法
    function apiInit()
    {
        //添加参数
        $this->addParam();
        //是否为查看文档模式
        $this->checkDoc();
        //获取传输数据
        $this->getRequestParams();
        //检测参数是否完整
        $this->param = $this->checkparam($this->info['parameter']);
        //检测是否模拟数据
        $this->checksimulate();
        return $this->param;
    }

    //添加接口文档参数
    function addParam()
    {
        if (!empty($this->parameter)) {
            $this->info['parameter'] = $this->addParameter($this->parameter);
        }
        if (!empty($this->fields)) {
            $this->info['fields'] = $this->addFields($this->fields);
        }

        prints($this->info['parameter'], false);
    }

    //添加接口参数
    function addsubset()
    {
        foreach ($this->info as $key => $value) {
            $tempapi[$key] = $value;
        }
        $this->subset_api['kind'][] = $tempapi;
    }

    /**
     * 添加请求参数和是否必填
     * @param $params
     * @return array|mixed
     */
    function addParameter($params)
    {
        if (is_array($params)) {
            $parameter = [];
            foreach ($params as $key => $value) {
                if (!is_numeric($key)) {
                    $is_must = $this->checkParamsMust($key);
                    if ($is_must === false) {
                        $key = substr($key, 1);
                    }

                    $parameter[$key] = $this->doc_params[$key];
                    $parameter[$key]['is_must'] = $is_must ? 1 : 0;
                    $parameter[$key]['data'] = $this->addParameter($value);
                } else {
                    $is_must = $this->checkParamsMust($value);
                    if ($is_must === false) {
                        $value = substr($value, 1);
                    }

                    $parameter[$value] = $this->doc_params[$value];
                    $parameter[$value]['is_must'] = $is_must ? 1 : 0;
                }
            }
            return $parameter;
        } else {
            $is_must = $this->checkParamsMust($params);
            if ($is_must === false) {
                $params = substr($params, 1);
            }
            $temp = $this->doc_params[$params];
            $temp['is_must'] = $is_must;
            return $temp;
        }
    }

    /**
     * 添加返回参数和示例值
     * @param $fields
     * @return array|mixed
     */
    function addFields($fields)
    {
        if (is_array($fields)) {
            foreach ($fields as $key => &$value) {
                $value = $this->addFields($value);
                if (!is_numeric($key)) {
                    $must = $this->checkParamsMust($key) ? 1 : 0;
                    if ($must === false) {
                        $key = substr($key, 1);
                    }
                    $value = array_merge($value, $this->doc_params[$key], ['is_must' => $must]);
                }
            }
            return $fields;
        } else {
            $must = $this->checkParamsMust($fields) ? 1 : 0;
            if ($must === false) {
                $fields = substr($fields, 1);
            }
            if (!empty($this->doc_params[$fields])) {
                $temp = $this->doc_params[$fields];
                $temp['name'] = $fields;
                $temp['is_must'] = $must;
                return $temp;
            } else {
                $this->outputResponseError("配置的输出参数" . $fields . "没有定义");
            }
        }
    }

    /**
     * 根据method获得param
     */
    function getRequestParams()
    {
        if ($this->info['method'] == 'POST') {
            $input = file_get_contents('php://input');
            if (empty($input)) {
                $this->param = $_POST;
                $method = 'POST';
            } else {
                $json_param = json_decode($input, 1);
                if (!empty($json_param)) {
                    $method = 'JSON';
                    $this->param = $json_param;
                } else {
                    if ($xml = @simplexml_load_string($input, 'SimpleXMLElement', LIBXML_NOCDATA)) {
                        $method = 'XML';
                        $this->param = $input;
                    } else {
                        parse_str($input, $json_param);
                        if (!empty($json_param)) {
                            $method = 'IPOST';
                            $this->param = $json_param;
                        } else {
                            $method = 'OTHER';
                            $this->param = $input;
                        }
                    }
                }
            }
        } else {
            $this->param = $_GET;
            $method = 'GET';
        }
        if ($this->open_api_log) {
            $this->db->update(
                'interface_log',
                [
                    'method' => $method,
                    'data' => json_encode_ex($this->param),
                    '#edittime' => 'now()'
                ],
                ['tbid' => $this->log_id]
            );
        }
    }

    //校验必须数据是否完整
    function checkRequestParams($parameter, $params = '', $file = '', $tipkey = '')
    {
        if (empty($params)) {
            $params = $this->param;
        }
        if (!empty($parameter)) {
            if (empty($file)) {
                $file = $_FILES;
            }

            foreach ($parameter as $key => $value) {
                if (!array_key_exists($key, $this->doc_params)) {
                    if ($this->debug) {
                        $this->outputResponseError("参数「" . $key . "」未定义");
                    }
                    $this->outputResponseError('参数未定义，请检查!');
                }

                if (is_array($value['data'])) {
                    if (!empty($params[$key])) {
                        foreach ($params[$key] as $tip_keys => &$son_params) {
                            if (empty($tip_keys)) {
                                $son_file = $file[$key]['name'][$tip_keys];
                            } else {
                                $son_file = $file[$key][$tip_keys];
                            }
                            if (empty($son_file)) {
                                $son_file = -1;
                            }
                            $son_params = $this->checkparam($value['data'], $son_params, $son_file, $tip_keys);
                        }
                    }

                    
                }
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

    /**
     * 判断是否为查看文档
     */
    function checkDoc()
    {
        if ($_GET['doc'] == true) {
            $this->infoApiDoc();
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

    /**
     * 去除重名参数定义的#后面内容
     * @param $k
     * @return false|string
     */
    function removeParamsTag($k)
    {
        if (stripos($k, '#')) {
            $k = substr($k, 0, stripos($k, '#'));
        }
        return $k;
    }

    /**
     * 验证是否是必须参数
     * @param $k
     * @return bool
     */
    private function checkParamsMust($k)
    {
        return substr($k, 0, 1) !== '@';
    }

    /**
     * 输出整个api文档
     */
    function listApiDoc()
    {
        $show_api = get_env('system.show_api_doc');
        if ($show_api) {
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
                'show_api' => $show_api,
                'api_title' => PLATFORM,
                'api_list' => $api_list
            ];
            echo json_encode_ex($echo_api);
        } else {
            echo json_encode_ex([
                'show_api' => $show_api,
                'api_title' => PLATFORM,
                'api_list' => []
            ]);
        }
        exit;
    }


    /**
     * 输出单个api文档详情
     */
    function infoApiDoc()
    {
        $show_api = get_env('system.show_api_doc');
        if ($show_api === false) {
            $this->outputResponseData(new stdClass(), 2);
        }

        $api_info = [
            'api_name' => $this->info['summary'],
            'api_module' => $this->subset_api['name'],
            'api_uri' => $this->module_name . '/' . str_replace('.', '/', $this->info['req']),
            'api_url' => API_DOMAIN . 'api' . '/' . $this->module_name . '/' . str_replace('.', '/', $this->info['req']),
            'method' => $this->info['method'],
            'request_param' => [],
            'response_param' => [],
            'json_string' => '{}'
        ];
        $data_url = '';
        if (!empty($this->info['parameter'])) {
            $api_info['request_param'] = $this->listParameters($this->info['parameter']);
            // if ($this->info['method'] == 'GET') {
            //     $date_url = '?' . $this->listTest($this->info['parameter'], $this->info['method']);
            // } else {
            //     $post_test = $this->listTest($this->info['parameter'], $this->info['method']);
            // }
            $data_url .= $this->info['method'] == 'GET' ? '?' : '';
            $data_url .= $this->listTest($this->info['parameter'], $this->info['method']);
        }
        if (!empty($this->info['fields'])) {
            $api_info['response_param'] = $this->listFields($this->info['fields']);
            $api_info['json_string'] = json_encode($this->listJson($this->fields), JSON_PRETTY_PRINT);
        }
        $api_info['test_url'] = '{{host}}/' . $this->module_name . '/' . str_replace('.', '/', $this->info['req']) . $data_url;
        $api_info['post_test'] = $data_url;
        $this->outputResponseData($api_info, 2);
    }

    /**
     * 输出json格式响应示例
     * @param $list
     * @return array
     */
    function listJson($list)
    {
        $json_data = [];
        foreach ($list as $k => $v) {
            if (is_numeric($k)) {
                $v = $this->removeParamsTag($v);
                $json_data[$v] = '';
                unset($list[$k]);
            } else {
                $k = $this->removeParamsTag($k);
                $json_data[$k][] = $this->listJson($list[$k]);
            }
        }
        return $json_data;
    }

    /**
     * 输出post的test参数
     * @param $parameter
     * @param $mode
     * @param string $key
     * @return array|string
     */
    function listTest($parameter, $mode, $key = '')
    {
        $data_url = '';
        $post_param = [];
        $i = 0;
        foreach ($parameter as $k => $value) {
            $k = $this->removeParamsTag($k);
            if (is_array($value['data'])) {
                if ($mode == 'GET') {
                    $data_url .= $this->listTest($value['data'], $mode, $k . '[0]');
                } else {
                    $post_param = array_merge($post_param, $this->listTest($value['data'], $mode, $k . '[0]'));
                }
            } else {
                if (!empty($key)) {
                    $data_url .= $key . '[' . $k . ']';
                } else {
                    $data_url .= $k;
                    if ($value['type'] == 'array') {
                        $data_url .= '[0]';
                    }
                }
                $i++;
                if ($mode == 'GET') {
                    if ($i < count($parameter)) {
                        $data_url .= '=&';
                    }
                } else {
                    $post_param[] = $data_url;
                    $data_url = '';
                }
            }
        }
        if ($mode == 'GET') {
            $end = (substr($data_url, -1) != '=') ? '=' : '';
            return $data_url . $end;
        }
        return $post_param;
    }

    /**
     * 处理返回参数
     * @param $fields
     * @return array
     * @author hzl
     */
    function listFields($fields)
    {
        $fields_param = [];
        foreach ($fields as $k => $v) {
            if (is_array($v)) {
                $list = [];
                if (is_numeric($k)) {
                    $v['name'] = $this->removeParamsTag($v['name']);
                } else if ($v) {
                    $dg = [];
                    $dg['data'] = $this->listFields($v);
                    $dg['summary'] = $v['summary'];
                    $dg['type'] = $v['type'];
                    $dg['name'] = $this->removeParamsTag($k);
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
    function listParameters($parameters)
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
                    $v['data'] = $this->listParameters($v['data']);
                }
                $v['name'] = $this->removeParamsTag($k);
                $format_parameters[] = $v;
            }
        }
        return $format_parameters;
    }
}
