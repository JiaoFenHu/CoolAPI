<?php
class interfaceLog extends base
{
	public $db;
	public $api;

	function __construct($api)
	{
		$this->api = $api;
		$this->table_name = 'interface_log';
		$this->table_name_as = 'interface_log(il)';
		$this->initDB();
	}

}
