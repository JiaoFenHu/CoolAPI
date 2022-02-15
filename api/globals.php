<?php
header("Content-type:text/html;charset=utf-8");
define('DS', DIRECTORY_SEPARATOR);
define('BASE_DIR', dirname(__DIR__). DS);
define('LIB_DIR', BASE_DIR . 'libs' . DS);
define('COMPOSER_DIR', BASE_DIR . 'vendor' . DS);
define('API_DIR', __DIR__ . DS);
define('INC_DIR', API_DIR . 'inc' . DS);
define('SERVICE_DIR', API_DIR . 'service' . DS);

/**
 * 程序环境切换
 * development:开发
 * release:预发布
 * production:生产
 */
define('PROJECT_ENV', 'development');