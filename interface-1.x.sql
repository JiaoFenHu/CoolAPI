/*
 Navicat Premium Data Transfer

 Source Server         : yw-ketao
 Source Server Type    : MySQL
 Source Server Version : 50646
 Source Host           : 202.91.248.122:3306
 Source Schema         : new_supply

 Target Server Type    : MySQL
 Target Server Version : 50646
 File Encoding         : 65001

 Date: 15/06/2021 13:54:41
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for tb_admin_member
-- ----------------------------
DROP TABLE IF EXISTS `tb_admin_member`;
CREATE TABLE `tb_admin_member` (
  `tbid` bigint(20) NOT NULL AUTO_INCREMENT,
  `account` varchar(255) NOT NULL COMMENT '账号',
  `password` varchar(255) NOT NULL COMMENT '密码',
  `mobile` varchar(255) NOT NULL DEFAULT '' COMMENT '手机号',
  `qq` varchar(255) NOT NULL DEFAULT '' COMMENT 'qq',
  `name` varchar(255) NOT NULL DEFAULT '' COMMENT '名称',
  `headimg` text COMMENT '头像地址',
  `department_id` bigint(20) NOT NULL DEFAULT '0' COMMENT '部门id',
  `authorization_list` text COMMENT '权限',
  `organization_id` bigint(20) NOT NULL DEFAULT '0' COMMENT '组织ID(公司或商家)',
  `role_id` bigint(20) NOT NULL DEFAULT '0' COMMENT '角色ID',
  `department_job_id` bigint(20) NOT NULL DEFAULT '0' COMMENT '职位id',
  `createtime` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `edittime` timestamp NULL DEFAULT NULL COMMENT '编辑时间',
  `status` enum('NORMAL','DISABLED') NOT NULL DEFAULT 'NORMAL' COMMENT '状态',
  `is_admin` tinyint(4) NOT NULL DEFAULT '0' COMMENT '是否管理员',
  `isdel` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`tbid`) USING BTREE,
  KEY `username` (`account`) USING BTREE,
  KEY `username_2` (`account`,`password`) USING BTREE,
  KEY `department_id` (`department_id`) USING BTREE,
  KEY `organization_id` (`organization_id`) USING BTREE,
  KEY `createtime` (`createtime`) USING BTREE,
  KEY `edittime` (`edittime`) USING BTREE,
  KEY `job_id` (`department_job_id`) USING BTREE,
  KEY `status` (`status`) USING BTREE,
  KEY `is_admin` (`is_admin`) USING BTREE,
  KEY `isdel` (`isdel`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC COMMENT='后台管理员';

-- ----------------------------
-- Table structure for tb_area
-- ----------------------------
DROP TABLE IF EXISTS `tb_area`;
CREATE TABLE `tb_area` (
  `tbid` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(20) CHARACTER SET utf8mb4 NOT NULL DEFAULT '' COMMENT '名称',
  `code` varchar(6) CHARACTER SET utf8mb4 NOT NULL DEFAULT '' COMMENT '行政区划代码',
  `parent_code` varchar(6) CHARACTER SET utf8mb4 NOT NULL DEFAULT '' COMMENT '上级代码',
  `status` enum('NORMAL','DISABLED') CHARACTER SET utf8mb4 NOT NULL DEFAULT 'NORMAL' COMMENT '状态',
  `type` enum('PROVINCE','CITY','AREA') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PROVINCE' COMMENT '类型',
  PRIMARY KEY (`tbid`) USING BTREE,
  KEY `status` (`status`) USING BTREE,
  KEY `name` (`name`) USING BTREE,
  KEY `code` (`code`) USING BTREE,
  KEY `type` (`type`) USING BTREE,
  KEY `parent_code` (`parent_code`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=3228 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC COMMENT='地区';

-- ----------------------------
-- Table structure for tb_authorization
-- ----------------------------
DROP TABLE IF EXISTS `tb_authorization`;
CREATE TABLE `tb_authorization` (
  `tbid` bigint(20) NOT NULL AUTO_INCREMENT,
  `name` varchar(20) NOT NULL COMMENT '名称',
  `module` varchar(20) NOT NULL COMMENT '模块名',
  `browse` tinyint(1) NOT NULL DEFAULT '0' COMMENT '浏览',
  `create` tinyint(1) NOT NULL DEFAULT '0' COMMENT '创建',
  `edit` tinyint(1) NOT NULL DEFAULT '0' COMMENT '编辑',
  `delete` tinyint(1) NOT NULL DEFAULT '0' COMMENT '删除',
  `type` enum('ADMIN','SUPPLY','PARTNER') NOT NULL DEFAULT 'ADMIN' COMMENT '类型',
  `createtime` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `edittime` timestamp NULL DEFAULT NULL COMMENT '编辑时间',
  PRIMARY KEY (`tbid`) USING BTREE,
  KEY `name` (`name`) USING BTREE,
  KEY `createtime` (`createtime`) USING BTREE,
  KEY `edittime` (`edittime`) USING BTREE,
  KEY `module` (`module`) USING BTREE,
  KEY `browse` (`browse`) USING BTREE,
  KEY `create` (`create`) USING BTREE,
  KEY `edit` (`edit`) USING BTREE,
  KEY `delete` (`delete`) USING BTREE,
  KEY `type` (`type`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC COMMENT='权限表';

-- ----------------------------
-- Table structure for tb_base_member
-- ----------------------------
DROP TABLE IF EXISTS `tb_base_member`;
CREATE TABLE `tb_base_member` (
  `uuid` varchar(64) NOT NULL COMMENT 'UUID',
  `status` enum('NORMAL','DISABLED') NOT NULL DEFAULT 'NORMAL' COMMENT '状态',
  `createtime` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `edittime` timestamp NULL DEFAULT NULL COMMENT '编辑时间',
  PRIMARY KEY (`uuid`) USING BTREE,
  UNIQUE KEY `uuid` (`uuid`) USING BTREE,
  KEY `status` (`status`) USING BTREE,
  KEY `createtime` (`createtime`) USING BTREE,
  KEY `edittime` (`edittime`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC COMMENT='平台唯一用户';

-- ----------------------------
-- Table structure for tb_captcha
-- ----------------------------
DROP TABLE IF EXISTS `tb_captcha`;
CREATE TABLE `tb_captcha` (
  `tbid` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `mobile` varchar(255) CHARACTER SET utf8mb4 NOT NULL DEFAULT '' COMMENT '手机号',
  `captcha` varchar(255) CHARACTER SET utf8mb4 NOT NULL DEFAULT '' COMMENT '验证码',
  `status` enum('NORMAL','DISABLED') CHARACTER SET utf8mb4 NOT NULL DEFAULT 'NORMAL' COMMENT '状态',
  `type` enum('REGISTERED','RETRIEVE_PASSWORD','BIND_THIRD_PARTY','REPLACE_PHONE_NEW','MODIFY_PAYMENT_PASSWORD','BIND_BANK_ACCOUNT','REPLACE_PHONE_OLD') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'REGISTERED' COMMENT '类型',
  `member_type` enum('ADMIN','SUPPLY','PARTNER') CHARACTER SET utf8mb4 NOT NULL DEFAULT 'ADMIN' COMMENT '用户类型',
  `createtime` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `edittime` timestamp NULL DEFAULT NULL COMMENT '编辑时间',
  `endtime` timestamp NULL DEFAULT NULL COMMENT '过期时间',
  PRIMARY KEY (`tbid`) USING BTREE,
  KEY `mobile` (`mobile`) USING BTREE,
  KEY `captcha` (`captcha`) USING BTREE,
  KEY `createtime` (`createtime`) USING BTREE,
  KEY `edittime` (`edittime`) USING BTREE,
  KEY `endtime` (`endtime`) USING BTREE,
  KEY `status` (`status`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC COMMENT='验证码';

-- ----------------------------
-- Table structure for tb_department
-- ----------------------------
DROP TABLE IF EXISTS `tb_department`;
CREATE TABLE `tb_department` (
  `tbid` bigint(20) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL COMMENT '名称',
  `type` enum('ADMIN','SUPPLY') NOT NULL DEFAULT 'ADMIN' COMMENT '类型',
  `createtime` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `edittime` timestamp NULL DEFAULT NULL COMMENT '编辑',
  `manager` varchar(255) NOT NULL DEFAULT '' COMMENT '部门主管',
  `manager_mobile` varchar(255) NOT NULL DEFAULT '' COMMENT '部门主管手机',
  `manager_tel` varchar(255) NOT NULL DEFAULT '' COMMENT '部门主管电话',
  `brief` varchar(255) NOT NULL DEFAULT '' COMMENT '简介',
  `org_id` bigint(20) NOT NULL DEFAULT '0' COMMENT '组织id',
  `isdel` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`tbid`) USING BTREE,
  KEY `name` (`name`) USING BTREE,
  KEY `type` (`type`) USING BTREE,
  KEY `org_id` (`org_id`) USING BTREE,
  KEY `isdel` (`isdel`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC COMMENT='部门';

-- ----------------------------
-- Table structure for tb_department_job
-- ----------------------------
DROP TABLE IF EXISTS `tb_department_job`;
CREATE TABLE `tb_department_job` (
  `tbid` bigint(20) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL COMMENT '职位',
  `type` enum('ADMIN','SUPPLY') NOT NULL DEFAULT 'ADMIN' COMMENT '类型',
  `brief` varchar(255) NOT NULL COMMENT '简介',
  `department_id` bigint(20) NOT NULL COMMENT '部门id',
  `org_id` bigint(20) NOT NULL DEFAULT '0' COMMENT '组织id',
  `createtime` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `edittime` timestamp NULL DEFAULT NULL COMMENT '编辑时间',
  `isdel` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`tbid`) USING BTREE,
  KEY `department_id` (`department_id`) USING BTREE,
  KEY `creattime` (`createtime`) USING BTREE,
  KEY `edittime` (`edittime`) USING BTREE,
  KEY `org_id` (`org_id`) USING BTREE,
  KEY `type` (`type`) USING BTREE,
  KEY `username` (`name`) USING BTREE,
  KEY `isdel` (`isdel`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC COMMENT='部门职位';

-- ----------------------------
-- Table structure for tb_goods
-- ----------------------------
DROP TABLE IF EXISTS `tb_goods`;
CREATE TABLE `tb_goods` (
  `tbid` bigint(20) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL DEFAULT '' COMMENT '名称',
  `summary` varchar(255) NOT NULL COMMENT '摘要',
  `image` text NOT NULL COMMENT '图片',
  `videotype` enum('LINK','VIDEO') DEFAULT NULL COMMENT '视频类型',
  `videolink` varchar(255) DEFAULT NULL COMMENT '视频外链',
  `video` varchar(255) DEFAULT NULL COMMENT '视频地址',
  `videoimg` varchar(255) DEFAULT NULL COMMENT '视频预览图',
  `itemcode` varchar(255) NOT NULL DEFAULT '' COMMENT '商品编码',
  `uuid` varchar(255) NOT NULL COMMENT '系统唯一编码',
  `skuattribute` longtext COMMENT 'sku属性',
  `attribute` longtext COMMENT '属性',
  `content` longtext COMMENT '详情',
  `status` enum('put_on_shelves','put_off_shelves') NOT NULL DEFAULT 'put_off_shelves' COMMENT '状态 put_on_shelves 上架 put_off_shelves 下架',
  `supply_id` bigint(20) NOT NULL COMMENT '供应商id',
  `category_id` bigint(20) NOT NULL DEFAULT '0' COMMENT '商品类目id',
  `top_category_id` bigint(20) NOT NULL COMMENT '顶级分类',
  `brand_id` bigint(20) NOT NULL DEFAULT '0' COMMENT '品牌id',
  `supply_price_cost` int(11) DEFAULT NULL COMMENT '供应商（成本价）',
  `supply_price_provision` int(11) DEFAULT NULL COMMENT '供应商（供货价）',
  `price_min` int(11) DEFAULT NULL COMMENT '售价（最低）',
  `price_max` int(11) DEFAULT NULL COMMENT '售价（最高）',
  `originalprice_min` int(11) DEFAULT NULL COMMENT '原价（最低）',
  `originalprice_max` int(11) DEFAULT NULL COMMENT '原价（最高）',
  `delivery_id` bigint(20) DEFAULT NULL COMMENT '运费模版id',
  `audit` enum('through','waiting','refuse') DEFAULT 'waiting' COMMENT '审核状态，waiting等待，through通过，refuse拒绝',
  `indexid` bigint(20) NOT NULL DEFAULT '0' COMMENT '排序id',
  `hits` bigint(20) NOT NULL DEFAULT '0' COMMENT '商品访问量',
  `createtime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '创建时间',
  `edittime` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '编辑时间',
  `sales` int(11) NOT NULL DEFAULT '0' COMMENT '销售量',
  `isrecommend` tinyint(4) NOT NULL DEFAULT '0' COMMENT '是否推荐',
  `isdel` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否删除：1删除 0正常',
  PRIMARY KEY (`tbid`) USING BTREE,
  KEY `state` (`status`) USING BTREE,
  KEY `goods_category_id` (`category_id`) USING BTREE,
  KEY `name` (`name`) USING BTREE,
  KEY `createtime` (`createtime`) USING BTREE,
  KEY `edittime` (`edittime`) USING BTREE,
  KEY `isdel` (`isdel`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT COMMENT='商品表';

-- ----------------------------
-- Table structure for tb_goods_attribute
-- ----------------------------
DROP TABLE IF EXISTS `tb_goods_attribute`;
CREATE TABLE `tb_goods_attribute` (
  `tbid` bigint(20) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL DEFAULT '' COMMENT '类目名',
  `attribute` longtext COMMENT '属性',
  `createtime` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `edittime` timestamp NULL DEFAULT NULL COMMENT '编辑时间',
  PRIMARY KEY (`tbid`) USING BTREE,
  KEY `name` (`name`) USING BTREE,
  KEY `createtime` (`createtime`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- ----------------------------
-- Table structure for tb_goods_category
-- ----------------------------
DROP TABLE IF EXISTS `tb_goods_category`;
CREATE TABLE `tb_goods_category` (
  `tbid` bigint(20) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL DEFAULT '' COMMENT '类目名',
  `level` tinyint(4) NOT NULL DEFAULT '1' COMMENT '类目等级',
  `goods_category_id` bigint(20) NOT NULL DEFAULT '0' COMMENT '父级id',
  `isparent` tinyint(4) NOT NULL DEFAULT '0' COMMENT '该类目是否有子集',
  `attribute` longtext COMMENT '属性',
  `image` text COMMENT '图片',
  `sku` longtext COMMENT 'sku',
  `isrecommend` tinyint(4) NOT NULL DEFAULT '0' COMMENT '是否推荐',
  `indexid` bigint(11) NOT NULL DEFAULT '0' COMMENT '排序id',
  `createtime` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `edittime` timestamp NULL DEFAULT NULL COMMENT '编辑时间',
  PRIMARY KEY (`tbid`) USING BTREE,
  KEY `name` (`name`) USING BTREE,
  KEY `level` (`level`) USING BTREE,
  KEY `goods_category_id` (`goods_category_id`) USING BTREE,
  KEY `isparent` (`isparent`) USING BTREE,
  KEY `createtime` (`createtime`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT COMMENT='商品类目';

-- ----------------------------
-- Table structure for tb_insterface_log
-- ----------------------------
DROP TABLE IF EXISTS `tb_insterface_log`;
CREATE TABLE `tb_insterface_log` (
  `tbid` bigint(20) NOT NULL AUTO_INCREMENT,
  `url` varchar(255) NOT NULL DEFAULT '',
  `req` varchar(255) NOT NULL DEFAULT '',
  `methed` varchar(255) NOT NULL DEFAULT '',
  `ip` bigint(20) NOT NULL DEFAULT '0',
  `data` longtext,
  `returndata` longtext,
  `createtime` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `edittime` timestamp NULL DEFAULT NULL COMMENT '编辑时间',
  PRIMARY KEY (`tbid`) USING BTREE,
  KEY `createtime` (`createtime`) USING BTREE,
  KEY `edittime` (`edittime`) USING BTREE,
  KEY `req` (`req`) USING BTREE,
  KEY `ip` (`ip`) USING BTREE,
  KEY `methed` (`methed`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=13078 DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC COMMENT='接口访问日志';

-- ----------------------------
-- Table structure for tb_login_log
-- ----------------------------
DROP TABLE IF EXISTS `tb_login_log`;
CREATE TABLE `tb_login_log` (
  `tbid` bigint(20) NOT NULL AUTO_INCREMENT,
  `member_id` bigint(20) NOT NULL DEFAULT '0' COMMENT '会员id',
  `type` enum('ADMIN','SUPPLY','PARTNER','MEMBER') NOT NULL DEFAULT 'ADMIN' COMMENT '类型',
  `ip` bigint(20) NOT NULL DEFAULT '0' COMMENT 'IP',
  `city` varchar(255) NOT NULL DEFAULT '' COMMENT '城市',
  `platform` enum('IOS','Android','Web') NOT NULL DEFAULT 'Web' COMMENT '平台',
  `createtime` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `edittime` timestamp NULL DEFAULT NULL COMMENT '编辑时间',
  `org_id` bigint(20) NOT NULL DEFAULT '0' COMMENT '组织id',
  PRIMARY KEY (`tbid`) USING BTREE,
  KEY `createtime` (`createtime`) USING BTREE,
  KEY `edittime` (`edittime`) USING BTREE,
  KEY `member_id` (`member_id`) USING BTREE,
  KEY `ip` (`ip`) USING BTREE,
  KEY `city` (`city`) USING BTREE,
  KEY `type` (`type`) USING BTREE,
  KEY `org_id` (`org_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=352 DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC COMMENT='登陆日志';

-- ----------------------------
-- Table structure for tb_operating_log
-- ----------------------------
DROP TABLE IF EXISTS `tb_operating_log`;
CREATE TABLE `tb_operating_log` (
  `tbid` bigint(20) NOT NULL AUTO_INCREMENT,
  `member_id` bigint(20) NOT NULL COMMENT '管理员id',
  `intro` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '简介',
  `content` text COMMENT '详情',
  `createtime` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `edittime` timestamp NULL DEFAULT NULL COMMENT '编辑时间',
  `type` enum('ADMIN','SUPPLY','PARTNER') NOT NULL DEFAULT 'ADMIN' COMMENT '类型',
  `org_id` bigint(20) NOT NULL DEFAULT '0' COMMENT '组织id',
  PRIMARY KEY (`tbid`) USING BTREE,
  KEY `member_id` (`member_id`) USING BTREE,
  KEY `createtime` (`createtime`) USING BTREE,
  KEY `edittime` (`edittime`) USING BTREE,
  KEY `type` (`type`) USING BTREE,
  KEY `org_id` (`org_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=172 DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC COMMENT='操作记录';

-- ----------------------------
-- Table structure for tb_operating_log_detail
-- ----------------------------
DROP TABLE IF EXISTS `tb_operating_log_detail`;
CREATE TABLE `tb_operating_log_detail` (
  `tbid` bigint(20) NOT NULL AUTO_INCREMENT,
  `operating_log_id` bigint(20) NOT NULL COMMENT '操作记录id',
  `table` varchar(255) NOT NULL COMMENT '表名',
  `source_id` bigint(20) NOT NULL DEFAULT '0' COMMENT '操作id',
  `type` enum('INSERT','UPDATE','DELETE') NOT NULL DEFAULT 'INSERT' COMMENT '类型',
  `old_data` text COMMENT '操作前数据',
  `new_data` text COMMENT '操作后数据',
  `createtime` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `edittime` timestamp NULL DEFAULT NULL COMMENT '编辑时间',
  PRIMARY KEY (`tbid`) USING BTREE,
  KEY `operating_log_id` (`operating_log_id`) USING BTREE,
  KEY `table` (`table`) USING BTREE,
  KEY `type` (`type`) USING BTREE,
  KEY `createtime` (`createtime`) USING BTREE,
  KEY `edittime` (`edittime`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=184 DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC COMMENT='操作详情';

-- ----------------------------
-- Table structure for tb_partner_member
-- ----------------------------
DROP TABLE IF EXISTS `tb_partner_member`;
CREATE TABLE `tb_partner_member` (
  `tbid` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL DEFAULT '' COMMENT '名称',
  `password` varchar(255) NOT NULL DEFAULT '' COMMENT '密码',
  `mobile` varchar(255) NOT NULL DEFAULT '' COMMENT '手机号',
  `createtime` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `edittime` timestamp NULL DEFAULT NULL COMMENT '编辑时间',
  `headimg` text COMMENT '头像地址',
  `department_id` bigint(20) NOT NULL DEFAULT '0' COMMENT '部门id',
  `authorization_list` text COMMENT '权限',
  `org_id` bigint(20) NOT NULL DEFAULT '0' COMMENT '组织ID(公司或商家)',
  `role_id` bigint(20) NOT NULL DEFAULT '0' COMMENT '角色ID',
  `department_job_id` bigint(20) NOT NULL DEFAULT '0' COMMENT '职位id',
  `status` enum('NORMAL','DISABLED') DEFAULT 'NORMAL' COMMENT '状态',
  `is_verified` tinyint(4) NOT NULL DEFAULT '1' COMMENT '是否实名',
  `isdel` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`tbid`) USING BTREE,
  KEY `creattime` (`createtime`) USING BTREE,
  KEY `account_2` (`password`) USING BTREE,
  KEY `realname` (`name`) USING BTREE,
  KEY `mobile` (`mobile`) USING BTREE,
  KEY `status` (`status`) USING BTREE,
  KEY `edittime` (`edittime`) USING BTREE,
  KEY `department_id` (`department_id`) USING BTREE,
  KEY `organization_id` (`org_id`) USING BTREE,
  KEY `isdel` (`isdel`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC COMMENT='合伙人';

-- ----------------------------
-- Table structure for tb_partner_principal
-- ----------------------------
DROP TABLE IF EXISTS `tb_partner_principal`;
CREATE TABLE `tb_partner_principal` (
  `tbid` bigint(20) NOT NULL AUTO_INCREMENT,
  `partner_id` bigint(20) NOT NULL DEFAULT '0' COMMENT '合伙人id',
  `principal_id` bigint(20) NOT NULL DEFAULT '0' COMMENT '负责人id',
  `createtime` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `edittime` timestamp NULL DEFAULT NULL COMMENT '编辑时间',
  `isdel` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`tbid`) USING BTREE,
  KEY `creattime` (`createtime`) USING BTREE,
  KEY `edittime` (`edittime`) USING BTREE,
  KEY `isdel` (`isdel`) USING BTREE,
  KEY `partner_id` (`partner_id`) USING BTREE,
  KEY `admin_id` (`principal_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC COMMENT='合伙人商务负责人关联';

-- ----------------------------
-- Table structure for tb_partner_verify
-- ----------------------------
DROP TABLE IF EXISTS `tb_partner_verify`;
CREATE TABLE `tb_partner_verify` (
  `tbid` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL DEFAULT '' COMMENT '名称',
  `ID_card_front` text COMMENT '身份证正面',
  `ID_card_back` text COMMENT '身份证反面',
  `member_id` bigint(20) NOT NULL DEFAULT '0' COMMENT '用户id',
  `brief` varchar(255) NOT NULL DEFAULT '' COMMENT '简介',
  `status` enum('REVIEW','PASSED','FAILURE') NOT NULL DEFAULT 'REVIEW' COMMENT '状态',
  `createtime` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `edittime` timestamp NULL DEFAULT NULL COMMENT '编辑时间',
  PRIMARY KEY (`tbid`) USING BTREE,
  KEY `creattime` (`createtime`) USING BTREE,
  KEY `name` (`name`) USING BTREE,
  KEY `status` (`status`) USING BTREE,
  KEY `edittime` (`edittime`) USING BTREE,
  KEY `member_id` (`member_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC COMMENT='合伙人认证';

-- ----------------------------
-- Table structure for tb_role
-- ----------------------------
DROP TABLE IF EXISTS `tb_role`;
CREATE TABLE `tb_role` (
  `tbid` bigint(20) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL COMMENT '名称',
  `brief` varchar(255) NOT NULL COMMENT '简介',
  `authorization_list` text COMMENT '权限',
  `createtime` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `edittime` timestamp NULL DEFAULT NULL COMMENT '编辑时间',
  `type` enum('ADMIN','SUPPLY') NOT NULL DEFAULT 'ADMIN' COMMENT '类型',
  `org_id` bigint(20) NOT NULL DEFAULT '0' COMMENT '组织id',
  `is_system` tinyint(4) NOT NULL DEFAULT '0' COMMENT '是否系统保留',
  `isdel` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`tbid`) USING BTREE,
  KEY `name` (`name`) USING BTREE,
  KEY `createtime` (`createtime`) USING BTREE,
  KEY `edittime` (`edittime`) USING BTREE,
  KEY `type` (`type`) USING BTREE,
  KEY `org_id` (`org_id`) USING BTREE,
  KEY `isdel` (`isdel`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC COMMENT='角色权限表';

-- ----------------------------
-- Table structure for tb_supply
-- ----------------------------
DROP TABLE IF EXISTS `tb_supply`;
CREATE TABLE `tb_supply` (
  `tbid` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL DEFAULT '' COMMENT '名称',
  `introducer` varchar(255) NOT NULL DEFAULT '' COMMENT '介绍人',
  `introducer_contact` varchar(255) NOT NULL DEFAULT '' COMMENT '介绍人联系方式',
  `address` varchar(255) NOT NULL DEFAULT '' COMMENT '地址',
  `partner_member_id` bigint(20) NOT NULL DEFAULT '0' COMMENT '品类合伙人id',
  `member_id` bigint(20) NOT NULL DEFAULT '0' COMMENT '管理员id',
  `brief` varchar(255) NOT NULL DEFAULT '' COMMENT '简介',
  `status` enum('NORMAL','DISABLED') NOT NULL DEFAULT 'NORMAL' COMMENT '状态',
  `is_hosting` tinyint(4) NOT NULL DEFAULT '1' COMMENT '是否托管',
  `createtime` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `edittime` timestamp NULL DEFAULT NULL COMMENT '编辑时间',
  PRIMARY KEY (`tbid`) USING BTREE,
  KEY `creattime` (`createtime`) USING BTREE,
  KEY `name` (`name`) USING BTREE,
  KEY `category_partners_id` (`partner_member_id`) USING BTREE,
  KEY `status` (`status`) USING BTREE,
  KEY `edittime` (`edittime`) USING BTREE,
  KEY `supply_member_id` (`member_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC COMMENT='供应商表';

-- ----------------------------
-- Table structure for tb_supply_member
-- ----------------------------
DROP TABLE IF EXISTS `tb_supply_member`;
CREATE TABLE `tb_supply_member` (
  `tbid` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL DEFAULT '' COMMENT '名称',
  `password` varchar(255) NOT NULL DEFAULT '' COMMENT '密码',
  `mobile` varchar(255) NOT NULL DEFAULT '' COMMENT '手机号',
  `createtime` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `edittime` timestamp NULL DEFAULT NULL COMMENT '编辑时间',
  `headimg` text COMMENT '头像地址',
  `department_id` bigint(20) NOT NULL DEFAULT '0' COMMENT '部门id',
  `authorization_list` text COMMENT '权限',
  `org_id` bigint(20) NOT NULL DEFAULT '0' COMMENT '组织ID(公司或商家)',
  `role_id` bigint(20) NOT NULL DEFAULT '0' COMMENT '角色ID',
  `department_job_id` bigint(20) NOT NULL DEFAULT '0' COMMENT '职位id',
  `status` enum('NORMAL','DISABLED') DEFAULT 'NORMAL' COMMENT '状态',
  `isdel` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`tbid`) USING BTREE,
  KEY `creattime` (`createtime`) USING BTREE,
  KEY `account_2` (`password`) USING BTREE,
  KEY `realname` (`name`) USING BTREE,
  KEY `mobile` (`mobile`) USING BTREE,
  KEY `status` (`status`) USING BTREE,
  KEY `edittime` (`edittime`) USING BTREE,
  KEY `department_id` (`department_id`) USING BTREE,
  KEY `organization_id` (`org_id`) USING BTREE,
  KEY `isdel` (`isdel`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC COMMENT='供应商员工表';

-- ----------------------------
-- Table structure for tb_supply_verify
-- ----------------------------
DROP TABLE IF EXISTS `tb_supply_verify`;
CREATE TABLE `tb_supply_verify` (
  `tbid` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL DEFAULT '' COMMENT '名称',
  `introducer` varchar(255) NOT NULL DEFAULT '' COMMENT '介绍人',
  `introducer_contact` varchar(255) NOT NULL DEFAULT '' COMMENT '介绍人联系方式',
  `address` varchar(255) NOT NULL DEFAULT '' COMMENT '地址',
  `member_id` bigint(20) NOT NULL DEFAULT '0' COMMENT '用户id',
  `brief` varchar(255) NOT NULL DEFAULT '' COMMENT '简介',
  `status` enum('REVIEW','PASSED','FAILURE') NOT NULL DEFAULT 'REVIEW' COMMENT '状态',
  `createtime` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `edittime` timestamp NULL DEFAULT NULL COMMENT '编辑时间',
  PRIMARY KEY (`tbid`) USING BTREE,
  KEY `creattime` (`createtime`) USING BTREE,
  KEY `name` (`name`) USING BTREE,
  KEY `status` (`status`) USING BTREE,
  KEY `edittime` (`edittime`) USING BTREE,
  KEY `member_id` (`member_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC COMMENT='供应商认证';

-- ----------------------------
-- Table structure for tb_token
-- ----------------------------
DROP TABLE IF EXISTS `tb_token`;
CREATE TABLE `tb_token` (
  `tbid` bigint(20) NOT NULL AUTO_INCREMENT,
  `member_id` bigint(20) NOT NULL DEFAULT '0' COMMENT '会员id',
  `type` enum('ADMIN','SUPPLY','PARTNER','MEMBER') NOT NULL DEFAULT 'ADMIN' COMMENT '类型',
  `token` varchar(255) NOT NULL DEFAULT '' COMMENT 'token',
  `platform` enum('IOS','Android','Web') NOT NULL DEFAULT 'Web' COMMENT '平台',
  `status` enum('NORMAL','DISABLED','REFRESH') NOT NULL DEFAULT 'NORMAL' COMMENT '状态 1生效 2失效',
  `createtime` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `edittime` timestamp NULL DEFAULT NULL COMMENT '编辑时间',
  `failure_time` timestamp NULL DEFAULT NULL COMMENT '失效时间',
  PRIMARY KEY (`tbid`) USING BTREE,
  KEY `createtime` (`createtime`) USING BTREE,
  KEY `edittime` (`edittime`) USING BTREE,
  KEY `member_id` (`member_id`) USING BTREE,
  KEY `token` (`token`) USING BTREE,
  KEY `failuretime` (`failure_time`) USING BTREE,
  KEY `status` (`status`) USING BTREE,
  KEY `type` (`type`) USING BTREE,
  KEY `platform` (`platform`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=454 DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC COMMENT='Token';

-- ----------------------------
-- Table structure for tb_upload_log
-- ----------------------------
DROP TABLE IF EXISTS `tb_upload_log`;
CREATE TABLE `tb_upload_log` (
  `tbid` bigint(20) NOT NULL AUTO_INCREMENT,
  `member_id` bigint(20) NOT NULL DEFAULT '0' COMMENT '会员id',
  `type` enum('IMAGE','SUPPLY','FILE') NOT NULL DEFAULT 'IMAGE' COMMENT '类型',
  `module` enum('MEMBER') NOT NULL DEFAULT 'MEMBER' COMMENT '模块',
  `mode` enum('ALIOSS','BOTH','LOCAL') NOT NULL DEFAULT 'ALIOSS' COMMENT '模式',
  `url` text COMMENT '地址',
  `createtime` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `edittime` timestamp NULL DEFAULT NULL COMMENT '编辑时间',
  PRIMARY KEY (`tbid`) USING BTREE,
  KEY `createtime` (`createtime`) USING BTREE,
  KEY `edittime` (`edittime`) USING BTREE,
  KEY `member_id` (`member_id`) USING BTREE,
  KEY `type` (`type`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=171 DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC COMMENT='上传日志';

SET FOREIGN_KEY_CHECKS = 1;
