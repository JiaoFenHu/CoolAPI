<?php

/**
 * 作者：陈志平
 * 日期：2014/07/23
 * 电邮：235437507@qq.com
 * 版本：V2.0
 * 更新时间：2019/12/7
 * 更新日志：
 * V1.1 解决group by多个元素的bug
 * V1.2 解决同表别名的bug
 * V1.3 解决like没有引号的bug
 * V1.4 解决*写在array的取值bug
 * V1.5 解决join的where指定表名错误的bug
 * V1.6 解决get的column为function无法正确返回的bug
 * V1.7 解决column为array(*)时取值不正确的bug
 * V1.8 解决数组元素不存在引起的warning
 * V1.9 解决同事开启虚拟删除和表前缀，删除时引起的bug
 * V1.10 解决count的group by和orderby引起的bug
 * V1.11 新增支持group by 写法 array('isdefault desc','tbid asc')
 * V1.12 修改虚拟删除字段is_del字段为isdel，添加索引并设置为不能为null,，解决第一次添加isdel字段时变更状态失败的bug
 * V1.13 解决log方法没有记录预处理sql的bug
 * V1.14 解决别名和前缀的bug
 * V1.15 增加delete强制删除参数
 * V1.16 解决别名order无法正常解析的bug
 * V1.17 一个奇怪的groupby current取不到数据的bug
 * V1.18 设置默认keyfunction的value值为加引号模式
 * V1.19 解决join不能多张表的bug
 * V1.20 解决表别名select的值不正确问题
 * v1.21 新增copy方法
 * v1.22 新增grouporder参数
 * v1.23 解决group by组内排序失效的问题
 * v1.24 group by组内排序limit暂时取消
 * v1.25 新增@@语法，强制转换value为引号返回
 * v1.26 修复一个order @的会丢失排序设置的问题
 * v1.27 新增group cloumn#符号，将cloumn只放置在外层（一般用于count）
 * v1.28 groupby默认内部limit99999
 * v1.29 支持enum类型
 * v1.30 临时增加gorder参数
 * v1.31 临时强行copy方法返回1
 * v1.32 修复浮点数入库int类型字段会丢失精度的问题
 * v1.33 修复join和group和function会添加表前缀的问题
 * v1.34 修改join联查on多条件问题
 * v2.0
 *  - 优化所有的方法；
 *  - 优化group的使用方式，取消强制子查询group；
 *  - group子查询条件bug修改；
 *  - 严格遵守psr-4的代码规范；
 *
 * 待处理，连接设置sqlmode等
 */
class DB extends PDO
{
    /**
     * PDO 对象
     * @var stdClass
     */
    protected $pdo;

    /**
     * 执行结果
     * @var string
     */
    protected $res;

    /**
     * 配置
     * @var string[]
     */
    protected $config;

    /**
     * 执行的 sql 列表
     * @var string[]
     */
    public $query_arr;

    /**
     * integral 字段类型组合
     * @var string[]
     */
    private $int_array;

    /**
     * float 字段类型组合
     * @var string[]
     */
    private $float_array;

    private $where;
    private $group_value;
    private $pre_array;
    private $join;
    private $main_table;
    private $table;
    private $column;
    private $single_column;

    private $columns;
    private $group_columns;
    private $group_in_columns;
    private $group_where_column_list;
    private $set;
    private $sql;
    private $join_tables;
    private $current_set;
    private $original_set;

    /**
     * DB constructor.
     * @param $config
     */
    function __construct($config)
    {
        $this->query_arr = array();
        $this->config = $config;
        $this->int_array = array('bit', 'tinyint', 'bool', 'boolean', 'smallint', 'mediumint', 'int', 'integer', 'bigint');
        $this->float_array = array('float', 'double', 'decimal');
        $this->connect();
    }

    /**
     * 运行并存储query记录
     * @param string $sql
     * @param int $mode
     * @param null $arg3
     * @param array $ctorargs
     * @return false|PDOStatement
     */
    public function query($sql, $mode = 0, $arg3 = null, array $ctorargs = array())
    {
        $this->query_arr[] = $sql;
        if ($mode == 0) {
            return parent::query($sql);
        }
        return true;
    }

    /**
     * 数据库连接
     */
    private function connect()
    {
        $host = $this->config['host'];
        if (!empty($this->config['port'])) {
            $host .= ":{$this->config['port']}";
        }
        parent::__construct(
            "{$this->config['db_type']}:host={$host};dbname={$this->config['database']}",
            $this->config['name'],
            $this->config['password']
        );

        $this->query("SET NAMES {$this->config['charset']};");

        // 如果执行遇到错误，将以异常的形式抛出异常，使用 PDOException捕获
        $this->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // 尝试驱动使用本地预处理 true:强制使用本地预处理 false:试着使用本地预处理
        $this->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        if (!empty($this->config['option']) && is_array($this->config['option'])) {
            foreach ($this->config['option'] as $key => $value) {
                $this->setAttribute($key, $value);
            }
        }
    }

    /**
     * 设置一些辅助配置
     * @debug 是否开启调试，开启则输出sql语句
     *     0:不开启
     *     1:开启
     *     2:开启并终止程序
     * @log 是否开启日志，开启则记录所有操作sql语句
     *     0:不开启
     *     1:开启
     * @prepare 是否开启预处理，开启则使用预处理提交
     *     0:不开启
     *     1:开启
     * @param $set
     * @param $value
     */
    public function setConfig($set, $value)
    {
        $this->config[$set] = $value;
    }

    /**
     * 获取配置信息
     * @param $key
     * @return string|array
     */
    public function getConfig($key)
    {
        return $this->config[$key];
    }

    /**
     * 初始化参数
     */
    private function init()
    {
        $this->group_value = array();
        $this->pre_array = array();
        $this->join = '';
        $this->table = '';
        $this->main_table = '';
        $this->where = array();
        $this->join_tables = array();
        $this->group_where_column_list = array();
        $this->current_set = array();
        $this->original_set = array();
        unset($this->config['sub_group']);
    }

