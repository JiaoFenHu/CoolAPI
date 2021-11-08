<?php


namespace libs\redisCache\src;


use libs\redisCache\connection;

class PSubscribe extends connection
{
    public function __construct($config = [])
    {
        parent::__construct($config);
    }

    /**
     * 订阅
     * @param array $patterns 订阅频道
     * @param string $callback 回调函数
     */
    public function pSubscribe($patterns, $callback)
    {
        self::$redis->psubscribe($patterns, $callback);
    }

    /**
     * 发布
     * @param string $channel 发布通道名称
     * @param string $message 发布内容
     * @return int
     */
    public function publish($channel, $message)
    {
        if (is_array($message)) {
            $message = json_encode_ex($message);
        }
        return self::$redis->publish($channel, $message);
    }
}