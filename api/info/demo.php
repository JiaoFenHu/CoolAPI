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

$this->info = array('req' => 'supply');
$this->info['summary'] = '供应商数据导入(miaoguo export)';
if ($this->checkthisapi()) {
    $this->info['method'] = 'GET';
    $this->parameter = array();
    $this->fields = array();
    $param = $this->apiinit();
    //具体执行代码
    if (REDIS_CONNECTION_STATUS) {
        global $redis;
        $res = $this->db->select(
            'supply(s)',
            [
                '[>]supply_archive(sa)' => ['s.tbid' => 'sa.supply_id']
            ],
            ['s.*', 'sa.email', 'sa.province', 'sa.city', 'sa.area', 'sa.address', 'sa.legal_person', 'sa.business_scope', 'sa.docking_person', 'sa.docking_contact', 'sa.keyword', 'sa.slogan', 'sa.intro'],
            [
                's.tbid[>]' => 0
            ]
        );
        $res = remove_db_data_num($res);

        foreach ($res as $value) {
            $redis::hset('supply', $value['account'], $value);
        }
        $data['success'] = true;
    } else {
        $this->dataerror("redis连接失败，请检查！");
    }


    //输出返回数据
    $this->echodata($data);
}
//添加所有接口参数
$this->addsubset();



$this->info = array('req' => 'partner');
$this->info['summary'] = '分销商导出(miaoguo export)';
if ($this->checkthisapi()) {
    $this->info['method'] = 'GET';
    $this->parameter = array();
    $this->fields = array();
    $param = $this->apiinit();
    //具体执行代码
    if (REDIS_CONNECTION_STATUS) {
        global $redis;
        $res = $this->db->select('partner_service', '*', ['tbid[>]' => 0]);
        $res = remove_db_data_num($res);

        foreach ($res as $value) {
            // if (!empty($value['member_id'])) {
            //     $member_info = $this->db->get('member', 'mobile,password', ['tbid' => $value['member_id']]);
            //     $value['account'] = $member_info['mobile'];
            //     $value['password'] = $member_info['password'];
            // }
            if (!empty($value['account'])) {
                $redis::hset('partner', $value['account'], $value);
            }
        }
        $data['success'] = true;
    } else {
        $this->dataerror("redis连接失败，请检查！");
    }


    //输出返回数据
    $this->echodata($data);
}
//添加所有接口参数
$this->addsubset();



