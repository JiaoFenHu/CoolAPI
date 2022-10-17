<?php
session_start();
ini_set('date.timezone', 'Asia/Shanghai');
header("Content-type:text/html;charset=utf-8");
header('Access-Control-Allow-Origin:*');

define('DS', DIRECTORY_SEPARATOR);
define('BASE_DIR', dirname(__FILE__). DS);
define('LIB_DIR', BASE_DIR . 'libs' . DS);
define('COMPOSER_DIR', BASE_DIR . 'vendor' . DS);
define('API_DIR', BASE_DIR . 'api' . DS);
define('COMMON_DIR', API_DIR . 'common' . DS);
define('SERVICE_DIR', API_DIR . 'service' . DS);
define('CONFIG_DIR', API_DIR . 'configs' . DS);

/**
 * 程序环境切换
 * development:开发
 * release:预发布
 * production:生产
 */
define('PROJECT_ENV', 'development');


require(COMMON_DIR . 'functions.php');
require(COMMON_DIR . 'defines.php');

if (getProEnv('system.showPHPError')) {
    ini_set('display_errors', 1);
    error_reporting(E_ALL ^ E_NOTICE);
} else {
    error_reporting(0);
}