    /**
     * 处理条件
     * @param $where
     */
    private function where($where)
    {
        $this->where = $this->whereSql($where, 'and', '', 1);
        if (!empty($this->where['where'])) {
            $this->where['where'] = ' WHERE ' . $this->where['where'];
        }
        if (!empty($this->where['pre_where'])) {
            $this->where['pre_where'] = ' WHERE ' . $this->where['pre_where'];
        }
    }

    /**
     * 组装条件
     * @param $where
     * @param string $connect
     * @param string $sptag
     * @param int $start
     * @return array|mixed
     */
    private function whereSql($where, $connect = 'and', $sptag = '', $start = 0)
    {
        $return = array();
        $tag = 0;
        if ($sptag == 'having') {
            $type = $sptag;
            $return = $this->preWhere($return, $type, ' ' . strtoupper($sptag) . ' ');
        } else {
            $type = 'where';
        }
        foreach ($where as $key => $value) {
            $function = 0;
            $key_function = 0;

            if (empty($key) || is_numeric($key)) {
                $key = 'AND';
            } else {
                if (substr($key, 0, 1) === '#') {
                    $function = 1;
                    $key = substr($key, 1);
                }

                if (substr($key, 0, 1) === '@') {
                    $key_function = 1;
                    $key = substr($key, 1);
                }

                $key = explode('#', $key);
                if (count($key) > 1) {
                    array_pop($key);
                }
                $key = trim(implode($key));
            }

            $types = array(
                0 => array('order', 'g_order', 'group_order', 'having', 'group', 's_group', 'limit'),
                1 => array('or', 'and'),
                2 => array('like', 'having')
            );

            $key = strtolower($key);
            if (!in_array($key, $types[0])) {
                if ($tag == 0) {
                    $tag = 1;
                } else {
                    $return = $this->preWhere($return, $type, ' ' . $connect . ' ');
                }
            }
            if (in_array($key, $types[1])) {
                if ($start != 1 || $key == 'or') {
                    $return = $this->preWhere($return, $type, '(');
                }
                $temp_return = $this->whereSql($value, $key, $sptag);
                foreach ($temp_return as $temp_key => $temp_value) {
                    $return[$temp_key] .= $temp_value;
                }
                if ($start != 1 || $key == 'or') {
                    $return = $this->preWhere($return, $type, ')');
                }
            } else if (in_array($key, $types[2])) {
                $temp_return = $this->whereSql($value, 'and', $key, $start);
                foreach ($temp_return as $temp_key => $temp_value) {
                    $return[$temp_key] .= $temp_value;
                }
            } else {
                switch ($key) {
                    case 'match':
                        $content = array('MATCH (' . implode(',', $this->fixColumn($value['columns'])) . ') AGAINST (', array($value['keyword']), ')');
                        $return = $this->preWhere($return, $type, $content);
                        break;
                    case 'group_order':
                    case 'g_order':
                    case 'order':
                        if (!is_array($value)) {
                            if (substr($value, 0, 1) != '@') {
                                $value = explode(',', $value);
                            } else {
                                $value = array($value);
                            }
                        }
                        $order = '';
                        foreach ($value as $order_key => $order_value) {
                            if (is_numeric($order_key)) {
                                if (substr($order_value, 0, 1) != '@') {
                                    $order_value = explode(' ', $order_value);
                                    $order .= ' ' . $this->fixColumn($order_value[0]) . ' ' . $order_value[1] . ',';
                                } else {
                                    $order .= ' ' . substr($order_value, 1, strlen($order_value)) . ',';
                                }
                            } else {
                                if (substr($order_key, 0, 1) != '@') {
                                    $order .= ' ' . $this->fixColumn($order_key) . ' ' . $order_value . ',';
                                } else {
                                    $order .= ' ' . substr($order_key, 1, strlen($order_key)) . ' ' . $order_value . ',';
                                }
                            }
                        }
                        $order = substr($order, 0, strlen($order) - 1);
                        $return[$key] .= ' ORDER BY' . $order;
                        break;
                    case 's_group':
                    case 'group':
                        if ($key === 's_group') {
                            $this->setConfig('sub_group', true);
                        }
                        $value = $this->fixColumn($value);
                        if (is_array($value)) {
                            $group_value = reset($value);
                            $value = implode(',', $value);
                        } else {
                            $group_value = $value;
                        }
                        $return['group'] .= ' GROUP BY ' . $value;
                        $this->group_value = $group_value;
                        break;
                    case 'limit':
                        if (is_array($value)) {
                            if (count($value) == 2) {
                                $limit = $value[0] . ',' . $value[1];
                            } else {
                                $limit = current($value);
                                $key = key($value);
                                if (!empty($key)) {
                                    $limit = $key . ',' . $limit;
                                }
                            }
                        } else {
                            $limit = $value;
                        }
                        $return['limit'] .= ' LIMIT ' . $limit;
                        break;
                    default:
                        if ($key_function == 1) {
                            preg_match('#^(@)?([^\[]*)(\[([^\]]*)\])?$#', $key, $keys);
                            $where_key = $keys[2];
                            $where_tag = $keys[4];
                            if ($function == 0) {
                                $key_type = 1;
                            } else {
                                if ($keys[1] == '@') {
                                    $key_type = 1;
                                } else {
                                    $key_type = '';
                                }
                            }
                        } else {
                            preg_match('#^((`)?([^\.`]*)(`)?(\.))?(`)?([^\[`]*)(`)?(\[([^\]]*)\])?$#', $key, $keys);
                            $table = $keys[3];
                            $where_tag = $keys[10];
                            $where_key = $keys[7];
                            if (empty($table)) {
                                $tables = $this->main_table;
                            } else {
                                if (!array_key_exists($table, $this->column)) {
                                    $table = $this->config['prefix'] . $table;
                                }
                                $tables = $table;
                            }
                            if ($function == 0) {
                                $key_type = $this->column[$tables][$where_key]['type'];
                            } else {
                                $key_type = '';
                            }
                            $where_key = '`' . $where_key . '`';
                        }
                        if (!empty($tables) && $key_function != 1 && !empty($this->join)) {
                            $content = '`' . $tables . '`.' . $where_key;
                        } else {
                            $content = $where_key;
                        }
                        $return = $this->preWhere($return, $type, $content);
                        if (!empty($where_tag)) {
                            switch ($where_tag) {
                                case '!':
                                    if (is_array($value) && empty($value)) {
                                        $value = 'null';
                                    }
                                    if (is_array($value)) {
                                        $content = array();
                                        $content[] = ' NOT IN (';
                                        $content[] = $this->arrayToReturn($value, $key_type, ',');
                                        $content[] = ')';
                                        $return = $this->preWhere($return, $type, $content);
                                    } else {
                                        if ($value === 'null' || gettype($value) == 'NULL') {
                                            $content = array(" IS NOT NULL");
                                            $return = $this->preWhere($return, $type, $content);
                                        } else {
                                            $content = array();
                                            $content[] = ' !';
                                            if (!empty($where_key)) {
                                                $content[] = '=';
                                            }
                                            $content[] = array('type' => $key_type, 'value' => $value);
                                            $return = $this->preWhere($return, $type, $content);
                                        }
                                    }
                                    break;
                                case '<>':
                                    $content = array();
                                    $content[] = ' BETWEEN ';
                                    $content[] = array('type' => $key_type, 'value' => $value[0]);
                                    $content[] = ' AND ';
                                    $content[] = array('type' => $key_type, 'value' => $value[1]);
                                    $return = $this->preWhere($return, $type, $content);
                                    break;
                                case '><':
                                    $content = array();
                                    $content[] = ' NOT BETWEEN ';
                                    $content[] = array('type' => $key_type, 'value' => $value[0]);
                                    $content[] = ' AND ';
                                    $content[] = array('type' => $key_type, 'value' => $value[1]);
                                    $return = $this->preWhere($return, $type, $content);
                                    break;
                                case '~':
                                    $value = $this->getLikeValueToWhere($value);
                                    $content = array();
                                    $content[] = " LIKE '";
                                    if (is_array($value)) {
                                        $content[] = $this->arrayToReturn($value, '', ' OR ');
                                    } else {
                                        $content[] = array($value);
                                    }
                                    $content[] = "'";
                                    $return = $this->preWhere($return, $type, $content);
                                    break;
                                case '!~':
                                    $value = $this->getLikeValueToWhere($value);
                                    $content = array();
                                    $content[] = " NOT LIKE '";
                                    if (is_array($value)) {
                                        $content[] = $this->arrayToReturn($value, '', ' OR ');
                                    } else {
                                        $content[] = array($value);
                                    }
                                    $content[] = "'";
                                    $return = $this->preWhere($return, $type, $content);
                                    break;
                                case '~~':
                                    $content = array();
                                    $content[] = ' REGEXP ';
                                    if (is_array($value)) {
                                        $content[] = $this->arrayToReturn($value, '', ' OR ');
                                    } else {
                                        $content[] = array($value);
                                    }
                                    $return = $this->preWhere($return, $type, $content);
                                    break;
                                case '!~~':
                                    $content = array();
                                    $content[] = ' NOT REGEXP ';
                                    if (is_array($value)) {
                                        $content[] = $this->arrayToReturn($value, '', ' OR ');
                                    } else {
                                        $content[] = array($value);
                                    }
                                    $return = $this->preWhere($return, $type, $content);
                                    break;
                                default:
                                    $content = array();
                                    $content[] = ' ' . $where_tag . ' ';
                                    $content[] = array('type' => $key_type, 'value' => $value);
                                    $return = $this->preWhere($return, $type, $content);
                                    break;
                            }
                        } else {
                            if (is_array($value) && empty($value)) {
                                $value = 'null';
                            }
                            if (is_array($value)) {
                                $content = array();
                                $content[] = ' in (';
                                $content[] = $this->arrayToReturn($value, $key_type, ',');
                                $content[] = ')';
                                $return = $this->preWhere($return, $type, $content);
                            } else {
                                if ($value === 'null' || gettype($value) == 'NULL') {
                                    $content = array(' is null');
                                    $return = $this->preWhere($return, $type, $content);
                                } else {
                                    $content = array();
                                    $content[] = ' ';
                                    if (!empty($where_key)) {
                                        $content[] = '= ';
                                    }
                                    $content[] = array('type' => $key_type, 'value' => $value);
                                    $return = $this->preWhere($return, $type, $content);
                                }
                            }
                        }
                        break;
                }
            }
        }
        return $return;
    }

