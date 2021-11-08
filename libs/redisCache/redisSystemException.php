<?php


namespace libs\redisCache;


class redisSystemException extends \Exception
{
    public function redisMessage()
    {
        return $this->getMessage();
    }
}