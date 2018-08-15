<?php
if (!pdo_fieldexists('cyl_vip_video', 'yvideo_url'))
{
    pdo_query("ALTER TABLE " . tablename('cyl_vip_video') . " ADD `yvideo_url` text NOT NULL");
}
if (!pdo_fieldexists('cyl_vip_video', 'type'))
{
    pdo_query("ALTER TABLE " . tablename('cyl_vip_video') . " ADD `type` VARCHAR( 25 ) NOT NULL");
}
if (!pdo_fieldexists('cyl_vip_video', 'index'))
{
    pdo_query("ALTER TABLE " . tablename('cyl_vip_video') . " ADD `index` int( 2 ) NOT NULL");
}
if (!pdo_fieldexists('cyl_vip_video', 'video_id'))
{
    pdo_query("ALTER TABLE " . tablename('cyl_vip_video') . " ADD `video_id` int( 11 ) NOT NULL");
}
if (!pdo_fieldexists('cyl_vip_video_hdp', 'out_link'))
{
    pdo_query("ALTER TABLE " . tablename('cyl_vip_video_hdp') . " ADD `out_link` VARCHAR( 1000 ) NOT NULL");
}
if (!pdo_fieldexists('cyl_vip_video_manage', 'cid'))
{
    pdo_query("ALTER TABLE " . tablename('cyl_vip_video_manage') . " ADD `cid` int( 3 ) NOT NULL");
}
if (!pdo_fieldexists('cyl_vip_video_manage', 'pid'))
{
    pdo_query("ALTER TABLE " . tablename('cyl_vip_video_manage') . " ADD `pid` int( 3 ) NOT NULL");
}
if (!pdo_fieldexists('cyl_vip_video_category', 'parentid'))
{
    pdo_query("ALTER TABLE " . tablename('cyl_vip_video_category') . " ADD `parentid` int( 10 ) NOT NULL");
}
if (!pdo_fieldexists('cyl_vip_video_category', 'name'))
{
    pdo_query("ALTER TABLE " . tablename('cyl_vip_video_category') . " ADD `name` VARCHAR( 20 ) NOT NULL");
}
if (!pdo_fieldexists('cyl_vip_video_category', 'url'))
{
    pdo_query("ALTER TABLE " . tablename('cyl_vip_video_category') . " ADD `url` VARCHAR( 1000 ) NOT NULL");
}
if (!pdo_fieldexists('cyl_vip_video_manage', 'click'))
{
    pdo_query("ALTER TABLE " . tablename('cyl_vip_video_manage') . " ADD `click` int( 6 ) NOT NULL");
}
if (!pdo_fieldexists('cyl_vip_video_manage', 'display'))
{
    pdo_query("ALTER TABLE " . tablename('cyl_vip_video_manage') . " ADD `display` int( 2 ) NOT NULL ");
}
if (!pdo_fieldexists('cyl_vip_video_manage', 'sort'))
{
    pdo_query("ALTER TABLE " . tablename('cyl_vip_video_manage') . " ADD `sort` int( 5 ) NOT NULL");
}
if (!pdo_fieldexists('cyl_vip_video_manage', 'out_link'))
{
    pdo_query("ALTER TABLE " . tablename('cyl_vip_video_manage') . " ADD `out_link` VARCHAR( 1000 ) NOT NULL");
}
if (!pdo_fieldexists('cyl_vip_video_manage', 'keyword'))
{
    pdo_query("ALTER TABLE " . tablename('cyl_vip_video_manage') . " ADD `keyword` VARCHAR( 25 ) NOT NULL ");
}
if (!pdo_fieldexists('cyl_vip_video_manage', 'password'))
{
    pdo_query("ALTER TABLE " . tablename('cyl_vip_video_manage') . " ADD `password` VARCHAR( 25 ) NOT NULL ");
}
if (!pdo_fieldexists('cyl_vip_video_manage', 'rid'))
{
    pdo_query("ALTER TABLE " . tablename('cyl_vip_video_manage') . " ADD `rid` int( 10 ) NOT NULL ");
}
if (!pdo_fieldexists('cyl_vip_video_member', 'old_time'))
{
    pdo_query("ALTER TABLE " . tablename('cyl_vip_video_member') . " ADD `old_time` VARCHAR( 15 ) NOT NULL");
}
if (!pdo_fieldexists('cyl_vip_video_order', 'desc'))
{
    pdo_query("ALTER TABLE " . tablename('cyl_vip_video_order') . " ADD `desc` VARCHAR( 25 ) NOT NULL");
}
$sql = "
CREATE TABLE IF NOT EXISTS `ims_cyl_vip_video_hdp` (
 `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
 `uniacid` int(10) NOT NULL,  
 `title` varchar(255) NOT NULL,
 `thumb` varchar(1000) NOT NULL,
 `type` varchar(15) NOT NULL,
 `link` varchar(1000) NOT NULL,
 `sort` int(5) NOT NULL,  
 PRIMARY KEY (`id`),
 UNIQUE KEY `id` (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `ims_cyl_vip_video_manage` (
 `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
 `uniacid` int(11) NOT NULL,
 `title` varchar(25) NOT NULL,
 `thumb` varchar(1000) NOT NULL,
 `year` varchar(25) NOT NULL,
 `star` varchar(25) NOT NULL,
 `type` varchar(25) NOT NULL,
 `actor` varchar(25) NOT NULL,
 `video_url` text NOT NULL,
 `desc` text NOT NULL,
 `time` varchar(25) NOT NULL,
 `screen` varchar(25) NOT NULL,
 `cid` int(3) NOT NULL,
 `pid` int(3) NOT NULL,
 `click` int(5) NOT NULL,
 `display` int(2) NOT NULL,
 `sort` int(5) NOT NULL,
 `out_link` varchar(1000) NOT NULL,
 `keyword` varchar(25) NOT NULL,
 `password` varchar(25) NOT NULL,
 `rid` int(10) NOT NULL,
 PRIMARY KEY (`id`),
 UNIQUE KEY `id` (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `ims_cyl_vip_video_category` (
 `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
 `parentid` int(10) NOT NULL,
 `uniacid` int(10) unsigned NOT NULL DEFAULT '0',
 `name` varchar(20) NOT NULL,
 `status` tinyint(3) unsigned NOT NULL DEFAULT '1',
 `displayorder` tinyint(3) unsigned NOT NULL DEFAULT '0',
 `url` varchar(1000) NOT NULL,
 PRIMARY KEY (`id`),
 KEY `uniacid` (`uniacid`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `ims_cyl_vip_video_keyword` (
 `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
 `uniacid` int(11) NOT NULL,
 `title` varchar(25) NOT NULL,
 `card_id` varchar(25) NOT NULL,
 `num` int(11) NOT NULL,
 `day` int(11) NOT NULL,
 PRIMARY KEY (`id`),
 UNIQUE KEY `id` (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `ims_cyl_vip_video_keyword_id` (
 `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
 `openid` varchar(1000) NOT NULL,
 `uniacid` int(11) NOT NULL,
 `pwd` varchar(25) NOT NULL,
 `card_id` varchar(25) NOT NULL,
 `day` int(11) NOT NULL,
 `status` int(2) NOT NULL,
 PRIMARY KEY (`id`),
 UNIQUE KEY `id` (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
DROP TABLE IF EXISTS `ims_cyl_vip_video_card`;
DROP TABLE IF EXISTS `ims_cyl_vip_video_card_id`;

CREATE TABLE IF NOT EXISTS `ims_cyl_vip_video_share` (
 `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
 `openid` varchar(1000) NOT NULL,
 `uniacid` int(11) NOT NULL,  
 `uid` int(11) NOT NULL,  
 `time` varchar(25) NOT NULL,
 PRIMARY KEY (`id`),
 UNIQUE KEY `id` (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `ims_cyl_vip_video_message` (
 `id` int(20) unsigned NOT NULL AUTO_INCREMENT,
 `video_id` varchar(50) NOT NULL,
 `uniacid` int(20) NOT NULL,
 `openid` varchar(255) NOT NULL,
 `old_id` varchar(255) NOT NULL,  
 `content` text NOT NULL,
 `huifu` text NOT NULL,
 `time` varchar(255) NOT NULL,
 `status` int(2) NOT NULL DEFAULT '1',  
 PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `ims_cyl_vip_video_ad` (
 `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
 `uniacid` int(10) NOT NULL,
 `thumb` varchar(1000) NOT NULL,
 `link` varchar(1000) NOT NULL,
 `end_time` varchar(15) NOT NULL,
 `sort` int(5) NOT NULL,  
 `second` int(3) NOT NULL,  
 `status` int(2) NOT NULL,  
 PRIMARY KEY (`id`),
 UNIQUE KEY `id` (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;";
pdo_run($sql);

?>