CREATE TABLE `tb_interface_log` (
  `tbid` bigint(20) NOT NULL AUTO_INCREMENT,
  `url` varchar(255) NOT NULL DEFAULT '' COMMENT '请求地址',
  `req` varchar(255) NOT NULL DEFAULT '' COMMENT '接口地址',
  `methed` varchar(255) NOT NULL DEFAULT '' COMMENT '请求方法',
  `ip` bigint(20) NOT NULL DEFAULT '0' COMMENT '来源ip地址',
  `data` longtext COMMENT '请求数据',
  `returndata` longtext COMMENT '返回数据',
  `createtime` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `edittime` timestamp NULL DEFAULT NULL COMMENT '编辑时间',
  PRIMARY KEY (`tbid`) USING BTREE,
  KEY `createtime` (`createtime`) USING BTREE,
  KEY `req` (`req`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='接口请求日志';



CREATE TABLE `tb_interface_db_err_log` (
  `tbid` bigint(20) NOT NULL AUTO_INCREMENT,
  `interface` varchar(255) NOT NULL DEFAULT '' COMMENT '接口地址',
  `err_content` longtext COMMENT '错误内容',
  `createtime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `edittime` timestamp NULL DEFAULT NULL COMMENT '编辑',
  PRIMARY KEY (`tbid`) USING BTREE,
  KEY `interface` (`interface`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='接口数据库错误日志';