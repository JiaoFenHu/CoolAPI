<?php
$this->subset_api = array('name' => '地区');
if (isset($this->module)) {
    $that = $this->module;
    //配置公用参数
    $this->doc_params['token'] = array('type' => 'string', 'summary' => 'token');
    $this->doc_params['name'] = array('type' => 'string', 'summary' => '地区名称');
    $this->doc_params['level'] = array('type' => 'int', 'summary' => '获取地区列表深度', 'list' => array(
        1 => '省份',
        2 => '城市',
        3 => '地区',
        4 => '街道(全部)'
    ), 'default' => 4);
    $this->doc_params['type'] = array('type' => 'string', 'summary' => '地区县级市', 'list' => array(
        'PROVINCE' => '省份',
        'CITY' => '城市',
        'AREA' => '地区',
        'STREET' => '街道'
    ));
    $this->doc_params['is_success'] = array('type' => 'bool', 'summary' => '是否成功');
    $this->doc_params['area_id'] = array('type' => 'int', 'summary' => '地区ID');
    $this->doc_params['area_id#1'] = array('type' => 'int', 'summary' => '地区ID带#号');
    $this->doc_params['area_list'] = array('type' => 'array', 'summary' => '子地区列表');
    $this->doc_params['list'] = array('type' => 'array', 'summary' => '列表数据');
    $this->doc_params['from'] = array('type' => 'int', 'summary' => '每页开始条数');
    $this->doc_params['limit'] = array('type' => 'int', 'summary' => '每页条目数');
    $this->doc_params['count'] = array('type' => 'int', 'summary' => '总数量');

    if (empty($this->req)) {
        return;
    }
}


$this->info = array('req' => 'info');
$this->info['summary'] = '详情测试';
if ($this->checkThisApi()) {
    $this->info['method'] = 'GET';
    $this->parameter = array('@level', '@from', '@limit', 'list' => ['@area_id#1', 'from']);
    $this->fields = array('list' => array('@area_id', 'name', 'type', 'area_list'), 'count');
    $param = $this->apiInit();
    //具体执行代码
    //输出返回数据
    $this->echodata($data);
}
//添加所有接口参数
$this->addsubset();
