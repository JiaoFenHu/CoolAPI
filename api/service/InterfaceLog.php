<?php
declare(strict_types=1);

namespace service;

use repository\BaseService;

class InterfaceLog extends BaseService
{
    function __construct(\api $api)
    {
        self::$api = $api;
        $this->table_name = 'interface_log';
        $this->table_name_as = 'interface_log(il)';
        $this->initDB();
    }

}