    /**
     * 处理字段前缀
     * @param $column
     * @return array|false|string
     */
    private function fixColumn($column)
    {
        if (is_array($column)) {
            foreach ($column as &$value) {
                $value = $this->fixColumn($value);
            }
            return $column;
        }

        if (substr($column, 0, 1) != '@') {
            preg_match('#^((`)?([^\.`]*)(`)?(\.))?(`)?([^\[`]*)(`)?$#', $column, $columns);
            if (!empty($columns[3])) {
                unset($columns[0], $columns[1]);
                if (!array_key_exists($columns[3], $this->column)) {
                    $columns[3] = $this->config['prefix'] . $columns[3];
                }
                return implode('', $columns);
            }

            if (!empty($this->join)) {
                $column = $this->main_table . '.' . $column;
            }
            return $column;
        }

        return substr($column, 1, strlen($column));
    }

    /**
     * 处理like字段
     * @param $value
     * @return array|mixed|string
     */
    private function getLikeValueToWhere($value)
    {
        if (is_array($value)) {
            foreach ($value as &$v) {
                $v = $this->getLikeValueToWhere($v);
            }
        } else {
            $pattern = '#((?!\\\).)([%_])#';
            if (!preg_match($pattern, $value)) {
                $value = '%' . $value . '%';
            }
        }
        return $value;
    }

    /**
     * 处理数组连接
     * @param $array
     * @param string $type
     * @param string $connect
     * @return array
     */
    private function arrayToReturn($array, $type = '', $connect = ',')
    {
        $i = 0;
        $return = array();
        $array_count = count($array);
        foreach ($array as $value) {
            $return[] = !empty($type) ? array('type' => $type, 'value' => $value) : array($value);
            if (!empty($connect) && $i != $array_count - 1) {
                $return[] = $connect;
            }
            $i++;
        }
        return $return;
    }

