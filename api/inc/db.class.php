<?php

/**
 * 作者：陈志平
 * 日期：2014/07/23
 * 电邮：235437507@qq.com
 * 版本：V1.33
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
     * @return false|PDOStatement
     */
    public function query($sql, $mode = 0)
    {
        $this->query_arr[] = $sql;
        if ($mode == 0) {
            return parent::query($sql);
        }
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
        $this->join = array();
        $this->table = '';
        $this->main_table = '';
        $this->where = array();
    }

    private function where($where)
    {
        $this->where = $this->whereSql($where, 'and', '', 1);
        if (!empty($this->where['where'])) {
            $this->where['where'] = ' where ' . $this->where['where'];
        }
        if (!empty($this->where['prewhere'])) {
            $this->where['prewhere'] = ' where ' . $this->where['prewhere'];
        }
    }

    private function whereSql($where, $connect = 'and', $sptag = '', $start = 0)
    {
        $return = array();
        $tag = 0;
        $function = 0;
        if ($sptag == 'having') {
            $type = $sptag;
            $return = $this->wherereturn($return, $type, ' ' . $sptag . ' ');
        } else {
            $type = 'where';
        }
        foreach ($where as $key => $value) {
            if (empty($key) || is_numeric($key)) {
                $key = 'and';
            } else {

                if (substr($key, 0, 1) != '#') {
                    $function = 0;
                } else {
                    $function = 1;
                    $key = substr($key, 1, strlen($key));
                }
                if (substr($key, 0, 1) != '@') {
                    $keyfunction = 0;
                } else {
                    $keyfunction = 1;
                    $key = substr($key, 1, strlen($key));
                }
                $key = explode('#', $key);
                if (count($key) > 1) {
                    array_pop($key);
                }
                $key = trim(implode($key));
            }
            $typearray = array(0 => array('order', 'grouporder', 'gorder', 'having', 'group', 'limit'), 1 => array('or', 'and'), 2 => array('like', 'having'));
            if (!in_array(strtolower($key), $typearray[0])) {
                if ($tag == 0) {
                    $tag = 1;
                } else {
                    $return = $this->wherereturn($return, $type, ' ' . $connect . ' ');
                }
            }
            if (in_array(strtolower($key), $typearray[1])) {
                if ($start != 1 || strtolower($key) == 'or') {
                    $return = $this->wherereturn($return, $type, '(');
                }
                $tempreturn = $this->wheresql($value, $key, $sptag);
                foreach ($tempreturn as $tempkey => $tempvalue) {
                    $return[$tempkey] .= $tempreturn[$tempkey];
                }
                if ($start != 1 || strtolower($key) == 'or') {
                    $return = $this->wherereturn($return, $type, ')');
                }
            } else if (in_array(strtolower($key), $typearray[2])) {
                $tempreturn = $this->wheresql($value, 'and', $key, $start);
                foreach ($tempreturn as $tempkey => $tempvalue) {
                    $return[$tempkey] .= $tempreturn[$tempkey];
                }
            } else {
                switch ($key) {
                    case 'match':
                        $content = array('match (' . implode(',', $this->fixcolumn($value['columns'])) . ') against (', array($value['keyword']), ')');
                        $return = $this->wherereturn($return, $type, $content);
                        break;
                    case 'grouporder':
                    case 'gorder':
                    case 'order':
                        if (!is_array($value)) {
                            if (substr($value, 0, 1) != '@') {
                                $value = explode(',', $value);
                            } else {
                                $value = array($value);
                            }
                        }
                        $order = '';
                        foreach ($value as $orderkey => $ordervalue) {
                            if (is_numeric($orderkey)) {
                                if (substr($ordervalue, 0, 1) != '@') {
                                    $ordervalue = explode(' ', $ordervalue);
                                    $order .= ' ' . $this->fixcolumn($ordervalue[0]) . ' ' . $ordervalue[1] . ',';
                                } else {
                                    $order .= ' ' . substr($ordervalue, 1, strlen($ordervalue)) . ',';
                                }
                            } else {
                                if (substr($orderkey, 0, 1) != '@') {
                                    $order .= ' ' . $this->fixcolumn($orderkey) . ' ' . $ordervalue . ',';
                                } else {
                                    $order .= ' ' . substr($orderkey, 1, strlen($orderkey)) . ' ' . $ordervalue . ',';
                                }
                            }
                        }
                        $order = substr($order, 0, strlen($order) - 1);
                        $return[$key] .= ' order by' . $order;
                        break;
                    case 'group':
                        $value = $this->fixcolumn($value);
                        if (is_array($value)) {
                            $groupvalue = reset($value);
                            $value = implode(',', $value);
                        } else {
                            $groupvalue = $value;
                        }
                        $return['group'] .= ' group by ' . $value;
                        $this->groupvalue = $groupvalue;
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
                        $return['limit'] .= ' limit ' . $limit;
                        break;
                    default:
                        if ($keyfunction == 1) {
                            preg_match('#^(@)?([^\[]*)(\[([^\]]*)\])?$#', $key, $keys);
                            $table = '';
                            $wherekey = $keys[2];
                            $wheretag = $keys[4];
                            if ($function == 0) {
                                $keytype = 1;
                            } else {
                                if ($keys[1] == '@') {
                                    $keytype = 1;
                                } else {
                                    $keytype = '';
                                }
                            }
                        } else {
                            preg_match('#^((`)?([^\.`]*)(`)?(\.))?(`)?([^\[`]*)(`)?(\[([^\]]*)\])?$#', $key, $keys);
                            $table = $keys[3];
                            $wheretag = $keys[10];
                            $wherekey = $keys[7];
                            if (empty($table)) {
                                $tables = $this->maintable;
                            } else {
                                if (!array_key_exists($table, $this->column)) {
                                    $table = $this->config['prefix'] . $table;
                                }
                                $tables = $table;
                            }
                            if ($function == 0) {
                                $keytype = $this->column[$tables][$wherekey]['type'];
                            } else {
                                $keytype = '';
                            }
                            $wherekey = '`' . $wherekey . '`';
                        }
                        if (!empty($tables) && $keyfunction != 1 && !empty($this->join)) {
                            $content = '`' . $tables . '`.' . $wherekey;
                        } else {
                            $content = $wherekey;
                        }
                        $return = $this->wherereturn($return, $type, $content);
                        if (!empty($wheretag)) {
                            switch ($wheretag) {
                                case '!':
                                    if (is_array($value) && empty($value)) {
                                        $value = 'null';
                                    }
                                    if (is_array($value)) {
                                        $content = array();
                                        $content[] = ' not in (';
                                        $content[] = $this->arraytoreturn($value, $keytype, ',');
                                        $content[] = ')';
                                        $return = $this->wherereturn($return, $type, $content);
                                    } else {
                                        if ($value === 'null' || gettype($value) == 'NULL') {
                                            $content = array(" is not null");
                                            $return = $this->wherereturn($return, $type, $content);
                                        } else {
                                            $content = array();
                                            $content[] = ' !';
                                            if (!empty($wherekey)) {
                                                $content[] = '=';
                                            }
                                            $content[] = array('type' => $keytype, 'value' => $value);
                                            $return = $this->wherereturn($return, $type, $content);
                                        }
                                    }
                                    break;
                                case '<>':
                                    $content = array();
                                    $content[] = ' between ';
                                    $content[] = array('type' => $keytype, 'value' => $value[0]);
                                    $content[] = ' and ';
                                    $content[] = array('type' => $keytype, 'value' => $value[1]);
                                    $return = $this->wherereturn($return, $type, $content);
                                    break;
                                case '><':
                                    $content = array();
                                    $content[] = ' not between ';
                                    $content[] = array('type' => $keytype, 'value' => $value[0]);
                                    $content[] = ' and ';
                                    $content[] = array('type' => $keytype, 'value' => $value[1]);
                                    $return = $this->wherereturn($return, $type, $content);
                                    break;
                                case '~':
                                    $value = $this->checkilikevalue($value);
                                    $content = array();
                                    $content[] = " like '";
                                    if (is_array($value)) {
                                        $content[] = $this->arraytoreturn($value, '', ' or ');
                                    } else {
                                        $content[] = array($value);
                                    }
                                    $content[] = "'";
                                    $return = $this->wherereturn($return, $type, $content);
                                    break;
                                case '!~':
                                    $value = $this->checkilikevalue($value);
                                    $content = array();
                                    $content[] = " not like '";
                                    if (is_array($value)) {
                                        $content[] = $this->arraytoreturn($value, '', ' or ');
                                    } else {
                                        $content[] = array($value);
                                    }
                                    $content[] = "'";
                                    $return = $this->wherereturn($return, $type, $content);
                                    break;
                                case '~~':
                                    $content = array();
                                    $content[] = ' regexp ';
                                    if (is_array($value)) {
                                        $content[] = $this->arraytoreturn($value, '', ' or ');
                                    } else {
                                        $content[] = array($value);
                                    }
                                    $return = $this->wherereturn($return, $type, $content);
                                    break;
                                case '!~~':
                                    $content = array();
                                    $content[] = ' not regexp ';
                                    if (is_array($value)) {
                                        $content[] = $this->arraytoreturn($value, '', ' or ');
                                    } else {
                                        $content[] = array($value);
                                    }
                                    $return = $this->wherereturn($return, $type, $content);
                                    break;
                                default:
                                    $content = array();
                                    $content[] = ' ' . $wheretag . ' ';
                                    $content[] = array('type' => $keytype, 'value' => $value);
                                    $return = $this->wherereturn($return, $type, $content);
                                    break;
                            }
                        } else {
                            if (is_array($value) && empty($value)) {
                                $value = 'null';
                            }
                            if (is_array($value)) {
                                $content = array();
                                $content[] = ' in (';
                                $content[] = $this->arraytoreturn($value, $keytype, ',');
                                $content[] = ')';
                                $return = $this->wherereturn($return, $type, $content);
                            } else {
                                if ($value === 'null' || gettype($value) == 'NULL') {
                                    $content = array(' is null');
                                    $return = $this->wherereturn($return, $type, $content);
                                } else {
                                    $content = array();
                                    $content[] = ' ';
                                    if (!empty($wherekey)) {
                                        $content[] = '= ';
                                    }
                                    $content[] = array('type' => $keytype, 'value' => $value);
                                    $return = $this->wherereturn($return, $type, $content);
                                }
                            }
                        }
                        break;
                }
            }
        }
        return $return;
    }

    private function fixcolumn($column)
    {
        if (is_array($column)) {
            foreach ($column as &$value) {
                $value = $this->fixcolumn($value);
            }
            return $column;
        } else {
            if (substr($column, 0, 1) != '@') {
                preg_match('#^((`)?([^\.`]*)(`)?(\.))?(`)?([^\[`]*)(`)?$#', $column, $columns);
                if (!empty($columns[3])) {
                    unset($columns[0]);
                    unset($columns[1]);
                    if (!array_key_exists($columns[3], $this->column)) {
                        $columns[3] = $this->config['prefix'] . $columns[3];
                    }
                    return implode('', $columns);
                } else {
                    if (!empty($this->join)) {
                        $column = $this->maintable . '.' . $column;
                    }
                    return $column;
                }
            } else {
                return substr($column, 1, strlen($column));
            }
        }
    }

    private function checkilikevalue($value)
    {
        if (is_array($value)) {
            foreach ($value as &$v) {
                $v = $this->checkilikevalue($v);
            }
        } else {
            $pattern = '#((?!\\\).)([%_])#';
            if (!preg_match($pattern, $value)) {
                $value = '%' . $value . '%';
            }
        }
        return $value;
    }

    private function arraytoreturn($array, $type = '', $connect = ',')
    {
        $i = 0;
        $return = array();
        foreach ($array as $value) {
            if (!empty($type)) {
                $return[] = array('type' => $type, 'value' => $value);
            } else {
                $return[] = array($value);
            }
            if (!empty($connect) && $i != count($array) - 1) {
                $return[] = $connect;
            }
            $i++;
        }
        return $return;
    }

    private function wherereturn($return, $type, $content)
    {
        if (is_array($content)) {
            foreach ($content as $value) {
                if (!is_array($value)) {
                    $return[$type] .= $value;
                    if ($this->config['prepare'] == 1) {
                        $return['pre' . $type] .= $value;
                    }
                } else {
                    if (isset($value['type'])) {
                        $data = $this->checkvalue($value['value'], $value['type']);
                        if ($this->config['prepare'] == 1) {
                            if (empty($value['type'])) {
                                $return['pre' . $type] .= $data['value'];
                            } else {
                                $this->prearray[] = $data['prevalue'];
                                $return['pre' . $type] .= '?';
                            }
                        }
                    } else {
                        if (is_array(current($value))) {
                            foreach ($value as $cvalue) {
                                $return = $this->wherereturn($return, $type, array($cvalue));
                            }
                        } else {
                            $data = $this->checkvalue(current($value));
                            $return['pre' . $type] .= $data['value'];
                        }
                    }
                    $return[$type] .= $data['value'];
                }
            }
        } else {
            $return[$type] .= $content;
            if ($this->config['prepare'] == 1) {
                $return['pre' . $type] .= $content;
            }
        }
        return $return;
    }

    private function checkvalue($value, $type = '')
    {
        $stringtype = 0;
        if ($value === null) {
            $value = 'null';
        }
        if ($value !== 'null') {
            if (!empty($type)) {
                if (in_array($type, $this->intarray)) {
                    $value = number_format($value, 0, '.', '');
                } else if (in_array($type, $this->floatarray)) {
                    $value = floatval($value);
                } else {
                    $stringtype = 1;
                }
            } else {
                $stringtype = 2;
            }
            if ($stringtype != 0) {
                $value = preg_replace('#^([\'\"])([^\'\"]*)([\'\"])$#', "\$2", $value);
            }
        }
        if ($this->config['prepare'] == 1) {
            $return['prevalue'] = $value;
        }
        if ($stringtype == 1) {
            $return['value'] = "'" . $value . "'";
        } else {
            $return['value'] = $value;
        }
        return $return;
    }

    public function getcolumn($table, $note = 0)
    {
        $table = explode(',', $table);
        if (count($table) > 1) {
            foreach ($table as $tables) {
                $this->getcolumn($tables, $note);
            }
        } else {
            $table = $table[0];
            preg_match('#^(`)?([^\(`]*)(`)?(\(([^\)]*)\))?$#', $table, $tables);
            $table = $this->config['prefix'] . $tables[2];
            if (empty($this->column[$table])) {
                $sql = 'show full fields from `' . $table . '`';
                $column = $this->query($sql);
                if (!empty($tables[5])) {
                    $table = $tables[5];
                }
                $tablecolumn = $column->fetchAll();
                foreach ($tablecolumn as $value) {
                    $columnset = array();
                    preg_match('#^([^\(]*)(\(([^\)]+)\))?(.*)$#', $value['Type'], $tempvalue);
                    $key = $value['Field'];
                    $columnset['type'] = $tempvalue[1];
                    if (!empty($tempvalue[3])) {
                        $tempvalue[3] = explode(',', $tempvalue[3]);
                        if (in_array($columnset['type'], $this->intarray)) {
                            $columnset['length'] = $tempvalue[3][0];
                            $columnset['decimalpoint'] = $tempvalue[3][1];
                        } else if ($columnset['type'] == 'enum') {
                            $columnset['list'] = $tempvalue[3];
                        } else {
                            $columnset['list'] = $tempvalue[5];
                        }
                    }
                    $this->column[$table][$key] = $columnset;
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
            if (empty($this->maintable) || $name != 'table') {
                $this->$name = $table;
            } else {
                $this->$name .= ',' . $table;
            }
            if (!empty($tables[5])) {
                $this->$name .= ' as ' . $tables[5];
            }
            if (empty($this->maintable)) {
                if (empty($tables[5])) {
                    $this->maintable = $tables[2];
                } else {
                    $this->maintable = $tables[5];
                }
            }
        }
    }

    private function join($join)
    {
        foreach ($join as $key => $value) {
            $this->join .= ' ';
            preg_match('#^(\[([^\]]*)\])?(`)?([^\(`]*)(`)?(\(([^\)]*)\))?$#', $key, $keys);
            $this->getcolumn($keys[3] . $keys[4] . $keys[5] . $keys[6]);
            if (!empty($keys[2])) {
                switch ($keys[2]) {
                    case '>':
                        $this->join .= 'left';
                        break;
                    case '<':
                        $this->join .= 'right';
                        break;
                    case '<>':
                        $this->join .= 'full';
                        break;
                    case '><':
                        $this->join .= 'inner';
                        break;
                }
                $this->join .= ' ';
            }
            $keys[4] = '`' . $this->config['prefix'] . $keys[4] . '`';
            $this->join .= 'join ' . $keys[4];
            if (!empty($keys[7])) {
                $this->join .= ' as ' . $keys[7];
                $keys[4] = $keys[7];
            }
            if (key($value) != '0') {
                $joins = '';
                $join_num = 1;
                foreach ($value as $sunkey => $sunvalue) {
                    if ($join_num > 1) {
                        $joins .= ' and ';
                    }
                    preg_match('#^((`)?([^\.`]*)(`)?(\.))?(`)?([^\[`]*)(`)?(\[([^\]]*)\])?$#', $sunkey, $sunkeys);
                    if (!empty($sunkeys[3])) {
                        $table = $sunkeys[3];
                    } else {
                        $table = $this->maintable;
                    }
                    $joins .= $table . '.' . $this->removal($sunkeys[7]) . '=';
                    preg_match('#^((`)?([^\.`]*)(`)?(\.))?(`)?([^\[`]*)(`)?(\[([^\]]*)\])?$#', $sunvalue, $sunvalues);
                    if (!empty($sunvalues[3])) {
                        $table = $sunvalues[3];
                    } else {
                        $table = $keys[4];
                    }
                    $joins .= $table . '.' . $this->removal($sunvalues[7]);
                    $join_num++;
                }
                $this->join .= ' on ' . $joins;
            } else {
                $this->join .= ' using (' . implode(',', $value) . ')';
            }
        }
    }

    private function removal($value)
    {
        $value = '`' . preg_replace("#^'([^']*)'$#", '$1', $value) . '`';
        return $value;
    }

    private function columns($columns)
    {
        $this->singlecolumn = false;
        if (is_array($columns) && count($columns) == 1) {
            $columns = current($columns);
        }
        if ($columns == '*' || $columns == '') {
            $this->columns = '*';
            $this->groupcolumns = '*';
            $this->groupincolumns = '*';
        } else {
            if (!is_array($columns)) {
                if ($columns[0] == '#') {
                    $this->singlecolumn = true;
                    $columns = explode(',', $columns);
                } else {
                    $columns = explode(',', $columns);
                    if (count($columns) == 1) {
                        $tempcolumn = explode('.*', $columns[0]);
                        if (count($tempcolumn) == 1) {
                            $this->singlecolumn = true;
                        }
                    }
                }
            }
            $columnlist = array();
            $groupcolumnlist = array();
            $groupincolumnlist = array();
            foreach ($columns as $column) {
                $value = explode('#', $column);
                if (count($value) == 2) {
                    $value = $value[1];
                    preg_match('#^([^\[]*)(\[([^\]]*)\])?$#', $value, $values);
                    $as = $values[3];
                    $temp = $values[1];
                } else {
                    $value = $value[0];
                    preg_match('#^((`)?([^\.`]*)(`)?(\.))?(`)?([^\[`]*)(`)?(\[([^\]]*)\])?$#', $value, $values);
                    $as = $values[10];
                    unset($values[0]);
                    unset($values[1]);
                    unset($values[9]);
                    unset($values[10]);
                    if (!empty($values[3])) {
                        if (!array_key_exists($values[3], $this->column)) {
                            $values[3] = $this->config['prefix'] . $values[3];
                        }
                    }
                    $temp = implode('', $values);
                }
                if (!empty($as)) {
                    $temp .= ' as ' . $as;
                }
                $columnlist[] = $temp;
                $value = explode('#', $column);
                if (count($value) == 2) {
                    $groupcolumnlist[] = $temp;
                } else {
                    $groupincolumnlist[] = $temp;
                    if (!empty($as)) {
                        $groupcolumnlist[] = $as;
                    } else {
                        $groupcolumnlist[] = $temp;
                    }
                }
            }
            $this->columns = implode(',', $columnlist);
            $this->groupincolumns = implode(',', $groupincolumnlist);
            $this->groupcolumns = implode(',', $groupcolumnlist);
        }
    }

    public function catche($e)
    {
        if ($this->inTransaction()) {
            $this->rollBack();
        }
        if (error_reporting() != 0) {
            if ($e->xdebug_message != '') {
                $message = '<font size="1"><table cellspacing="0" cellpadding="1" border="1" dir="ltr" class="xdebug-error">' . $e->xdebug_message . '</table></font>';
            } else {
                $message = $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine() . '<br>Stack trace:<br>';
                $trace = $e->getTrace();
                foreach ($trace as $key => $value) {
                    $message .= '#' . $key . ' ' . $value['file'] . '(' . $value['line'] . '): ' . $value['class'] . $value['type'] . $value['function'] . '()<br>';
                }
            }
            echo $message;
        }
    }


    /**
     * 将数据库报错保存日志，前台显示非敏感错误！
     * @param $e
     * @param $api_name
     * @throws Exception
     */
    public function TransactionErrorLog($e, $api_name)
    {
        if ($this->inTransaction()) {
            $this->rollBack();
        }
        if (error_reporting() != 0) {

            $message = $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine();
            $message .= 'Stack trace:';
            $trace = $e->getTrace();
            foreach ($trace as $key => $value) {
                $message .= '#' . $key . ' ' . $value['file'] . '(' . $value['line'] . '): ' . $value['class'] . $value['type'] . $value['function'] . '()';
            }

            require_once(API_DIR . 'info/monolog.class.php');
            $log_class = new monolog(1);
            $err_clog = $log_class::init('DBErrorLog', $api_name, 'ERROR');
            $err_clog->error($message);
        }
    }

    private function columnset($set)
    {
        $this->set = array();
        $this->set = $this->wherereturn($this->set, 'set', ' set ');
        $i = 0;
        foreach ($set as $key => $value) {
            if (substr($key, 0, 1) != '#') {
                $function = 0;
            } else {
                $function = 1;
                $key = substr($key, 1, strlen($key));
            }
            if (substr($key, 0, 1) != '@') {
                $keyfunction = 0;
            } else {
                $keyfunction = 1;
                $key = substr($key, 1, strlen($key));
            }
            if ($keyfunction == 1) {
                preg_match('#^([^\[]*)(\[([^\]]*)\])?$#', $key, $keys);
                $table = '';
                $setkey = $keys[1];
                $settag = $keys[3];
                $keytype = '';
            } else {
                preg_match('#^(\(JSON\))?((`)?([^\.`]*)(`)?(\.))?(`)?([^\[`]*)(`)?(\[([\+\-\*\/])\])?$#i', $key, $keys);
                if (is_array($value)) {
                    if (strtoupper($keys[1]) == "(JSON)") {
                        $value = json_encode($value);
                    } else {
                        $value = serialize($value);
                    }
                }
                if (!empty($keys[4])) {
                    $table = $keys[4];
                } else {
                    $table = $this->maintable;
                }
                $table = $keys[4];
                $setkey = $keys[8];
                $settag = $keys[11];
                if ($function == 0) {
                    if (empty($table)) {
                        $tables = $this->maintable;
                    } else {
                        $tables = $table;
                    }
                    $keytype = $this->column[$tables][$setkey]['type'];
                } else {
                    $keytype = '';
                }
                $setkey = '`' . $setkey . '`';
            }
            if (!empty($table)) {
                $contents = '`' . $table . '`.' . $setkey;
            } else {
                $contents = $setkey;
            }
            $content = array();
            $content[] = $contents . ' = ';
            if (!empty($settag)) {
                $content[] = $contents . ' ' . $settag . ' ';
            }
            $content[] = array('type' => $keytype, 'value' => $value);
            if ($i != count($set) - 1) {
                $content[] = ', ';
            }
            $this->set = $this->wherereturn($this->set, 'set', $content);
            $i++;
        }
    }

    private function copyset($set)
    {
        $this->cset = array();
        $this->ocset = array();
        if (!is_array($set)) {
            $set = explode(',', $set);
        }
        foreach ($set as $key => $value) {
            if (is_numeric($key)) {
                $set = $this->copycolumnset($value);
                $this->cset[] = $set;
                $this->ocset[] = $set;
            } else {
                $this->cset[] = $this->copycolumnset($key);
                $this->ocset[] = $this->copycolumnset($value);
            }
        }
        $this->cset = '(' . implode(',', $this->cset) . ')';
        $this->ocset = implode(',', $this->ocset);
    }

    private function copycolumnset($key)
    {
        if (substr($key, 0, 1) != '#') {
            $function = 0;
        } else {
            $function = 1;
            $key = substr($key, 1, strlen($key));
        }
        if (substr($key, 0, 1) != '@') {
            $keyfunction = 0;
        } else {
            $keyfunction = 1;
            $key = substr($key, 1, strlen($key));
        }
        if ($keyfunction == 1) {
            preg_match('#^([^\[]*)(\[([^\]]*)\])?$#', $key, $keys);
            $table = '';
            $setkey = $keys[1];
            $settag = $keys[3];
        } else {
            preg_match('#^((`)?([^\.`]*)(`)?(\.))?(`)?([^\[`]*)(`)?$#i', $key, $keys);
            if (!empty($keys[3])) {
                $table = $keys[3];
            } else {
                $table = $this->maintable;
            }
            $table = $keys[3];
            $setkey = $keys[7];
            if ($function == 0) {
                if (empty($table)) {
                    $tables = $this->maintable;
                } else {
                    $tables = $table;
                }
            }
            $setkey = '`' . $setkey . '`';
        }
        if (!empty($table)) {
            return '`' . $table . '`.' . $setkey;
        } else {
            return $setkey;
        }
    }

    private function doquery($mode)
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
            if (!empty($this->prearray)) {
                foreach ($this->prearray as $value) {
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

    private function assembling($mode, $prepare = 0)
    {
        if ($prepare == 1) {
            if ($mode != 'insert') {
                $where = !empty($this->where['prewhere']) ? $this->where['prewhere'] : '';
            }
            $set = $this->set['preset'];
        } else {
            if ($mode != 'insert') {
                $where = !empty($this->where['where']) ? $this->where['where'] : '';
            }
            $set = $this->set['set'];
        }
        $this->where['order'] = !empty($this->where['order']) ? $this->where['order'] : '';
        switch ($mode) {
            case 'get':
                $this->where['limit'] = ' limit 0,1';
            case 'select':
                if (!empty($this->groupvalue)) {
                    if (empty($this->where['grouporder'])) {
                        $this->where['grouporder'] = $this->where['order'];
                    }
                    if (!empty($this->where['gorder'])) {
                        $this->where['order'] = $this->where['gorder'];
                    }
                    $sql = 'select ' . $this->groupincolumns . ' from ' . $this->table . $this->join . $where . $this->where['grouporder'];
                    $sql .= ' limit 999999';
                    $sql = 'select ' . $this->groupcolumns . ' from (' . $sql . ') a' . $this->where['group'] . $this->where['having'] . $this->where['order'] . $this->where['limit'];
                } else {
                    $sql = 'select ' . $this->columns . ' from ' . $this->table . $this->join . $where . $this->where['order'] . $this->where['limit'];
                }
                break;
            case 'has':;
            case 'count':
                if (!empty($this->groupvalue)) {
                    $sql = 'select count(*) from (select ' . $this->groupvalue . ' from ' . $this->table . $this->join . $where . $this->where['group'] . $this->where['having'] . $this->where['limit'] . ') a';
                } else {
                    $sql = 'select count(*) from ' . $this->table . $this->join . $where . $this->where['order'] . $this->where['limit'];
                }
                break;
            case 'insert':
                $sql = 'insert into ' . $this->table . $set;
                break;
            case 'update':
                $sql = 'update ' . $this->table . $this->join . $set . $where;

                break;
            case 'delete':
                $sql = 'delete from ' . $this->table . $where;
                break;
            case 'copy':
                $sql = 'insert into ' . $this->table . $this->cset . ' select ' . $this->ocset . ' from ' . $this->otable . $where;
                break;
        }
        return $sql;
    }

    public function debug()
    {
        $this->config['debug'] = 2;
        return $this;
    }

    public function log()
    {
        var_dump($this->queryarr);
    }

    public function last_query()
    {
        echo end($this->queryarr);
    }

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
        //参数处理
        $this->init();
        $this->table($table);
        if (is_array($join)) {
            if (key($join)) {
                $joins = substr(key($join), 0, 1);
            }
        }
        if ($joins == '[') {
            $this->join($join);
        } else {
            $where = $columns;
            $columns = $join;
        }
        $this->getcolumn($table);
        $this->columns($columns);
        if (array_key_exists('isdel', $this->column[$this->main_table]) && $this->config['realdelete'] == 0) {
            if (!empty($where)) {
                $where[$this->main_table . '.isdel[!]'] = 1;
            } else {
                $where = array('isdel[!]' => 1);
            }
        }
        if (!empty($where)) {
            $this->where($where);
        }
        //数据库操作
        $this->doquery('select');
        $return = $this->res->fetchAll();
        if ($this->singlecolumn == true) {
            $returnvalue = array();
            foreach ($return as $value) {
                $returnvalue[] = reset($value);
            }
            $return = $returnvalue;
        }
        return $return;
    }

    public function sql($table, $join = '', $columns = '', $where = [])
    {
        //参数处理
        $this->init();
        $this->table($table);
        if (is_array($join)) {
            if (key($join)) {
                $joins = substr(key($join), 0, 1);
            }
        }
        if ($joins == '[') {
            $this->join($join);
        } else {
            $where = $columns;
            $columns = $join;
        }
        $this->getcolumn($table);
        $this->columns($columns);
        if (array_key_exists('isdel', $this->column[$this->maintable]) && $this->config['realdelete'] == 0) {
            if (!empty($where)) {
                $where[$this->maintable . '.isdel[!]'] = 1;
            } else {
                $where = array('isdel[!]' => 1);
            }
        }
        if (!empty($where)) {
            $this->where($where);
        }
        //返回sql字符串
        return $this->assembling('select');
    }

    public function count($table, $join = '', $where = [])
    {
        //参数处理
        $this->init();
        $this->table($table);
        if (is_array($join)) {
            if (key($join)) {
                $joins = substr(key($join), 0, 1);
            }
        }
        if ($joins == '[') {
            $this->join($join);
        } else {
            $where = $join;
        }
        $this->getcolumn($table);
        if (array_key_exists('isdel', $this->column[$this->maintable]) && $this->config['realdelete'] == 0) {
            if (!empty($where)) {
                $where = array_merge($where, array($this->maintable . '.isdel[!]' => 1));
            } else {
                $where = array('isdel[!]' => 1);
            }
        }
        if (!empty($where)) {
            $this->where($where);
        }
        //数据库操作
        $this->doquery('count');
        $return = $this->res->fetchColumn();
        return $return;
    }

    public function get($table, $join = '', $columns = '', $where = [])
    {
        //参数处理
        $this->init();
        $this->table($table);
        if (is_array($join)) {
            if (key($join)) {
                $joins = substr(key($join), 0, 1);
            }
        }
        if ($joins == '[') {
            $this->join($join);
        } else {
            $where = $columns;
            $columns = $join;
        }
        $this->getcolumn($table);
        $this->columns($columns);
        if (array_key_exists('isdel', $this->column[$this->maintable]) && $this->config['realdelete'] == 0) {
            if (!empty($where)) {
                $where = array_merge($where, array($this->maintable . '.isdel[!]' => 1));
            } else {
                $where = array('isdel[!]' => 1);
            }
        }
        if (!empty($where)) {
            $this->where($where);
        }
        //数据库操作
        $this->doquery('get');
        $return = $this->res->fetch();
        if ($this->singlecolumn == true) {
            $return = $return[0];
        }
        return $return;
    }

    public function has($table, $join = '', $where = [])
    {
        //参数处理
        $this->init();
        $this->table($table);
        if (is_array($join)) {
            if (key($join)) {
                $joins = substr(key($join), 0, 1);
            }
        }
        if ($joins == '[') {
            $this->join($join);
        } else {
            $where = $join;
        }
        $this->getcolumn($table);
        if (array_key_exists('isdel', $this->column[$this->maintable]) && $this->config['realdelete'] == 0) {
            if (!empty($where)) {
                $where = array_merge($where, array($this->maintable . '.isdel[!]' => 1));
            } else {
                $where = array('isdel[!]' => 1);
            }
        }
        if (!empty($where)) {
            $this->where($where);
        }
        $this->doquery('has');
        $return = $this->res->fetchColumn();
        if ($return > 0) {
            return true;
        } else {
            return false;
        }
    }

    public function insert($table, $set)
    {
        //参数处理
        $this->init();
        $this->table($table);
        $this->getcolumn($table);
        $this->columnset($set);
        //数据库操作
        $this->doquery('insert');
        return $this->lastInsertId();
    }

    public function copy($table, $otable, $set = '', $where = [])
    {
        //参数处理
        $this->init();
        $this->table($table);
        $this->table($otable, 'otable');
        if (!empty($set)) {
            $this->copyset($set);
        } else {
            $this->cset = '';
            $this->ocset = '*';
        }
        if (!empty($where)) {
            $this->where($where);
        }
        //数据库操作
        $this->doquery('copy');
        return 1;
        return $this->res->rowCount();
    }

    public function update($table, $join, $set = '', $where = [])
    {
        //参数处理
        $this->init();
        $this->table($table);
        if (is_array($join)) {
            if (key($join)) {
                $joins = substr(key($join), 0, 1);
            }
        }
        if ($joins == '[') {
            $this->join($join);
        } else {
            $where = $set;
            $set = $join;
        }
        $this->getcolumn($table);
        $this->columnset($set);
        if (!empty($where)) {
            $this->where($where);
        }
        //数据库操作
        $this->doquery('update');
        return $this->res->rowCount();
    }

    public function delete($table, $where = [], $mode = 0)
    {
        //参数处理
        $this->init();
        $this->getcolumn($table);
        $this->table($table);
        if ($this->config['realdelete'] == 1 || $mode == 1) {
            if (!empty($where)) {
                $this->where($where);
            }
            //数据库操作
            $this->doquery('delete');
            return $this->res->rowCount();
        } else {
            if (!array_key_exists('isdel', $this->column[$this->maintable])) {
                $sql = "ALTER TABLE " . $this->table . " ADD COLUMN `isdel`  tinyint(1) NOT NULL DEFAULT 0 , ADD INDEX (`isdel`)";
                //数据库操作
                $this->query($sql);
            }
            return $this->update($table, array("#isdel" => 1), $where);
        }
    }

    public function showtables($table = '')
    {
        if (empty($table)) {
            $sql = 'show tables';
        } else {
            $sql = "show table status like '" . $this->config['prefix'] . $table . "'";
        }
        $this->res = $this->query($sql);
        return $this->res->fetchAll();
    }

    public function istableexist($table)
    {
        $return = $this->has('information_schema.tables', '', array('@table_name' => $table, 'TABLE_SCHEMA' => $this->config['database']));
        return $return;
    }

    public function creattable($table, $tablecomment = '', $fields)
    {
        $this->init();
        $indexarray = array();
        $sql = "CREATE TABLE `" . $this->config['prefix'] . $table . "` (";
        foreach ($fields as $key => $value) {
            $sql .= "`" . $key . "`  " . $value['type'];
            if ($value['length']) {
                $sql .= "(" . $value['length'];
                if ($value['decimalpoint']) {
                    $sql .= "," . $value['decimalpoint'];
                }
                $sql .= ')';
            }
            if ($value['required']) {
                $sql .= ' NOT NULL';
            }
            if ($value['auto']) {
                $sql .= ' AUTO_INCREMENT';
            }
            if ($value['primary']) {
                $primarysql = ",PRIMARY KEY (`" . $key . "`)";
            }
            if ($value['indexing'] == 1) {
                $indexarray[] = '`' . $key . '`';
            } else if ($value['indexing'] == 2) {
                $uniqueindexarray[] = '`' . $key . '`';
            }
            if (isset($value['defaultset'])) {
                if ($value['defaultmode'] != 1) {
                    $value['defaultset'] = $this->checkvalue($value['defaultset'], $value['type']);
                    $value['defaultset'] = $value['defaultset']['value'];
                    $sql .= " DEFAULT " . $value['defaultset'];
                }
            }
            if ($value['comment']) {
                $sql .= " COMMENT '" . $value['comment'] . "'";
            }
            $sql .= ",";
        }
        $sql = substr($sql, 0, strlen($sql) - 1) . $primarysql;
        if (!empty($uniqueindexarray)) {
            foreach ($uniqueindexarray as $value) {
                $sql .= ",INDEX (" . $value . ")";
            }
        }
        if (!empty($indexarray)) {
            foreach ($indexarray as $value) {
                $sql .= ",INDEX (" . $value . ")";
            }
        }
        $sql .= ")";
        if (!empty($tablecomment)) {
            $sql .= "COMMENT='" . $tablecomment . "'";
        }
        if ($this->config['debug'] != 0) {
            echo $sql;
            if ($this->config['debug'] == 2) {
                exit;
            }
        }
        $this->query($sql);
    }

    public function getindex($table)
    {
        $sql = "show index from `" . $table . "`";
        $this->index = $this->query($sql)->fetchAll();
        return $this->index;
    }

    private function delindex($table)
    {
        $this->getindex($table);
        foreach ($this->index as $value) {
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
            $sql = substr($sql, 0, strlen($sql) - 1);
            $this->query($sql);
        }
    }

    public function updatetable($table, $tablecomment = '', $fields)
    {
        $this->init();
        $this->getcolumn($table);
        $this->table($table);
        foreach ($this->column[$this->maintable] as $key => $value) {
            if ($value[0]['Key'] == 'PRI') {
                $maincolumn = $key;
            }
        }
        $this->delindex($this->maintable);
        $indexarray = array();
        $sql = "ALTER TABLE `" . $this->maintable . "` ";
        $i = 0;
        foreach ($fields as $key => $value) {
            if (!array_key_exists($key, $this->column[$this->maintable])) {
                $sql .= "ADD";
                if ($value['defaultmode'] == 1) {
                    $value['defaultmode'] = 0;
                }
            } else if ($value['replaceid']) {
                $sql .= "CHANGE";
            } else {
                $sql .= "MODIFY";
            }
            $sql .= " COLUMN `" . $key . "`";
            if ($value['replaceid']) {
                $sql .= " `" . $value['replaceid'] . "`";
            }
            $sql .= " " . $value['type'];
            if ($value['length']) {
                $sql .= "(" . $value['length'];
                if ($value['decimalpoint']) {
                    $sql .= "," . $value['decimalpoint'];
                }
                $sql .= ')';
            }
            if ($value['required']) {
                $sql .= ' NOT NULL';
            }
            if ($value['auto']) {
                $sql .= ' AUTO_INCREMENT';
            }
            if ($value['primary']) {
                $primarysql = ",PRIMARY KEY (`" . $key . "`)";
            }
            if ($value['indexing'] == 1) {
                $indexarray[] = '`' . $key . '`';
            } else if ($value['indexing'] == 2) {
                $uniqueindexarray[] = '`' . $key . '`';
            }
            if (isset($value['defaultset'])) {
                if ($value['defaultmode'] != 1) {
                    $value['defaultset'] = $this->checkvalue($value['defaultset'], $value['type']);
                    $value['defaultset'] = $value['defaultset']['value'];
                }
                $sql .= " DEFAULT " . $value['defaultset'];
            }
            if ($value['comment']) {
                $sql .= " COMMENT '" . $value['comment'] . "'";
            }
            if ($i == 0) {
                $column = $maincolumn;
            } else {
                // $column = $precolumn;
                empty($column);
            }
            // if (empty($value['replaceid'])) {
            //     $precolumn = $key;
            // } else {
            //     $precolumn = $value['replaceid'];
            // }
            if (empty($column)) {
                $sql .= " FIRST,";
            } else {
                $sql .= " AFTER `" . $column . "`,";
            }
            $i++;
        }
        $sql = substr($sql, 0, strlen($sql) - 1) . $primarysql;
        if (!empty($tablecomment)) {
            $sql .= ",COMMENT='" . $tablecomment . "'";
        }
        if ($this->config['debug'] != 0) {
            echo $sql;
            if ($this->config['debug'] == 2) {
                exit;
            }
        }
        $this->query($sql);

        if (!empty($indexarray)) {
            foreach ($indexarray as $value) {
                $indexsql = "ALTER TABLE `" . $this->maintable . "` ADD INDEX (" . $value . ")";
                $this->query($indexsql);
            }
        }
        if (!empty($uniqueindexarray)) {
            foreach ($uniqueindexarray as $value) {
                $indexsql = "ALTER TABLE `" . $this->maintable . "` ADD UNIQUE (" . $value . ")";
                $this->query($indexsql);
            }
        }
    }

    public function renametable($table, $newtable)
    {
        $sql = 'ALTER  TABLE `' . $this->config['prefix'] . $table . '` RENAME TO `' . $newtable . '`';
        $this->query($sql);
    }

    public function has_table($table)
    {
        $sql = "show tables like '" . $this->config['prefix'] . $table . "'";
        $this->res = $this->query($sql);
        $return = $this->res->fetchColumn();
        if ($return > 0) {
            return true;
        } else {
            return false;
        }
    }
}
