DROP TABLE IF EXISTS `hkcms_admin`;
CREATE TABLE `hkcms_admin` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '登录名称',
  `nickname` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '昵称',
  `email` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '邮箱',
  `password` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '密码',
  `salt` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '密码盐',
  `avatar` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '头像',
  `remark` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '备注',
  `logintime` int(11) DEFAULT NULL COMMENT '登录时间',
  `loginip` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '登录IP',
  `status` enum('normal','hidden') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'normal' COMMENT '状态:normal-正常,hidden-禁用',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniaue_username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='后台管理员';

DROP TABLE IF EXISTS `hkcms_admin_log`;
CREATE TABLE `hkcms_admin_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `admin_id` int(10) unsigned NOT NULL COMMENT '用户id',
  `username` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '用户名',
  `useragent` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'useragent',
  `title` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '标题',
  `url` varchar(1500) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'URL',
  `ip` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'IP地址',
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '内容',
  `create_time` int(11) NOT NULL COMMENT '操作时间',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='后台操作日志';


DROP TABLE IF EXISTS `hkcms_auth_group`;
CREATE TABLE `hkcms_auth_group` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `name` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '名称',
  `parent_id` mediumint(8) unsigned NOT NULL DEFAULT '0' COMMENT '父级',
  `rules` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '规则',
  `remark` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '备注',
  `status` enum('normal','hidden') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'normal' COMMENT '状态:normal-正常,hidden-禁用',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='角色管理';

insert  into `hkcms_auth_group` values (1,'超级管理员',0,'*','','normal',1594794684,1594795080);


DROP TABLE IF EXISTS `hkcms_auth_group_access`;
CREATE TABLE `hkcms_auth_group_access` (
  `admin_id` int(10) unsigned NOT NULL COMMENT '管理员ID',
  `group_id` mediumint(8) unsigned NOT NULL COMMENT '角色组ID',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
  UNIQUE KEY `uid_group_id` (`admin_id`,`group_id`),
  KEY `uid` (`admin_id`),
  KEY `group_id` (`group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='权限分组表';

insert  into `hkcms_auth_group_access` values (1,1,1595834080,1595834080);


