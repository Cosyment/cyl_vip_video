<?php

$sql = <<<EOF
DROP TABLE IF EXISTS `ims_cyl_vip_video`;
DROP TABLE IF EXISTS `ims_cyl_vip_video_category`;
DROP TABLE IF EXISTS `ims_cyl_vip_video_hdp`;
DROP TABLE IF EXISTS `ims_cyl_vip_video_keyword`;
DROP TABLE IF EXISTS `ims_cyl_vip_video_keyword_id`;
DROP TABLE IF EXISTS `ims_cyl_vip_video_manage`;
DROP TABLE IF EXISTS `ims_cyl_vip_video_member`;
DROP TABLE IF EXISTS `ims_cyl_vip_video_order`;
DROP TABLE IF EXISTS `ims_cyl_vip_video_share`;
EOF
;
pdo_run($sql);