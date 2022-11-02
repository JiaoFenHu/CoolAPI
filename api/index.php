<?php
declare(strict_types=1);
/**
 * @anthor hujiaofen
 * @ver 1.0
 * @changeDate 2022-02-19
 * @desc 入口文件
 */
require(dirname(__DIR__) . '/globals.php');
require(COMPOSER_DIR . 'autoload.php');

$api = new api("controller");

class api
{
    const CODE_OK = 200;    // 请求响应成功
    const CODE_LOGIN_VALID = 401;   // 登录失效，未登录
    const CODE_LACK_PARAM = 403;    // 缺少参数
    const CODE_ERROR = 500; // 请求响应异常

    #接口文档数组
    public array $api = [];
    #接口文档单个接口数组
    public array $info = [];
    #接口文档预配置参数数组
    public array $docParams = [];
    #接口文档模块数组
    public array $subsetApi = [];
    #接口文档模块输入参数
    public array $parameter = [];
    #接口文档模块输出参数
    public array $fields = [];
    #输入参数
    public $param;
    #返回数据
    public array $data = [];
    #接口req参数
    public $req;
    #模块名
    public $moduleName;
    #站点
    public string $station;
    #模块类
    public bool $loadFieldDoc = false;
    #日志id
    public string $logId = '0';
    #接口目录
    private string $interfaceDir;
    #debug
    private bool $debug;
    #接口访问日志开关
    private bool $openApiLog;
    #接口日志服务
    private \service\InterfaceLog $interfaceLogService;
    #实例化service组
    private static array $moduleInstances = [];
    #用户ID
    public ?int $memberId;
    #请求头
    public array $headers = [];
    #权限组
    public array $authorization = [];
    #签名认证字段
    public string $authField;

    public function __construct($app)
    {
        $this->debug = getProEnv('system.openApiDebug', false) ? true : false;
        $this->openApiLog = getProEnv('system.openRequestLog', false) ? true : false;
        $this->authField = getProEnv('system.authFiled', 'token');
        $this->interfaceDir = API_DIR . $app;

        // 全局捕获异常
        try {
            $options = getopt("q:");
            if (!empty($options)) {
                $_REQUEST['req'] = $options['q'];
            }

            // 文档
            if ($_REQUEST['req'] == "" && $_REQUEST['doc'] == 1) {
                $apiStations = \repository\basic\Configure::app('apiStationDirs');
                $infoDirList = [];
                $dh = opendir($this->interfaceDir);
                while (($file = readdir($dh)) !== false) {
                    if ($file !== '.' && $file !== '..') {
                        $infoDirList[] = [
                            'name' => $apiStations[$file] ?? "站点「{$file}」",
                            'client' => $file
                        ];
                    }
                }
                closedir($dh);
                $this->responseOk(jsonEncodeExtend($infoDirList), 2, false);
            }

            // 接口
            if (!empty($_REQUEST['req']) && $_REQUEST['req'] !== 'doc') {
                $this->req = explode('.', $_REQUEST['req']);
                $this->station = array_shift($this->req);
                if (empty($this->req)) {
                    // $this->api = ['api_name' => PLATFORM . '接口文档'];
                    $this->listApiDir($this->station);
                    $this->listApiDoc();
                }
                $this->moduleName = array_shift($this->req);
                $this->req = implode('.', $this->req);
                $this->loadFieldDoc = true;
                $this->initParamDoc();
                $this->includeFile("{$this->interfaceDir}/{$this->station}/{$this->moduleName}.php");
                $this->responseOk("Interface Does Not Respond To Data!", 2);
            }
            $this->responseOk("No Permission To Access Interface Documents!", 2, false);
        } catch (TypeError $te) {
            $this->responseError($te->getMessage());
        } catch (Throwable $e) {
            if ($this->debug) {
                $detailError = $this->getExceptionDetail($e);
                $this->responseOk($detailError, 2, false);
            }
            $this->responseError($e->getMessage());
        }
    }

