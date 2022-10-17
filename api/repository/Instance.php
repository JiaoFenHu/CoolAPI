<?php
declare(strict_types = 1);

namespace repository;

class Instance
{
    protected static array $_instances = [];

    /**
     * 单例模式加载service类
     * @return mixed|BaseService|static
     */
    final public static function getInstance()
    {
        $service = get_called_class();
        if (!(self::$_instances[$service] instanceof Instance)) {
            self::$_instances[$service] = new static();
        }
        return self::$_instances[$service];
    }
}
