<?php
declare(strict_types = 1);

namespace repository;

use repository\CoolORM as ORM;

class BaseService extends ORM
{
    protected static \api $api;
    protected static array $_instances = [];

    /**
     * 单例模式加载service类
     * @return mixed|BaseService|static
     */
    final public static function getInstance()
    {
        $service = get_called_class();
        if (!(self::$_instances[$service] instanceof BaseService)) {
            self::$_instances[$service] = new static(static::$api);
        }
        return self::$_instances[$service];
    }
}
