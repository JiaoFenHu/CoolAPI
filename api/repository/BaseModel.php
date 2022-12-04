<?php
declare(strict_types=1);

namespace repository;

use repository\CoolORM as ORM;

class BaseModel extends ORM
{
    protected static array $_instances = [];

    /**
     * 单例模式加载service类
     * @return mixed|BaseService|static
     */
    final public static function getInstance()
    {
        $model = get_called_class();
        if (!(self::$_instances[$model] instanceof BaseModel)) {
            self::$_instances[$model] = new static();
        }
        return self::$_instances[$model];
    }

    /**
     * 设置table表名
     * @param $camelCaps
     * @param string $separator
     * @return void
     */
    final protected function setSplitTableName($camelCaps, string $separator = '_'): void
    {
        preg_match_all('/([A-Z])/', $camelCaps, $tableAsArr);
        $this->table_name = strtolower(preg_replace('/([a-z])([A-Z])/', "$1{$separator}$2", $camelCaps));
        $this->table_name_as = strtolower(implode('', $tableAsArr));
    }

}