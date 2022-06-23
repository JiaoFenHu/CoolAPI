<?php

class orm implements interfaceORM
{
    private $db;

    public $table_name;
    public $table_name_as;

    /**
     * 初始化父级DB类
     */
    protected function initDB()
    {
        if (!($this->db instanceof DB)) {
            global $db;
            $this->db = $db;
        }
    }

    /**
     * 验证条件内的数据是否存在
     * @param array $where
     * @param array $join
     * @return bool
     */
    public function check(array $where, $join = [])
    {
        if (empty($join)) {
            return $this->db->has($this->table_name, $where);
        }
        return $this->db->has($this->table_name_as, $join, $where);
    }

    /**
     * 获取一条数据
     * @param array $where
     * @param string[] $column
     * @param array $join
     * @return mixed
     */
    public function get(array $where, $column = ['*'], $join = [])
    {
        if (empty($join)) {
            return $this->db->get($this->table_name, $column, $where);
        }
        return $this->db->get($this->table_name_as, $join, $column, $where);
    }

    /**
     * 获取列表数据
     * @param array $where
     * @param string[] $column
     * @param array $join
     * @return array
     */
    public function getList(array $where, $column = ['*'], $join = [])
    {
        if (empty($join)) {
            return $this->db->select($this->table_name, $column, $where);
        }
        return $this->db->select($this->table_name_as, $join, $column, $where);
    }

    /**
     * 添加数据
     * @param array $set
     * @return string
     */
    public function add(array $set)
    {
        $set['#create_time'] = 'NOW()';
        $set['#edit_time'] = 'NOW()';
        return $this->db->insert($this->table_name, $set);
    }

    /**
     * 修改数据
     * @param array $set
     * @param array $where
     * @return mixed
     */
    public function update(array $set, array $where)
    {
        $set['#edit_time'] = 'NOW()';
        return $this->db->update($this->table_name, $set, $where);
    }

    /**
     * 删除数据
     * @param array $set
     * @param boolean $real_remove
     * @return mixed
     */
    public function delete(array $set, $real_remove = false)
    {
        return $this->db->delete($this->table_name, $set, $real_remove ? 1 : 0);
    }


    /**
     * 输出最贴近debug的sql语句
     */
    public function outputSQL()
    {
        $this->db->debug();
    }

    /**
     * 获取数据库orm操作对象
     * @return DB
     */
    public function DBInstances()
    {
        return $this->db;
    }
}