<?php
$this->subset_api = array('name' => 'demo');
if (isset($this->module)) {
    $that = $this->module;
    //配置公用参数
    $this->infoarr['token'] = array('type' => 'string', 'summary' => 'token');
    $this->infoarr['id'] = array('type' => 'int', 'summary' => 'id');
    $this->infoarr['hashId'] = array('type' => 'string', 'summary' => 'hashId密文');
    $this->infoarr['length'] = array('type' => 'int', 'summary' => '密文长度');
    $this->infoarr['mode'] = array('type' => 'int', 'summary' => '类型', 'list' => [1 => '纯数字', 2 => '纯英文', 3 => '混合']);
    $this->infoarr['pattern'] = array('type' => 'int', 'summary' => '选项', 'default' => 1);

    if (empty($this->req)) {
        return;
    }
}

$this->info = array('req' => 'hashids');
$this->info['summary'] = 'hashid混淆加密';
if ($this->checkthisapi()) {
    $this->info['method'] = 'GET';
    $this->parameter = array();
    $this->fields = array();
    $param = $this->apiinit();
    //具体执行代码
    $hashIds = include_class($this, "hashId");

    // $data['hashId'] = $hashIds->getHashIdString($param['id'], $param['length'], $param['mode'], $param['pattern']);
    $set = [
        'd.account' => '17610062223',
        'd.password' => '123123'
    ];
    $join = [
        '[>]department_job(dj)' => ['d.tbid' => 'dj.department_id']
    ];
    $column = ['dj.name[dname]','dj.type[dtype]'];
    $this->db->get('department(d)', $join, $column, $set);
    // $that->get_join_list();
    //输出返回数据
    $this->echodata($data);
}
//添加所有接口参数
$this->addsubset();