    /**
     * 预处理where语句
     * @param $return
     * @param $type
     * @param $content
     * @return mixed
     */
    private function preWhere($return, $type, $content)
    {
        if (is_array($content)) {
            foreach ($content as $value) {
                if (!is_array($value)) {
                    $return[$type] .= $value;
                    if ($this->config['prepare'] == 1) {
                        $return['pre_' . $type] .= $value;
                    }
                } else {
                    if (isset($value['type'])) {
                        $data = $this->transformValue($value['value'], $value['type']);
                        if ($this->config['prepare'] == 1) {
                            if (empty($value['type'])) {
                                $return['pre_' . $type] .= $data['value'];
                            } else {
                                $this->pre_array[] = $data['prevalue'];
                                $return['pre_' . $type] .= '?';
                            }
                        }
                    } else {
                        if (is_array(current($value))) {
                            foreach ($value as $c_value) {
                                $return = $this->preWhere($return, $type, array($c_value));
                            }
                        } else {
                            $data = $this->transformValue(current($value));
                            $return['pre_' . $type] .= $data['value'];
                        }
                    }
                    $return[$type] .= $data['value'];
                }
            }
        } else {
            $return[$type] .= $content;
            if ($this->config['prepare'] == 1) {
                $return['pre_' . $type] .= $content;
            }
        }
        return $return;
    }

    /**
     * 值转换
     * @param $value
     * @param string $type
     * @return mixed
     */
    private function transformValue($value, $type = '')
    {
        $string_type = 0;
        if ($value === null) {
            $value = 'null';
        }
        if ($value !== 'null') {
            if (!empty($type)) {
                if (in_array($type, $this->int_array)) {
                    $value = number_format($value, 0, '.', '');
                } else if (in_array($type, $this->float_array)) {
                    $value = floatval($value);
                } else {
                    $string_type = 1;
                }
            } else {
                $string_type = 2;
            }
            if ($string_type != 0) {
                $value = preg_replace('#^([\'\"])([^\'\"]*)([\'\"])$#', "\$2", $value);
            }
        }
        if ($this->config['prepare'] == 1) {
            $return['pre_value'] = $value;
        }
        if ($string_type == 1) {
            $return['value'] = "'" . $value . "'";
        } else {
            $return['value'] = $value;
        }
        return $return;
    }

    /**
     * 获取数据表字段
     * @param $table
     * @param int $note
     */
    public function getColumn($table, $note = 0)
    {
        $table = explode(',', $table);
        if (count($table) > 1) {
            foreach ($table as $tables) {
                $this->getColumn($tables, $note);
            }
        } else {
            $table = $table[0];
            preg_match('#^(`)?([^\(`]*)(`)?(\(([^\)]*)\))?$#', $table, $tables);
            $table = $this->config['prefix'] . $tables[2];
            if (empty($this->column[$table])) {
                $sql = 'SHOW FULL FIELDS FROM `' . $table . '`';
                $column = $this->query($sql);
                if (!empty($tables[5])) {
                    $table = $tables[5];
                }
                $table_column = $column->fetchAll();
                foreach ($table_column as $value) {
                    $column_set = array();
                    preg_match('#^([^\(]*)(\(([^\)]+)\))?(.*)$#', $value['Type'], $temp_value);
                    $key = $value['Field'];
                    $column_set['type'] = $temp_value[1];
                    if (!empty($temp_value[3])) {
                        $temp_value[3] = explode(',', $temp_value[3]);
                        if (in_array($column_set['type'], $this->int_array)) {
                            $column_set['length'] = $temp_value[3][0];
                            $column_set['decimal_point'] = $temp_value[3][1];
                        } else if ($column_set['type'] == 'enum') {
                            $column_set['list'] = $temp_value[3];
                        } else {
                            $column_set['list'] = $temp_value[5];
                        }
                    }
                    $this->column[$table][$key] = $column_set;
                    if ($note == 1) {
                        $this->column[$table][$key]['comment'] = $value['Comment'];
                    }
                }
            } else {
                if (!empty($tables[5])) {
                    $this->column[$tables[5]] = $this->column[$table];
                }
            }
        }
    }

    /**
     * 处理表名
     * @param $table
     * @param string $name
     */
    private function table($table, $name = 'table')
    {
        $table = explode(',', $table);
        if (count($table) > 1) {
            foreach ($table as $tables) {
                $this->table($tables, 1);
            }
        } else {
            $table = $table[0];
            preg_match('#^(`)?([^\(`]*)(`)?(\(([^\)]*)\))?$#', $table, $tables);
            $tables[2] = $this->config['prefix'] . $tables[2];
            $table = '`' . $tables[2] . '`';
            if (empty($this->main_table) || $name != 'table') {
                $this->$name = $table;
            } else {
                $this->$name .= ',' . $table;
            }
            if (!empty($tables[5])) {
                $this->$name .= ' AS ' . $tables[5];
            }
            if (empty($this->main_table)) {
                $this->main_table = empty($tables[5]) ? $tables[2] : $tables[5];
                $this->join_tables[] = $this->main_table;
            }
        }
    }

