<?php

//decode by http://www.yunlu99.com/
defined('IN_IA') or exit('Access Denied');
class Cyl_vip_videoModuleReceiver extends WeModuleReceiver
{
    public function receive()
    {
        $type = $this->message['type'];
    }
}