$this->info = array('req' => 'ocss.partner');
$this->info['summary'] = '分销商导入(ocss import)';
if ($this->checkthisapi()) {
    $this->info['method'] = 'GET';
    $this->parameter = array();
    $this->fields = array();
    $param = $this->apiinit();
    //具体执行代码
    if (REDIS_CONNECTION_STATUS) {
        global $redis;

        $res = $redis::hgetall('partner');

        $this->db->beginTransaction();
        try {

            foreach ($res as $account => $value) {
                $value = json_decode($value, true);
                $this->db->insert('partner_service', [
                    'tbid' => $value['tbid'],
                    'account' => $account,
                    'password' => $value['password'],
                    'pass_card' => $value['pass_card'],
                    'balance' => $value['balance'],
                    'email' => $value['email'],
                    'name' => $value['name'] ?: '',
                    'docking' => $value['docking'],
                    'docking_contact' => $value['docking_contact'],
                    'province_id' => $value['province_id'],
                    'city_id' => $value['city_id'],
                    'area_id' => $value['area_id'],
                    'status' => $value['status'] == 1 ? 'NORMAL' : 'DISABLED',
                    'identity' => $value['identity'],
                    'level' => $value['level'],
                    'invitationcode' => $value['invitationcode'],
                    'partner_id' => $value['partner_id'],
                    'createtime' => $value['createtime'],
                    'edittime' => $value['edittime'],
                    'member_id' => $value['member_id'],
                    'verify' => 'SUCCESS',
                    'is_distributor' => 1,
                    '#vip_failure_time' => "DATE_ADD('{$value['createtime']}', INTERVAL 1 YEAR)"
                ]);

                $province = $this->db->get('area', 'name', ['tbid' => $value['province_id']]);
                $city = $this->db->get('area', 'name', ['tbid' => $value['city_id']]);
                $area = $this->db->get('area', 'name', ['tbid' => $value['area_id']]);

                $information_set = [
                    'name' => $value['name'] ?: '',
                    'type' => 'PERSONAL',
                    'province' => $province ?: '',
                    'city' => $city ?: '',
                    'area' => $area ?: '',
                    'address' => '',
                    'realname' => '',
                    'mobile' => $account,
                    'email' => $value['email'],
                    // 'wechat' => $value['wechat'] ?: '',
                    // 'tel' => $value['tel'] ?: '',
                    // 'fax' => $value['fax'] ?: '',
                    // 'birthday' => $value['birthday'] ?: NULL
                ];
                // if (isset($param['recommend_member']) && !empty($param['recommend_member'])) {
                //     $partner_info = $partner_service->get(['invitationcode' => $param['recommend_member']]);
                //     if (empty($partner_info)) $this->dataerror("错误的邀请码！");
                //     if ($partner_info['tbid'] == $memberId) $this->dataerror('无法填写自己的邀请码！');
                //     $information_set['recommend_member'] = $param['recommend_member'];
                // }
                $information_id = $that->db->insert('partner_verify_information', $information_set);

                $qualification_set = [
                    'name' => $param['name'] ?: '',
                    // 'code' => $param['code'],
                    // 'is_long_time' => $param['is_long_time'],
                    // 'fail_date' => $param['fail_datetime'] ?: NULL
                ];
                // switch ($type) {
                //     case "COMPANY":
                //         $qualification_set['company_img'] = remove_domain($param['company_img']);
                //         break;
                //     case "PERSONAL":
                //         $qualification_set['idcard_front'] = remove_domain($param['positive_idcode']);
                //         $qualification_set['idcard_back'] = remove_domain($param['opposite_idcode']);
                //         break;
                //     default:
                //         $this->dataerror("未知的认证类型选项！");
                // }
                $qualification_id = $that->db->insert('partner_verify_qualification', $qualification_set);

                $settlement_set = [
                    'alipay' => '',
                    // 'bank_account_name' => $param['bank_account_name'],
                    // 'bank_account' => $param['bank_account'],
                    // 'bank_name' => $param['bank_name'],
                    // 'bank_area' => $param['bank_area'],
                    // 'bank_sub_name' => $param['bank_sub_name'] ?: '',
                    // 'company_name' => $param['company_name'] ?: '',
                    // 'company_tax_id' => $param['company_tax_id'] ?: ''
                ];
                $settlement_id = $that->db->insert('partner_verify_settlement', $settlement_set);

                $verify_set = [
                    'information_id' => $information_id,
                    'qualification_id' => $qualification_id,
                    'settlement_id' => $settlement_id
                ];
                $set = [
                    'partner_id' => $value['tbid'],
                    'verify_info' => json_encode_ex($verify_set),
                    'information' => $information_id,
                    'qualification' => $qualification_id,
                    'settlement' => $settlement_id
                ];
                $that->db->insert('partner_verify', $set);
            }

            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            $this->dataerror($e->getMessage());
        }

        $data['success'] = true;
    } else {
        $this->dataerror("redis连接失败，请检查！");
    }


    //输出返回数据
    $this->echodata($data);
}
//添加所有接口参数
$this->addsubset();