    /**
     * join连表
     * @param array $join
     */
    private function join($join)
    {
        foreach ($join as $key => $value) {
            $this->join .= ' ';
            preg_match('#^(\[([^\]]*)\])?(`)?([^\(`]*)(`)?(\(([^\)]*)\))?$#', $key, $keys);
            $this->getColumn($keys[3] . $keys[4] . $keys[5] . $keys[6]);
            if (!empty($keys[2])) {
                switch ($keys[2]) {
                    case '>':
                        $this->join .= 'LEFT';
                        break;
                    case '<':
                        $this->join .= 'RIGHT';
                        break;
                    case '<>':
                        $this->join .= 'FULL';
                        break;
                    case '><':
                        $this->join .= 'INNER';
                        break;
                }
                $this->join .= ' ';
            }
            $keys[4] = '`' . $this->config['prefix'] . $keys[4] . '`';
            $this->join .= 'JOIN ' . $keys[4];
            $this->join_tables[] = $keys[4];
            if (!empty($keys[7])) {
                $this->join .= ' AS ' . $keys[7];
                $keys[4] = $keys[7];
                $this->join_tables[] = $keys[7];
            }
            if (key($value) != '0') {
                $joins = '';
                $join_num = 1;
                foreach ($value as $sun_key => $sun_value) {
                    if ($join_num > 1) {
                        $joins .= ' AND ';
                    }
                    preg_match('#^((`)?([^\.`]*)(`)?(\.))?(`)?([^\[`]*)(`)?(\[([^\]]*)\])?$#', $sun_key, $sun_keys);
                    $table = $this->main_table;
                    if (!empty($sun_keys[3])) {
                        $table = $sun_keys[3];
                    }
                    $joins .= $table . '.' . $this->removal($sun_keys[7]) . '=';
                    preg_match('#^((`)?([^\.`]*)(`)?(\.))?(`)?([^\[`]*)(`)?(\[([^\]]*)\])?$#', $sun_value, $sun_values);
                    $table = $keys[4];
                    if (!empty($sun_values[3])) {
                        $table = $sun_values[3];
                    }
                    $joins .= $table . '.' . $this->removal($sun_values[7]);
                    $join_num++;
                }
                $this->join .= ' ON ' . $joins;
            } else {
                $this->join .= ' USING (' . implode(',', $value) . ')';
            }
        }
    }

    /**
     * 返回
     * @param $value
     * @return string
     */
    private function removal($value)
    {
        $value = '`' . preg_replace("#^'([^']*)'$#", '$1', $value) . '`';
        return $value;
    }

    /**
     * 预处理查询字段
     * @param $columns
     * @return bool
     */
    private function columns($columns)
    {
        $this->single_column = false;
        if (is_array($columns) && count($columns) == 1) {
            $columns = current($columns);
        }
        if ($columns == '*' || $columns == '') {
            $this->columns = '*';
            $this->group_columns = '*';
            $this->group_in_columns = '*';
            return true;
        }

        if (!is_array($columns)) {
            $columns = explode(',', $columns);
            if ($columns[0] == '#') {
                $this->single_column = true;
            }
            if (count($columns) == 1 && count(explode('.*', $columns[0])) == 1) {
                $this->single_column = true;
            }
        }
        $column_list = array();
        $group_column_list = array();
        $group_in_column_list = array();
        foreach ($columns as $column) {
            $value = explode('#', $column);
            if (count($value) == 2) {
                $column = $value[1];
                preg_match('#^([^\[]*)(\[([^\]]*)\])?$#', $column, $values);
                $as = $values[3];
                $temp = $values[1];
            } else {
                $column = $value[0];
                preg_match('#^((`)?([^\.`]*)(`)?(\.))?(`)?([^\[`]*)(`)?(\[([^\]]*)\])?$#', $column, $values);
                $as = $values[10];
                unset($values[0], $values[1], $values[9], $values[10]);
                if (!empty($values[3])) {
                    if (!array_key_exists($values[3], $this->column)) {
                        $values[3] = $this->config['prefix'] . $values[3];
                    }
                }
                $temp = implode('', $values);
            }

            $this->group_where_column_list[$temp] = $temp;
            if (!empty($as)) {
                $this->group_where_column_list[$temp] = $as;
                $temp .= ' AS ' . $as;
            }
            $column_list[] = $temp;
            if (count($value) == 2) {
                $group_column_list[] = $temp;
            } else {
                $group_in_column_list[] = $temp;
                $group_column_list[] = empty($as) ? $temp : $as;
            }
        }
        $this->columns = implode(',', $column_list);
        $this->group_in_columns = implode(',', $group_in_column_list);
        $this->group_columns = implode(',', $group_column_list);
        return true;
    }

    /**
     * 将数据库报错保存日志，前台显示非敏感错误！
     * @param $e
     * @param string $mode
     */
    public function TransactionErrorLog($e, $mode = '')
    {
        if ($this->inTransaction()) {
            $this->rollBack();
        }
        if (error_reporting() != 0) {
            $message = $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine() . PHP_EOL;
            $message .= 'Stack trace:' . PHP_EOL;
            $trace = $e->getTrace();
            foreach ($trace as $key => $value) {
                $message .= '#' . $key . ' ' . $value['file'] . '(' . $value['line'] . '): ' . $value['class'] . $value['type'] . $value['function'] . '()' . PHP_EOL;
            }
        }
    }

    /**
     * 设置新增，更新字段
     * @param $set
     */
    private function columnSet($set)
    {
        $this->set = array();
        $this->set = $this->preWhere($this->set, 'set', ' set ');
        $i = 0;
        foreach ($set as $key => $value) {
            $function = 0;
            $key_function = 0;
            if (substr($key, 0, 1) === '#') {
                $function = 1;
                $key = substr($key, 1, strlen($key));
            }
            if (substr($key, 0, 1) === '@') {
                $key_function = 1;
                $key = substr($key, 1, strlen($key));
            }

            if ($key_function == 1) {
                preg_match('#^([^\[]*)(\[([^\]]*)\])?$#', $key, $keys);
                $table = '';
                $set_key = $keys[1];
                $set_tag = $keys[3];
                $key_type = '';
            } else {
                preg_match('#^(\(JSON\))?((`)?([^\.`]*)(`)?(\.))?(`)?([^\[`]*)(`)?(\[([\+\-\*\/])\])?$#i', $key, $keys);
                if (is_array($value)) {
                    if (strtoupper($keys[1]) == "(JSON)") {
                        $value = json_encode($value);
                    } else {
                        $value = serialize($value);
                    }
                }

                $table = $keys[4];
                $set_key = $keys[8];
                $set_tag = $keys[11];
                if ($function == 0) {
                    $tables = empty($table) ? $this->main_table : $table;
                    $key_type = $this->column[$tables][$set_key]['type'];
                } else {
                    $key_type = '';
                }
                $set_key = '`' . $set_key . '`';
            }
            $contents = empty($table) ? $set_key : '`' . $table . '`.' . $set_key;
            $content = array();
            $content[] = $contents . ' = ';
            if (!empty($settag)) {
                $content[] = $contents . ' ' . $settag . ' ';
            }
            $content[] = array('type' => $key_type, 'value' => $value);
            if ($i != count($set) - 1) {
                $content[] = ', ';
            }
            $this->set = $this->preWhere($this->set, 'set', $content);
            $i++;
        }
    }

