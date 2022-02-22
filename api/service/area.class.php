<?php
class area extends base
{
	public $db;
	public $api;

	function __construct($api)
	{
		$this->api = $api;
		$this->table_name = 'area';
		$this->table_name_as = 'area(ta)';
		$this->initDB();
	}

}