DROP TABLE IF EXISTS `hkcms_auth_rule`;
CREATE TABLE `hkcms_auth_rule` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `parent_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '父级',
  `name` char(80) CHARACTER SET utf8 NOT NULL DEFAULT '' COMMENT '规则',
  `title` char(50) CHARACTER SET utf8 NOT NULL DEFAULT '' COMMENT '标题',
  `route` char(80) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '路由',
  `app` char(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '所属应用',
  `icon` char(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '图标',
  `remark` char(250) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '备注',
  `weigh` int(11) NOT NULL DEFAULT '0' COMMENT '排序',
  `type` tinyint(2) NOT NULL DEFAULT '1' COMMENT '类型:0-权限规则,1-菜单,2-菜单头',
  `status` enum('normal','hidden') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'normal' COMMENT '状态:normal-正常,hidden-禁用',
  `is_nav` tinyint(2) NOT NULL DEFAULT '0' COMMENT '快速导航:1-是,0-否',
  `condition` char(100) CHARACTER SET utf8 NOT NULL DEFAULT '' COMMENT '附加条件',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=100 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='菜单规则';

insert into `hkcms_auth_rule` values (33,0,'index/dashboard','Dashboard','','','fas fa-tachometer-alt','',5,0,'normal',0,'',1594779897,1617268812);
insert into `hkcms_auth_rule` values (34,58,'auth','Auth','','','fas fa-user-alt','',17,1,'normal',0,'',1594779929,1610535401);
insert into `hkcms_auth_rule` values (35,34,'auth/rule','Menu','auth.rule/index','','far fa-circle','',20,1,'normal',1,'',1594780028,1601016617);
insert into `hkcms_auth_rule` values (36,35,'auth/rule/index','View','auth.rule/index','','far fa-circle','',0,0,'normal',0,'',1594780073,1594978319);
insert into `hkcms_auth_rule` values (37,35,'auth/rule/add','Add','auth.rule/add','','far fa-circle','',0,0,'normal',0,'',1594780119,1594780119);
insert into `hkcms_auth_rule` values (38,35,'auth/rule/edit','Edit','auth.rule/edit','','far fa-circle','',0,0,'normal',0,'',1594780154,1594780794);
insert into `hkcms_auth_rule` values (39,35,'auth/rule/del','Delete','auth.rule/del','','far fa-circle','',0,0,'normal',0,'',1594780205,1594780205);
insert into `hkcms_auth_rule` values (40,34,'auth/admin','Admin','auth.admin/index','','far fa-circle','',18,1,'normal',1,'',1594780596,1601016611);
insert into `hkcms_auth_rule` values (41,34,'auth/group','Group','auth.group/index','','far fa-circle','',19,1,'normal',1,'',1594780999,1601016612);
insert into `hkcms_auth_rule` values (42,34,'auth/adminlog','Admin log','auth.adminlog/index','','far fa-circle','',21,1,'normal',1,'',1594781045,1601016622);
insert into `hkcms_auth_rule` values (43,57,'appcenter','Application','','','fas fa-th','',31,1,'normal',0,'',1594781146,1610535417);
insert into `hkcms_auth_rule` values (45,58,'cms/model','Model','cms.model/index','','fas fa-database','',16,1,'normal',1,'',1596080998,1610535376);
insert into `hkcms_auth_rule` values (46,0,'cms/category','Category','cms.category/index','','fas fa-list','',7,1,'normal',1,'',1596085252,1605256403);
insert into `hkcms_auth_rule` values (47,0,'cms/content/index','Content','cms.content/index','','fas fa-file','',6,1,'normal',1,'',1596085625,1610587398);
insert into `hkcms_auth_rule` values (48,35,'auth/rule/batches','Batch edit','auth.rule/batches','','far fa-circle','',0,0,'normal',0,'',1599105525,1599105525);
insert into `hkcms_auth_rule` values (50,108,'routine/config','Site config','routine.config/index','','fas fa-globe','',11,1,'normal',1,'',1599134646,1610534629);
insert into `hkcms_auth_rule` values (51,42,'auth/adminlog/index','View','auth.adminlog/index','','far fa-circle','',0,0,'normal',0,'',1599557159,1599557214);
insert into `hkcms_auth_rule` values (52,58,'routine/attachment','Attachment','routine.Attachment/index','','fas fa-folder','',15,1,'normal',1,'',1600849714,1610535390);
insert into `hkcms_auth_rule` values (54,50,'routine/config/edit','Edit','routine.config/edit','','far fa-circle','',20,0,'normal',0,'',1601436785,1601436851);
insert into `hkcms_auth_rule` values (55,0,'cms/recommend','Site module','cms.recommend/index','','fas fa-cubes','',8,1,'normal',1,'',1602207862,1605257692);
insert into `hkcms_auth_rule` values (57,0,'more','More','','','far fa-circle','',30,2,'normal',0,'',1605256229,1605256347);
insert into `hkcms_auth_rule` values (58,0,'setting','Setting','','','far fa-circle','',9,2,'normal',0,'',1605256302,1605256425);
insert into `hkcms_auth_rule` values (59,40,'auth/admin/edit','Edit','auth.admin/edit','','fas fa-circle','',0,0,'normal',0,'',1608516768,1608516768);
insert into `hkcms_auth_rule` values (60,40,'auth/admin/index','View','auth.admin/index','','fas fa-circle','',0,0,'normal',0,'',1608517595,1608517844);
insert into `hkcms_auth_rule` values (61,40,'auth/admin/add','Add','auth.admin/admin','','fas fa-circle','',0,0,'normal',0,'',1608517687,1608517687);
insert into `hkcms_auth_rule` values (62,40,'auth/admin/del','Delete','auth.admin/del','','fas fa-circle','',0,0,'normal',0,'',1608517733,1608517826);
insert into `hkcms_auth_rule` values (63,40,'auth/admin/batches','Batch edit','auth.admin/batches','','fas fa-circle','',0,0,'normal',0,'',1608517821,1608517821);
insert into `hkcms_auth_rule` values (64,41,'auth/group/index','View','auth.group/index','','fas fa-circle','',0,0,'normal',0,'',1608518081,1608518093);
insert into `hkcms_auth_rule` values (65,41,'auth/group/add','Add','auth.group/add','','fas fa-circle','',0,0,'normal',0,'',1608518320,1608518327);
insert into `hkcms_auth_rule` values (66,41,'auth/group/edit','Edit','auth.group/edit','','fas fa-circle','',0,0,'normal',0,'',1608518434,1608518434);
insert into `hkcms_auth_rule` values (67,41,'auth/group/del','Delete','auth.group/del','','fas fa-circle','',0,0,'normal',0,'',1608518463,1608518463);
insert into `hkcms_auth_rule` values (68,41,'auth/group/batches','Batch edit','auth.group/batches','','fas fa-circle','',0,0,'normal',0,'',1608518515,1608518528);
insert into `hkcms_auth_rule` values (69,42,'auth/adminlog/del','Delete','auth.adminlog/del','','fas fa-circle','',0,0,'normal',0,'',1608518616,1608518897);
insert into `hkcms_auth_rule` values (70,52,'common/upload','Upload','','','fas fa-circle','',0,0,'normal',0,'',1608545354,1608545354);
insert into `hkcms_auth_rule` values (71,52,'routine/attachment/del','Delete','routine.attachment/del','','fas fa-circle','',0,0,'normal',0,'',1608545444,1608545444);
insert into `hkcms_auth_rule` values (72,52,'routine/attachment/index','View','routine.attachment/index','','fas fa-circle','',0,0,'normal',0,'',1608545893,1608545893);
insert into `hkcms_auth_rule` values (73,45,'cms/model/index','View','cms.model/index','','fas fa-circle','',0,0,'normal',0,'',1608545982,1608545982);
insert into `hkcms_auth_rule` values (74,45,'cms/model/add','Add','cms.model/add','','fas fa-circle','',0,0,'normal',0,'',1608546009,1608546009);
insert into `hkcms_auth_rule` values (75,45,'cms/model/edit','Edit','cms.model/edit','','fas fa-circle','',0,0,'normal',0,'',1608546042,1608546087);
insert into `hkcms_auth_rule` values (76,45,'cms/model/del','Delete','cms.model/del','','fas fa-circle','',0,0,'normal',0,'',1608546082,1608546082);
insert into `hkcms_auth_rule` values (77,45,'cms/modelfield/index','Model field view','cms.model_field/index','','fas fa-circle','',0,0,'normal',0,'',1608546138,1608546138);
insert into `hkcms_auth_rule` values (78,45,'cms/modelfield/add','Model field add','cms.model_field/add','','fas fa-circle','',0,0,'normal',0,'',1608546185,1608546185);
insert into `hkcms_auth_rule` values (79,45,'cms/modelfield/edit','Model field edit','cms.model_field/edit','','fas fa-circle','',0,0,'normal',0,'',1608546212,1608546212);
insert into `hkcms_auth_rule` values (80,45,'cms/modelfield/del','Model field delete','cms.model_field/del','','fas fa-circle','',0,0,'normal',0,'',1608546248,1608546248);
insert into `hkcms_auth_rule` values (82,45,'cms/model/batches','Batch edit','cms.model/batches','','fas fa-circle','',0,0,'normal',0,'',1608546422,1608546427);
insert into `hkcms_auth_rule` values (83,45,'cms/modelfield/batches','Model field batch edit','cms.model_field/batches','','fas fa-circle','',0,0,'normal',0,'',1608546463,1608546463);
insert into `hkcms_auth_rule` values (84,55,'cms/recommend/index','View','cms.recommend/index','','fas fa-circle','',0,0,'normal',0,'',1608547657,1608547657);
insert into `hkcms_auth_rule` values (85,55,'cms/recommend/add','Add','cms.recommend/add','','fas fa-circle','',0,0,'normal',0,'',1608548615,1608548615);
insert into `hkcms_auth_rule` values (86,55,'cms/recommend/edit','Edit','cms.recommend/edit','','fas fa-circle','',0,0,'normal',0,'',1608548650,1608548650);
insert into `hkcms_auth_rule` values (87,55,'cms/recommend/del','Delete','cms.recommend/del','','fas fa-circle','',0,0,'normal',0,'',1608548678,1608548678);
insert into `hkcms_auth_rule` values (88,55,'cms/recommend/batches','Batch edit','cms.recommend/batches','','fas fa-circle','',0,0,'normal',0,'',1608548894,1608548894);
insert into `hkcms_auth_rule` values (89,46,'cms/category/index','View','cms.category/index','','fas fa-circle','',0,0,'normal',0,'',1608550359,1608550363);
insert into `hkcms_auth_rule` values (90,46,'cms/category/add','Add','cms.category/add','','fas fa-circle','',0,0,'normal',0,'',1608551179,1608551261);
insert into `hkcms_auth_rule` values (91,46,'cms/category/del','Delete','cms.category/del','','fas fa-circle','',0,0,'normal',0,'',1608551315,1608551315);
insert into `hkcms_auth_rule` values (92,46,'cms/category/edit','Edit','cms.category/edit','','fas fa-circle','',0,0,'normal',0,'',1608551316,1608551316);
insert into `hkcms_auth_rule` values (93,46,'cms/category/batches','Batch edit','cms.category/batches','','fas fa-circle','',0,0,'normal',0,'',1608551362,1610588095);
insert into `hkcms_auth_rule` values (94,46,'cms/category/recycle','Recycle','cms.category/recycle','','fas fa-circle','',0,0,'normal',0,'',1608551416,1608551416);
insert into `hkcms_auth_rule` values (95,46,'cms/category/destroy','Destroy','cms.category/destroy','','fas fa-circle','',0,0,'normal',0,'',1608551728,1608551728);
insert into `hkcms_auth_rule` values (96,46,'cms/category/restore','Restore','cms.category/restore','','fas fa-circle','',0,0,'normal',0,'',1608551775,1608551775);
insert into `hkcms_auth_rule` values (97,43,'appcenter/index','Local','','','fa fa-cloud-download-alt','',32,1,'normal',0,'',1608705333,1608705348);
insert into `hkcms_auth_rule` values (98,43,'appcenter/online','Online','','','fas fa-cloud','',33,1,'normal',0,'',1608705372,1617268688);
insert into `hkcms_auth_rule` values (99,46,'cms/category/auth','Column auth','cms.ategory/auth','','fas fa-circle','',0,0,'normal',0,'',1610623305,1610623305);
insert into `hkcms_auth_rule` values (100,45,'cms/modelfield/fieldcategory', 'Designated column', 'cms.model_field/fieldcategory', '','fas fa-circle', '', 0, 0, 'normal', 0, '', 1618999384, 1618999384);
insert into `hkcms_auth_rule` values (101,46,'cms/category/fields','Extended field','cms/category/fields','','fas fa-circle','',0,0,'normal',0,'',1626420443,1626420443);
insert into `hkcms_auth_rule` values (102,45,'cms/model/export','Model export','cms.model/export','','fas fa-circle','',0,0,'normal',0,'',1629871111,1629871280);
insert into `hkcms_auth_rule` values (103,45,'cms/model/import','Model import','cms.model/import','','fas fa-circle','',0,0,'normal',0,'',1629871219,1629871355);
insert into `hkcms_auth_rule` values (104,45,'cms/model/copy','Model copy','cms.model/copy','','fas fa-circle','',0,0,'normal',0,'',1629871319,1629871319);
insert into `hkcms_auth_rule` values (105,52,'routine/attachment/edit','Edit','routine.attachment/edit','','fas fa-circle','',0,0,'normal',0,'',1608545893,1608545893);
insert into `hkcms_auth_rule` values (106,108,'routine/seo/index','SEO Setting','routine.Seo/index','','fas fa-link','',12,1,'normal',1,'',1649080788,1649903731);
insert into `hkcms_auth_rule` values (107,108,'cms/flags','Flag','cms.flags/index','','fas fa-file-alt','',13,1,'normal',1,'',1649081519,1649903749);
insert into `hkcms_auth_rule` values (108,58,'config','Configure','','','fas fa-cog','',10,1,'normal',0,'',1649903613,1649903997);
insert into `hkcms_auth_rule` values (109,107,'cms/flags/index','View','cms.flag/index','','fas fa-circle','',0,0,'normal',0,'',1649904448,1649904448);
insert into `hkcms_auth_rule` values (110,107,'cms/flags/add','Add','cms.flags/add','','fas fa-circle','',0,0,'normal',0,'',1649904896,1649904896);
insert into `hkcms_auth_rule` values (111,107,'cms/flags/edit','Edit','cms.flags/edit','','fas fa-circle','',0,0,'normal',0,'',1649905610,1649905610);
insert into `hkcms_auth_rule` values (112,107,'cms/flags/del','Delete','cms.flags/del','','fas fa-circle','',0,0,'normal',0,'',1649905706,1649905706);
insert into `hkcms_auth_rule` values (113,107,'cms/flags/batches','Batch edit','cms.flags/batches','','fas fa-circle','',0,0,'normal',0,'',1649905774,1649905774);
insert into `hkcms_auth_rule` values (114,52,'routine/attachment/water','Watermark','routine.attachment/water','','fas fa-tint','',0,0,'normal',1,'',1658593132,1658593132);
insert into `hkcms_auth_rule` values (115,52,'routine/attachment/thumb','Thumbnail','routine.attachment/thumb','','far fa-image','',0,0,'normal',1,'',1658593175,1658593175);
insert into `hkcms_auth_rule` values (116,0,'index/clearcache','Clean cache','','','fas fa-circle','',5,0,'normal',0,'',1594779897,1617268812);
insert into `hkcms_auth_rule` values (117,108,'tags/index','Tags manage','','','fas fa-tags','',15,1,'normal',1,'',1666665197,1666665361);
insert into `hkcms_auth_rule` values (118,117,'tags/add','Add','','','far fa-circle','',100,0,'normal',0,'',1666665197,1666665197);
insert into `hkcms_auth_rule` values (119,117,'tags/edit','Edit','','','far fa-circle','',100,0,'normal',0,'',1666665197,1666665197);
insert into `hkcms_auth_rule` values (120,117,'tags/delete','Delete','','','far fa-circle','',100,0,'normal',0,'',1666665197,1666665197);
insert into `hkcms_auth_rule` values (123,58,'user','Member','','','fas fa-user-tie','',100,1,'normal',0,'',1669370924,1669371783);
insert into `hkcms_auth_rule` values (124,123,'user/user','Member','user.user/index','','far fa-user-circle','',0,1,'normal',1,'',1669370924,1669371278);
insert into `hkcms_auth_rule` values (125,124,'user/user/index','View','user.user/index','','fas fa-circle','',0,0,'normal',0,'',1669370924,1669370924);
insert into `hkcms_auth_rule` values (126,124,'user/user/edit','Edit','user.user/edit','','fas fa-circle','',0,0,'normal',0,'',1669370924,1669370924);
insert into `hkcms_auth_rule` values (127,124,'user/user/del','Delete','user.user/del','','fas fa-circle','',0,0,'normal',0,'',1669370924,1669370924);
insert into `hkcms_auth_rule` values (128,124,'user/user/batches','Batch edit','user.user/batches','','fas fa-circle','',0,0,'normal',0,'',1669370924,1669370924);
insert into `hkcms_auth_rule` values (129,123,'user/group','Member group','user.group/index','','fas fa-users','',0,1,'normal',1,'',1669370924,1669371795);
insert into `hkcms_auth_rule` values (130,129,'user/group/index','View','user.group/index','','fas fa-circle','',0,0,'normal',0,'',1669370924,1669370924);
insert into `hkcms_auth_rule` values (131,129,'user/group/edit','Edit','user.group/edit','','fas fa-circle','',0,0,'normal',0,'',1669370924,1669370924);
insert into `hkcms_auth_rule` values (132,129,'user/group/add','Add','user.group/add','','fas fa-circle','',0,0,'normal',0,'',1669370924,1669370924);
insert into `hkcms_auth_rule` values (133,129,'user/group/del','Delete','user.group/del','','fas fa-circle','',0,0,'normal',0,'',1669370924,1669370924);
insert into `hkcms_auth_rule` values (134,129,'user/group/batches','Batch edit','user.group/batches','','fas fa-circle','',0,0,'normal',0,'',1669370924,1669370924);
insert into `hkcms_auth_rule` values (135,123,'user/rule','Member rule','user.rule/index','','fas fa-user-lock','',0,1,'normal',1,'',1669370924,1669371418);
insert into `hkcms_auth_rule` values (136,135,'user/rule/index','View','user.rule/index','','fas fa-circle','',0,0,'normal',0,'',1669370924,1669370924);
insert into `hkcms_auth_rule` values (137,135,'user/rule/edit','Edit','user.rule/edit','','fas fa-circle','',0,0,'normal',0,'',1669370924,1669370924);
insert into `hkcms_auth_rule` values (138,135,'user/rule/add','Add','user.rule/add','','fas fa-circle','',0,0,'normal',0,'',1669370924,1669370924);
insert into `hkcms_auth_rule` values (139,135,'user/rule/del','Delete','user.rule/del','','fas fa-circle','',0,0,'normal',0,'',1669370924,1669370924);
insert into `hkcms_auth_rule` values (140,135,'user/rule/batches','Batch edit','user.rule/batches','','fas fa-circle','',0,0,'normal',0,'',1669370924,1669371448);
insert into `hkcms_auth_rule` values (141,50,'routine/lang','Lang management','routine.Lang','','fas fa-language','',100,0,'normal',1,'',1682150648,1682151957);
insert into `hkcms_auth_rule` values (142,141,'routine/lang/index','View','routine.Lang/index','','fas fa-circle','',100,0,'normal',0,'',1682150953,1682150953);
insert into `hkcms_auth_rule` values (143,141,'routine/lang/add','Add','routine.Lang/add','','fas fa-circle','',100,0,'normal',0,'',1682151046,1682151046);
insert into `hkcms_auth_rule` values (144,141,'routine/lang/edit','Edit','routine.Lang/edit','','fas fa-circle','',100,0,'normal',0,'',1682151102,1682151102);
insert into `hkcms_auth_rule` values (145,141,'routine/lang/del','Delete','routine.Lang/del','','fas fa-circle','',100,0,'normal',0,'',1682151166,1682152133);
insert into `hkcms_auth_rule` values (146,141,'routine/lang/batches','Batch edit','routine.Lang/batches','','fas fa-circle','',100,0,'normal',0,'',1682151621,1682151621);
insert into `hkcms_auth_rule` values (147,141,'routine/lang/setdefault','Set default','routine.Lang/setDefault','','fas fa-circle','',100,0,'normal',0,'',1682151785,1682152199);
insert into `hkcms_auth_rule` values (148,50,'routine/config/index','View','routine.config/index','','fas fa-circle','',10,0,'normal',0,'',1682152458,1682152494);
insert into `hkcms_auth_rule` values (149,50,'routine/config/add','Add','routine.config/edit','','fas fa-circle','',30,0,'normal',0,'',1682152809,1682152816);



DROP TABLE IF EXISTS `hkcms_admin_panel`;
CREATE TABLE `hkcms_admin_panel` (
  `auth_rule_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '菜单ID',
  `admin_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
  UNIQUE KEY `userid` (`auth_rule_id`,`admin_id`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='管理后台常用菜单';


DROP TABLE IF EXISTS `hkcms_app`;
CREATE TABLE `hkcms_app` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL COMMENT '应用标识',
  `title` varchar(80) NOT NULL COMMENT '标题',
  `image` varchar(80) NOT NULL DEFAULT '' COMMENT '封面',
  `price` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '价格',
  `module` varchar(20) NOT NULL DEFAULT '' COMMENT '所属模块',
  `type` enum('addon','module','template') NOT NULL COMMENT '类型',
  `config` text COMMENT '配置信息',
  `description` varchar(255) NOT NULL DEFAULT '' COMMENT '描述',
  `author` varchar(50) NOT NULL DEFAULT '' COMMENT '作者',
  `version` varchar(20) NOT NULL COMMENT '版本',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '状态:1-启用,0-未安装,-1-禁用',
  `createtime` int(11) DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8 COMMENT='应用表';

insert  into `hkcms_app` values (3,'simditor','simditor编辑器','http://api.hkcms.cn/uploads/20210319/6d1cbf8f5d89c8eb0248282df29686ae.png',0.00,'','addon',NULL,'simditor编辑器','HkCms','1.0.1',1,1618552757);
insert  into `hkcms_app` values (9,'default','默认前台模板','',0.00,'index','template',NULL,'默认前台模板主题，更多主题前往应用市场下载安装。','HkCms','1.0.0',1,1618564047);
insert  into `hkcms_app` values (10,'adminlte','后台模板','',0.00,'admin','template',NULL,'默认后台模板主题，更多主题前往应用市场下载安装。','HkCms','1.0.0',1,1618564047);
insert  into `hkcms_app` values (12,'address','地图位置选取插件','',0.00,'','addon','','支持百度、高德地图位置选取。','HkCms','1.0.0',1,1618566933);


DROP TABLE IF EXISTS `hkcms_attachment`;
CREATE TABLE `hkcms_attachment` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '附件ID',
  `user_id` int(11) NOT NULL COMMENT '用户ID',
  `user_type` tinyint(1) NOT NULL DEFAULT '1' COMMENT '用户类型:1-后台管理员',
  `title` char(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '附件名',
  `path` char(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '附件路径',
  `size` int(11) NOT NULL DEFAULT '0' COMMENT '附件大小(字节)',
  `ext` char(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '附件扩展',
  `mime_type` char(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '附件mimeType',
  `md5` char(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'md5',
  `storage` char(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'local' COMMENT '存储位置',
  `remark` char(250) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '描述',
  `status` enum('normal','hidden') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'normal' COMMENT '状态:normal-正常,hidden-禁用',
  `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `path` (`path`,`storage`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='附件表';


DROP TABLE IF EXISTS `hkcms_category`;
CREATE TABLE `hkcms_category` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `model_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '模型ID',
  `parent_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '父ID',
  `type` enum('link','list','category') NOT NULL DEFAULT 'link' COMMENT '类型',
  `app` varchar(15) NOT NULL DEFAULT 'cms' COMMENT '所属应用',
  `name` varchar(50) NOT NULL COMMENT '栏目名称',
  `title` varchar(50) NOT NULL DEFAULT '' COMMENT '栏目标题',
  `url` varchar(250) NOT NULL DEFAULT '' COMMENT '指定url',
  `image` varchar(250) NOT NULL DEFAULT '' COMMENT '栏目图片',
  `category_tpl` varchar(100) NOT NULL DEFAULT '' COMMENT '栏目模板',
  `list_tpl` varchar(100) NOT NULL DEFAULT '' COMMENT '列表模板',
  `show_tpl` varchar(100) NOT NULL DEFAULT '' COMMENT '内容模板',
  `seo_title` varchar(250) NOT NULL DEFAULT '' COMMENT 'SEO标题',
  `seo_keywords` varchar(250) NOT NULL DEFAULT '' COMMENT 'SEO关键字',
  `seo_desc` varchar(250) NOT NULL DEFAULT '' COMMENT 'SEO描述',
  `ismenu` tinyint(4) NOT NULL DEFAULT '1' COMMENT '导航显示:1-显示,0-隐藏',
  `target` varchar(20) NOT NULL DEFAULT '_self' COMMENT '弹出方式',
  `user_auth` tinyint(1) NOT NULL DEFAULT '0' COMMENT '用户投稿:1=允许,0=不允许',
  `lang` varchar(20) NOT NULL DEFAULT '' COMMENT '语言标识',
  `weigh` int(11) NOT NULL DEFAULT '0' COMMENT '排序',
  `num` int(11) NOT NULL DEFAULT '10' COMMENT '分页大小',
  `delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
  `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  `status` enum('normal','hidden') NOT NULL DEFAULT 'normal' COMMENT '状态:normal-正常,hidden-禁用',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COMMENT='栏目管理';


DROP TABLE IF EXISTS `hkcms_category_priv`;
CREATE TABLE `hkcms_category_priv` (
  `category_id` int(10) unsigned NOT NULL COMMENT '栏目ID',
  `auth_group_id` mediumint(8) NOT NULL COMMENT '管理员ID',
  `action` char(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '操作方法'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='栏目权限';


DROP TABLE IF EXISTS `hkcms_config`;
CREATE TABLE `hkcms_config` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `group` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '分组：basics（基础），mail（邮箱），extend（扩展）',
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '键名',
  `title` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '名称',
  `value` text COLLATE utf8mb4_unicode_ci COMMENT '值',
  `type` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '类型',
  `data_list` text COLLATE utf8mb4_unicode_ci COMMENT '数据列表',
  `tips` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '提示',
  `error_tips` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '错误提示',
  `rules` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '规则',
  `extend` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '附加属性',
  `max_number` int(11) NOT NULL DEFAULT '0' COMMENT '最大数量',
  `is_default` tinyint(1) NOT NULL DEFAULT '1' COMMENT '默认配置',
  `lang` char(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '语言标识',
  `setting` text COLLATE utf8mb4_unicode_ci COMMENT '配置信息',
  `weigh` int(11) NOT NULL DEFAULT '1' COMMENT '排序',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `uq_name_lang` (`name`,`lang`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='全站配置';


insert  into `hkcms_config` values (1,'basics','title','Site name','HkCms开源内容管理系统','text',NULL,'请填写网站名称','','','',0,1,'zh-cn',NULL,1);
insert  into `hkcms_config` values (2,'basics','cdn','Site domain','','text',NULL,'网站网址、填写后，影响静态资源访问地址，https://xx.com，无需\"/\"结尾','','','',0,1,-1,NULL,8);
insert  into `hkcms_config` values (3,'basics','home_title','Home title','HkCms演示站点 - 网站首页','text',NULL,'','','','',0,1,'zh-cn',NULL,1);
insert  into `hkcms_config` values (4,'basics','keyword','Home Keyword','关键字,企业站点','textarea',NULL,'建议不超过100字符','','','',0,1,'zh-cn',NULL,1);
insert  into `hkcms_config` values (5,'basics','description','Home description','HkCms开源内容管理系统是一款基于ThinkPHP6.0开发的CMS系统。以免授权、永久商用、系统易安装升级、界面功能简洁轻便、易上手、插件与模板在线升级安装、建站联盟扶持计划等优势为一体的CMS系统。','textarea',NULL,'建议不超过200字符','','','',0,1,'zh-cn',NULL,1);
insert  into `hkcms_config` values (6,'basics','icp','Case number','粤ICP备10000000号-1','text',NULL,'粤ICP备10000000号-1','','','',0,1,'zh-cn',NULL,3);
insert  into `hkcms_config` values (8,'basics','version','Version','1.0.0','text',NULL,'修改版本号更新前台JS、CSS','','','',0,1,-1,NULL,9);
insert  into `hkcms_config` values (9,'mail','mail_type','Mail sending mode','smtp','select','{\"smtp\":\"SMTP\"}','选择邮件发送方式','','','',0,1,-1,NULL,2);
insert  into `hkcms_config` values (10,'mail','mail_server','SMTP server','smtp.qq.com','text',NULL,'错误的配置发送邮件会导致服务器超时','','','',0,1,-1,NULL,2);
insert  into `hkcms_config` values (11,'mail','mail_port','SMTP port','465','text',NULL,'(不加密默认25,SSL默认465,TLS默认587)','','','',0,1,-1,NULL,2);
insert  into `hkcms_config` values (12,'mail','mail_from','Sender mailbox','','text',NULL,'（填写完整邮箱）','','','',0,1,-1,NULL,2);
insert  into `hkcms_config` values (13,'mail','mail_fname','Sender name','','text',NULL,'（发件人名称标题）','','','',0,1,-1,NULL,2);
insert  into `hkcms_config` values (14,'mail','mail_auth','SMTP auth','','select','{\"ssl\":\"SSL\",\"tls\":\"TLS\"}','（SMTP验证方式[推荐SSL]）','','','',0,1,-1,NULL,2);
insert  into `hkcms_config` values (15,'mail','mail_user','SMTP username','','text',NULL,'（填写完整邮箱）','','','',0,1,-1,NULL,2);
insert  into `hkcms_config` values (16,'mail','mail_password','SMTP password','','text',NULL,'（密码）','','','',0,1,-1,NULL,2);
insert  into `hkcms_config` values (17,'upload','file_type','Upload attachment type','jpg|gif|png|bmp|jpeg|ico|webp|zip|gz|rar|iso|txt|doc|xls|xlsx|ppt|wps|swf|mpg|mp3|rm|rmvb|wmv|wma|wav|mid|mov|mp4|docx','textarea',NULL,'格式：value0|value1|value2','','','',0,1,-1,NULL,1);
insert  into `hkcms_config` values (18,'upload','file_size','Upload attachment size','10','number',NULL,'MB','','required','',0,1,-1,NULL,1);
insert  into `hkcms_config` values (35,'more','cloud_username','Application center account','','text',NULL,'应用中心登录账号','','','',0,1,-1,NULL,1);
insert  into `hkcms_config` values (36,'more','cloud_password','Application center password','','text',NULL,'应用中心登录账号的密码','','','',0,1,-1,NULL,1);
insert  into `hkcms_config` values (42,'basics','logo','Website LOGO','http://download.hkcms.cn/hkcms/logo.png','image',NULL,'','','','',0,1,'zh-cn',NULL,8);
insert  into `hkcms_config` values (43,'basics','favicon','Address bar icon','/favicon.ico','image',NULL,'','','','',0,1,'zh-cn',NULL,8);
insert  into `hkcms_config` values (44,'basics','web_status','Site status','1','radio','{\"1\":\"\\u5f00\\u542f\",\"0\":\"\\u5173\\u95ed\"}','','','','',0,1,-1,NULL,10);
insert  into `hkcms_config` values (47,'more','admin_theme','Background template','adminlte','text',NULL,'','','','',0,1,-1,NULL,1);
insert  into `hkcms_config` values (48,'more','index_theme','Foreground template','default','text',NULL,'','','','',0,1,-1,NULL,1);
insert  into `hkcms_config` values (51,'group','group','Group config','{\"basics\":\"Basic config\",\"mail\":\"Mail config\",\"upload\":\"Upload config\",\"language\":\"Language\",\"more\":\"Advanced config\",\"member\":\"Member Center\"}','',NULL,'配置分组','','','',0,1,-1,NULL,1);
insert  into `hkcms_config` values (52,'upload','upload_url','Upload url','/common/upload','text',NULL,'每个模块默认上传地址','','','',0,1,-1,NULL,1);
insert  into `hkcms_config` values (53,'upload','cdn_url','CDN url','','text',NULL,'','','','',0,1,-1,NULL,1);
insert  into `hkcms_config` values (54,'upload','savename','Save format','/uploads/{year}{month}{day}/{md5}{suffix}','text',NULL,'','','','',0,1,-1,NULL,1);
insert  into `hkcms_config` values (55,'upload','chunk','Chunk','2','radio','{\"1\":\"\\u5f00\\u542f\",\"2\":\"\\u5173\\u95ed\"}','','','','',0,1,-1,NULL,1);
insert  into `hkcms_config` values (56,'upload','chunk_size','Chunk size','2','number',NULL,'单位：MB','','required','',0,1,-1,NULL,1);
insert  into `hkcms_config` values (57,'seo','url_mode','URL mode','0','radio','{\"1\":\"伪静态\",\"0\":\"动态\",\"2\":\"静态页面\"}','','','','',0,1,-1,NULL,1);
insert  into `hkcms_config` values (58,'language','content_lang_on','Content multilingual','2','radio','{\"1\":\"开启\",\"2\":\"关闭\"}','内容多语言，开启后栏目、内容将支持多语言。详情前往手册了解','','','',0,1,-1,NULL,1);
insert  into `hkcms_config` values (59,'mail','mail_on','Mailbox switch','0','radio','{\"1\":\"开启\",\"0\":\"关闭\"}','','','','',0,1,-1,NULL,1);
insert  into `hkcms_config` values (60,'more','url_rewrite','URL rewrite','{\"tags/index\":\"/t/index$.html\",\"tags/lists\":\"/t/:tag$.html\",\"search/index\":\"/search$.html\",\"guestbook/index\":\"/guestbook$.html\",\"index/lists\":\"/:catname/$,/:catname/list_:page$.html\",\"index/show\":\"/:catname/:id$.html\"}','array','{\"key\":\"URL\\u5730\\u5740\",\"value\":\"\\u89c4\\u5219\"}','','','','',0,1,'-1',NULL,3);
insert  into `hkcms_config` values (62,'more','mobile_domain','Mobile domain name','','text',NULL,'填写域名后开启，访问时将自动访问手机端模板，如果是响应式则访问响应式','','','',0,1,'-1','{\"ext\":\"\",\"filesize\":\"\",\"filter_option\":\"\"}',4);
insert  into `hkcms_config` values (63,'more','url_file','Entry file','1','radio','{\"1\":\"显示\",\"0\":\"隐藏\"}','用于URL模式为动态时，默认加上入口文件实现无需配置URL重写，注意：隐藏后您的服务器必须配置URL重写才能正常访问','','','',0,1,'-1',NULL,2);
insert  into `hkcms_config` values (64,'more','category_format','Column title format','$name - $site','text',NULL,'','','','',0,1,'-1',NULL,8);
insert  into `hkcms_config` values (65,'more','content_format','Content title format','$title - $name - $site','text',NULL,'','','','',0,1,'-1',NULL,8);
insert  into `hkcms_config` values (66,'basics','thirdcode_pc','Third party code,PC','','textarea',NULL,'代码自动放在前台网页底部，无需手动添加，常用于站点统计、百度商桥等代码','','','',0,1,-1,'{\"ext\":\"\",\"filesize\":\"\",\"filter_option\":\"\"}',9);
insert  into `hkcms_config` values (67,'basics','thirdcode_mobile','Third party code,Mobile','','textarea',NULL,'代码自动放在前台网页底部，无需手动添加，常用于站点统计、百度商桥等代码','','','',0,1,-1,'{\"ext\":\"\",\"filesize\":\"\",\"filter_option\":\"\"}',9);
insert  into `hkcms_config` values (68,'basics','dev','Developer','disabled','radio','{\"enable\":\"\\u5f00\\u542f\",\"disabled\":\"\\u5173\\u95ed\"}','一键开启调试模式，方便修改页面、不受缓存影响以及显示详细的错误信息。','','','',0,1,-1,'{\"ext\":\"\",\"filesize\":\"\",\"filter_option\":\"\"}',9);
insert  into `hkcms_config` values (69,'seo','html_dir','静态文件保存目录','','text',NULL,'','','','',0,1,'-1',NULL,1);
insert  into `hkcms_config` values (70,'seo','html_column_rules','栏目规则','/[list]/index.html|/[list]/index_[page].html','text',NULL,'','','','',0,1,'-1',NULL,1);
insert  into `hkcms_config` values (71,'seo','html_content_rules','内容规则','/[list]/[id].html|/[list]/[id]_[page].html','text',NULL,'','','','',0,1,'-1',NULL,1);
insert  into `hkcms_config` values (72,'water','water_on','水印功能','0','radio','{\"1\":\"\\u5f00\\u542f\",\"0\":\"\\u5173\\u95ed\"}','','','','',0,1,'-1','{\"ext\":\"\",\"filesize\":\"\",\"filter_option\":\"\"}',11);
insert  into `hkcms_config` values (73,'water','water_type','水印类型','1','radio','{\"1\":\"\\u56fe\\u7247\",\"2\":\"\\u6587\\u5b57\"}','','','','',0,1,'-1','{\"ext\":\"\",\"filesize\":\"\",\"filter_option\":\"\"}',12);
insert  into `hkcms_config` values (74,'water','water_img','水印图片','/static/common/image/water.png','text',NULL,'','','','',0,1,'-1','{\"ext\":\"\",\"filesize\":\"\",\"filter_option\":\"\"}',13);
insert  into `hkcms_config` values (75,'water','water_width','图片宽度','100','text',NULL,'','','','',0,1,'-1','{\"ext\":\"\",\"filesize\":\"\",\"filter_option\":\"\"}',14);
insert  into `hkcms_config` values (76,'water','water_height','图片高度','50','text',NULL,'','','','',0,1,'-1','{\"ext\":\"\",\"filesize\":\"\",\"filter_option\":\"\"}',15);
insert  into `hkcms_config` values (77,'water','water_text','文字','HkCms','text',NULL,'','','','',0,1,'-1','{\"ext\":\"\",\"filesize\":\"\",\"filter_option\":\"\"}',16);
insert  into `hkcms_config` values (78,'water','water_text_size','文字大小','16','text',NULL,'','','','',0,1,'-1','{\"ext\":\"\",\"filesize\":\"\",\"filter_option\":\"\"}',17);
insert  into `hkcms_config` values (79,'water','water_text_color','文字颜色','#00000042','text',NULL,'','','','',0,1,'-1','{\"ext\":\"\",\"filesize\":\"\",\"filter_option\":\"\"}',18);
insert  into `hkcms_config` values (80,'water','water_img_opacity','水印透明度','60','text',NULL,'','','','',0,1,'-1','{\"ext\":\"\",\"filesize\":\"\",\"filter_option\":\"\"}',19);
insert  into `hkcms_config` values (81,'water','water_img_position','水印位置','7','text',NULL,'','','','',0,1,'-1','{\"ext\":\"\",\"filesize\":\"\",\"filter_option\":\"\"}',20);
insert  into `hkcms_config` values (82,'thumb','thumb_on','缩略图','0','radio','{\"1\":\"\\u5f00\\u542f\",\"0\":\"\\u5173\\u95ed\"}','','','','',0,1,'-1','{\"ext\":\"\",\"filesize\":\"\",\"filter_option\":\"\"}',11);
insert  into `hkcms_config` values (83,'thumb','thumb_type','生成方式','1','text',NULL,'','','','',0,1,'-1','{\"ext\":\"\",\"filesize\":\"\",\"filter_option\":\"\"}',12);
insert  into `hkcms_config` values (84,'thumb','thumb_width','宽度','160','text',NULL,'','','','',0,1,'-1','{\"ext\":\"\",\"filesize\":\"\",\"filter_option\":\"\"}',13);
insert  into `hkcms_config` values (85,'thumb','thumb_height','高度','120','text',NULL,'','','','',0,1,'-1','{\"ext\":\"\",\"filesize\":\"\",\"filter_option\":\"\"}',14);
insert  into `hkcms_config` values (86,'basics','psrn','PSRN','京公网安备10000000号','text',NULL,'公安网站备案号','','','',0,1,'zh-cn',NULL,3);
insert  into `hkcms_config` values (87,'tags','tags_index','标签首页模板','tags_index','text',NULL,'','','','',0,1,'-1',NULL,1);
insert  into `hkcms_config` values (88,'tags','tags_list','标签列表页模板','tags_list','text',NULL,'','','','',0,1,'-1',NULL,2);
insert  into `hkcms_config` values (89,'tags','tags_seo_title','SEO标题','','text',NULL,'','','','',0,1,'-1',NULL,3);
insert  into `hkcms_config` values (90,'tags','tags_seo_keyword','SEO关键字','','text',NULL,'','','','',0,1,'-1',NULL,4);
insert  into `hkcms_config` values (91,'tags','tags_seo_desc','SEO描述','','text',NULL,'','','','',0,1,'-1',NULL,5);
insert  into `hkcms_config` values (92,'member','user_on','Member Center switch','1','radio','{\"1\":\"\\u5f00\\u542f\",\"0\":\"\\u5173\\u95ed\"}','','','','',0,1,-1,NULL,1);
insert  into `hkcms_config` values (93,'member','register_captcha','Registration verification code','2','radio','{\"1\":\"\\u6587\\u5b57\",\"2\":\"\\u90ae\\u7bb1\",\"3\":\"\\u624b\\u673a\"}','邮箱验证码在邮件配置，手机验证码安装短信插件','','','',0,1,-1,NULL,2);
insert  into `hkcms_config` values (94,'member','login_captcha','Login verification code','2','radio','{\"1\":\"\\u5f00\\u542f\",\"2\":\"\\u5173\\u95ed\"}','开启关闭登录页验证码功能','','','',0,1,-1,NULL,3);
insert  into `hkcms_config` values (95,'language','admin_lang_on','Background language','2','radio','{\"1\":\"开启\",\"2\":\"关闭\"}','','','','',0,1,'-1',NULL,1);
insert  into `hkcms_config` values (96,'language','index_lang_on','Foreground language','2','radio','{\"1\":\"开启\",\"2\":\"关闭\"}','','','','',0,1,'-1',NULL,3);
insert  into `hkcms_config` values (97,'member','login_fail_count','Login fail count','5','number',NULL,'账号密码登录失败次数','','required','',0,1,'-1',NULL,4);




DROP TABLE IF EXISTS `hkcms_ems`;
CREATE TABLE `hkcms_ems` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `event` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '事件',
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '邮箱',
  `code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '验证码',
  `count` int(11) NOT NULL DEFAULT '0' COMMENT '验证次数',
  `ip` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'IP',
  `create_time` int(11) NOT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `hkcms_fields`;
CREATE TABLE `hkcms_fields` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `source` varchar(30) NOT NULL DEFAULT '' COMMENT '来源',
  `source_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '来源ID',
  `field_name` varchar(20) NOT NULL COMMENT '字段名称',
  `field_title` varchar(50) NOT NULL DEFAULT '' COMMENT '字段中文名',
  `form_type` varchar(20) NOT NULL COMMENT '字段类型',
  `field_group` varchar(50) NOT NULL DEFAULT '常规' COMMENT '字段分组',
  `length` int(11) NOT NULL DEFAULT '250' COMMENT '长度',
  `default_value` text COMMENT '默认值',
  `data_list` text COMMENT '选项列表',
  `max_number` int(11) NOT NULL DEFAULT '0' COMMENT '最大数量',
  `decimals` tinyint(4) NOT NULL DEFAULT '0' COMMENT '小数位',
  `rules` varchar(255) NOT NULL DEFAULT '' COMMENT '规则',
  `tips` varchar(255) NOT NULL DEFAULT '' COMMENT '提示信息',
  `error_tips` varchar(255) NOT NULL DEFAULT '' COMMENT '错误提示',
  `extend` varchar(500) NOT NULL DEFAULT '' COMMENT '附加属性',
  `weigh` int(11) NOT NULL DEFAULT '0' COMMENT '排序',
  `setting` text COMMENT '配置信息',
  `status` enum('normal','hidden') NOT NULL DEFAULT 'normal' COMMENT '状态:normal-正常,hidden-禁用',
  `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COMMENT='通用字段表';


DROP TABLE IF EXISTS `hkcms_model_controller`;
CREATE TABLE `hkcms_model_controller` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `title` varchar(80) NOT NULL COMMENT '标题',
  `name` varchar(50) NOT NULL COMMENT '控制器名称',
  `sql_file` varchar(500) NOT NULL COMMENT 'sql执行文件',
  `single_sql` varchar(500) NOT NULL DEFAULT '' COMMENT '已废弃',
  `type` enum('more','single') NOT NULL DEFAULT 'single' COMMENT '类型:sing-单页,more-列表',
  `is_search` tinyint(1) NOT NULL DEFAULT '1' COMMENT '搜索:1-支持全局,-1-不支持',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  `status` enum('normal','hidden') NOT NULL DEFAULT 'normal' COMMENT '状态:normal-正常,hidden-禁用',
  `config` text COMMENT '模型初始配置',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COMMENT='模型控制器';


insert  into `hkcms_model_controller` values (1,'文章类','Archives','extend/libs/table/template/base_data.sql,extend/libs/table/template/base_insert_field.sql','','more',1,1606820939,'normal',null);
insert  into `hkcms_model_controller` values (2,'单页类','Single','extend/libs/table/template/base_data.sql,extend/libs/table/template/base_single_insert_field.sql','','single',1,1606820939,'normal',null);
insert  into `hkcms_model_controller` values (3,'表单类','Guestbook','extend/libs/table/template/guestbook.sql','','single',-1,1606820939,'normal','{\"captcha\":{\"title\":\"验证码\",\"type\":\"radio\",\"tips\":\"开启后表单提交必须要有验证码\",\"rules\":\"\",\"error_tips\":\"\",\"options\":{\"1\":\"开启\",\"0\":\"关闭\"},\"value\":\"1\"},\"type\":{\"title\":\"验证码类型\",\"type\":\"radio\",\"tips\":\"手机验证码需要短信插件，邮箱验证码站点配置-邮箱配置\",\"rules\":\"\",\"error_tips\":\"\",\"options\":{\"text\":\"文本\",\"email\":\"邮箱\",\"mobile\":\"手机\"},\"value\":\"text\"},\"msg\":{\"title\":\"留言通知\",\"type\":\"radio\",\"tips\":\"开启后用户留言您将收到通知~\",\"rules\":\"\",\"error_tips\":\"\",\"options\":{\"1\":\"开启\",\"0\":\"关闭\"},\"value\":\"0\"},\"msgtype\":{\"title\":\"留言通知方式\",\"type\":\"checkbox\",\"tips\":\"开启后用户留言您将收到通知\",\"rules\":\"\",\"error_tips\":\"\",\"options\":{\"email\":\"邮箱\"},\"value\":\"email\"},\"msgemail\":{\"title\":\"通知邮箱地址\",\"type\":\"text\",\"tips\":\"通知方式为邮箱时生效,格式：admin@qq.com\",\"rules\":\"\",\"error_tips\":\"\",\"value\":\"\"},\"tcount\":{\"title\":\"间隔\",\"type\":\"text\",\"tips\":\"表单提交间隔(秒)\",\"rules\":\"\",\"error_tips\":\"\",\"value\":\"60\"}}');


DROP TABLE IF EXISTS `hkcms_model`;
CREATE TABLE `hkcms_model` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '模型ID',
  `name` char(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '模型名称',
  `alias` char(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '模型别名',
  `diyname` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '自定义URL',
  `tablename` char(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '表名称',
  `remark` char(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '备注说明',
  `controller` char(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Archives' COMMENT '控制器',
  `type` enum('single','more') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'more' COMMENT '类型:sing-单页,more-列表',
  `allow_single` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'type=more下有效，1-允许单表，0-不允许',
  `is_search` tinyint(1) NOT NULL DEFAULT '1' COMMENT '搜索:1-允许搜索,0-不允许,-1-不支持',
  `category_tpl` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '栏目模板',
  `list_tpl` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '列表模板',
  `show_tpl` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '内容模板',
  `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  `status` enum('normal','hidden') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'normal' COMMENT '状态:normal-正常,hidden-禁用',
  `config` text COLLATE utf8mb4_unicode_ci COMMENT '模型配置',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='模型表';


insert  into `hkcms_model` values (1,'文章模型','文章','archives','archives_base','','Archives','more',0,1,'','','',1603332354,1602330338,'normal',null);
insert  into `hkcms_model` values (2,'单页模型','单页','single','archives_single','','Single','single',0,1,'','','',1617965433,1606901317,'normal',null);
insert  into `hkcms_model` values (5,'留言表单','留言','guestbook','guestbook','','Guestbook','single',0,-1,'','','',1617871031,1617871031,'normal','{"captcha":"1","type":"text","msg":"0","msgtype":["email"],"msgemail":"","tcount":"60"}');


DROP TABLE IF EXISTS `hkcms_model_field`;
CREATE TABLE `hkcms_model_field` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `model_id` int(10) unsigned NOT NULL COMMENT '模型ID',
  `field_name` varchar(20) NOT NULL COMMENT '字段名称',
  `field_title` varchar(50) NOT NULL DEFAULT '' COMMENT '字段标题',
  `form_type` varchar(20) NOT NULL COMMENT '字段类型',
  `field_group` varchar(50) NOT NULL DEFAULT '常规' COMMENT '字段分组',
  `length` int(11) NOT NULL DEFAULT '250' COMMENT '长度',
  `default_value` text COMMENT '默认值',
  `data_list` text COMMENT '选项列表',
  `max_number` int(11) NOT NULL DEFAULT '0' COMMENT '最大数量',
  `decimals` tinyint(4) NOT NULL DEFAULT '0' COMMENT '小数位',
  `rules` varchar(255) NOT NULL DEFAULT '' COMMENT '规则',
  `tips` varchar(255) NOT NULL DEFAULT '' COMMENT '提示信息',
  `error_tips` varchar(255) NOT NULL DEFAULT '' COMMENT '错误提示',
  `extend` varchar(500) NOT NULL DEFAULT '' COMMENT '附加属性',
  `iscore` tinyint(1) NOT NULL DEFAULT '0' COMMENT '主表字段:1-是,0-不是',
  `default_field` tinyint(1) NOT NULL DEFAULT '0' COMMENT '默认字段:1-默认,0-不是',
  `weigh` int(11) NOT NULL DEFAULT '0' COMMENT '排序',
  `setting` text COMMENT '配置信息',
  `user_auth` tinyint(1) NOT NULL DEFAULT '0' COMMENT '用户投稿:1=允许,0=不允许',
  `admin_auth` tinyint(1) NOT NULL DEFAULT '1' COMMENT '后台投稿:1=允许,0=不允许',
  `is_filter` tinyint(1) NOT NULL DEFAULT '0' COMMENT '筛选:1=开启,0=关闭',
  `is_order` tinyint(1) NOT NULL DEFAULT '0' COMMENT '排序:1=开启,0=关闭',
  `status` enum('normal','hidden') NOT NULL DEFAULT 'normal' COMMENT '状态:normal-正常,hidden-禁用',
  `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=143 DEFAULT CHARSET=utf8mb4 COMMENT='模型字段表';


insert  into `hkcms_model_field` values (21,1,'title','标题','text','常规',200,'',NULL,0,0,'required','','','',1,1,1,null,1,1,0,0,'normal',1604564165,1600400002);
insert  into `hkcms_model_field` values (32,1,'thumb','封面图','image','常规',100,'',NULL,0,0,'','','','',1,1,3,null,1,1,0,0,'normal',1604564175,1600400154);
insert  into `hkcms_model_field` values (33,1,'keywords','关键词','text','SEO',200,'',NULL,0,0,'','多关键词之间用英文逗号隔开','','',1,1,15,null,0,1,0,0,'normal',1604564378,1600400833);
insert  into `hkcms_model_field` values (34,1,'description','描述','textarea','SEO',250,'',NULL,0,0,'','','','',1,1,16,null,0,1,0,0,'normal',1603937358,1600400870);
insert  into `hkcms_model_field` values (35,1,'show_tpl','内容模板','text','其他',100,'show.html',NULL,0,0,'','','','class=\"form-control selectpage\",data-select-only=\"true\",data-data=\"/cms.model/getTplName.html?type=show\",data-pagination=\"false\",data-key-field=\"name\"',1,1,24,null,0,1,0,0,'normal',1604561449,1600400927);
insert  into `hkcms_model_field` values (37,1,'publish_time','发布时间','datetime','其他',0,'',NULL,0,0,'','','','',1,1,23,null,0,1,0,1,'normal',1604563079,1600401003);
insert  into `hkcms_model_field` values (39,1,'weigh','排序','number','常规',11,'100',NULL,0,0,'','','','',1,1,8,null,0,1,0,0,'normal',1604564047,1600401198);
insert  into `hkcms_model_field` values (40,1,'content','内容','editor','常规',0,'',NULL,0,0,'','','','',0,1,7,null,1,1,0,0,'normal',1604565377,1600410507);
insert  into `hkcms_model_field` values (73,1,'status','状态','radio','常规',250,'normal','{\"normal\":\"\\u6b63\\u5e38\",\"hidden\":\"\\u7981\\u7528\",\"reject\":\"\\u62d2\\u7edd\",\"audit\":\"\\u5f85\\u5ba1\\u6838\"}',0,0,'','','','',1,1,11,null,0,1,0,0,'normal',1604041706,1604041706);
insert  into `hkcms_model_field` values (74,1,'author','作者','text','其他',250,'小编',NULL,0,0,'','','','',1,1,17,null,0,1,0,0,'normal',1604561438,1604557612);
insert  into `hkcms_model_field` values (76,1,'views','浏览量','number','其他',11,'0',NULL,0,0,'','','','',1,1,18,null,0,1,0,1,'normal',1604563975,1604561217);
insert  into `hkcms_model_field` values (78,1,'subtitle','副标题','text','常规',200,'',NULL,0,0,'','','','',1,1,2,null,0,1,0,0,'normal',1604564165,1600400002);
insert  into `hkcms_model_field` values (79,1,'user_id','投稿人','selectpage','常规',10,0,'{\"type\":\"table\",\"url\":\"\",\"url-show-field\":\"\",\"url-key-field\":\"\",\"url-search-field\":\"\",\"table\":\"user\",\"show-field\":\"username\",\"key-field\":\"id\",\"search-field\":\"username,nickname\",\"and-or\":\"\",\"order-by\":\"\",\"param\":{\"custom\":{\"status\":\"normal\"}},\"pagination\":\"1\"}',0,0,'','需要安装用户中心插件','','',1,1,3,'{\"ext\":\"\",\"filesize\":\"\",\"filter_option\":\"\"}',0,0,0,0,'normal',1649942700,1649924031);
insert  into `hkcms_model_field` values (80,1,'flags','文档属性','selectpage','常规',100,'','{\"type\":\"table\",\"url\":\"\",\"url-show-field\":\"\",\"url-key-field\":\"\",\"url-search-field\":\"\",\"table\":\"flags\",\"show-field\":\"title\",\"key-field\":\"name\",\"search-field\":\"title\",\"and-or\":\"\",\"order-by\":\"weigh desc\",\"param\":{\"custom\":{\"status\":\"normal\"}},\"multiple\":\"1\",\"pagination\":\"1\",\"select-only\":\"1\",\"enable-lang\":\"1\"}',0,0,'','','','',1,1,4,'{\"ext\":\"\",\"filesize\":\"\",\"filter_option\":\"\"}',0,1,0,0,'normal',1649942700,1649924031);
insert  into `hkcms_model_field` values (81,1,'price','价格','number','常规',10,'0',NULL,0,2,'','','','',1,1,4,null,1,0,0,1,'normal',1616492830,1616492650);
insert  into `hkcms_model_field` values (82,1,'diyname','自定义URL名','text','常规',100,'',NULL,0,0,'','用于地址栏访问的名称，支持字母,数字,-,_，若为空则采用文章ID','','',1,1,6,null,0,1,0,0,'normal',1604564165,1600400002);
insert  into `hkcms_model_field` values (83,1,'iscomment','允许评论','radio','常规',1,'1','{\"1\":\"允许\",\"0\":\"不允许\"}',0,0,'','需要安装评论插件','','',1,1,9,null,0,1,0,0,'normal',1604041706,1604041706);
insert  into `hkcms_model_field` values (84,1,'islogin','访问限制','radio','常规',1,'0','{\"1\":\"需要登录\",\"0\":\"无限制\"}',0,0,'','需要安装用户中心插件','','',1,1,10,null,0,1,0,0,'normal',1604041706,1604041706);
insert  into `hkcms_model_field` values (85,1,'seotitle','seo标题','text','SEO',200,'',NULL,0,0,'','seo标题，为空则使用文章标题','','',1,1,14,null,0,1,0,0,'normal',1604564378,1600400833);
insert  into `hkcms_model_field` values (86,1,'jumplink','跳转链接','text','其他',255,'',NULL,0,0,'','设置文档跳转到外部链接','','',1,1,18,null,0,1,0,0,'normal',1604564165,1600400002);
insert  into `hkcms_model_field` values (87,1,'likes','点赞数','number','其他',11,'0',NULL,0,0,'','','','',1,1,19,null,0,1,0,1,'normal',1604563975,1604561217);
insert  into `hkcms_model_field` values (88,1,'dislikes','点踩数','number','其他',11,'0',NULL,0,0,'','','','',1,1,20,null,0,1,0,0,'normal',1604563975,1604561217);
insert  into `hkcms_model_field` values (89,1,'comments','评论数','number','其他',11,'0',NULL,0,0,'','','','',1,1,21,null,0,0,0,1,'normal',1604563975,1604561217);
insert  into `hkcms_model_field` values (90,1,'collection','收藏数','number','其他',11,'0',NULL,0,0,'','','','',1,1,22,null,0,0,0,1,'normal',1604563975,1604561217);
insert  into `hkcms_model_field` values (91,1,'tags','TAG标签','text','常规',250,'',NULL,0,0,'','','','class=\"form-control selectpage\",data-show-field=\"title\",data-search-field=\"title\",data-key-field=\"title\",data-data=\"/tags/tags\",data-pagination=\"false\",data-multiple=\"true\"',1,1,6,NULL,0,1,0,0,'normal',1667096966,1667096966);

insert  into `hkcms_model_field` values (113,2,'title','标题','text','常规',200,'',NULL,0,0,'required','','','',1,1,1,null,1,1,0,0,'normal',1604564165,1600400002);
insert  into `hkcms_model_field` values (114,2,'keywords','关键词','text','SEO',200,'',NULL,0,0,'','多关键词之间用英文逗号隔开','','',1,1,15,null,0,1,0,0,'normal',1604564378,1600400833);
insert  into `hkcms_model_field` values (115,2,'show_tpl','内容模板','text','其他',100,'show.html',NULL,0,0,'','','','class=\"form-control selectpage\",data-select-only=\"true\",data-data=\"/cms.model/getTplName.html?type=page\",data-pagination=\"false\",data-key-field=\"name\"',1,1,24,null,0,1,0,0,'normal',1604561449,1600400927);
insert  into `hkcms_model_field` values (116,2,'content','内容','editor','常规',0,'',NULL,0,0,'','','','',0,1,7,null,1,1,0,0,'normal',1604565377,1600410507);
insert  into `hkcms_model_field` values (117,2,'thumb','封面图','image','常规',100,'',NULL,0,0,'','','','',1,1,3,null,1,1,0,0,'normal',1604564175,1600400154);
insert  into `hkcms_model_field` values (118,2,'description','描述','textarea','SEO',250,'',NULL,0,0,'','','','',1,1,16,null,0,1,0,0,'normal',1603937358,1600400870);
insert  into `hkcms_model_field` values (119,2,'subtitle','副标题','text','常规',200,'',NULL,0,0,'','','','',1,1,2,null,0,1,0,0,'normal',1604564165,1600400002);
insert  into `hkcms_model_field` values (120,2,'seotitle','seo标题','text','SEO',200,'',NULL,0,0,'','seo标题，为空则使用文章标题','','',1,1,14,null,0,1,0,0,'normal',1604564378,1600400833);
insert  into `hkcms_model_field` values (121,2,'status','状态','radio','常规',250,'normal','{\"normal\":\"\\u6b63\\u5e38\",\"hidden\":\"\\u7981\\u7528\",\"reject\":\"\\u62d2\\u7edd\",\"audit\":\"\\u5f85\\u5ba1\\u6838\"}',0,0,'','','','',1,1,11,null,0,1,0,0,'normal',1604041706,1604041706);
insert  into `hkcms_model_field` values (122,2,'user_id','投稿人','selectpage','常规',10,0,'{\"type\":\"table\",\"url\":\"\",\"url-show-field\":\"\",\"url-key-field\":\"\",\"url-search-field\":\"\",\"table\":\"user\",\"show-field\":\"username\",\"key-field\":\"id\",\"search-field\":\"username,nickname\",\"and-or\":\"\",\"order-by\":\"\",\"param\":{\"custom\":{\"status\":\"normal\"}},\"pagination\":\"1\"}',0,0,'','需要安装用户中心插件','','',1,1,3,'{\"ext\":\"\",\"filesize\":\"\",\"filter_option\":\"\"}',0,0,0,0,'normal',1649942700,1649924031);
insert  into `hkcms_model_field` values (123,2,'flags','文档属性','selectpage','常规',100,'','{\"type\":\"table\",\"url\":\"\",\"url-show-field\":\"\",\"url-key-field\":\"\",\"url-search-field\":\"\",\"table\":\"flags\",\"show-field\":\"title\",\"key-field\":\"name\",\"search-field\":\"title\",\"and-or\":\"\",\"order-by\":\"weigh desc\",\"param\":{\"custom\":{\"status\":\"normal\"}},\"multiple\":\"1\",\"pagination\":\"1\",\"select-only\":\"1\",\"enable-lang\":\"1\"}',0,0,'','','','',1,1,4,'{\"ext\":\"\",\"filesize\":\"\",\"filter_option\":\"\"}',0,0,0,0,'normal',1649942700,1649924031);
insert  into `hkcms_model_field` values (124,2,'price','价格','number','常规',10,'0',NULL,0,2,'','','','',1,1,4,null,1,0,0,1,'normal',1616492830,1616492650);
insert  into `hkcms_model_field` values (125,2,'diyname','自定义URL名','text','常规',100,'',NULL,0,0,'','用于地址栏访问的名称，支持字母,数字,-,_，若为空则采用文章ID','','',1,1,6,null,0,0,0,0,'normal',1604564165,1600400002);
insert  into `hkcms_model_field` values (126,2,'weigh','排序','number','常规',11,'100',NULL,0,0,'','','','',1,1,8,null,0,0,0,0,'normal',1604564047,1600401198);
insert  into `hkcms_model_field` values (127,2,'iscomment','允许评论','radio','常规',1,'1','{\"1\":\"允许\",\"0\":\"不允许\"}',0,0,'','需要安装评论插件','','',1,1,9,null,0,0,0,0,'normal',1604041706,1604041706);
insert  into `hkcms_model_field` values (128,2,'islogin','访问限制','radio','常规',1,'0','{\"1\":\"需要登录\",\"0\":\"无限制\"}',0,0,'','需要安装用户中心插件','','',1,1,10,null,0,0,0,0,'normal',1604041706,1604041706);
insert  into `hkcms_model_field` values (129,2,'author','作者','text','其他',250,'小编',NULL,0,0,'','','','',1,1,17,null,0,1,0,0,'normal',1604561438,1604557612);
insert  into `hkcms_model_field` values (130,2,'jumplink','跳转链接','text','其他',255,'',NULL,0,0,'','设置文档跳转到外部链接','','',1,1,18,null,0,1,0,0,'normal',1604564165,1600400002);
insert  into `hkcms_model_field` values (131,2,'views','浏览量','number','其他',11,'0',NULL,0,0,'','','','',1,1,18,null,0,1,0,1,'normal',1604563975,1604561217);
insert  into `hkcms_model_field` values (132,2,'likes','点赞数','number','其他',11,'0',NULL,0,0,'','','','',1,1,19,null,0,1,0,1,'normal',1604563975,1604561217);
insert  into `hkcms_model_field` values (133,2,'dislikes','点踩数','number','其他',11,'0',NULL,0,0,'','','','',1,1,20,null,0,0,0,0,'normal',1604563975,1604561217);
insert  into `hkcms_model_field` values (134,2,'comments','评论数','number','其他',11,'0',NULL,0,0,'','','','',1,1,21,null,0,0,0,1,'normal',1604563975,1604561217);
insert  into `hkcms_model_field` values (135,2,'collection','收藏数','number','其他',11,'0',NULL,0,0,'','','','',1,1,22,null,0,0,0,1,'normal',1604563975,1604561217);
insert  into `hkcms_model_field` values (136,2,'publish_time','发布时间','datetime','其他',0,'',NULL,0,0,'','','','',1,1,23,null,0,1,0,1,'normal',1604563079,1600401003);


insert  into `hkcms_model_field` values (139,5,'name','姓名','text','常规',100,'',NULL,0,0,'required','','','',1,0,2,null,0,1,0,0,'normal',1617871125,1617871125);
insert  into `hkcms_model_field` values (140,5,'email','邮箱','text','常规',250,'',NULL,0,0,'required,email','','','',1,0,3,null,0,1,0,0,'normal',1617871193,1617871193);
insert  into `hkcms_model_field` values (141,5,'phone','手机','text','常规',20,'',NULL,0,0,'required,mobile','','','',1,0,4,null,0,1,0,0,'normal',1617871239,1617871239);
insert  into `hkcms_model_field` values (142,5,'content','内容','textarea','常规',250,'',NULL,0,0,'required','','','',1,0,5,null,0,1,0,0,'normal',1617871288,1617871288);


DROP TABLE IF EXISTS `hkcms_model_field_bind`;
CREATE TABLE `hkcms_model_field_bind` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `category_id` int(11) NOT NULL DEFAULT '0' COMMENT '栏目ID',
  `model_field_id` int(11) NOT NULL COMMENT '字段ID',
  `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COMMENT='模型字段绑定栏目表';


DROP TABLE IF EXISTS `hkcms_archives`;
CREATE TABLE `hkcms_archives` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `category_id` int(11) NOT NULL COMMENT '栏目ID',
  `category_ids` varchar(100) NOT NULL DEFAULT '' COMMENT '副栏目合集',
  `model_id` int(11) NOT NULL DEFAULT '0' COMMENT '模型ID',
  `admin_id` int(11) NOT NULL DEFAULT '0' COMMENT '管理员ID',
  `user_id` int(11) NOT NULL DEFAULT '0' COMMENT '用户ID',
  `title` varchar(200) NOT NULL DEFAULT '' COMMENT '标题',
  `subtitle` varchar(200) NOT NULL DEFAULT '' COMMENT '副标题',
  `thumb` varchar(100) NOT NULL DEFAULT '' COMMENT '封面图',
  `seotitle` varchar(200) NOT NULL DEFAULT '' COMMENT 'SEO标题',
  `keywords` varchar(200) NOT NULL DEFAULT '' COMMENT '关键字',
  `description` varchar(250) NOT NULL DEFAULT '' COMMENT '描述',
  `tags` varchar(255) NOT NULL DEFAULT '' COMMENT '标签',
  `style` varchar(100) NOT NULL DEFAULT '' COMMENT '样式',
  `weigh` int(11) NOT NULL DEFAULT '100' COMMENT '排序',
  `author` char(250) NOT NULL DEFAULT '小编' COMMENT '作者',
  `url` char(250) NOT NULL DEFAULT '' COMMENT 'URL',
  `diyname` varchar(100) NOT NULL DEFAULT '' COMMENT '自定义URL名',
  `views` int(11) NOT NULL DEFAULT '0' COMMENT '浏览量',
  `flags` varchar(100) NOT NULL DEFAULT '' COMMENT '文档属性',
  `price` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '价格',
  `jumplink` varchar(255) NOT NULL DEFAULT '' COMMENT '跳转链接',
  `comments` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '评论数',
  `iscomment` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '允许评论:1=允许,0=不允许',
  `collection` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '收藏数',
  `likes` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '点赞数',
  `dislikes` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '点踩数',
  `islogin` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '访问限制:1=需要登录,0=无限制',
  `show_tpl` char(100) NOT NULL DEFAULT 'show.html' COMMENT '内容模板',
  `status` enum('normal','hidden','reject','audit') NOT NULL DEFAULT 'normal' COMMENT '状态',
  `lang` varchar(20) NOT NULL DEFAULT '' COMMENT '语言标识',
  `publish_time` int(11) DEFAULT NULL COMMENT '发布时间',
  `delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
  `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS `hkcms_archives_base`;
CREATE TABLE `hkcms_archives_base` (
  `id` int(10) unsigned NOT NULL,
  `content` text COMMENT '内容',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `hkcms_archives_single`;
CREATE TABLE `hkcms_archives_single` (
  `id` int(10) unsigned NOT NULL,
  `content` text COMMENT '内容',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS `hkcms_guestbook`;
CREATE TABLE `hkcms_guestbook` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `category_id` int(11) NOT NULL COMMENT '栏目ID',
  `model_id` int(11) NOT NULL COMMENT '模型ID',
  `name` char(100) NOT NULL DEFAULT '' COMMENT '姓名',
  `email` char(250) NOT NULL DEFAULT '' COMMENT '邮箱',
  `phone` char(20) NOT NULL DEFAULT '' COMMENT '手机',
  `content` varchar(250) NOT NULL DEFAULT '' COMMENT '内容',
  `is_read` tinyint(1) NOT NULL DEFAULT '0' COMMENT '查阅:1=已阅读,0=未读',
  `lang` char(20) NOT NULL DEFAULT '' COMMENT '语言标识',
  `ip` varchar(255) NOT NULL DEFAULT '' COMMENT 'IP',
  `show_tpl` varchar(50) NOT NULL DEFAULT 'page_guestbook.html' COMMENT '模板',
  `read_time` int(11) DEFAULT NULL COMMENT '阅读时间',
  `status` enum('normal','hidden','reject','audit') NOT NULL DEFAULT 'normal' COMMENT '状态',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='留言表单';


DROP TABLE IF EXISTS `hkcms_recommend`;
CREATE TABLE `hkcms_recommend` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '推荐位id',
  `admin_id` int(11) NOT NULL COMMENT '管理员ID',
  `type` char(10) CHARACTER SET utf8 NOT NULL DEFAULT '' COMMENT '类型:1-图集,2-多媒体,3-HTML内容,4-内容数据,5-文字链接',
  `value_id` text CHARACTER SET utf8 COMMENT '内容id',
  `name` char(20) CHARACTER SET utf8 NOT NULL COMMENT '标识',
  `remark` char(100) CHARACTER SET utf8 NOT NULL DEFAULT '' COMMENT '备注',
  `weigh` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '排序',
  `lang` char(20) NOT NULL DEFAULT '' COMMENT '语言标识',
  `status` enum('normal','hidden') CHARACTER SET utf8 NOT NULL DEFAULT 'normal' COMMENT '状态:normal-正常,hidden-禁用',
  `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `uq_name_lang` (`name`,`lang`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='推荐位';


DROP TABLE IF EXISTS `hkcms_banner`;
CREATE TABLE `hkcms_banner` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `recommend_id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL COMMENT '管理员ID',
  `type` tinyint(2) NOT NULL DEFAULT '1' COMMENT '类型:1-图集,2-多媒体,3-html代码',
  `title` varchar(100) NOT NULL DEFAULT '' COMMENT '标题',
  `image` varchar(255) NOT NULL DEFAULT '' COMMENT '图片路径',
  `url` varchar(255) NOT NULL DEFAULT '' COMMENT '链接',
  `notes` text COMMENT '描述',
  `content` text COMMENT '内容',
  `weigh` int(11) NOT NULL DEFAULT '0' COMMENT '排序',
  `new_window` tinyint(2) NOT NULL DEFAULT '0' COMMENT '新窗口:1-启用,0-禁用',
  `lang` char(20) NOT NULL DEFAULT '' COMMENT '语言标识',
  `status` enum('normal','hidden') NOT NULL DEFAULT 'normal' COMMENT '状态:normal-正常,hidden-禁用',
  `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='轮播图/广告';


DROP TABLE IF EXISTS `hkcms_lang_bind`;
CREATE TABLE `hkcms_lang_bind` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `main_id` int(11) NOT NULL DEFAULT '0' COMMENT '主来源ID',
  `value_id` int(11) NOT NULL DEFAULT '0' COMMENT '绑定ID',
  `table` char(50) NOT NULL DEFAULT '' COMMENT '表',
  `lang` char(50) NOT NULL DEFAULT 'zh-cn' COMMENT '语言标识',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='内容语言绑定';


DROP TABLE IF EXISTS `hkcms_flags`;
CREATE TABLE `hkcms_flags` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `title` char(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '名称',
  `name` char(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '属性值',
  `weigh` int(11) NOT NULL DEFAULT '1' COMMENT '排序',
  `lang` char(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '语言',
  `status` enum('normal','hidden') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'normal' COMMENT '状态',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


insert  into `hkcms_flags` values (1,'置顶','top',1,'zh-cn','normal',1650166154,1650166154);


DROP TABLE IF EXISTS `hkcms_tags`;
CREATE TABLE `hkcms_tags` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `title` VARCHAR(50) NOT NULL COMMENT '标题',
  `img` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '标签封面',
  `seo_title` VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'SEO标题',
  `seo_keywords` VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'SEO关键字',
  `seo_description` VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'SEO描述',
  `total` INT(11) NOT NULL DEFAULT '0' COMMENT '文档数量',
  `views` INT(11) NOT NULL DEFAULT '0' COMMENT '点击量',
  `weigh` INT(11) NOT NULL DEFAULT '100' COMMENT '排序',
  `autolink` TINYINT(2) NOT NULL DEFAULT '1' COMMENT '自动内链:1=自动内链',
  `lang` VARCHAR(20) NOT NULL DEFAULT '' COMMENT '语言标识',
  `create_time` INT(11) DEFAULT NULL COMMENT '创建时间',
  `update_time` INT(11) DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`)
) ENGINE=INNODB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 COMMENT='标签';

DROP TABLE IF EXISTS `hkcms_tags_list`;
CREATE TABLE `hkcms_tags_list` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `tags_id` INT(10) UNSIGNED NOT NULL COMMENT '标签ID',
  `model_id` INT(10) UNSIGNED NOT NULL COMMENT '模型ID',
  `category_id` INT(11) NOT NULL COMMENT '栏目ID',
  `content_id` INT(11) NOT NULL COMMENT '内容ID',
  `content_title` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '内容标题',
  `lang` VARCHAR(20) NOT NULL DEFAULT '' COMMENT '语言标识',
  `create_time` INT(11) DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`)
) ENGINE=INNODB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `hkcms_user`;
CREATE TABLE `hkcms_user` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `username` char(32) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '登录名称',
  `nickname` char(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '昵称',
  `email` char(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '邮箱',
  `mobile` char(11) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '手机',
  `password` char(32) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '密码',
  `salt` char(10) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '密码盐',
  `money` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '金额',
  `score` int(11) NOT NULL DEFAULT '0' COMMENT '积分',
  `level` tinyint(4) NOT NULL DEFAULT '0' COMMENT '等级',
  `exp` int(11) NOT NULL DEFAULT '0' COMMENT '经验值',
  `avatar` char(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '头像',
  `gender` tinyint(1) NOT NULL DEFAULT '0' COMMENT '性别:1-男,2-女,0-未指定',
  `birthday` date DEFAULT NULL COMMENT '生日',
  `introduction` char(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '个人简介',
  `remark` char(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '备注',
  `latest_time` int(11) DEFAULT NULL COMMENT '上次登录时间',
  `login_time` int(11) DEFAULT NULL COMMENT '登录时间',
  `login_ip` char(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '登录IP',
  `login_failed` tinyint(4) NOT NULL DEFAULT '0' COMMENT '登录失败次数',
  `status` enum('normal','hidden') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'normal' COMMENT '状态:normal-正常,hidden-禁用',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniaue_username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户表';

DROP TABLE IF EXISTS `hkcms_user_group`;
CREATE TABLE `hkcms_user_group` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `parent_id` mediumint(8) NOT NULL DEFAULT '0' COMMENT '父级',
  `name` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '名称',
  `rules` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '规则',
  `remark` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '备注',
  `status` enum('normal','hidden') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'normal' COMMENT '状态:normal-正常,hidden-禁用',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='角色管理';

DROP TABLE IF EXISTS `hkcms_user_group_access`;
CREATE TABLE `hkcms_user_group_access` (
  `user_id` int(10) unsigned NOT NULL COMMENT '用户表ID',
  `group_id` mediumint(8) unsigned NOT NULL COMMENT '角色组ID',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
  UNIQUE KEY `uid_group_id` (`user_id`,`group_id`),
  KEY `uid` (`user_id`),
  KEY `group_id` (`group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='权限分组表';

DROP TABLE IF EXISTS `hkcms_user_rule`;
CREATE TABLE `hkcms_user_rule` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `parent_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '父级',
  `name` char(80) CHARACTER SET utf8 NOT NULL DEFAULT '' COMMENT '规则',
  `title` char(50) CHARACTER SET utf8 NOT NULL DEFAULT '' COMMENT '标题',
  `route` char(80) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '路由',
  `app` char(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '所属应用',
  `icon` char(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '图标',
  `remark` char(250) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '备注',
  `weigh` int(11) NOT NULL DEFAULT '0' COMMENT '排序',
  `type` tinyint(2) NOT NULL DEFAULT '1' COMMENT '类型:0-权限规则,1-菜单,2-额外标识',
  `status` enum('normal','hidden') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'normal' COMMENT '状态:normal-正常,hidden-禁用',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='菜单规则';

DROP TABLE IF EXISTS `hkcms_user_token`;
CREATE TABLE `hkcms_user_token` (
  `token` char(32) NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `expire_time` int(11) DEFAULT NULL,
  `create_time` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `hkcms_lang`;
CREATE TABLE `hkcms_lang` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `mark` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '标志',
  `title` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '标题',
  `subtitle` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '别名',
  `image` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '图片',
  `icon` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'ICON',
  `target` tinyint(1) NOT NULL DEFAULT '0' COMMENT '弹出方式:1=新窗口',
  `weigh` int(11) NOT NULL DEFAULT '100' COMMENT '排序',
  `is_default` tinyint(1) NOT NULL DEFAULT '0' COMMENT '默认语言',
  `module` tinyint(1) NOT NULL COMMENT '所属模块:1=前台界面,2=后台界面,3=内容语言',
  `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '状态:0=禁用,1=启用',
  `create_time` bigint(20) DEFAULT NULL COMMENT '创建时间',
  `update_time` bigint(20) DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `mark_module_unique` (`mark`,`module`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='多语言列表';


DROP TABLE IF EXISTS `hkcms_admin_field`;
CREATE TABLE `hkcms_admin_field` (
 `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
 `admin_id` int(10) unsigned NOT NULL COMMENT '管理员ID',
 `table_name` varchar(30) NOT NULL COMMENT '不含前缀的表名',
 `catid` int(11) DEFAULT '0' COMMENT '用于内容管理',
 `model_id` int(11) DEFAULT '0' COMMENT '用于内容管理',
 `column_data` text COMMENT '字段信息',
 `update_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '更新时间',
 `create_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
 PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8 COMMENT='保存管理员展示的表格显示字段';

insert  into `hkcms_admin_field`(`id`,`admin_id`,`table_name`,`catid`,`model_id`,`column_data`,`update_time`,`create_time`) values (6,1,'archives',0,1,'[{\"field\":\"weigh\",\"checked\":\"true\"},{\"field\":\"id\",\"checked\":\"true\"},{\"field\":\"category_title\",\"checked\":\"true\"},{\"field\":\"username\",\"checked\":\"true\"},{\"field\":\"title\",\"checked\":\"true\"},{\"field\":\"subtitle\",\"checked\":\"true\"},{\"field\":\"thumb\",\"checked\":\"true\"},{\"field\":\"diyname\",\"checked\":\"true\"},{\"field\":\"tags\",\"checked\":\"true\"},{\"field\":\"content\",\"checked\":\"true\"},{\"field\":\"iscomment\",\"checked\":\"true\"},{\"field\":\"islogin\",\"checked\":\"true\"},{\"field\":\"status\",\"checked\":\"true\"},{\"field\":\"author\",\"checked\":\"true\"},{\"field\":\"views\",\"checked\":\"true\"},{\"field\":\"jumplink\",\"checked\":\"true\"},{\"field\":\"likes\",\"checked\":\"true\"},{\"field\":\"dislikes\",\"checked\":\"true\"},{\"field\":\"publish_time\",\"checked\":\"true\"},{\"field\":\"show_tpl\",\"checked\":\"true\"},{\"field\":\"operate\",\"checked\":\"true\"},{\"field\":\"category_ids\",\"checked\":\"false\"},{\"field\":\"seotitle\",\"checked\":\"false\"},{\"field\":\"keywords\",\"checked\":\"false\"},{\"field\":\"description\",\"checked\":\"false\"}]','2023-10-22 18:05:05','2023-10-22 18:05:05');
insert  into `hkcms_admin_field`(`id`,`admin_id`,`table_name`,`catid`,`model_id`,`column_data`,`update_time`,`create_time`) values (7,1,'archives',0,6,'[{\"field\":\"weigh\",\"checked\":\"true\"},{\"field\":\"id\",\"checked\":\"true\"},{\"field\":\"category_title\",\"checked\":\"true\"},{\"field\":\"category_ids\",\"checked\":\"true\"},{\"field\":\"username\",\"checked\":\"true\"},{\"field\":\"title\",\"checked\":\"true\"},{\"field\":\"subtitle\",\"checked\":\"true\"},{\"field\":\"thumb\",\"checked\":\"true\"},{\"field\":\"price\",\"checked\":\"true\"},{\"field\":\"diyname\",\"checked\":\"true\"},{\"field\":\"tags\",\"checked\":\"true\"},{\"field\":\"content\",\"checked\":\"true\"},{\"field\":\"iscomment\",\"checked\":\"true\"},{\"field\":\"islogin\",\"checked\":\"true\"},{\"field\":\"status\",\"checked\":\"true\"},{\"field\":\"author\",\"checked\":\"true\"},{\"field\":\"views\",\"checked\":\"true\"},{\"field\":\"jumplink\",\"checked\":\"true\"},{\"field\":\"likes\",\"checked\":\"true\"},{\"field\":\"dislikes\",\"checked\":\"true\"},{\"field\":\"publish_time\",\"checked\":\"true\"},{\"field\":\"show_tpl\",\"checked\":\"true\"},{\"field\":\"operate\",\"checked\":\"true\"},{\"field\":\"images\",\"checked\":\"false\"},{\"field\":\"buylink\",\"checked\":\"false\"},{\"field\":\"attr\",\"checked\":\"false\"},{\"field\":\"color\",\"checked\":\"false\"},{\"field\":\"seotitle\",\"checked\":\"false\"},{\"field\":\"keywords\",\"checked\":\"false\"},{\"field\":\"description\",\"checked\":\"false\"}]','2023-10-22 18:06:08','2023-10-22 18:06:08');
insert  into `hkcms_admin_field`(`id`,`admin_id`,`table_name`,`catid`,`model_id`,`column_data`,`update_time`,`create_time`) values (8,1,'archives',0,7,'[{\"field\":\"weigh\",\"checked\":\"true\"},{\"field\":\"id\",\"checked\":\"true\"},{\"field\":\"category_title\",\"checked\":\"true\"},{\"field\":\"category_ids\",\"checked\":\"true\"},{\"field\":\"username\",\"checked\":\"true\"},{\"field\":\"title\",\"checked\":\"true\"},{\"field\":\"subtitle\",\"checked\":\"true\"},{\"field\":\"thumb\",\"checked\":\"true\"},{\"field\":\"diyname\",\"checked\":\"true\"},{\"field\":\"tags\",\"checked\":\"true\"},{\"field\":\"content\",\"checked\":\"true\"},{\"field\":\"iscomment\",\"checked\":\"true\"},{\"field\":\"islogin\",\"checked\":\"true\"},{\"field\":\"status\",\"checked\":\"true\"},{\"field\":\"views\",\"checked\":\"true\"},{\"field\":\"jumplink\",\"checked\":\"true\"},{\"field\":\"likes\",\"checked\":\"true\"},{\"field\":\"dislikes\",\"checked\":\"true\"},{\"field\":\"publish_time\",\"checked\":\"true\"},{\"field\":\"show_tpl\",\"checked\":\"true\"},{\"field\":\"operate\",\"checked\":\"true\"},{\"field\":\"images\",\"checked\":\"false\"},{\"field\":\"seotitle\",\"checked\":\"false\"},{\"field\":\"keywords\",\"checked\":\"false\"},{\"field\":\"description\",\"checked\":\"false\"}]','2023-10-22 18:06:52','2023-10-22 18:06:52');


insert  into `hkcms_lang`(`id`,`mark`,`title`,`subtitle`,`image`,`icon`,`target`,`weigh`,`is_default`,`module`,`status`,`create_time`,`update_time`) values (1,'zh-cn','中文简体','','','',0,100,1,1,1,1682153018,1682153018);
insert  into `hkcms_lang`(`id`,`mark`,`title`,`subtitle`,`image`,`icon`,`target`,`weigh`,`is_default`,`module`,`status`,`create_time`,`update_time`) values (2,'en','English','','','',0,100,0,1,1,1682153018,1682153018);
insert  into `hkcms_lang`(`id`,`mark`,`title`,`subtitle`,`image`,`icon`,`target`,`weigh`,`is_default`,`module`,`status`,`create_time`,`update_time`) values (3,'zh-cn','中文简体','','','',0,100,1,2,1,1682153018,1682153018);
insert  into `hkcms_lang`(`id`,`mark`,`title`,`subtitle`,`image`,`icon`,`target`,`weigh`,`is_default`,`module`,`status`,`create_time`,`update_time`) values (4,'en','English','','','',0,100,0,2,1,1682153018,1682153018);
insert  into `hkcms_lang`(`id`,`mark`,`title`,`subtitle`,`image`,`icon`,`target`,`weigh`,`is_default`,`module`,`status`,`create_time`,`update_time`) values (5,'zh-cn','中文简体','','','',0,100,1,3,1,1682153018,1682153018);
insert  into `hkcms_lang`(`id`,`mark`,`title`,`subtitle`,`image`,`icon`,`target`,`weigh`,`is_default`,`module`,`status`,`create_time`,`update_time`) values (6,'en','English','','','',0,100,0,3,1,1682153018,1682153018);
