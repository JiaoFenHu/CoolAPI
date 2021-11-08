<?php


namespace libs\redisCache\src;


use libs\redisCache\connection;

class cacheList extends connection
{
    public function __construct($config = [])
    {
        parent::__construct($config);
    }

    /**
     * 将一个或多个值插入到列表头部（值可重复）或列表尾部。如果列表不存在，则创建新列表并插入值
     * @param string $list 列表名
     * @param string|array $value 要插入的值,如果是多个值请放入数组传入
     * @param string $pop 要插入的位置，默认first头部,last表示尾部
     * @param int $expire 过期时间, 如果不填则不设置过期时间
     * @return boolean 返回列表的长度
     */
    public function push($list, $value, $pop = 'last', $expire = 0)
    {
        if (is_string($value)) $value = [$value];
        foreach ($value as $v) {
            $result = ($pop == 'last') ? self::$redis->rPush($list, $v) : self::$redis->lPush($list, $v);
        }
        if ((int)$expire) {
            $this->expire($list, $expire);
        }
        return !empty($result);
    }

    /**
     * 通过索引获取列表中的元素
     * @param string $list 列表名
     * @param int $index 索引位置，从0开始计,默认0表示第一个元素，-1表示最后一个元素索引
     * @return string  返回指定索引位置的元素
     */
    public function lIndex($list, $index = 0)
    {
        return self::$redis->lIndex($list, $index);
    }

    /**
     * 通过索引来设置元素的值
     * @param string $list 列表名
     * @param string $value 元素值
     * @param int $index 索引值
     * @return bool  成功返回true,否则false.当索引参数超出范围，或列表不存在返回false。
     */
    public function lSet($list, $index, $value)
    {
        return self::$redis->lSet($list, $index, $value);
    }

    /**
     * 返回列表中指定区间内的元素
     * @param string $list 列表名
     * @param int $start 起始位置，从0开始计,默认0
     * @param int $end 结束位置，-1表示最后一个元素，默认-1
     * @return array  返回列表元素数组
     */
    public function lRange($list, $start = 0, $end = -1)
    {
        return self::$redis->lRange($list, $start, $end);
    }

    /**
     * 返回列表的长度
     * @param string $list 列表名
     * @return int  列表长度
     */
    public function lLen($list)
    {
        return self::$redis->lLen($list);
    }

    /**
     * 移出并获取列表的第一个元素或最后一个元素
     * @param string $list 列表名
     * @param string $pop 移出并获取的位置，默认first为第一个元素
     * @return string|bool  列表第一个元素或最后一个元素,如果列表不存在则返回false
     */
    public function lPop($list, $pop = 'first')
    {
        if ($pop == 'last') {
            return self::$redis->rPop($list);
        }
        return self::$redis->lPop($list);
    }

    /**
     * 从列表中弹出最后一个值，将弹出的元素插入到另外一个列表开头并返回这个元素
     * @param string $list1 要弹出元素的列表名
     * @param string $list2 要接收元素的列表名
     * @return string|bool  返回被弹出的元素,如果其中有一个列表不存在则返回false
     */
    public function PopPush($list1, $list2)
    {
        if ($this->lRange($list1) && $this->lRange($list2)) {
            return self::$redis->brpoplpush($list1, $list2, 500);
        }
        return false;
    }

    /**
     * 用于在指定的列表元素前或者后插入元素。如果元素有重复则选择第一个出现的。当指定元素不存在于列表中时，不执行任何操作
     * @param string $list 列表名
     * @param string $element 指定的元素
     * @param string $value 要插入的元素
     * @param string $pop 要插入的位置，before前,after后。默认before
     * @return int  返回列表的长度。 如果没有找到指定元素 ，返回 -1 。 如果列表不存在或为空列表，返回 0 。
     */
    public function lInsert($list, $element, $value, $pop = 'before')
    {
        return self::$redis->lInsert($list, $pop, $element, $value);
    }

    /**
     * 移除列表中指定的元素
     * @param string $list 列表名
     * @param string $element 指定的元素
     * @param int $count 要删除的个数，0表示删除所有指定元素，负整数表示从表尾搜索, 默认0
     * @return int  被移除元素的数量。 如果指定的key不是列表或者不存在时返回 false
     */
    public function lRem($list, $element, $count = 0)
    {
        return self::$redis->lRem($list, $count, $element);
    }

    /**
     * 让列表只保留指定区间内的元素，不在指定区间之内的元素都将被删除
     * @param string $list 列表名
     * @param int $start 起始位置，从0开始
     * @param int $stop 结束位置，负数表示倒数第n个
     * @return bool  成功返回true否则false
     */
    public function lTrim($list, $start, $stop)
    {
        return self::$redis->lTrim($list, $start, $stop);
    }
}