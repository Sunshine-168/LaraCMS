CREATE TABLE `@prefix@@tablename@` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `category_id` int(11) NOT NULL COMMENT '栏目ID',
  `model_id` int(11) NOT NULL COMMENT '模型ID',
  `is_read` tinyint(1) NOT NULL DEFAULT 0 COMMENT '查阅:1=已阅读,0=未读',
  `lang` char(20) NOT NULL DEFAULT '' COMMENT '语言标识',
  `ip` varchar(255) NOT NULL DEFAULT '' COMMENT 'IP',
  `show_tpl` varchar(50) NOT NULL DEFAULT 'page_guestbook.html' COMMENT '模板',
  `read_time` int(11) DEFAULT NULL COMMENT '阅读时间',
  `status` enum('normal','hidden','reject','audit') NOT NULL DEFAULT 'normal' COMMENT '状态',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='留言表单';