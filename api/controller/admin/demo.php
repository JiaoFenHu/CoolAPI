<?php
$this->subsetApi = ['name' => 'demo'];
if ($this->loadFieldDoc) {
    $this->docParams['id'] = ['type' => 'int', 'summary' => 'id'];
    $this->docParams['hashId'] = ['type' => 'string', 'summary' => 'hashId密文'];
    $this->docParams['length'] = ['type' => 'int', 'summary' => '密文长度'];
    $this->docParams['mode'] = ['type' => 'int', 'summary' => '类型', 'list' => [1 => '纯数字', 2 => '纯英文', 3 => '混合']];
    $this->docParams['pattern'] = ['type' => 'int', 'summary' => '选项', 'default' => 1];
}

$this->info = ['req' => 'test.list'];
$this->info['summary'] = '列表测试';
if ($this->checkThisApi()) {
    $this->info['method'] = 'GET';
    $this->authorization = ['browse'];
    $this->headers = ['@token'];
    $this->parameter = ['hashId'];
    $this->fields = ['list' => ['mode', 'hashId', 'success', 'createTime'], 'total'];
    $param = $this->apiInit();
    //具体执行代码
    $jwt = $this->loadService("jwtAuthorize");

    $data = [];
    $data['total'] = 10;
    $data['list'][] = [
        'mode' => 1,
        'hashId' => 'Sxvdfg',
        'success' => true,
        'createTime' => date('Y-m-d H:i:s')
    ];
    $data['list'][] = [
        'mode' => 2,
        'hashId' => 'Sxvdfg',
        'success' => false,
        'createTime' => date('Y-m-d H:i:s')
    ];

    $this->responseOk($data);
}
//添加所有接口参数
$this->addSubset();


$this->info = ['req' => 'test.infos'];
$this->info['summary'] = '详情字段测试';
if ($this->checkThisApi()) {
    $this->info['method'] = 'GET';
    $this->authorization = ['browse', 'detail' => '查看详情'];
    $this->headers = ['@token'];
    $this->parameter = ['hashId'];
    $this->fields = ['list' => ['mode', 'hashId', 'success', 'createTime'], 'total'];
    $param = $this->apiInit();
    //具体执行代码
    $jwt = $this->loadService("jwtAuthorize");

    $data = [];
    $data['total'] = 10;
    $data['list'][] = [
        'mode' => 1,
        'hashId' => 'Sxvdfg',
        'success' => true,
        'createTime' => date('Y-m-d H:i:s')
    ];
    $data['list'][] = [
        'mode' => 2,
        'hashId' => 'Sxvdfg',
        'success' => false,
        'createTime' => date('Y-m-d H:i:s')
    ];

    $this->responseOk($data);
}
//添加所有接口参数
$this->addSubset();
