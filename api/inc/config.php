<?php
session_start();
ini_set('date.timezone', 'Asia/Shanghai');
require_once(INC_DIR . 'db.class.php');
require_once(INC_DIR . 'function.inc.php');
require_once(INC_DIR . 'defines.' . PROJECT_ENV . '.php');

$db_config = array(
	'dbtype' => 'mysql',
	'host' => 'localhost',
	'port' => '',
	'database' => 'zx',
	'name' => 'root',
	'password' => 'eoner.com',
	'log' => 1,
	'prepare' => 1,
	'realdelete' => 0, //虚拟删除开关，设置为0时，调用delete方法不删除对应条目，而是把对应条目的is_del属性设置为1
	'charset' => 'utf8',
	'prefix' => 'tb_',
	'option' => array(PDO::ATTR_CASE => PDO::CASE_NATURAL),
);
$db = new DB($db_config);
