<?php
declare(strict_types = 1);

namespace repository;

class BaseService
{
    protected \api $api;
    protected static array $_instances = [];

    /**
     * 单例模式加载service类
     * @return mixed|BaseService|static
     */
    final public static function getInstance(\api $api)
    {
        $service = get_called_class();
        if (!(self::$_instances[$service] instanceof BaseService)) {
            self::$_instances[$service] = new static();
            self::$_instances[$service]->api = $api;
        }
        return self::$_instances[$service];
    }
}
