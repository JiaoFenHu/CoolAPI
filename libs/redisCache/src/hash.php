<?php


namespace libs\redisCache\src;


use libs\redisCache\connection;

class hash extends connection
{
    /**
     * hash constructor.
     * @param array $config
     */
    public function __construct($config = [])
    {
        parent::__construct($config);
    }

    /**
     * 为哈希表中的字段赋值
     * @param string $table 哈希表名
     * @param string $column 字段名
     * @param string|array $value 字段值
     * @param int $expire 过期时间, 如果不填则不设置过期时间
     * @return int|boolean 1:值不存在并创建 0:值替换成功 false:替换失败
     */
    public function hSet($table, $column, $value, $expire = 0)
    {
        $value = is_array($value) ? json_encode($value) : $value;
        $res = self::$redis->hSet($table, $column, $value);
        if ((int)$expire) {
            $this->expire($table, $expire);
        }
        return $res;
    }

    /**
     * 获取哈希表字段值
     * @param string $table 表名
     * @param string $column 字段名
     * @return mixed  返回字段值，如果字段值是数组保存的返回json格式字符串，转换成数组json_encode($value),如果字段不存在返回false;
     */
    public function hGet($table, $column)
    {
        return self::$redis->hGet($table, $column);
    }

    /**
     * 删除哈希表 key 中的一个或多个指定字段，不存在的字段将被忽略
     * @param string $table 表名
     * @param string|array $columns 字段名
     * @return int  返回被成功删除字段的数量，不包括被忽略的字段,(删除哈希表用self::del($table))
     */
    public function hDel($table, $columns)
    {
        $num = 0;
        if (is_string($columns)) {
            self::$redis->hDel($table, $columns);
            return 1;
        }

        for ($i = 1; $i < count($columns); $i++) {
            $num += self::$redis->hDel($table, $columns[$i]);
        }
        return $num;
    }

    /**
     * 查看哈希表的指定字段是否存在
     * @param string $table 表名
     * @param string $column 字段名
     * @return bool  存在返回true,否则false
     */
    public function hExists($table, $column)
    {
        if ((int)self::$redis->hExists($table, $column)) {
            return true;
        }
        return false;
    }

    /**
     * 返回哈希表中，所有的字段和值
     * @param string $table 表名
     * @return array 返回键值对数组
     */
    public function hGetAll($table)
    {
        return self::$redis->hGetAll($table);
    }

    /**
     * 为哈希表中的字段值加上指定增量值(支持整数和浮点数)
     * @param string $table 表名
     * @param string $column 字段名
     * @param int|float $num 增量值，默认1, 也可以是负数值,相当于对指定字段进行减法操作
     * @return int|float|bool  返回计算后的字段值,如果字段值不是数字值则返回false,如果哈希表不存在或字段不存在返回false
     */
    public function hInc($table, $column, $num = 1)
    {
        $value = $this->hGet($table, $column);
        if (is_numeric($value)) { //数字类型，包括整数和浮点数
            $value += $num;
            $this->hSet($table, $column, $value);
            return $value;
        } else {
            return false;
        }
    }

    /**
     * 获取哈希表中的所有字段
     * @param string $table 表名
     * @return array  返回包含所有字段的数组
     */
    public function hKeys($table)
    {
        return self::$redis->hKeys($table);
    }

    /**
     * 返回哈希表所有域(field)的值
     * @param string $table 表名
     * @return array 返回包含所有字段值的数组,数字索引
     */
    public function hVals($table)
    {
        return self::$redis->hVals($table);
    }

    /**
     * 获取哈希表中字段的数量
     * @param string $table 表名
     * @return int 如果哈希表不存在则返回0
     */
    public function hLen($table)
    {
        return self::$redis->hLen($table);
    }

    /**
     * 获取哈希表中，一个或多个给定字段的值
     * @param string $table 表名
     * @param string|array $columns 字段名
     * @return array  返回键值对数组，如果字段不存在则字段值为null, 如果哈希表不存在返回空数组
     */
    public function hmGet($table, $columns)
    {
        if (!is_array($columns)) {
            $columns = func_get_args();
            unset($columns[0]);
        }
        return self::$redis->hMGet($table, $columns);
    }

    /**
     * 同时将多个 field-value (字段-值)对设置到哈希表中
     * @param string $table 表名
     * @param array $data 要添加的键值对
     * @param int $expire 过期时间，不填则不设置过期时间
     * @return bool 成功返回true,否则false
     */
    public function hmSet($table, array $data, $expire = 0)
    {
        $result = self::$redis->hMSet($table, $data);
        if ((int)$expire) {
            $this->expire($table, $expire);
        }
        return $result;
    }

    /**
     * 为哈希表中不存在的的字段赋值
     * @param string $table 哈希表名
     * @param string $column 字段名
     * @param string|array $value 字段值
     * @param int $expire 过期时间, 如果不填则不设置过期时间
     * @return bool  如果成功返回true，否则返回 false.
     */
    public function hSetNx($table, $column, $value, $expire = 0)
    {
        if (is_array($value)) {
            $value = json_encode($value);
        }
        $result = self::$redis->hSetNx($table, $column, $value);
        if ((int)$expire) {
            $this->expire($table, $expire);
        }
        return $result;
    }

    /**
     * 获取hash某个字段字符串的长度
     * @param string $table 哈希表名
     * @param string $column 字段名
     * @return int 字符串长度
     */
    public function hStrLen($table, $column)
    {
        return self::$redis->hStrLen($table, $column);
    }
}