$this->info = array('req' => 'ocss.supply');
$this->info['summary'] = '供应商导入(ocss import)';
if ($this->checkthisapi()) {
    $this->info['method'] = 'GET';
    $this->parameter = array();
    $this->fields = array();
    $param = $this->apiinit();
    //具体执行代码
    if (REDIS_CONNECTION_STATUS) {
        global $redis;

        $res = $redis::hgetall('supply');

        $this->db->beginTransaction();
        try {

            foreach ($res as $account => $value) {
                $value = json_decode($value, true);
                $this->db->insert('supply', [
                    'tbid' => $value['tbid'],
                    'account' => $value['docking_contact'],
                    'password' => $value['password'],
                    'name' => $value['name'] ?: '',
                    'nickname' => $value['name'] ?: '',
                    'introducer_person' => $value['introducer_person'],
                    'introducer_contact' => $value['introducer_contact'],
                    'category_partners_id' => $value['category_partners_id'],
                    'remark' => $value['remark'],
                    'status' => $value['status'],
                    'createtime' => $value['createtime'],
                    'edittime' => $value['edittime'],
                    'isrecommend' => $value['isrecommend'],
                    'is_supply' => $value['is_supply'],
                    'is_distributor' => $value['is_distributor'],
                    'proportion' => $value['proportion'],
                    'email' => $value['email'] ?: $account,
                    '#vip_failure_time' => "DATE_ADD('{$value['createtime']}', INTERVAL 1 YEAR)"
                ]);

                // $province = $this->db->get('area', 'name', ['tbid' => $value['province_id']]);
                // $city = $this->db->get('area', 'name', ['tbid' => $value['city_id']]);
                // $area = $this->db->get('area', 'name', ['tbid' => $value['area_id']]);

                $information_set = [
                    'name' => $value['name'] ?: '',
                    'type' => 'PERSONAL',
                    'province' => $value['province'] ?: '',
                    'city' => $value['city'] ?: '',
                    'area' => $value['area'] ?: '',
                    'address' => $value['address'],
                    'realname' => $value['docking_person'] ?: '',
                    'mobile' => $value['docking_contact'],
                    'email' => $value['email'] ?: $account,
                    // 'wechat' => $value['wechat'] ?: '',
                    // 'tel' => $value['tel'] ?: '',
                    // 'fax' => $value['fax'] ?: '',
                    // 'birthday' => $value['birthday'] ?: NULL
                ];
                // if (isset($param['recommend_member']) && !empty($param['recommend_member'])) {
                //     $partner_info = $partner_service->get(['invitationcode' => $param['recommend_member']]);
                //     if (empty($partner_info)) $this->dataerror("错误的邀请码！");
                //     if ($partner_info['tbid'] == $memberId) $this->dataerror('无法填写自己的邀请码！');
                //     $information_set['recommend_member'] = $param['recommend_member'];
                // }
                $information_id = $that->db->insert('supply_verify_information', $information_set);

                $qualification_set = [
                    'name' => $param['name'] ?: '',
                    // 'code' => $param['code'],
                    // 'is_long_time' => $param['is_long_time'],
                    // 'fail_date' => $param['fail_datetime'] ?: NULL
                ];
                // switch ($type) {
                //     case "COMPANY":
                //         $qualification_set['company_img'] = remove_domain($param['company_img']);
                //         break;
                //     case "PERSONAL":
                //         $qualification_set['idcard_front'] = remove_domain($param['positive_idcode']);
                //         $qualification_set['idcard_back'] = remove_domain($param['opposite_idcode']);
                //         break;
                //     default:
                //         $this->dataerror("未知的认证类型选项！");
                // }
                $qualification_id = $that->db->insert('supply_verify_qualification', $qualification_set);

                $settlement_set = [
                    'alipay' => '',
                    // 'bank_account_name' => $param['bank_account_name'],
                    // 'bank_account' => $param['bank_account'],
                    // 'bank_name' => $param['bank_name'],
                    // 'bank_area' => $param['bank_area'],
                    // 'bank_sub_name' => $param['bank_sub_name'] ?: '',
                    // 'company_name' => $param['company_name'] ?: '',
                    // 'company_tax_id' => $param['company_tax_id'] ?: ''
                ];
                $settlement_id = $that->db->insert('supply_verify_settlement', $settlement_set);

                $verify_set = [
                    'information_id' => $information_id,
                    'qualification_id' => $qualification_id,
                    'settlement_id' => $settlement_id
                ];
                $set = [
                    'supply_id' => $value['tbid'],
                    'verify_info' => json_encode_ex($verify_set),
                    'information' => $information_id,
                    'qualification' => $qualification_id,
                    'settlement' => $settlement_id,
                    'verify' => 'SUCCESS'
                ];
                $that->db->insert('supply_verify', $set);
            }

            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            $this->dataerror($e->getMessage());
        }

        $data['success'] = true;
    } else {
        $this->dataerror("redis连接失败，请检查！");
    }


    //输出返回数据
    $this->echodata($data);
}
//添加所有接口参数
$this->addsubset();


$this->info = array('req' => 'hashids');
$this->info['summary'] = 'hashid混淆加密';
if ($this->checkthisapi()) {
    $this->info['method'] = 'GET';
    $this->parameter = array('id', 'length', 'mode', ['pattern', 0]);
    $this->fields = array('hashId');
    $param = $this->apiinit();
    //具体执行代码
    $hashIds = includeclass($this, "hashId");

    $data['hashId'] = $hashIds->getHashIdString($param['id'], $param['length'], $param['mode'], $param['pattern']);
    //输出返回数据
    $this->echodata($data);
}
//添加所有接口参数
$this->addsubset();
