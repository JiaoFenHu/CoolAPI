<?php

/**
 * @anthor hujiaofen，七星伴月
 * @ver 1.0
 * @changeDate 2022-02-19
 * @desc 入口文件
 */
header('Access-Control-Allow-Origin:*');
require('globals.php');
require(INC_DIR . 'config.php');
require(COMPOSER_DIR . 'autoload.php');

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
    private $interface_dir;
    #debug
    private $debug;
    #接口访问日志开关
    private $open_api_log;
    #接口日志服务
    private $interfaceLogService;

    public function __construct($app)
    {
        global $db;
        $this->db = $db;
        $this->debug = get_env('system.open_api_debug');
        $this->interface_dir = API_DIR . $app;
        $this->open_api_log = get_env('system.open_request_log');

        $options = getopt("q:");
        if (!empty($options)) {
            $_REQUEST['req'] = $options['q'];
        }

        // 查看文档
        if ($_REQUEST['req'] == 'doc') {
            $this->api = ['api_name' => PLATFORM . '接口文档'];
            $this->listDir();
            $this->listApiDoc();
        }

        // 接口
        if (!empty($_REQUEST['req']) && $_REQUEST['req'] !== 'doc') {
            include(INC_DIR . 'orm.class.php');
            include(SERVICE_DIR . 'base.class.php');
            if ($this->open_api_log) {
                $this->interfaceLogService = $this->loadService("interfaceLog");
                $this->log_id = $this->interfaceLogService->add([
                    'ip' => get_ip(),
                    'url' => $_SERVER['SCRIPT_NAME'],
                    'api' => $_REQUEST['req'],
                ]);
            }

            $this->req = explode('.', $_REQUEST['req']);
            $this->module_name = array_shift($this->req);
            $this->req = implode('.', $this->req);
            $this->module = $this->loadService($this->module_name);
            $this->initParamDoc();
            $this->includeFile($this->interface_dir . "/" . $this->module_name . '.php');
            if ($this->debug == 1) {
                $this->outputResponseError('请确认接口类型' . $this->module_name . '-' . $this->req);
            }
        }
        $this->outputResponseError('No Permission To Access Interface Documents!');
    }

    /**
     * 初始化常用参数
     */
    private function initParamDoc()
    {
        $this->doc_params['list'] = ['type' => 'array', 'summary' => '列表数据'];
        $this->doc_params['from'] = ['type' => 'int', 'summary' => '每页开始条数'];
        $this->doc_params['limit'] = ['type' => 'int', 'summary' => '每页条目数'];
        $this->doc_params['count'] = ['type' => 'int', 'summary' => '总数量'];
        $this->doc_params['success'] = ['type' => 'bool', 'summary' => '是否成功'];
        $this->doc_params['create_time'] = ['type' => 'datetime', 'summary' => '创建时间'];
        $this->doc_params['token'] = ['type' => 'string', 'summary' => 'token'];
    }

    /**
     * 遍历加载接口文件夹，加载接口
     */
    private function listDir()
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
    private function includeFile($file)
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
     * 校验输出数据是否完整
     * @param $fields
     * @param $data
     */
    private function checkResponseDataFields($fields, $data)
    {
        foreach ($fields as $key => $value) {
            if (is_array($value)) {
                $key = $this->checkFields($data, $key);
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
     * @param array $data
     * @param string $field
     * @return string
     */
    private function checkFields($data, $field)
    {
        $field = $this->removeParamsTag($field);
        $must = $this->checkParamsMust($field);
        if ($must === false) {
            $field = substr($field, 1);
        }
        if ((!is_array($data) || !array_key_exists($field, $data)) && $must) {
            if ($this->debug == 1) {
                $this->outputResponseError('响应参数:' . $field . '缺失!');
            }
            $this->outputResponseError('响应参数缺失!');
        }
        return $field;
    }

    /**
     * 接口预处理方法
     * @return array
     */
    private function apiInit()
    {
        //添加参数
        $this->addParam();
        //是否为查看文档模式
        $this->checkDoc();
        //获取传输数据
        $this->getRequestParams();
        //检测参数是否完整
        $this->param = $this->checkRequestParams($this->info['parameter']);
        //检测是否模拟数据
        $this->checkSimulate();
        return $this->param;
    }

    /**
     * 添加接口文档参数
     */
    private function addParam()
    {
        if (!empty($this->parameter)) {
            $this->info['parameter'] = $this->addParameter($this->parameter);
        }
        if (!empty($this->fields)) {
            $this->info['fields'] = $this->addFields($this->fields);
        }
    }

    /**
     * 添加接口相关信息
     * 不添加则无法输出单个api
     */
    private function addSubset()
    {
        $subset = [];
        foreach ($this->info as $key => $value) {
            $subset[$key] = $value;
        }
        $this->subset_api['kind'][] = $subset;
    }

    /**
     * 添加请求参数和是否必填
     * @param $params
     * @return array|mixed
     */
    private function addParameter($params)
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
    private function addFields($fields)
    {
        if (is_array($fields)) {
            foreach ($fields as $key => &$value) {
                $value = $this->addFields($value);
                if (!is_numeric($key)) {
                    $must = $this->checkParamsMust($key) ? 1 : 0;
                    if ($must === 0) {
                        $key = substr($key, 1);
                    }
                    $value = array_merge($value, $this->doc_params[$key], ['is_must' => $must]);
                }
            }
            return $fields;
        } else {
            $must = $this->checkParamsMust($fields) ? 1 : 0;
            if ($must === 0) {
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
    private function getRequestParams()
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
            $this->interfaceLogService->update(
                ['method' => $method, 'request' => json_encode_ex($this->param)],
                ['tbid' => $this->log_id]
            );
        }
    }

    /**
     * 校验必须数据是否完整
     * @param $parameter
     * @param array $params
     * @param array $file
     * @param string $tip_key
     * @return array
     */
    private function checkRequestParams($parameter, $params = [], $file = [], $tip_key = '')
    {
        if (empty($params)) {
            $params = $this->param;
        }
        if (!empty($parameter)) {
            if (empty($file)) {
                $file = $_FILES;
            }

            foreach ($parameter as $key => $value) {
                $rtk = $this->removeParamsTag($key);
                if (!array_key_exists($key, $this->doc_params)) {
                    if ($this->debug) {
                        $this->outputResponseError("参数「" . $key . "」未定义");
                    }
                    $this->outputResponseError('参数未定义，请检查!');
                }

                if (is_array($value['data'])) {
                    if (!empty($params[$rtk])) {
                        foreach ($params[$rtk] as $tip_keys => &$son_params) {
                            $son_params = $this->checkRequestParams($value['data'], $son_params, -1, $tip_keys);
                        }
                    }
                }

                $param = $this->doc_params[$key]['type'] == 'file' ? $file[$rtk] : $params[$rtk];
                if ($value['is_must'] === 0 && is_null($param) && isset($value['default'])) {
                    $params[$rtk] = $value['default'];
                    continue;
                }
                if ($value['is_must'] === 1 && is_null($param)) {
                    $param_errors[] = $rtk;
                    continue;
                }

                // 验证入参是否存在与指定的数据内
                if (isset($value['list']) && is_array($value['list'])) {
                    if (!array_key_exists($param, $value['list'])) {
                        if ($this->debug) {
                            $selector_text = ' 可选值:';
                            foreach ($value['list'] as $list_k => $list_v) {
                                $selector_text .= $list_k . '(' . $list_v . '),';
                            }
                            $selector_text .= rtrim($selector_text, ',');
                            $this->outputResponseError("参数 " . $rtk . " 数值不正确,请输入正确的值。" . $selector_text);
                        }
                        $this->outputResponseError('参数数值不正确，请检查!');
                    }
                }

                // 验证参数数据类型是否正确
                switch ($value['type']) {
                    case 'string':
                        if (!is_string($param)) {
                            if ($this->debug) {
                                $this->outputResponseError("参数 " . $rtk . " 类型不正确，请传入字符串值。");
                            }
                            $this->outputResponseError('参数数据格式异常，请检查!');
                        }
                        break;
                    case 'array':
                        if (!is_array($param)) {
                            if ($this->debug) {
                                $this->outputResponseError("参数 " . $rtk . " 类型不正确，请传入数组值。");
                            }
                            $this->outputResponseError('参数数据格式异常，请检查!');
                        }
                        break;
                    case 'int':
                    case 'integral':
                        if (!is_numeric($param)) {
                            if ($this->debug) {
                                $this->outputResponseError("参数 " . $rtk . " 类型不正确，请传入数字值。");
                            }
                            $this->outputResponseError('参数数据格式异常，请检查!');
                        }
                        break;
                    case 'date':
                    case 'datetime':
                        if (strtotime($param) === false) {
                            if ($this->debug) {
                                $this->outputResponseError("参数 " . $rtk . " 类型不正确，请传入时间值。");
                            }
                            $this->outputResponseError('参数数据格式异常，请检查!');
                        }
                        break;
                }
            }

            if (!empty($param_errors)) {
                if ($this->debug) {
                    $tips = "缺少参数" . implode(',', $param_errors);
                    if ($tip_key !== '') {
                        $tips = '数据组【' . $tip_key . '】' . $tip_key;
                    }
                    $this->outputResponseError($tips);
                }
                $this->outputResponseError('缺少参数，请检查!');
            }
        }
        return $params;
    }

    /**
     * 判断是否为当前接口
     * @return bool
     */
    private function checkThisApi()
    {
        return $this->req == $this->info['req'];
    }

    /**
     * 判断是否为查看文档
     */
    private function checkDoc()
    {
        if ($_GET['doc'] == true) {
            $this->infoApiDoc();
        }
    }

    /**
     * 判断是否为模拟数据并直接输出模拟数据
     */
    private function checkSimulate()
    {
        if ($this->param['simulate'] == 1) {
            foreach ($this->info['fields'] as $key => $value) {
                $this->data[$key] = $this->generateSimulate($value);
            }
            $this->outputResponseData();
        }
    }

    /**
     * 生成模拟数据
     * @param $param
     * @return array|bool|int|string|null
     */
    private function generateSimulate($param)
    {
        switch ($param['type']) {
            case 'int':
            case 'integral':
                $simulate = rand();
                break;
            case 'string':
                $simulate = generate_random_code(rand(0, 10));
                break;
            case 'bool':
                $simulate = rand(0, 1) === 1;
                break;
            case 'array':
                $simulate = [];
                break;
            case 'date':
                $simulate = date('Y-m-d');
                break;
            case 'datetime':
                $simulate = date('Y-m-d H:i:s');
                break;
            default:
                $simulate = null;
                break;
        }
        return $simulate;
    }

    /**
     * 去除重名参数定义的#后面内容
     * @param $k
     * @return false|string
     */
    private function removeParamsTag($k)
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
     * 删除必输参数tag
     * @param $k
     * @return false|string
     */
    private function removeParamMustTag($k)
    {
        if ($this->checkParamsMust($k) === false) {
            $k = substr($k, 1);
        }
        return $k;
    }

    /**
     * 输出整个api文档
     */
    private function listApiDoc()
    {
        $show_api = get_env('system.show_api_doc');
        $echo_api = [
            'show_api' => $show_api,
            'api_title' => PLATFORM,
            'api_list' => []
        ];
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
            $echo_api['api_list'] = $api_list;
        }
        $this->outputResponseData(json_encode_ex($echo_api), 2, false);
        exit;
    }

    /**
     * 输出单个api文档详情
     */
    private function infoApiDoc()
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
            $data_url .= $this->info['method'] == 'GET' ? '?' : '';
            $data_url .= $this->listTest($this->info['parameter'], $this->info['method']);
        }
        if (!empty($this->info['fields'])) {
            $api_info['response_param'] = $this->listFields($this->info['fields']);
            $api_info['json_string'] = json_encode($this->listJson($this->fields), JSON_PRETTY_PRINT);
        }
        $api_info['test_url'] = '{{host}}/' . $this->module_name . '/' . str_replace('.', '/', $this->info['req']) . $data_url;
        $api_info['post_test'] = $data_url;
        $this->outputResponseData(json_encode_ex($api_info), 2);
    }

    /**
     * 输出json格式响应示例
     * @param $list
     * @return array
     */
    private function listJson($list)
    {
        $json_data = [];
        foreach ($list as $k => $v) {
            if (is_numeric($k)) {
                $v = $this->removeParamsTag($v);
                $v = $this->removeParamMustTag($v);
                $json_data[$v] = '';
                unset($list[$k]);
            } else {
                $k = $this->removeParamsTag($k);
                $k = $this->removeParamMustTag($k);
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
    private function listTest($parameter, $mode, $key = '')
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
    private function listFields($fields)
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
                    $dg['is_must'] = $this->checkParamsMust($k) ? 1 : 0;
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
    private function listParameters($parameters)
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
     * 处理输出sql报错信息
     * @param object $e
     * @return void
     */
    public function outputPdoException($e)
    {
        $message = $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine() . '<br>Stack trace:<br>';
        $trace = $e->getTrace();
        foreach ($trace as $key => $value) {
            $message .= '#' . $key . ' ' . $value['file'] . '(' . $value['line'] . '): ' . $value['class'] . $value['type'] . $value['function'] . '()<br>';
        }

        $sqlerrorService = $this->loadService("interfaceSqlerrorLog");
        $sqlerrorService->add([
            'api' => $this->module_name . '.' . $this->info['req'],
            'err_content' => $message,
        ]);
        if ($this->debug) {
            $this->outputResponseError($message);
        }
        $this->outputResponseError('系统繁忙，请稍后重试！');
    }

    /**
     * 接口响应错误输出
     * @param $error
     * @param int $status
     */
    public function outputResponseError($error, $status = 1)
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
     * @param bool $write_log
     */
    public function outputResponseData($data = [], $mode = 1, $write_log = true)
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
        if ($this->open_api_log && $write_log) {
            $this->interfaceLogService->update(['response' => $this->data], ['tbid' => $this->log_id]);
        }
        exit;
    }
}
