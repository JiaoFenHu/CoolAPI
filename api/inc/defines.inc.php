<?php

define('PLATFORM', '优选方法集合（开发）');
define('API_DOMAIN', isHttps() . '://' . $_SERVER['HTTP_HOST'] . '/');
define('API_DOMAIN_REAL', 'https://hzsmk.lookbi.com/');
define('AVATAR', API_DOMAIN . 'libs/avatar.jpg');
define('SHOW_API', true); // 配置是否展示api文档，如果无此常量就会读取数据库配置，此常量优先于数据库配置

//token有效期，单位小时
define('VALIDITY', 24);

/**
 * 上传类型
 * LOCAL 本地
 * OSS 阿里OSS
 * BOTH 全部
 */
define('UPLOAD_MODE', 'LOCAL');
define('UPLOAD_DIRECTORY', 'uploads');

//默认缩略图宽度，只有本地存储才生成缩略图, 默认缩略图高度
define('SIMG_WIDTH', '400');
define('SIMG_HEIGHT', '400');
define('FILE_SPLIT', '<{|}>');
define('MAX_FILESIZE', 30); //最大上传文件大小，单位M

//阿里OSS
define('ALIOSS_KEYID', 'LTAI7HMGO5kVcS5q');
define('ALIOSS_SECRET', 'Biy96o9lfwiz0DHtlKuzk09xedLDeS');
define('ALIOSS_ENDPOINT', 'oss-cn-hangzhou.aliyuncs.com');
define('ALIOSS_BUCKET', 'lookbiimage');
define('ALIOSS_URL', 'http://image.lookbi.com/');

/**
 * 短信模板
 * MESSAGE_MODE:
 *  - ALIDAYU 阿里大于
 *  - ALIYUN  阿里云
 */
define('MESSAGE_APPKEY', '23391749');
define('MESSAGE_SECRETKEY', '6eef104ed6e80736ee310686b16337f0');
define('MESSAGE_SIGNAME', '易网测试');
define('MESSAGE_MODE', 'ALIDAYU');
define('MESSAGE_TEMPLATE', 'SMS_10795433');
define('MESSAGE_TEMPLATE_LIST', [
    'REGISTERED' => array('name' => '注册', 'template' => 'SMS_2550004'),
    'RETRIEVE_PASSWORD' => array('name' => '找回密码', 'template' => 'SMS_10795433'),
    'MODIFY_PAYMENT_PASSWORD' => array('name' => '修改支付密码', 'template' => 'SMS_10795433'),
    'BIND_BANK_ACCOUNT' => array('name' => '绑定提现账号', 'template' => 'SMS_10795433'),
    'BIND_THIRD_PARTY' => array('name' => '绑定第三方登陆账户', 'template' => 'SMS_10795433'),
    'REPLACE_PHONE_NEW' => array('name' => '更换绑定手机', 'template' => 'SMS_10795433'),
    'REPLACE_PHONE_OLD' => array('name' => '绑定手机', 'template' => 'SMS_10795433'),
    'SMS_LOGIN' => array('name' => '短信登录', 'template' => 'SMS_10795433'),
]);