    /**
     * 初始化常用参数
     */
    private function initParamDoc(): void
    {
        $this->docParams['list'] = ['type' => 'array[object]', 'summary' => '列表数据'];
        $this->docParams['from'] = ['type' => 'int', 'summary' => '每页开始条数'];
        $this->docParams['limit'] = ['type' => 'int', 'summary' => '每页条目数'];
        $this->docParams['total'] = ['type' => 'int', 'summary' => '总数量'];
        $this->docParams['success'] = ['type' => 'bool', 'summary' => '是否成功'];
        $this->docParams['createTime'] = ['type' => 'datetime', 'summary' => '创建时间'];
        $this->docParams[$this->authField] = ['type' => 'string', 'summary' => '登录身份认证'];
    }

    /**
     * 遍历加载接口文件夹，加载接口
     * @param string $station
     */
    private function listApiDir(string $station): void
    {
        $interfaceDir = $this->interfaceDir . DS . $station;
        if (!is_dir($interfaceDir)) {
            $this->responseError('接口文件目录不正确！');
        }

        $dh = opendir($interfaceDir);
        if ($dh === false) {
            $this->responseError('无法读取接口文件目录！');
        }

        while (($file = readdir($dh)) !== false) {
            $interfacePath = $interfaceDir . DS . $file;
            if (substr($file, -3) === 'php' && is_file($interfacePath)) {
                $moduleName = substr($file, 0, -4);
                include $interfacePath;
                $this->api['api_list'][$moduleName] = $this->subsetApi;
            }
        }
        closedir($dh);
    }

    /**
     * 加载单个文件
     * @param string $file
     */
    private function includeFile(string $file): void
    {
        if (!file_exists($file)) {
            if ($this->debug) {
                $this->responseError('文件路径:' . $file . '不存在');
            }

            $this->responseError('文件缺失或不存在该接口');
        }

        require_once($file);
    }

