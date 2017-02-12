CREATE TABLE `repo` (
  `repo_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `group` varchar(16) NOT NULL COMMENT '分组名称',
  `rgroup_id` int(10) unsigned NOT NULL COMMENT '所属组',
  `name` varchar(20) NOT NULL COMMENT 'git库名称',
  `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '状态 -1 删除 0 禁用 1 正常',
  `create_time` int(10) unsigned NOT NULL COMMENT '创建时间',
  `user_id` int(10) unsigned NOT NULL COMMENT '创建者',
  `summary` varchar(100) NOT NULL DEFAULT '' COMMENT '简介',
  `setting` json DEFAULT NULL COMMENT '设置 anonymouse:是否匿名访问',
  PRIMARY KEY (`repo_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1000 DEFAULT CHARSET=utf8 COMMENT='GIT库';

CREATE TABLE `repo_group` (
  `rgroup_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(16) NOT NULL COMMENT '分组英文名',
  `summary` varchar(100) NOT NULL COMMENT '简介',
  `user_id` int(10) unsigned NOT NULL COMMENT '创建人',
  `create_time` int(10) unsigned NOT NULL COMMENT '创建时间',
  `status` tinyint(4) NOT NULL COMMENT '状态 -1 删除 0 禁用 1 正常',
  PRIMARY KEY (`rgroup_id`),
  UNIQUE KEY `repo_group_name_pk` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=1000 DEFAULT CHARSET=utf8 COMMENT='分组';

CREATE TABLE `repo_user` (
  `repo_id` int(10) unsigned NOT NULL COMMENT '仓库ID',
  `user_id` int(10) unsigned NOT NULL COMMENT '用户ID',
  `create_time` int(10) unsigned NOT NULL COMMENT '创建时间',
  `role` smallint(5) unsigned NOT NULL DEFAULT '1' COMMENT '角色\n1 正常用户\n2 管理员',
  PRIMARY KEY (`repo_id`,`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='用户和仓库关系表';