    /**
     * copy条件设置
     * @param $set
     */
    private function copySet($set)
    {
        if (!is_array($set)) {
            $set = explode(',', $set);
        }
        foreach ($set as $key => $value) {
            if (is_numeric($key)) {
                $set = $this->copyColumnSet($value);
                $this->current_set[] = $set;
                $this->original_set[] = $set;
            } else {
                $this->current_set[] = $this->copyColumnSet($key);
                $this->original_set[] = $this->copyColumnSet($value);
            }
        }
        $this->current_set = '(' . implode(',', $this->current_set) . ')';
        $this->original_set = implode(',', $this->original_set);
    }

    /**
     * copy字段设置
     * @param $key
     * @return mixed|string
     */
    private function copyColumnSet($key)
    {
        $function = 0;
        $key_function = 0;
        if (substr($key, 0, 1) === '#') {
            $function = 1;
            $key = substr($key, 1, strlen($key));
        }
        if (substr($key, 0, 1) === '@') {
            $key_function = 1;
            $key = substr($key, 1, strlen($key));
        }

        if ($key_function == 1) {
            preg_match('#^([^\[]*)(\[([^\]]*)\])?$#', $key, $keys);
            $table = '';
            $set_key = $keys[1];
            $set_tag = $keys[3];
        } else {
            preg_match('#^((`)?([^\.`]*)(`)?(\.))?(`)?([^\[`]*)(`)?$#i', $key, $keys);
            $table = $keys[3];
            $set_key = $keys[7];
            if ($function == 0) {
                $tables = empty($table) ? $this->main_table : $table;
            }
            $set_key = '`' . $set_key . '`';
        }
        if (!empty($table)) {
            return '`' . $table . '`.' . $set_key;
        }
        return $set_key;
    }

    /**
     * 执行sql语句
     * @param $mode
     */
    private function doQuery($mode)
    {
        if ($this->config['debug'] != 0) {
            echo $this->assembling($mode) . ';';
            if ($this->config['debug'] == 2) {
                exit;
            }
        }
        if ($this->config['prepare'] == 1) {
            $this->sql = $this->assembling($mode, 1);
            $this->res = $this->prepare($this->sql);
            $this->sql = $this->assembling($mode);
            $this->query($this->sql, 1);
            $i = 1;
            if (!empty($this->pre_array)) {
                foreach ($this->pre_array as $value) {
                    if ($value === 'null') {
                        $value = null;
                    }
                    if (is_int($value) || is_float($value)) {
                        $this->res->bindValue($i, $value, PDO::PARAM_INT);
                    } else {
                        $this->res->bindValue($i, $value, PDO::PARAM_STR);
                    }
                    $i++;
                }
            }
            $this->res->execute();
        } else {
            $this->sql = $this->assembling($mode);
            $this->res = $this->query($this->sql);
        }
    }

    /**
     * sql组装
     * @param $mode
     * @param int $prepare
     * @return string
     */
    private function assembling($mode, $prepare = 0)
    {
        $where = "";
        if ($prepare == 1) {
            if ($mode != 'insert') {
                $where = !empty($this->where['pre_where']) ? $this->where['pre_where'] : '';
            }
            $set = $this->set['pre_set'];
        } else {
            if ($mode != 'insert') {
                $where = !empty($this->where['where']) ? $this->where['where'] : '';
            }
            $set = $this->set['set'];
        }
        $this->where['order'] = !empty($this->where['order']) ? $this->where['order'] : '';
        switch ($mode) {
            case 'get':
                $this->where['limit'] = ' LIMIT 0,1';
            case 'select':
                if (!empty($this->group_value)) {
                    if (empty($this->where['group_order'])) {
                        $this->where['group_order'] = $this->where['order'];
                    }
                    if (!empty($this->where['g_order'])) {
                        $this->where['order'] = $this->where['g_order'];
                    }

                    $sub_group = false;
                    $sql = 'SELECT ' . $this->group_in_columns . ' FROM ' . $this->table . $this->join . $where;
                    if (isset($this->config['sub_group'])) {
                        $sql .= $this->where['group_order'];
                        $sql .= ' LIMIT 999999';
                        $sql = 'SELECT * FROM (' . $sql . ') a';
                        $sub_group = true;
                    }

                    $sql .= $this->tableNameResetByGroup($this->where['group'], 'a', $sub_group);
                    if (!empty($this->where['having'])) {
                        $sql .= $this->tableNameResetByGroup($this->where['having'], 'a', $sub_group);
                    }
                    if (!empty($this->where['order'])) {
                        $sql .= $this->tableNameResetByGroup($this->where['order'], 'a', $sub_group);
                    }
                    if (!empty($this->where['limit'])) {
                        $sql .= $this->where['limit'];
                    }
                } else {
                    $sql = 'SELECT ' . $this->columns . ' FROM ' . $this->table . $this->join . $where . $this->where['order'] . $this->where['limit'];
                }
                break;
            case 'has':
            case 'count':
                if (!empty($this->group_value)) {
                    $sql = 'SELECT COUNT(*) FROM (SELECT ' . $this->group_value . ' FROM ' . $this->table . $this->join . $where . $this->where['group'] . $this->where['having'] . $this->where['limit'] . ') a';
                } else {
                    $sql = 'SELECT COUNT(*) FROM ' . $this->table . $this->join . $where . $this->where['order'] . $this->where['limit'];
                }
                break;
            case 'insert':
                $sql = 'INSERT INTO ' . $this->table . $set;
                break;
            case 'update':
                $sql = 'UPDATE ' . $this->table . $this->join . $set . $where;

                break;
            case 'delete':
                $sql = 'DELETE FROM ' . $this->table . $where;
                break;
            case 'copy':
                $sql = 'INSERT INTO ' . $this->table . $this->cset . ' SELECT ' . $this->ocset . ' FROM ' . $this->otable . $where;
                break;
        }
        return $sql;
    }

