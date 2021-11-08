<?php

namespace libs\redisCache;

use libs\redisCache\redisSystemException;
use \Redis as BaseRedis;
use \RedisException;


class connection
{
    public static $redis = null;

    /**
     * 默认存储时间(秒)
     * @var int 0 不设置时间
     */
    public $expire = 0;

    /**
     * 链接地址
     * @var string
     */
    private $host = '127.0.0.1';

    /**
     * 端口
     * @var string
     */
    private $port = '6379';

    /**
     * 访问密码
     * @var string
     */
    private $auth = '';

    /**
     * 数据库选择
     * @var int
     */
    private $db = 0;

    /**
     * 链接超时时间
     * @var int
     */
    private $timeout = 5;

    /**
     * connection constructor.
     * @param array $config
     * @throws \libs\redisCache\redisSystemException
     */
    public function __construct($config = [])
    {
        if (!is_array($config)) throw new redisSystemException("配置参数格式错误!");
        if (static::$redis == null) {
            $this->setConfig($config);

            try {

                if (self::$redis == null) {
                    self::$redis = new \Redis();
                }

                self::$redis->connect($this->host, $this->port, $this->timeout);
                if (!empty($this->auth)) {
                    self::$redis->auth($this->auth);
                }
                if ($this->db) {
                    self::$redis->select((int)$this->db);
                }
            } catch (\RedisException $e) {
                throw new redisSystemException("redis链接失败, error:" . $e->getMessage());
            }
        }
    }

    /**
     * 配置
     * @param array $config
     */
    private function setConfig($config)
    {
        if (isset($config['host'])) $this->host = $config['host'];
        if (isset($config['port'])) $this->port = $config['port'];
        if (isset($config['auth'])) $this->auth = $config['auth'];
        if (isset($config['db'])) $this->db = $config['db'];
        if (isset($config['timeout'])) $this->timeout = $config['timeout'];
    }


    /**
     * 切换到指定的数据库, 数据库索引号用数字值指定
     * @param $db
     */
    public function selectDb($db)
    {
        self::$redis->select((int)$db);
    }

    /**
     * 创建当前数据库的备份(该命令将在 redis 安装目录中创建dump.rdb文件)
     * @return bool 成功true否则false (如果需要恢复数据，只需将备份文件 (dump.rdb) 移动到 redis 安装目录并启动服务即可)
     */
    public function saveDb()
    {
        return self::$redis->save();
    }

    /**
     * 设置过期时间
     * @param string|int $key 键名
     * @param int $expire 过期时间(秒)
     * @return bool 返回布尔值  [如果成功返回true,如果键不存在或已过期则返回false]
     */
    public function expire($key, $expire = 0)
    {
        if ($expire > 0) {
            if (self::$redis->expire($key, $expire)) {
                return true;
            }
            return false;
        }
        return true;
    }

    /**
     * 移除某个键的存储有效时间
     * @param $key
     * @return bool
     */
    public function removeExpire($key)
    {
        return self::$redis->persist($key);
    }


    /**
     * 开启事务
     */
    public function transaction()
    {
        self::$redis->multi();
    }

    /**
     * 提交事务
     */
    public function commit()
    {
        self::$redis->exec();
    }

    /**
     * 取消事务
     */
    public function discard()
    {
        self::$redis->discard();
    }
}