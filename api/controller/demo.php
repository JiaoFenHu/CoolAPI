<?php
$this->subset_api = array('name' => 'demo');
if (isset($this->module)) {
    $that = $this->module;
    //配置公用参数
    $this->doc_param['token'] = array('type' => 'string', 'summary' => 'token');
    $this->doc_param['id'] = array('type' => 'int', 'summary' => 'id');
    $this->doc_param['hashId'] = array('type' => 'string', 'summary' => 'hashId密文');
    $this->doc_param['length'] = array('type' => 'int', 'summary' => '密文长度');
    $this->doc_param['mode'] = array('type' => 'int', 'summary' => '类型', 'list' => [1 => '纯数字', 2 => '纯英文', 3 => '混合']);
    $this->doc_param['pattern'] = array('type' => 'int', 'summary' => '选项', 'default' => 1);

    if (empty($this->req)) {
        return;
    }
}

$this->info = array('req' => 'test');
$this->info['summary'] = '测试';
if ($this->checkThisApi()) {
    $this->info['method'] = 'GET';
    $this->parameter = array();
    $this->fields = array();
    $param = $this->apiInit();
    //具体执行代码
    $jwt = $this->loadService("jwtAuthorize");

    $token = $jwt->createToken(1);
    prints($token, true, false);
    // eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiIsIm5hbWUiOiJ0ZXN0In0.eyJpc3MiOiJodHRwczpcL1wvaHpzbWsubG9va2JpLmNvbVwvIiwiYXVkIjoiaHR0cHM6XC9cL2h6c21rLmxvb2tiaS5jb21cLyIsImp0aSI6IjExYlJuWGtsYkFWTjAzVTMiLCJpYXQiOjE2NDU2MDg5NDUsIm5iZiI6MTY0NTYwODk0NSwiZXhwIjoxNjQ1Njk1MzQ1LCJtZW1iZXJfaWQiOjF9.QX_AvQk7JO5geCIMjN7Mw_tg_UCjBzbfam8mr9BdrOs
    //输出返回数据
    $parse = $jwt->parseToken($token);
    prints($parse, false);

    $this->outputResponseData($data);
}
//添加所有接口参数
$this->addsubset();