    /**
     * 根据分组修改名称
     * @param $orig_sql
     * @param string $replace
     * @param bool $sub_group
     * @return string|string[]
     */
    private function tableNameResetByGroup($orig_sql, $replace = '', $sub_group = true)
    {
        if ($sub_group) {
            foreach ($this->group_where_column_list as $orig_column => $as_column) {
                $orig_sql = str_replace($orig_column, $replace . '.' . $as_column, $orig_sql);
            }

            foreach ($this->join_tables as $table_name) {
                $orig_sql = str_replace($table_name . '.', $replace . '.', $orig_sql);
            }
        }
        return $orig_sql;
    }

    /**
     * 拼接sql
     * @param $table
     * @param array $join
     * @param string|array $columns
     * @param array $where
     * @return string
     */
    public function sql($table, $join = [], $columns = [], $where = [])
    {
        //参数处理
        $this->init();
        $this->table($table);
        if (is_array($join)) {
            if (key($join)) {
                $joins = substr(key($join), 0, 1);
            }
        }
        if (isset($joins) && $joins == '[') {
            $this->join($join);
        } else {
            $where = $columns;
            $columns = $join;
        }
        $this->getColumn($table);
        $this->columns($columns);
        if (array_key_exists('del', $this->column[$this->main_table]) && $this->config['real_delete'] == 0) {
            if (!empty($where)) {
                $where[$this->main_table . '.del[!]'] = 1;
            } else {
                $where = array('del[!]' => 1);
            }
        }
        if (!empty($where)) {
            $this->where($where);
        }
        //返回sql字符串
        return $this->assembling('select');
    }

    /**
     * select 列表数据查询
     * mode 0 多条记录 1 单条记录 2总数 3只适用于查询单个字段下的内容，直接返回对应的内容或者数组 4直接返回sql语句
     * @param $table
     * @param array $join
     *      - [>] == LEFT JOIN
     *      - [<] == RIGHT JOIN
     *      - [<>] == FULL JOIN
     *      - [><] == INNER JOIN
     * @param array $columns
     * @param array $where
     * @return array
     */
    public function select($table, $join = [], $columns = [], $where = [])
    {
        $this->init();
        $this->table($table);
        if (is_array($join)) {
            if (is_string(key($join))) {
                $joins = substr(key($join), 0, 1);
            }
        }


        if (isset($joins) && $joins == '[') {
            $this->join($join);
        } else {
            $where = $columns;
            $columns = $join;
        }
        $this->getColumn($table);
        $this->columns($columns);
        if (array_key_exists('del', $this->column[$this->main_table]) && $this->config['real_delete'] == 0) {
            if (!empty($where)) {
                $where[$this->main_table . '.del[!]'] = 1;
            } else {
                $where = array('del[!]' => 1);
            }
        }
        if (!empty($where)) {
            $this->where($where);
        }
        //数据库操作
        $this->doQuery('select');
        $return = $this->res->fetchAll(PDO::FETCH_ASSOC);
        /*if ($this->single_column == true) {
            $new_return = array();
            foreach ($return as $value) {
                $new_return[] = reset($value);
            }
            return $new_return;
        }*/
        return $return;
    }

    /**
     * 统计sql
     * @param $table
     * @param string $join
     * @param array $where
     * @return mixed
     */
    public function count($table, $join = '', $where = [])
    {
        $this->init();
        $this->table($table);
        if (is_array($join)) {
            if (key($join)) {
                $joins = substr(key($join), 0, 1);
            }
        }
        if (isset($joins) && $joins == '[') {
            $this->join($join);
        } else {
            $where = $join;
        }
        $this->getColumn($table);
        if (array_key_exists('del', $this->column[$this->main_table]) && $this->config['real_delete'] == 0) {
            if (!empty($where)) {
                $where = array_merge($where, array($this->main_table . '.del[!]' => 1));
            } else {
                $where = array('del[!]' => 1);
            }
        }
        if (!empty($where)) {
            $this->where($where);
        }
        //数据库操作
        $this->doQuery('count');
        return $this->res->fetchColumn();
    }

    /**
     * 获取单条记录
     * @param $table
     * @param array $join
     * @param array|string $columns
     * @param array $where
     * @return mixed
     */
    public function get($table, $join = [], $columns = [], $where = [])
    {
        //参数处理
        $this->init();
        $this->table($table);
        if (is_array($join)) {
            if (key($join)) {
                $joins = substr(key($join), 0, 1);
            }
        }
        if (isset($joins) && $joins == '[') {
            $this->join($join);
        } else {
            $where = $columns;
            $columns = $join;
        }
        $this->getColumn($table);
        $this->columns($columns);
        if (array_key_exists('del', $this->column[$this->main_table]) && $this->config['real_delete'] == 0) {
            if (!empty($where)) {
                $where = array_merge($where, array($this->main_table . '.del[!]' => 1));
            } else {
                $where = array('del[!]' => 1);
            }
        }
        if (!empty($where)) {
            $this->where($where);
        }
        //数据库操作
        $this->doQuery('get');
        $return = $this->res->fetch();
        if ($this->single_column == true) {
            $return = $return[0];
        }
        return $return;
    }

    /**
     * 检测数据是否存在
     * @param $table
     * @param array $join
     * @param array $where
     * @return bool
     */
    public function has($table, $join = [], $where = [])
    {
        //参数处理
        $this->init();
        $this->table($table);
        if (is_array($join)) {
            if (key($join)) {
                $joins = substr(key($join), 0, 1);
            }
        }
        if (isset($joins) && $joins == '[') {
            $this->join($joins);
        } else {
            $where = $join;
        }
        $this->getColumn($table);
        if (array_key_exists('del', $this->column[$this->main_table]) && $this->config['real_delete'] == 0) {
            if (!empty($where)) {
                $where = array_merge($where, array($this->main_table . '.del[!]' => 1));
            } else {
                $where = array('del[!]' => 1);
            }
        }
        if (!empty($where)) {
            $this->where($where);
        }
        $this->doQuery('has');
        $return = $this->res->fetchColumn();
        return $return > 0;
    }

