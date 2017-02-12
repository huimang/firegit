CREATE TABLE `user` (
  `user_id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '用户ID',
  `username` varchar(20) NOT NULL COMMENT '用户名',
  `email` varchar(50) NOT NULL COMMENT '邮箱',
  `password` char(40) NOT NULL COMMENT '密码',
  `salt` char(32) NOT NULL COMMENT '加密因子',
  `realname` varchar(30) NOT NULL COMMENT '真实姓名',
  `sex` tinyint(3) unsigned NOT NULL COMMENT '性别 0：未知 1：男 2：女',
  `phone` bigint(20) unsigned NOT NULL DEFAULT '0' COMMENT '电话',
  `role` int(10) unsigned NOT NULL DEFAULT '1' COMMENT '0x1 普通用户\n0x2 管理员',
  `status` tinyint(4) NOT NULL COMMENT '状态 -1 删除 0 禁用 1 正常',
  `create_time` int(10) unsigned NOT NULL COMMENT '创建时间',
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `user_username_pk` (`username`),
  UNIQUE KEY `user_email_pk` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=1000 DEFAULT CHARSET=utf8 COMMENT='用户';