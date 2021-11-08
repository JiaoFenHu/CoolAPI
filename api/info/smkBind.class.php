<?php
class smkBind
{
    public $db;
    public $api;
    public $table;
    private $key = 'wertwsx!.*';

    function __construct($api)
    {
        global $db;
        $this->db = $db;
        $this->api = $api;
        $this->table = 'area';
    }

    // 验证
    function check($set, $msg = '')
    {
        $check = $this->db->has($this->table, $set);
        if ($check === false && $msg !== '') {
            $this->api->dataerror($msg);
        }
        return $check;
    }

    // 详情
    function get($set, $column = '*')
    {
        return $this->db->get($this->table, $column, $set);
    }

    // 列表
    function get_list($set, $column = '*')
    {
        return $this->db->select($this->table, $column, $set);
    }

    // 统计
    function get_count($set)
    {
        return $this->db->count($this->table, $set);
    }

    // 新增
    function add($set)
    {
        $set['#createtime'] = 'now()';
        $set['#edittime'] = 'now()';
        return $this->db->insert($this->table, $set);
    }

    // 编辑
    function update($set, $id)
    {
        $set['#edittime'] = 'now()';
        return $this->db->update($this->table, $set, array('tbid' => $id));
    }


    /**
     * 给市民卡密码进行加密
     * @param string $password
     * @return string
     */
    public function card_encrypt($password)
    {
        $sign_str = $this->key . $password . $this->key;
        return $this->encrypt($sign_str);
    }


    /**
     * 创建签名
     * @param array $param
     * @return string
     */
    public function create_sign($param)
    {
        if (!is_array($param)) $this->api->dataerror("签名错误！");
        if (isset($param['timestamp']) && $param['timestamp'] < strtotime('-10 minute')) {
            $this->api->dataerror("签名错误！");
        }

        ksort($param);
        $key_num = 1;
        $sign_str = '';
        foreach ($param as $k => $v) {

            if ($k !== 'sign') {
                if ($key_num === 1) {
                    $sign_str .= "{$k}={$v}";
                } else {
                    $sign_str .= "&{$k}={$v}";
                }
                $key_num++;
            }
        }

        $sign_str = $this->key . $sign_str . $this->key;
        return $this->encrypt($sign_str);
    }


    /**
     * 加密
     * @param string $sign_str
     * @return string
     */
    public function encrypt($sign_str)
    {
        return strtoupper(sha1(strtoupper(md5($sign_str))));
    }
}