    /**
     * 新增数据
     * @param $table
     * @param array $set
     * @return string
     */
    public function insert($table, $set)
    {
        $this->init();
        $this->table($table);
        $this->getColumn($table);
        $this->columnSet($set);
        //数据库操作
        $this->doQuery('insert');
        return $this->lastInsertId();
    }

    /**
     * 数据字段copy
     * @param $table
     * @param $otable
     * @param array $set
     * @param array $where
     * @return int
     */
    public function copy($table, $otable, $set = [], $where = [])
    {
        $this->init();
        $this->table($table);
        $this->table($otable, 'otable');
        if (!empty($set)) {
            $this->copySet($set);
        } else {
            $this->current_set = '';
            $this->original_set = '*';
        }
        if (!empty($where)) {
            $this->where($where);
        }
        //数据库操作
        $this->doQuery('copy');
        return 1;
    }

    /**
     * 更新数据
     * @param $table
     * @param array $join
     * @param array $set
     * @param array $where
     * @return mixed
     */
    public function update($table, $join = [], $set = [], $where = [])
    {
        //参数处理
        $this->init();
        $this->table($table);
        if (is_array($join)) {
            if (key($join)) {
                $joins = substr(key($join), 0, 1);
            }
        }
        if (isset($joins) && $joins == '[') {
            $this->join($join);
        } else {
            $where = $set;
            $set = $join;
        }
        $this->getColumn($table);
        $this->columnSet($set);
        if (!empty($where)) {
            $this->where($where);
        }
        //数据库操作
        $this->doQuery('update');
        return $this->res->rowCount();
    }

    /**
     * 删除操作
     * @param $table
     * @param array $where
     * @param int $mode
     * @return mixed
     */
    public function delete($table, $where = [], $mode = 0)
    {
        $this->init();
        $this->getColumn($table);
        $this->table($table);
        if ($this->config['real_delete'] == 1 || $mode == 1) {
            if (!empty($where)) {
                $this->where($where);
            }
            //数据库操作
            $this->doQuery('delete');
            return $this->res->rowCount();
        } else {
            if (!array_key_exists('del', $this->column[$this->main_table])) {
                $sql = "ALTER TABLE " . $this->table . " ADD COLUMN `del`  tinyint(1) NOT NULL DEFAULT 0 , ADD INDEX (`del`)";
                $this->query($sql);
            }
            return $this->update($table, array("del" => 1), $where);
        }
    }

    /**
     * 查看的数据表
     * @param string $table
     * @return array
     */
    public function showTables($table = '')
    {
        if (empty($table)) {
            $sql = 'show tables';
        } else {
            $sql = "show table status like '" . $this->config['prefix'] . $table . "'";
        }
        $this->res = $this->query($sql);
        return $this->res->fetchAll();
    }

    /**
     * 验证数据表是否存在
     * @param $table
     * @return bool
     */
    public function tableExist($table)
    {
        return $this->has('information_schema.tables', '', array(
            '@table_name' => $table,
            'TABLE_SCHEMA' => $this->config['database']
        ));
    }

    /**
     * 查看数据表索引
     * @param $table
     * @return array
     */
    public function getTableIndex($table)
    {
        $sql = "show index from `" . $table . "`";
        return $this->query($sql)->fetchAll();
    }

    /**
     * 删除索引
     * @param $table
     * @return bool
     */
    private function delTableIndex($table)
    {
        $index = array();
        $table_index = $this->getTableIndex($table);
        foreach ($table_index as $value) {
            if ($value['Key_name'] != "PRIMARY") {
                $index[] = $value['Key_name'];
            }
        }
        if (is_array($index)) {
            $index = array_unique($index);
            $sql = 'ALTER TABLE `' . $table . '`';
            foreach ($index as $value) {
                $sql .= " DROP INDEX `" . $value . "`,";
            }
            $sql = substr($sql, 0, -1);
            $this->query($sql);
        }
        return true;
    }

    /**
     * 数据表重命名
     * @param $table
     * @param $newtable
     * @return bool
     */
    public function renameTable($table, $newtable)
    {
        $sql = 'ALTER  TABLE `' . $this->config['prefix'] . $table . '` RENAME TO `' . $newtable . '`';
        $this->query($sql);
        return true;
    }

    /**
     * 查看数据表是否存在
     * @param $table
     * @return bool
     */
    public function has_table($table)
    {
        $sql = "show tables like '" . $this->config['prefix'] . $table . "'";
        $this->res = $this->query($sql);
        $return = $this->res->fetchColumn();
        return $return > 0;
    }

    /**
     * 日志打印
     * @return $this
     */
    public function debug()
    {
        $this->config['debug'] = 2;
        return $this;
    }

    /**
     * 查询执行日志
     */
    public function log()
    {
        var_dump($this->query_arr);
    }

    /**
     * 最后一次执行sql日志
     */
    public function last_query()
    {
        echo end($this->query_arr);
    }

    /**
     * 执行事务，必须是个方法
     * @param $actions
     * @return false
     */
    public function action($actions)
    {
        if (is_callable($actions)) {
            $this->beginTransaction();
            $result = $actions($this);
            if ($result === false) {
                $this->rollBack();
            } else {
                $this->commit();
            }
        } else {
            return false;
        }
    }

    /**
     * 数据库信息
     */
    public function info()
    {
        $result = array();
        $result['server'] = $this->getAttribute(PDO::ATTR_SERVER_INFO);
        $result['client'] = $this->getAttribute(PDO::ATTR_CLIENT_VERSION);
        $result['driver'] = $this->getAttribute(PDO::ATTR_DRIVER_NAME);
        $result['version'] = $this->getAttribute(PDO::ATTR_SERVER_VERSION);
        $result['connection'] = $this->getAttribute(PDO::ATTR_CONNECTION_STATUS);
        print_r($result);
    }
}
