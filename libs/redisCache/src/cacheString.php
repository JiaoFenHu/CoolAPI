<?php


namespace libs\redisCache\src;

use libs\redisCache\connection;


class cacheString extends connection
{
    /**
     * cacheString constructor.
     * @param array $config
     * @throws \libs\redisCache\redisSystemException
     */
    public function __construct($config = [])
    {
        parent::__construct($config);
    }



    /**
     * 存储一个键值
     * @param string|int $key 键名
     * @param mixed $value 键值，支持数组、对象
     * @param int $expire 过期时间(秒)
     * @return bool 返回布尔值
     */
    public function set($key, $value, $expire = -1)
    {
        if (is_int($key) || is_string($key)) {
            //如果是int类型的数字就不要序列化，否则用自增自减功能会失败，
            //如果不序列化，set()方法只能保存字符串和数字类型,
            //如果不序列化，浮点型数字会有失误，如13.6保存，获取时是13.59999999999
            $value = is_int($value) ? $value : serialize($value);
            $expire = (int)$expire ? $expire : $this->expire;
            if (self::$redis->set($key, $value) && $this->expire($key, $expire)) {
                return true;
            }
            return false;
        }
        return false;
    }

    /**
     * 获取键值
     * @param string|int $key 键名
     * @return mixed 返回值
     */
    public function get($key)
    {
        $value = self::$redis->get($key);
        if (is_object($value)) {
            return $value;
        }
        return is_numeric($value) ? $value : unserialize($value);
    }

    /**
     * 删除一个键值
     * @param string|int $key 键名
     * @return int 成功返回1 ，失败或不存在键返回0
     */
    public function del($key)
    {
        return self::$redis->del($key);
    }

    /**
     * 截取字符串,支持汉字
     * @param string|int $key 键名
     * @param int $start 起始位，从0开始
     * @param int $end 截取长度
     * @return string   返回字符串,如果键不存在或值不是字符串类型则返回false
     */
    public function substr($key, $start, $end = 0)
    {
        $value = $this->get($key);
        if ($value && is_string($value)) {
            if ($end) {
                return mb_substr($value, $start, $end);
            }
            return mb_substr($value, $start);
        }
        return false;
    }

    /**
     * 设置指定 key 的值，并返回 key 的旧值
     * @param string|int  $key 键名
     * @param mixed $value 要指定的健值，支持数组
     * @param int $expire 过期时间，如果不填则用全局配置
     * @return mixed 返回旧值，如果旧值不存在则返回false,并新创建key的键值
     */
    public function replace($key, $value, $expire = 0)
    {
        $value = self::$redis->getSet($key, $value);
        $expire = (int)$expire ? $expire : $this->expire;
        $this->expire($key, $expire);
        return is_numeric($value) ? $value : unserialize($value);
    }

    /**
     * 同时设置一个或多个键值对。（支持数组值）
     * @param array $arr [要设置的键值对数组]
     * @return bool 返回布尔值，成功true否则false
     */
    public function mSet($arr)
    {
        if ($arr && is_array($arr)) {
            foreach ($arr as &$value) {
                $value = is_int($value) ? $value : serialize($value);
            }
            if (self::$redis->mset($arr)) {
                return true;
            }
            return false;
        }
        return false;
    }

    /**
     * 返回所有(一个或多个)给定 key 的值
     * 可传入一个或多个键名参数，键名字符串类型，如 $values = $redis::mget('one','two','three', ...);
     * @param $keys
     * @return array|false 返回包含所有指定键值数组，如果不存在则返回false
     */
    public function mGet($keys)
    {
        $keys = is_string($keys) ? [$keys] : $keys;
        if ($keys) {
            $values = self::$redis->mget($keys);
            if ($values) {
                foreach ($values as &$value) {
                    $value = is_numeric($value) ? $value : unserialize($value);
                }
                return $values;
            }
        }
        return false;
    }

    /**
     * 查询剩余过期时间（秒）
     * @param string|int $key  键名
     * @return int 返回剩余的时间，如果已过期则返回负数
     */
    public function expireTime($key)
    {
        return self::$redis->ttl($key);
    }

    /**
     * 指定的 key 不存在时，为 key 设置指定的值(SET if Not eXists)
     * @param string|int $key  键名
     * @param mixed $value 要指定的健值，支持数组
     * @param int $expire 过期时间，如果不填则用全局配置
     * @return bool  设置成功返回true 否则false
     */
    public function setNx($key, $value, $expire = 0)
    {
        $value = is_int($value) ? $value : serialize($value);
        $res = self::$redis->setnx($key, $value);
        if ($res) {
            $expire = (int)$expire ? $expire : $this->expire;
            $this->expire($key, $expire);
        }
        return $res;
    }

    /**
     * 返回对应键值的长度
     * @param string|int $key  键名
     * @return int  返回字符串的长度，如果键值是数组则返回数组元素的个数，如果键值不存在则返回0
     */
    public function strLen($key)
    {
        $value = $this->get($key);
        $length = 0;
        if ($value) {
            if (is_array($value)) {
                $length = count($value);
            } else {
                $length = strlen($value);
            }
        }
        return $length;
    }

    /**
     * 将 key 中储存的数字值自增
     * @param string|int $key  键名
     * @param int $int 自增量，如果不填则默认是自增量为 1
     * @return int  返回自增后的值，如果键不存在则新创建一个值为0，并在此基础上自增，返回自增后的数值.如果键值不是可转换的整数，则返回false
     */
    public function inc($key, $int = 0)
    {
        if ((int)$int) {
            return self::$redis->incrby($key, $int);
        } else {
            return self::$redis->incr($key);
        }
    }

    /**
     * 将 key 中储存的数字值自减
     * @param string|int $key  键名
     * @param int $int 自减量，如果不填则默认是自减量为 1
     * @return int  返回自减后的值，如果键不存在则新创建一个值为0，并在此基础上自减，返回自减后的数值.如果键值不是可转换的整数，则返回false
     */
    public function dec($key, $int = 0)
    {
        if ((int)$int) {
            return self::$redis->decrby($key, $int);
        } else {
            return self::$redis->decr($key);
        }
    }

    /**
     * 为指定的 key 追加值
     * @param string|int $key  键名
     * @param mixed $value 要指定的健值，支持数组
     * @param bool $pos 要追加的位置，默认false为追加至末尾，true则追加到开头
     * @param int $expire 过期时间，如果不填则用全局配置
     * @return bool  设置成功返回true 否则false,支付向字符串或者数组追加内容，向字符串追加时加入的值必须为字符串类型，如果健不存在则创建新的键值对
     */
    public function append($key, $value, $pos = false, $expire = 0)
    {
        $cache = $this->get($key);
        if ($cache) {
            if (is_array($cache)) {
                if ($pos === true) {
                    $value = array_unshift($cache, $value);
                } else {
                    $value = array_push($cache, $value);
                }
            } else {
                if (!is_string($value)) {
                    return false;
                }
                if ($pos === true) {
                    $value .= $cache;
                } else {
                    $value = $cache . $value;
                }
            }
        }
        return $this->set($key, $value, $expire);
    }


    /**
     * 重置key的名称
     * @param string $key
     * @param string $rename
     * @return bool true:成功,false:失败
     */
    public function renameKey($key, $rename)
    {
        return self::$redis->rename($key, $rename);
    }


}