    /**
     * 校验输出数据是否完整
     * @param array $fields
     * @param array $data
     */
    private function checkResponseDataFields(array $fields, array $data): void
    {
        foreach ($fields as $key => $value) {
            if (is_array($value)) {
                $key = $this->checkFields($data, $key);
                if (!empty($data[$key])) {
                    if (!is_array($data[$key])) {
                        $this->responseError("响应参数「{$key}」数据类型异常！");
                    }

                    if ($this->docParams[$key]['type'] == 'array') {
                        foreach ($data[$key] as $objField => $field) {
                            if (!is_array($field)) {
                                $this->responseError("响应参数「{$key}」数据类型异常！");
                            }
                            $this->checkResponseDataFields($value, $field);
                        }
                    }else {
                        $this->checkResponseDataFields($value, $data[$key]);
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
    private function checkFields(array $data, string $field): string
    {
        $origField = $field;
        $field = $this->removeParamsTag($field);
        $must = $this->checkParamsMust($field);
        if ($must === false) {
            $field = substr($field, 1);
            $origField = substr($origField, 1);
        }

        if ((!is_array($data) || !array_key_exists($field, $data)) && $must) {
            $this->responseError("响应参数「{$field}」缺失!");
        }

        if (array_key_exists($field, $data)) {
            $dataType = $this->docParams[$origField]['type'];
            switch ($dataType) {
                case 'string':
                    if (!is_string($data[$field])) {
                        $this->responseError("响应参数:「{$field}」数据类型异常!");
                    }
                    break;
                case 'bool':
                    if (!is_bool($data[$field])) {
                        $this->responseError("响应参数:「{$field}」数据类型异常!");
                    }
                    break;
                case 'int':
                case 'integer':
                    if (!is_numeric($data[$field])) {
                        $this->responseError("响应参数:「{$field}」数据类型异常!");
                    }
                    break;
                case 'array[object]':
                    if (!is_array($data[$field])) {
                        $this->responseError("响应参数:「{$field}」数据类型异常!");
                    }
                    if (count($data[$field]) !== 0) {
                        foreach ($data[$field] as $objK => $objV) {
                            if (!is_int($objK) || !is_array($objV)) {
                                $this->responseError("响应参数:「{$field}」数据类型异常!");
                            }
                        }
                    }
                    break;
                case 'array[int]':
                    if (!is_array($data[$field])) {
                        $this->responseError("响应参数:「{$field}」数据类型异常!");
                    }
                    if (count($data[$field]) !== 0) {
                        foreach ($data[$field] as $intK => $intV) {
                            if (!is_int($intK) || !is_int($intV)) {
                                $this->responseError("响应参数:「{$field}」数据类型异常!");
                            }
                        }
                    }
                    break;
                case 'array[string]':
                    if (!is_array($data[$field])) {
                        $this->responseError("响应参数:「{$field}」数据类型异常!");
                    }
                    if (count($data[$field]) !== 0) {
                        foreach ($data[$field] as $strK => $strV) {
                            if (!is_int($strK) || !is_string($strV)) {
                                $this->responseError("响应参数:「{$field}」数据类型异常!");
                            }
                        }
                    }
                    break;
                case 'date':
                case 'datetime':
                    if (strtotime($data[$field]) === false) {
                        $this->responseError("响应参数:「{$field}」数据类型异常!");
                    }
                    break;
            }
        }
        return $field;
    }

    /**
     * 接口预处理方法
     * @return mixed
     * @throws Exception
     */
    private function apiInit()
    {
        //添加参数
        $this->addParam();
        //是否为查看文档模式
        $this->checkDoc();
        //验证请求头
        $this->checkRequestHeaders();
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
    private function addParam(): void
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
    private function addSubset(): void
    {
        $subset = [];
        foreach ($this->info as $key => $value) {
            $subset[$key] = $value;
        }
        $this->subsetApi['kind'][] = $subset;
    }

    /**
     * 添加请求参数和是否必填
     * @param $params
     * @return array
     */
    private function addParameter($params): array
    {
        if (is_array($params)) {
            $parameter = [];
            foreach ($params as $key => $value) {
                if (!is_numeric($key)) {
                    $is_must = $this->checkParamsMust($key);
                    if ($is_must === false) {
                        $key = substr($key, 1);
                    }

                    $parameter[$key] = $this->docParams[$key];
                    $parameter[$key]['is_must'] = $is_must;
                    $parameter[$key]['data'] = $this->addParameter($value);
                } else {
                    $is_must = $this->checkParamsMust($value);
                    if ($is_must === false) {
                        $value = substr($value, 1);
                    }

                    $parameter[$value] = $this->docParams[$value];
                    $parameter[$value]['is_must'] = $is_must;
                }
            }
            return $parameter;
        } else {
            $is_must = $this->checkParamsMust($params);
            if ($is_must === false) {
                $params = substr($params, 1);
            }
            $temp = $this->docParams[$params];
            $temp['is_must'] = $is_must;
            return $temp;
        }
    }

    /**
     * 添加返回参数和示例值
     * @param $fields
     * @return array|mixed
     */
    private function addFields($fields): array
    {
        if (is_array($fields)) {
            foreach ($fields as $key => &$value) {
                $value = $this->addFields($value);
                if (!is_numeric($key)) {
                    $must = $this->checkParamsMust($key);
                    if ($must === 0) {
                        $key = substr($key, 1);
                    }
                    $value = array_merge($value, $this->docParams[$key], ['is_must' => $must]);
                }
            }
            return $fields;
        } else {
            $must = $this->checkParamsMust($fields);
            if ($must === 0) {
                $fields = substr($fields, 1);
            }
            if (empty($this->docParams[$fields])) {
                $this->responseError("配置的输出参数" . $fields . "没有定义");
            }

            $temp = $this->docParams[$fields];
            $temp['name'] = $fields;
            $temp['is_must'] = $must;
            return $temp;
        }
    }

    /**
     * 根据method获得param
     */
    private function getRequestParams(): void
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
        if ($this->openApiLog && $this->logId !== '0') {
            $this->interfaceLogService->update(
                ['method' => $method, 'request' => jsonEncodeExtend($this->param)],
                ['tbid' => $this->logId]
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
        if (!empty($parameter) && is_array($params)) {
            if (empty($file)) {
                $file = $_FILES;
            }

            foreach ($parameter as $key => $value) {
                $rtk = $this->removeParamsTag($key);
                if (!array_key_exists($key, $this->docParams)) {
                    $this->responseError("参数「{$key}」未定义！");
                }

                if (is_array($value['data'])) {
                    if (!empty($params[$rtk])) {
                        foreach ($params[$rtk] as $tip_keys => &$son_params) {
                            $son_params = $this->checkRequestParams($value['data'], $son_params, -1, $tip_keys);
                        }
                    }
                }

                $param = $this->docParams[$key]['type'] == 'file' ? $file[$rtk] : $params[$rtk];
                if ($value['is_must'] === false && is_null($param) && isset($value['default'])) {
                    $params[$rtk] = $value['default'];
                    continue;
                }
                if ($value['is_must'] === true && is_null($param)) {
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
                            $this->responseError("参数 " . $rtk . " 数值不正确,请输入正确的值。" . $selector_text);
                        }
                        $this->responseError('参数可选值异常!');
                    }
                }

                // 验证参数数据类型是否正确
                switch ($value['type']) {
                    case 'string':
                        if (!is_string($param)) {
                            if ($this->debug) {
                                $this->responseError("参数 " . $rtk . " 类型不正确，请传入字符串值。");
                            }
                            $this->responseError('参数数据格式异常!');
                        }
                        break;
                    case 'array[int]':
                    case 'array[string]':
                        if (!is_array($param)) {
                            if ($this->debug) {
                                $this->responseError("参数 " . $rtk . " 类型不正确，请传入数组值。");
                            }
                            $this->responseError('参数数据格式异常!');
                        }
                        break;
                    case 'int':
                    case 'integer':
                        if (!is_numeric($param)) {
                            if ($this->debug) {
                                $this->responseError("参数 " . $rtk . " 类型不正确，请传入数字值。");
                            }
                            $this->responseError('参数数据格式异常!');
                        }
                        break;
                    case 'date':
                    case 'datetime':
                        if (strtotime($param) === false) {
                            if ($this->debug) {
                                $this->responseError("参数 " . $rtk . " 类型不正确，请传入时间值。");
                            }
                            $this->responseError('参数数据格式异常!');
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
                    $this->responseError($tips);
                }
                $this->responseError('缺少参数，请检查!');
            }
        }
        return $params;
    }

    /**
     * 判断是否为当前接口
     * @return bool
     */
    private function checkThisApi(): bool
    {
        return $this->req == $this->info['req'];
    }

    /**
     * 判断是否为查看文档
     * @throws Exception
     */
    private function checkDoc(): void
    {
        if (isset($_GET['doc']) && $_GET['doc'] == true) {
            $this->infoApiDoc();
        }

        //写入请求日志
        if ($this->openApiLog) {
            $this->interfaceLogService = $this->loadService("InterfaceLog");
            $this->logId = $this->interfaceLogService->add([
                'ip' => getClientIp(true),
                'url' => $_SERVER['SCRIPT_NAME'],
                'api' => $_REQUEST['req'],
            ]);
        }
    }

    /**
     * 验证请求header
     * @return bool
     */
    private function checkRequestHeaders(): bool
    {
        $headers = array_unique($this->headers);
        if (empty($headers)) {
            return true;
        }

        $realHeaders = [];
        foreach ($headers as $hv) {
            $isMust = $this->checkParamsMust($hv);
            $hv = $this->removeParamsTag($this->removeParamMustTag($hv));
            $realHeaders[$hv] = $isMust;
        }

        $HTTPHeaders = getRequestHeaders();
        foreach ($realHeaders as $hName => $must) {
            if ($must && !array_key_exists($hName, $HTTPHeaders)) {
                $this->responseError("缺少请求头：{$hName}", self::CODE_LACK_PARAM);
            }

            if ($hName == $this->authField && array_key_exists($hName, $HTTPHeaders)) {
                $this->checkTokenAuthorize($HTTPHeaders[$hName]);
            }
        }
        return true;
    }

    /**
     * 验证token
     * @param $token
     * @return mixed
     */
    private function checkTokenAuthorize($token)
    {
        $jwt = $this->loadService('JwtAuthorize');
        return $jwt->verifyToken($token);
    }

    /**
     * 判断是否为模拟数据并直接输出模拟数据
     * @throws Exception
     */
    private function checkSimulate(): void
    {

        if (isset($_GET['coolSimulate'])) {
            $simulateData = $this->listJson($this->fields);
            $this->responseOk($simulateData);
        }
    }

    /**
     * 生成模拟数据
     * @param $param
     * @return array|bool|int|string|null
     * @throws Exception
     */
    private function generateSimulate($param)
    {
        switch ($param['type']) {
            case 'int':
            case 'integer':
                $simulate = random_int(0, 9999);
                break;
            case 'string':
                $simulate = generateRandomCode(rand(0, 10));
                break;
            case 'bool':
                $simulate = random_int(0, 1) === 1;
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
    private function removeParamsTag($k): string
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
    private function checkParamsMust($k): bool
    {
        return substr($k, 0, 1) !== '@';
    }

    /**
     * 删除必输参数tag
     * @param $k
     * @return false|string
     */
    private function removeParamMustTag($k): string
    {
        if ($this->checkParamsMust($k) === false) {
            $k = substr($k, 1);
        }
        return $k;
    }

    /**
     * 获取请求头字段解释
     * @return array
     */
    private function getHeadersDoc(): array
    {
        $this->docParams;
        $realHeaders = [];
        foreach ($this->headers as $hv) {
            $isMust = $this->checkParamsMust($hv);
            $hv = $this->removeParamMustTag($hv);

            if (!array_key_exists($hv, $this->docParams)) {
                if (PROJECT_ENV !== 'production') {
                    $this->responseError("请求头「{$hv}」字段说明未定义！");
                }
                $this->docParams[$hv]['summary'] = "";
                $this->docParams[$hv]['type'] = "string";
                $this->docParams[$hv]['list'] = [];
            }

            $tmp = $this->docParams[$hv];
            $tmp['is_must'] = $isMust;
            $tmp['name'] = $this->removeParamsTag($hv);
            $realHeaders[] = $tmp;
            unset($tmp);
        }
        return $realHeaders;
    }

    /**
     * 输出整个api文档
     */
    private function listApiDoc(): void
    {
        $show_api = getProEnv('system.showApiDoc');
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
        $this->responseOk(jsonEncodeExtend($echo_api), 2, false);
        exit;
    }

    /**
     * 输出单个api文档详情
     * @throws Exception
     */
    private function infoApiDoc(): void
    {
        $show_api = getProEnv('system.showApiDoc');
        if ($show_api === false) {
            $this->responseOk(new stdClass(), 2, false);
        }

        $api_info = [
            'api_name' => $this->info['summary'],
            'api_module' => $this->subsetApi['name'],
            'api_uri' => $this->moduleName . '/' . str_replace('.', '/', $this->info['req']),
            'api_url' => API_DOMAIN . 'api/' . $this->station . '/' . $this->moduleName . '/' . str_replace('.', '/', $this->info['req']),
            'method' => $this->info['method'],
            'authorizations' => [],
            'request_headers' => $this->getHeadersDoc(),
            'request_param' => [],
            'response_param' => [],
            'json_string' => '{}'
        ];
        $data_url = '';
        if (!empty($this->authorization)) {
            $api_info['authorizations'] = $this->getAuthorizations();
        }
        if (!empty($this->info['parameter'])) {
            $api_info['request_param'] = $this->listParameters($this->info['parameter']);
            $data_url .= $this->info['method'] == 'GET' ? '?' : '';
            $data_url .= $this->listTest($this->info['parameter'], $this->info['method']);
        }
        if (!empty($this->info['fields'])) {
            $api_info['response_param'] = $this->listFields($this->info['fields']);

            $resp = [];
            $resp['data'] = $this->listJson($this->fields);
            $resp['msg'] = "";
            $resp['code'] = 200;
            $api_info['json_string'] = json_encode($resp, JSON_PRETTY_PRINT);
        }
        $api_info['test_url'] = '{{host}}/' . $this->moduleName . '/' . str_replace('.', '/', $this->info['req']) . $data_url;
        $api_info['post_test'] = strtoupper($this->info['method']) == 'GET' ? "" : $data_url;
        $this->responseOk(jsonEncodeExtend($api_info), 2, false);
    }

    /**
     * 获取接口权限
     * @return array
     * @throws \repository\exception\CoolException
     */
    private function getAuthorizations(): array
    {
        $authorizations = [];
        $authDoc = \repository\basic\Configure::app('authorizationDoc', []);
        foreach ($this->authorization as $key => $val) {
            if (is_numeric($key)) {
                $authName = $authDoc[$val] ?? "unknow";
                $authorizations[] = [
                    'authorization' => "{$this->moduleName}.{$val}",
                    'name' => "{$this->subsetApi['name']}[{$authName}]"
                ];
                continue;
            }
            $authorizations[] = [
                'authorization' => "{$this->moduleName}.{$key}",
                'name' => "{$this->subsetApi['name']}[{$val}]"
            ];
        }
        return $authorizations;
    }

    /**
     * 输出json格式响应示例
     * @param $list
     * @return array
     * @throws Exception
     */
    private function listJson($list): array
    {
        $json_data = [];
        foreach ($list as $k => $v) {
            if (is_numeric($k)) {
                $simulateVal = $this->generateSimulate($this->docParams[$v]);
                $v = $this->removeParamsTag($v);
                $v = $this->removeParamMustTag($v);
                $json_data[$v] = $simulateVal;
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
    private function listFields($fields): array
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
                    $dg['is_must'] = $this->checkParamsMust($k);
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
    private function listParameters($parameters): array
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
        if (!(self::$moduleInstances[$service_name] instanceof $service_name)) {
            $namespaceClass = "\\service\\{$service_name}";
            self::$moduleInstances[$service_name] = new $namespaceClass($this);
        }
        return self::$moduleInstances[$service_name];
    }

    /**
     * 处理输出sql报错信息
     * @param $e
     * @return string
     */
    public function getExceptionDetail($e): string
    {
        $message = "Message:{$e->getMessage()}" . PHP_EOL;
        $message .= "File:{$e->getFile()}" . PHP_EOL;
        $message .= "Line:{$e->getLine()}" . PHP_EOL;
        $message .= "Content:" . PHP_EOL;
        $trace = $e->getTrace();
        foreach ($trace as $key => $value) {
            $message .= "#{$key} {$value['file']}({$value['line']}):{$value['class']}{$value['type']}{$value['function']}()" . PHP_EOL;
        }
        return $message;
    }

    /**
     * 接口响应错误输出
     * @param $error
     * @param int $status
     */
    public function responseError($error, $status = self::CODE_ERROR): void
    {
        $this->data['msg'] = $error;
        $this->data['code'] = $status;
        $this->responseOk();
    }

    /**
     * 接口响应数据输出
     * @param array $data
     * @param int $mode
     * @param bool $write_log
     */
    public function responseOk($data = [], int $mode = 1, bool $write_log = true): void
    {
        if ($mode == 1) {
            if (!empty($data)) {
                if ($this->debug) {
                    $this->checkResponseDataFields($this->fields, $data);
                }
                $this->data['data'] = $data;
            }
            if (!isset($this->data['msg'])) {
                $this->data['msg'] = '';
            }
            if (!isset($this->data['code'])) {
                $this->data['code'] = self::CODE_OK;
            }

            $echo_response = empty($_GET['callback'])
                ? jsonEncodeExtend($this->data)
                : $_GET['callback'] . '(' . json_encode($this->data) . ')'; // jsonp
        } else {
            $echo_response = $data;
        }
        echo $echo_response;
        if ($this->openApiLog && $write_log && ($this->logId !== '0')) {
            $this->interfaceLogService->update(['response' => $this->data], ['tbid' => $this->logId]);
        }
        exit;
    }
}
