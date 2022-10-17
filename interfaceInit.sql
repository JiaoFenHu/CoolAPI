DROP TABLE IF EXISTS `tb_interface_log`;
CREATE TABLE `tb_interface_log` (
  `tbid` bigint(20) NOT NULL AUTO_INCREMENT,
  `url` varchar(255) NOT NULL DEFAULT '' COMMENT '请求地址',
  `api` varchar(255) NOT NULL DEFAULT '' COMMENT '接口地址',
  `method` varchar(255) NOT NULL DEFAULT '' COMMENT '请求方法',
  `ip` bigint(20) NOT NULL DEFAULT '0' COMMENT '来源ip地址',
  `request` longtext COMMENT '请求数据',
  `response` longtext COMMENT '返回数据',
  `create_time` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `edit_time` timestamp NULL DEFAULT NULL COMMENT '编辑时间',
  PRIMARY KEY (`tbid`) USING BTREE,
  KEY `idx_create_time` (`create_time`) USING BTREE,
  KEY `idx_api_ip` (`api`, `ip`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='接口请求日志';


DROP TABLE IF EXISTS `tb_interface_sqlerror_log`;
CREATE TABLE `tb_interface_sqlerror_log` (
  `tbid` bigint(20) NOT NULL AUTO_INCREMENT,
  `api` varchar(255) NOT NULL DEFAULT '' COMMENT '接口地址',
  `err_content` longtext COMMENT '错误内容',
  `create_time` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `edit_time` timestamp NULL DEFAULT NULL COMMENT '编辑时间',
  PRIMARY KEY (`tbid`) USING BTREE,
  KEY `idx_api` (`api`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='接口数据库错误日志';