<?php

namespace libs\redisCache\src;

use libs\redisCache\connection;

class cacheSet extends connection
{
    public function __construct($config = [])
    {
        parent::__construct($config);
    }

    /**
     * 将一个或多个元素加入到无序集合中，已经存在于集合的元素将被忽略.如果集合不存在，则创建一个只包含添加的元素作成员的集合。
     * @param string $set 集合名称
     * @param string|array $value 元素值（唯一）,如果要加入多个元素请传入多个元素的数组
     * @return int  返回被添加元素的数量.如果$set不是集合类型时返回0
     */
    public function sAdd($set, $value)
    {
        $num = 0;
        if (is_array($value)) {
            foreach ($value as $key => $v) {
                $num += self::$redis->sAdd($set, $v);
            }
        } else {
            $num += self::$redis->sAdd($set, $value);
        }
        return $num;
    }

    /**
     * 返回无序集合中的所有的成员
     * @param string $set 集合名称
     * @return array 返回包含所有成员的数组
     */
    public function sMembers($set)
    {
        return self::$redis->sMembers($set);
    }

    /**
     * 获取集合中元素的数量。
     * @param string $set 集合名称
     * @return int  返回集合的成员数量
     */
    public function sCard($set)
    {
        return self::$redis->sCard($set);
    }

    /**
     * 移除并返回集合中的一个随机元素
     * @param string $set 集合名称
     * @param int $count 要随机移除原始的个数, 最大移除数量小于集合的总数量
     * @return string|bool 返回移除的元素,如果集合为空则返回false
     */
    public function sPop($set, $count = 1)
    {
        return self::$redis->sPop($set, $count);
    }

    /**
     * 移除集合中的一个或多个成员元素，不存在的成员元素会被忽略
     * @param string $set 集合名称
     * @param string|array $member 要移除的元素，如果要移除多个请传入多个元素的数组
     * @return int  返回被移除元素的个数
     */
    public function sRem($set, $member)
    {
        $num = 0;
        if (is_array($member)) {
            foreach ($member as $value) {
                $num += self::$redis->sRem($set, $value);
            }
        } else {
            $num += self::$redis->sRem($set, $member);
        }
        return $num;
    }

    /**
     * 返回集合中的一个或多个随机元素
     * @param string $set 集合名称
     * @param int $count 要返回的元素个数，0表示返回单个元素，大于等于集合基数则返回整个元素数组。默认0
     * @return string|array   返回随机元素，如果是返回多个则为数组返回
     */
    public function sRand($set, $count = 0)
    {
        return ((int)$count == 0) ? self::$redis->sRandMember($set) : self::$redis->sRandMember($set, $count);
    }

    /**
     * 返回给定集合之间的差集(集合1对于集合2的差集)。不存在的集合将视为空集
     * @param string $set1 集合1名称
     * @param string $set2 集合2名称
     * @return array  返回差集（即筛选存在集合1中但不存在于集合2中的元素）
     */
    public function sDiff($set1, $set2)
    {
        return self::$redis->sDiff($set1, $set2);
    }

    /**
     * 将给定集合set1和set2之间的差集存储在指定的set集合中。如果指定的集合已存在，则会被覆盖。
     * @param string $set 指定存储的集合
     * @param string $set1 集合1
     * @param string $set2 集合2
     * @return int  返回指定存储集合元素的数量
     */
    public function sDiffStore($set, $set1, $set2)
    {
        return self::$redis->sDiffStore($set, $set1, $set2);
    }

    /**
     * 返回两个集合的交集（即筛选同时存在多个集合中的元素）
     * @param $sets array 集合组
     * @return array|boolean
     */
    public function sinter($sets)
    {
        if (!is_array($sets) || count($sets) < 2) return false;
        return call_user_func_array([self::$redis, "sInter"], $sets);
    }

    /**
     * 将给定集合set1和set2之间的交集存储在指定的set集合中。如果指定的集合已存在，则会被覆盖。
     * @param $set string 指定存储的集合
     * @param $sets array 待比对集合组
     * @return int 返回指定存储集合元素的数量
     */
    public function sInterStore($set, $sets)
    {
        if (is_string($sets)) $sets = [$sets];
        array_unshift($sets, $set);
        return call_user_func_array([self::$redis, "sInterStore"], $sets);
    }

    /**
     * 判断成员元素是否是集合的成员
     * @param string $set 集合名称
     * @param string $member 要判断的元素
     * @return bool 如果成员元素是集合的成员返回true,否则false
     */
    public function sIsMember($set, $member)
    {
        return self::$redis->sIsMember($set, $member);
    }

    /**
     * 将元素从集合1中移动到集合2中
     * @param string $set1 集合1
     * @param string $set2 集合2
     * @param string $member 要移动的元素成员
     * @return bool  成功返回true,否则false
     */
    public function sMove($set1, $set2, $member)
    {
        return self::$redis->sMove($set1, $set2, $member);
    }

    /**
     * 返回集合1和集合2的并集(即两个集合合并后去重的结果)。不存在的集合被视为空集。
     * @param string $sets 集合组
     * @return array|boolean  返回并集数组
     */
    public function sUnion($sets)
    {
        if (!is_array($sets) || count($sets) < 2) return false;
        return self::$redis->sUnion($sets);
    }

    /**
     * 将给定集合set1和set2之间的并集存储在指定的set集合中。如果指定的集合已存在，则会被覆盖。
     * @param string $set 指定存储的集合
     * @param string $set1 集合1
     * @param string $set2 集合2
     * @return int  返回指定存储集合元素的数量
     */
    public function sUnionStore($set, $set1, $set2)
    {
        return self::$redis->sUnionStore($set, $set1, $set2);
    }

}