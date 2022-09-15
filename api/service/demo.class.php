<?php
class demo extends base
{
    function __construct(api $api)
    {
        $this->api = $api;
    }

    public function test()
    {
        $param = $this->initParam();
        prints($param, true, false);
        prints($this->api->memberId);
    }
}
