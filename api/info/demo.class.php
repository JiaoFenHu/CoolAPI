<?php
class demo
{
    public $db;
    public $api;
    public $table;

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
     * 获取地区地址列表
     * @param int $level
     * @param array $limit
     * @param int $parent_code
     * @param string $type
     * @return array
     */
    function get_area_list_by_level($level, $limit, $parent_code = 0, $type = 'PROVINCE')
    {
        $data = [];
        if ($level > 0) {

            $level--;

            $set = [
                'parent_code' => $parent_code,
                'type' => $type
            ];

            switch ($type) {
                case 'PROVINCE':
                    $type = 'CITY';
                    if (!empty($limit)) {
                        $set['limit'] = $limit;
                    }
                    break;
                case 'CITY':
                    $type = 'AREA';
                    break;
                case 'AREA':
                    $type = 'STREET';
                    break;
            }
            $rst = $this->get_list($set);

            foreach ($rst as $value) {
                $tmp = [];
                $tmp['area_id'] = $value['tbid'];
                $tmp['name'] = $value['name'];
                $tmp['type'] = $value['type'];
                $tmp['area_list'] = $this->get_area_list_by_level($level, $limit, $value['code'], $type);
                $data[] = $tmp;
                unset($tmp);
            }
        }
        return $data;
    }

    /**
     * 验证地区地址是否合法
     * @param string $name
     * @param string $err_msg
     * @param int $code
     * @return mixed
     */
    function check_area($name, $err_msg, $code = 0)
    {
        $set = [
            'name' => $name,
            'parent_code' => $code
        ];

        $parent_code = $this->get($set, 'code');
        if (empty($parent_code)) $this->api->dataerror($err_msg);
        return $parent_code;
    }
}
