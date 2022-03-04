<?php
class interfaceLog extends base
{
	function __construct(api $api)
	{
		$this->api = $api;
		$this->table_name = 'interface_log';
		$this->table_name_as = 'interface_log(il)';
		$this->initDB();
	}

}
