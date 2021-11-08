<?php
$this->subset_api = ['name' => '绑定市民卡'];
if (isset($this->module)) {
    $that = $this->module;
    //配置公用参数
    $this->infoarr['token'] = ['type' => 'string', 'summary' => 'token'];
    $this->infoarr['name'] = ['type' => 'string', 'summary' => '名称'];

    if (empty($this->req)) {
        return;
    }
}

$this->info = ['req' => 'bind.test'];
$this->info['summary'] = '市民卡绑定测试';
if ($this->checkthisapi()) {
    $this->info['method'] = 'GET';
    $this->parameter = array();
    $this->fields = array();
    $param = $this->apiinit();
    //具体执行代码

    $set = [
        'member_id' => 1,
        'card_number' => '1100080415002161',
        'card_password' => $that->card_encrypt('0B044qv1D716envw'),
        'timestamp' => time()
    ];
    $set['sign'] = $that->create_sign($set);

    // var_dump($set);
    // die;

    $url = 'http://hzfk.1daas.com/api/station_member/card/bindingcard';
    $response = geturlcontent($url, 'POST', $set);
    $response = !empty($response) ? json_decode($response, true) : $this->dataerror('领取卡失败！');
    if (!empty($response['error'])) {
        $this->dataerror($response['error']);
    } else {
        // if ($response['data']['success'] !== true) {
        //     $this->dataerror($response);
        // }
        $data['response'] = $response['data'];
    }

    //输出返回数据
    $this->echodata($data);
}
//添加所有接口参数
$this->addsubset();