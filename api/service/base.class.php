<?php
class base extends orm
{
    /**
     * 初始化接口请求参数
     * @return array|mixed
     */
    final protected function initParam()
    {
        $params = $this->api->param;
        if (is_array($params) && array_key_exists('token', $params)) {
            $JWT = $this->api->loadService("jwtAuthorize");
            $JWT->verifyToken($params['token']);
        }
        return $params;
    }
}
