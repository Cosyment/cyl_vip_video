<?php
defined('IN_IA') or exit('Access Denied');
include IA_ROOT . "/addons/cyl_vip_video/model.php";
class Cyl_vip_videoModuleSite extends WeModuleSite
{
    public function __construct()
    {
        global $_W, $_GPC;
        load()->model('mc');
        if (empty($_W['fans']['nickname']))
        {
            $fans = mc_oauth_userinfo();
        }
        $uni_settings = pdo_get('uni_settings', array('uniacid' => $_W['uniacid']));
        $mc_oauth_fans = pdo_getall('mc_oauth_fans', array('oauth_openid' => $fans['openid']));
        $member = member($_W['openid']);
        $data = array('uniacid' => $_W['uniacid'],
            'openid' => $_W['openid'],
            'nickname' => $_W['fans']['tag']['nickname'],
            'avatar' => $_W['fans']['tag']['avatar'],
            'time' => TIMESTAMP,
            'old_time' => TIMESTAMP
            );
        if ($_W['account']['level'] == 4)
        {
            $data['uid'] = $_W['fans']['uid'];
        }
        else
        {
            $data['uid'] = $_W['member']['uid'];
        }
        if (empty($data['avatar']))
        {
            $data['avatar'] = $fans['headimgurl'];
        }
        if (empty($data['nickname']))
        {
            $data['nickname'] = $fans['nickname'];
        }
        if ($data['avatar'] && $data['nickname'])
        {
            if ($member)
            {
                unset($data['time']);
                pdo_update('cyl_vip_video_member', $data, array('id' => $member['id']));
            }
            else
            {
                pdo_insert('cyl_vip_video_member', $data);
            }
        }
    }
    public function doMobileDiscover()
    {
        global $_W, $_GPC;
        $settings = $this->module['config'];
        $acc = WeAccount::create();
        $member = member($_W['openid']);
        $jilu = pdo_getall('cyl_vip_video', array('uniacid' => $_W['uniacid'], 'openid' => $_W['openid'],), array() , '', 'id DESC limit 10');
        $num = pdo_fetchcolumn("SELECT COUNT(*) FROM " . tablename('cyl_vip_video') . " WHERE uniacid = :uniacid AND openid = :openid ", array(':uniacid' => $_W['uniacid'],
                ':openid' => $member['openid']
                ));
        $account_api = WeAccount::create();
        $info = $account_api->fansQueryInfo($_W['openid']);
        $category = pdo_fetchall("SELECT * FROM " . tablename('cyl_vip_video_category') . " WHERE uniacid = '{$_W['uniacid']}' AND parentid = 0 ORDER BY parentid ASC, displayorder ASC, id ASC ", array(), 'id');
        $hdp = pdo_getall('cyl_vip_video_hdp', array('uniacid' => $_W['uniacid']), array() , '', 'sort DESC , id DESC');
        $record = pdo_fetch("SELECT * FROM " . tablename('cyl_vip_video') . " WHERE uniacid = :uniacid AND openid = :openid ORDER BY id DESC", array(':uniacid' => $_W['uniacid'], ':openid' => $member['openid']));
        if (TIMESTAMP > $member['end_time'] && $member['is_pay'] == 1)
        {
            pdo_update('cyl_vip_video_member', array('end_time' => null, 'is_pay' => 0), array('openid' => $member['openid']));
            $data = array('first' => array('value' => '您好,' . $member['nickname'] . '您的会员已到期',
                    'color' => '#ff510'
                    ) ,
                'keyword1' => array('value' => '会员到期',
                    'color' => '#ff510'
                    ) ,
                'keyword2' => array('value' => '到期提醒',
                    'color' => '#ff510'
                    ) ,
                'remark' => array('value' => '点击详情开通',
                    'color' => '#ff510'
                    ) ,
                );
            $url = $_W['siteroot'] . 'app' . ltrim(murl('entry', array('do' => 'member', 'm' => 'cyl_vip_video')) , '.');
            $acc->sendTplNotice($member['openid'], $settings['tpl_id'], $data, $url, $topcolor = '#FF683F');
        }
        if (checksubmit())
        {
            $url = $_GPC['url'];
            $c = explode('m.v.qq', $url);
            if (count($c) > 1)
            {
                $url = 'https://v.qq' . $c['1'];
            }
            if (!isUrl($url)) message('输入的网页地址错误，请重新输入,检查是否含有http://');
            if ($num >= $settings['free_num'] && $member['is_pay'] == 0)
            {
                message('您的免费观看次数已用完，请点击确定开通会员，无限制观看', $this->createMobileUrl('member', array('op' => 'open')), 'error');
            }
            $video = pdo_get('cyl_vip_video', array('uniacid' => $_W['uniacid'], 'openid' => $_W['openid'], 'video_url' => $url));
            if (!$url)
            {
                message('请输入链接');
            }
            if ($video)
            {
                message('这个视频您之前提交过了，点击确定跳转继续观看', $this->createMobileUrl('detail', array('url' => $url, 'index' => 1)), 'success');
            }
            $html = file_get_contents($url);
            $title = str_substr("<title>", "</title>", $html);
            $res = pdo_insert('cyl_vip_video', array('uniacid' => $_W['uniacid'], 'openid' => $_W['openid'], 'uid' => $_W['fans']['uid'], 'title' => $title, 'video_url' => $url, 'time' => TIMESTAMP, 'share' => $_GPC['share'], 'index' => 1));
            $video_url = $this->createMobileUrl('detail', array('url' => $url, 'index' => 1));
            Header("Location: $video_url");
            exit();
        }
        include $this->template('discover');
    }
    public function doMobileIndex()
    {
        global $_W, $_GPC;
        $account_api = WeAccount::create();
        $op = $_GPC['op'] ?$_GPC['op'] : 'index';
        $pid = $_GPC['pid'];
        $settings = $this->module['config'];
        $num = $settings['list'] ?$settings['list'] : 6;
        $member = member($_W['openid']);
        if (TIMESTAMP > $member['end_time'] && $member['is_pay'] == 1)
        {
            pdo_update('cyl_vip_video_member', array('end_time' => null, 'is_pay' => 0), array('openid' => $member['openid']));
            $data = array('first' => array('value' => '您好,' . $member['nickname'] . '您的会员已到期',
                    'color' => '#ff510'
                    ) ,
                'keyword1' => array('value' => '会员到期',
                    'color' => '#ff510'
                    ) ,
                'keyword2' => array('value' => '到期提醒',
                    'color' => '#ff510'
                    ) ,
                'remark' => array('value' => '点击详情开通',
                    'color' => '#ff510'
                    ) ,
                );
            $url = $_W['siteroot'] . 'app' . ltrim(murl('entry', array('do' => 'member', 'm' => 'cyl_vip_video')) , '.');
            $account_api->sendTplNotice($member['openid'], $settings['tpl_id'], $data, $url, $topcolor = '#FF683F');
        }
        $jilu = pdo_getall('cyl_vip_video', array('uniacid' => $_W['uniacid'], 'openid' => $_W['openid'],), array() , '', 'id DESC limit 10');
        $hdp = pdo_getall('cyl_vip_video_hdp', array('uniacid' => $_W['uniacid'], 'type' => $op), array() , '', 'sort DESC , id DESC');
        $category = pdo_fetchall("SELECT * FROM " . tablename('cyl_vip_video_category') . " WHERE uniacid = '{$_W['uniacid']}' AND parentid = 0 ORDER BY parentid ASC, displayorder ASC, id ASC ", array(), 'id');
        $parent = array();
        $children = array();
        if (!empty($category))
        {
            $children = '';
            foreach ($category as $cid => $cate)
            {
                if (!empty($cate['parentid']))
                {
                    $children[$cate['parentid']][] = $cate;
                }
                else
                {
                    $parent[$cate['id']] = $cate;
                }
            }
        }
        if ($op == 'index')
        {
            $time = cache_load('cyl_vip_video:time');
            $data = pdo_getall('cyl_vip_video_manage', array('uniacid' => $_W['uniacid'], 'display !=' => 1), array() , '', 'sort DESC , id DESC');
            if ((TIMESTAMP - $time) > 3600)
            {
                $dianying = index_list($url, $settings['dianying_rank'] ?$settings['dianying_rank'] : 'rankhot', 'dianying');
                $dianshi = index_list($url, 'rankhot', 'dianshi');
                $zongyi = index_list($url, $settings['zongyi_rank'] ?$settings['zongyi_rank'] : 'rankhot', 'zongyi');
                $dongman = index_list($url, $settings['dongman_rank'] ?$settings['dongman_rank'] : 'rankhot', 'dongman');
                cache_write('cyl_vip_video:time', TIMESTAMP);
                cache_write('cyl_vip_video:dianying', $dianying);
                cache_write('cyl_vip_video:dianshi', $dianshi);
                cache_write('cyl_vip_video:zongyi', $zongyi);
                cache_write('cyl_vip_video:dongman', $dongman);
            }
            else
            {
                $dianying = cache_load('cyl_vip_video:dianying');
                $dianshi = cache_load('cyl_vip_video:dianshi');
                $zongyi = cache_load('cyl_vip_video:zongyi');
                $dongman = cache_load('cyl_vip_video:dongman');
            }
            include $this->template('news/index');
        }
        else
        {
            if ($op > 0)
            {
                $url = $category[$op]['url'];
                if ($url)
                {
                    $data = youku($url);
                }
                else
                {
                    $where['uniacid'] = $_W['uniacid'];
                    $where['cid'] = $op;
                    if ($pid > 0)
                    {
                        $where['pid'] = $pid;
                    }
                    $data = pdo_getall('cyl_vip_video_manage', $where, array() , '', 'id DESC');
                }
                $cat = pdo_getall('cyl_vip_video_category', array('uniacid' => $_W['uniacid'], 'parentid' => $op), array() , '', 'id DESC');
            }elseif ($op == 'yule' || $op == 'gaoxiao')
            {
                $data = kan360_list($op);
            }
            else
            {
                $url = $_GPC['url'];
                $num = $_GPC['num'] ?$_GPC['num'] : 0;
                $rank = $_GPC['rank'] ?$_GPC['rank'] : 'rankhot';
                if ($_GPC['cat'] || $_GPC['act'] || $_GPC['year'] || $_GPC['area'] || $rank)
                {
                    $url = "http://www.360kan.com/{$op}/list.php?rank={$rank}&year={$_GPC['year']}&area={$_GPC['area']}&act={$_GPC['act']}&cat={$_GPC['cat']}&pageno={$num}";
                }
                else
                {
                    $url = "http://www.360kan.com/{$op}/list.php?rank={$rank}&cat=all&area=all&act=all&year=all&pageno={$num}";
                }
                $discover_time = cache_load('discover:time' . $op . $rank);
                $data = cache_load('discover:data' . $op . $rank . $_GPC['cat'] . $_GPC['year'] . $_GPC['area']); 
                // print_r($data);
                if (empty($data) || (TIMESTAMP - $discover_time) > 86400)
                {
                    $data = discover($url);
                    cache_write('discover:data' . $op . $rank . $_GPC['cat'] . $_GPC['year'] . $_GPC['area'], $data);
                }
                else
                {
                    $data = cache_load('discover:data' . $op . $rank . $_GPC['cat'] . $_GPC['year'] . $_GPC['area']);
                } 
                // print_r($data);
                if ((TIMESTAMP - $discover_time) > 86400)
                {
                    $category_list = category_list($url);
                    $cat = $category_list['0'];
                    $year = $category_list['1'];
                    $area = $category_list['2'];
                    $star = $category_list['3'];
                    cache_write('discover:time' . $op . $rank, TIMESTAMP);
                    cache_write('discover:cat' . $op . $rank, $cat);
                    cache_write('discover:year' . $op . $rank, $year);
                    cache_write('discover:area' . $op . $rank, $area);
                    cache_write('discover:star' . $op . $rank, $star);
                }
                else
                {
                    $cat = cache_load('discover:cat' . $op . $rank);
                    $year = cache_load('discover:year' . $op . $rank);
                    $area = cache_load('discover:area' . $op . $rank);
                    $star = cache_load('discover:star' . $op . $rank);
                }
            }
            $cid = $_GPC['cid'];
            if ($_GPC['type'] == 'json')
            {
                if ($op > 0)
                {
                    $num = isset($_GPC['page']) ?$_GPC['page'] : 2;
                    $pageindex = 50;
                    if (!empty($_GPC['keyword']))
                    {
                        $condition .= " AND title LIKE '%{$_GPC['keyword']}%'";
                    }
                    if (!empty($_GPC['pcate']))
                    {
                        $pcate = intval($_GPC['pcate']);
                        $condition .= " AND pcate = $pcate";
                    }
                    if (!empty($_GPC['ccate']))
                    {
                        $ccate = $_GPC['ccate'];
                        $condition .= " AND ccate = $ccate";
                    }
                    $total = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('cyl_vip_video_manage') . " WHERE uniacid = {$_W['uniacid']} $condition");
                    $data = pdo_fetchall("SELECT * FROM " . tablename('cyl_vip_video_manage') . " WHERE uniacid = '{$_W['uniacid']}' $condition ORDER BY id DESC LIMIT " . ($num -1) * $pageindex . ',' . $pageindex);
                }elseif ($op == 'yule' || $op == 'gaoxiao')
                {
                    $num = $_GPC['num'] + 1;
                    $cid = $_GPC['cid'];
                    $data = kan360_list($op, $cid, $num);
                }
                else
                {
                    $url = $_GPC['url'];
                    $num = $_GPC['num'];
                    $rank = $_GPC['rank'] ?$_GPC['rank'] : 'rankhot';
                    if ($_GPC['cat'] || $_GPC['act'] || $_GPC['year'] || $_GPC['area'] || $rank)
                    {
                        $url = "http://www.360kan.com/{$op}/list.php?rank={$rank}&year={$_GPC['year']}&area={$_GPC['area']}&act={$_GPC['act']}&cat={$_GPC['cat']}&pageno={$num}";
                    }
                    else
                    {
                        $url = "http://www.360kan.com/{$op}/list.php?rank={$rank}&cat=all&area=all&act=all&year=all&pageno={$num}";
                    }
                    $discover_time = cache_load('discover:time' . $op . $rank . $num);
                    $data = cache_load('discover:data' . $op . $rank . $_GPC['cat'] . $_GPC['year'] . $_GPC['area'] . $num);
                    if (empty($data) || (TIMESTAMP - $discover_time) > 86400)
                    {
                        cache_write('discover:time' . $op . $rank . $num, TIMESTAMP);
                        $data = discover($url);
                        cache_write('discover:data' . $op . $rank . $_GPC['cat'] . $_GPC['year'] . $_GPC['area'] . $num, $data);
                    }
                    else
                    {
                        $data = cache_load('discover:data' . $op . $rank . $_GPC['cat'] . $_GPC['year'] . $_GPC['area'] . $num);
                    }
                }
                include $this->template('discover_json');
                exit();
            }
            include $this->template('news/index');
        }
    }
    public function doMobileDetail()
    {
        global $_W, $_GPC;
        $op = $_GPC['op'];
        $id = $_GPC['id'];
        $account_api = WeAccount::create();
        $password = $_COOKIE['password'];
        $info = $account_api->fansQueryInfo($_W['openid']);
        $settings = $this->module['config'];
        $member = member($_W['openid']);
        if (TIMESTAMP > $member['end_time'] && $member['is_pay'] == 1)
        {
            pdo_update('cyl_vip_video_member', array('end_time' => null, 'is_pay' => 0), array('openid' => $member['openid']));
            $data = array('first' => array('value' => '您好,' . $member['nickname'] . '您的会员已到期',
                    'color' => '#ff510'
                    ) ,
                'keyword1' => array('value' => '会员到期',
                    'color' => '#ff510'
                    ) ,
                'keyword2' => array('value' => '到期提醒',
                    'color' => '#ff510'
                    ) ,
                'remark' => array('value' => '点击详情开通',
                    'color' => '#ff510'
                    ) ,
                );
            $url = $_W['siteroot'] . 'app' . ltrim(murl('entry', array('do' => 'member', 'm' => 'cyl_vip_video')) , '.');
            $account_api->sendTplNotice($member['openid'], $settings['tpl_id'], $data, $url, $topcolor = '#FF683F');
        }
        $ad = pdo_fetch("SELECT * FROM " . tablename('cyl_vip_video_ad') . " WHERE uniacid = :uniacid  AND status = 0 ORDER BY rand() DESC LIMIT 1", array(':uniacid' => $_W['uniacid']), 'id');
        if (TIMESTAMP > $ad['end_time'])
        {
            pdo_update('cyl_vip_video_ad', array('status' => 1), array('id' => $ad['id']));
        }
        /**
         * if (!pdo_tableexists('cyl_video_pc_site'))
         * {
         * if (!is_weixin())
         * {
         * message('暂时只支持微信,请使用微信观看视频');
         * }
         * }
         * if ($settings['is_pc'] == 1)
         * {
         * if (!is_weixin())
         * {
         * message('暂时只支持微信,请使用微信观看视频');
         * }
         * }
         */
        $hdp = pdo_getall('cyl_vip_video_hdp', array('uniacid' => $_W['uniacid'], 'type' => $_GPC['do']), array() , '', 'sort DESC , id DESC');
        $category = pdo_fetchall("SELECT * FROM " . tablename('cyl_vip_video_category') . " WHERE uniacid = '{$_W['uniacid']}' AND parentid = 0 ORDER BY parentid ASC, displayorder ASC, id ASC ", array(), 'id');
        $url = $_GPC['url'];
        $vid = $_GPC['vid'];
        $yurl = $_GPC['url'];
        $num = pdo_fetchcolumn("SELECT COUNT(*) FROM " . tablename('cyl_vip_video') . " WHERE uniacid = :uniacid AND openid = :openid ", array(':uniacid' => $_W['uniacid'], ':openid' => $member['openid']));
        if ($num >= $settings['free_num'] && $member['is_pay'] == 0)
        {
            message($settings['warn_font'] ?$settings['warn_font'] : '您的免费观看次数已用完，请点击确定开通会员，无限制观看', $this->createMobileUrl('member', array('op' => 'open')), 'error');
        }
        $jilu = pdo_getall('cyl_vip_video', array('uniacid' => $_W['uniacid'], 'openid' => $_W['openid'],), array() , '', 'id DESC limit 10');
        $video_id = $_GPC['url'] ?$_GPC['url'] : $id;
        $comment = pdo_getall('cyl_vip_video_message', array('uniacid' => $_W['uniacid'], 'video_id' => $video_id), array() , '', 'id DESC');
        if ($id)
        {
            $content = pdo_fetch("SELECT * FROM " . tablename('cyl_vip_video_manage') . " WHERE id=:id", array(':id' => $id));
            if (checksubmit('submit'))
            {
                if ($_GPC['password'] == $content['password'])
                {
                    setcookie("password", $_GPC['password'], time() + 2 * 7 * 24 * 3600);
                    $url = $this->createMobileUrl('detail', array('id' => $id));
                    Header("Location: $url");
                }
                else
                {
                    message('密码不正确，请重新输入', '', 'error');
                }
            }
            $click = $content['click'];
            $juji = iunserializer($content['video_url']);
            if (count($juji) < 2)
            {
                $url = $juji['0']['link'];
            }
            else
            {
                $url = $_GPC['url'];
                if (!$url)
                {
                    $url = $juji['0']['link'];
                }
            }
            pdo_update('cyl_vip_video_manage', array('click +=' => 1), array('id' => $id));
        }elseif ($op == 'yule' || $op == 'gaoxiao')
        {
            $url = kan360($url, $vid);
            $content['title'] = $url['title'];
            $content['thumb'] = $url['thumb'];
            $url = $url['mp4'] . '&type=zhilian';
        }
        else
        {
            $url_time = cache_load('pc_caiji_detail:' . $url);
            $content = cache_load('content:' . $url);
            if (empty($url_time))
            {
                $url_time = 0;
            }
            if ((TIMESTAMP - $url_time) > 86400 || empty($content))
            {
                $content = pc_caiji_detail($url);
                $tuijian = pc_caiji_detail_tuijian($url);
                $daoyan = pc_caiji_detail_daoyan($url);
                cache_write('pc_caiji_detail:' . $url, TIMESTAMP);
                cache_write('content:' . $url, $content);
                cache_write('tuijian:' . $url, $tuijian);
                cache_write('daoyan:' . $url, $daoyan);
            }
            else
            {
                $content = cache_load('content:' . $url);
                $tuijian = cache_load('tuijian:' . $url);
                $daoyan = cache_load('daoyan:' . $url);
            }
            $site = site();
            $content = $content['0'];
            if ($op == 'dianying')
            {
                if ((TIMESTAMP - $url_time) > 86400)
                {
                    $link = caiji_url($url);
                    cache_write('caiji_url:' . $url, $link);
                }
                else
                {
                    $link = cache_load('caiji_url:' . $url);
                }
                if ($_GPC['link'])
                {
                    $url = $_GPC['link'];
                }
                else
                {
                    if (strpos($link['0']['link'], 'qq') && count($link) > 1 && !$settings['tengxun'])
                    {
                        $url = $link['1']['link'];
                        $site_title = $link['1']['title'];
                    }
                    else
                    {
                        $url = $link['0']['link'];
                    }
                }
            }
            if ($op == 'dianshi')
            {
                $link = caiji_url($url);
                if ($_GPC['site'])
                {
                    $site = array_keys($site, $_GPC['site']);
                }
                else
                {
                    if (strexists($link['0']['title'], '腾讯') && count($link) > 1 && !$settings['tengxun'])
                    {
                        $site_title = $link['1']['title'];
                    }
                    else
                    {
                        $site_title = $link['0']['title'];
                    }
                    $site = array_keys($site, str_replace('(付费)', '', $site_title));
                }
                $juji = juji_url($url, $site);
                if ($_GPC['link'])
                {
                    $url = $_GPC['link'];
                }
                else
                {
                    $url = $juji['0']['link'];
                }
            }
            if ($op == 'dongman')
            {
                $link = caiji_url($url);
                if ($_GPC['site'])
                {
                    $site = array_keys($site, $_GPC['site']);
                }
                else
                {
                    if (strexists($link['0']['title'], '腾讯') && count($link) > 1 && !$settings['tengxun'])
                    {
                        $site_title = $link['1']['title'];
                    }
                    else
                    {
                        $site_title = $link['0']['title'];
                    }
                    $site = array_keys($site, str_replace('(付费)', '', $site_title));
                }
                $juji = dongman_url($url, $site);
                if ($_GPC['link'])
                {
                    $url = $_GPC['link'];
                }
                else
                {
                    $url = $juji['0']['link'];
                }
            }
            if ($op == 'zongyi')
            {
                $link = zongyi_url($url);
                if ($_GPC['site'])
                {
                    $site = array_keys($site, $_GPC['site']);
                }
                else
                {
                    if (strexists($link['0']['title'], '腾讯') && count($link) > 1 && !$settings['tengxun'])
                    {
                        $site_title = $link['1']['title'];
                    }
                    else
                    {
                        $site_title = $link['0']['title'];
                    }
                    $site = array_keys($site, str_replace('(付费)', '', $site_title));
                }
                $year = $_GPC['year'];
                if ($year)
                {
                    $ss = '/([\x80-\xff]*)/i';
                    $year = preg_replace($ss, '', $year);
                    $juji = zongyi_juji_url($url, $site, $year);
                }
                else
                {
                    $juji = zongyi_juji_url($url, $site);
                }
                $year = zongyi_year_url($url);
                if (!$_GPC['year'])
                {
                    $_GPC['year'] = $year['0']['date'];
                }
                if ($_GPC['link'])
                {
                    $url = $_GPC['link'];
                }
                else
                {
                    $url = $juji['0']['link'];
                }
            }
            $click = pdo_fetchcolumn('SELECT * FROM ' . tablename('cyl_vip_video') . " WHERE uniacid = :uniacid AND yvideo_url = :yvideo_url ", array(':uniacid' => $_W['uniacid'], ':yvideo_url' => $yurl));
        }
        $video = pdo_get('cyl_vip_video', array('uniacid' => $_W['uniacid'], 'openid' => $_W['openid'], 'video_url' => $url));
        if (!$video)
        {
            if ($id)
            {
                pdo_insert('cyl_vip_video', array('uniacid' => $_W['uniacid'], 'openid' => $_W['openid'], 'uid' => $_W['fans']['uid'], 'title' => $content['title'], 'video_url' => $url, 'video_id' => $id, 'type' => $op, 'time' => TIMESTAMP, 'share' => $_GPC['jishu']));
            }
            else
            {
                pdo_insert('cyl_vip_video', array('uniacid' => $_W['uniacid'], 'openid' => $_W['openid'], 'uid' => $_W['fans']['uid'], 'title' => $content['title'], 'video_url' => $url, 'yvideo_url' => $yurl, 'type' => $op, 'time' => TIMESTAMP, 'share' => $_GPC['jishu']));
            }
        }
        if ($settings['api'])
        {
            $tempapi = explode(";", $settings['api']);
            foreach($tempapi as $k => $v)
            {
                $v = trim($v);
                if (empty($v)) continue;
                $title = explode("|", $v);
                if (count($title) > 2)
                {
                    continue;
                }elseif (count($title) > 1)
                {
                    $urlapi[] = array('title' => $title[0],
                        'link' => trim($title[1]),
                        );
                }
                else
                {
                    $urlapi[] = array('title' => "播放源" . ($k + 1),
                        'link' => trim($title[0]),
                        );
                }
            }

            if (strexists($url, 'zhilian'))
            {
                $url = explode('&type=zhilian', $url);
                $api = $url['0'];
            }elseif (strexists($url, 'baidu'))
            {
                $api = $settings['baidu_api'] . $url;
            }
            else
            { 
                // if ($op == 'yule' || $op == 'gaoxiao'){$api = $url ;} else{$api = $settings['api'] . $url . '&link=' . $_GPC['link'];}
                $api = $urlapi[0]['link'] . $url . '&link=' . $_GPC['link'];
            }
        }
        else
        {
            if ($id)
            {
                if (strexists($url, 'zhilian'))
                {
                    $url = explode('&type=zhilian', $url);
                    $api = $url['0'];
                }elseif ($settings['baidu_api'] && strexists($url, 'baidu'))
                {
                    $api = $settings['baidu_api'] . $url;
                }
                else
                { 
                    // $api = 'http://cyl.go8goo.com/vip/api.php?url=' . $url . '&link=' . $_GPC['link'];
                    $api = 'http://cyl.go8goo.com/vip/api.php?url=' . $url;
                }
            }
            else
            {
                if (strexists($url, 'zhilian'))
                {
                    $url = explode('&type=zhilian', $url);
                    $api = $url['0'];
                }
                else
                {
                    $api = 'http://cyl.go8goo.com/vip/api.php?url=' . $url;
                } 
                // $api = 'http://cyl.go8goo.com/vip/vip.php?url=' . $url . '&link=' . $_GPC['link'];
            }
        }
        if ($_GPC['index'] == 1)
        {
            $id = $_GPC['id'];
            $data = array('uniacid' => $_W['uniacid'],
                'id' => $id,
                );
            $item = pdo_get('cyl_vip_video', $data);
            include $this->template('news/detail');
            exit();
        }
        if ($op == 'comment')
        {
            $data = array('uniacid' => $_W['uniacid'],
                'video_id' => $_GPC['video_id'],
                'openid' => $member['openid'],
                'content' => $_GPC['content'],
                'time' => TIMESTAMP
                );
            if ($settings['status'] == 1)
            {
                $data['status'] = 1;
            }
            else
            {
                $data['status'] = 0;
            }
            if ($data['openid'])
            {
                $ret = pdo_insert('cyl_vip_video_message', $data);
            }
            if (!empty($ret))
            {
                echo json_encode($data);
                exit();
            }
            else
            {
                echo '留言失败';
            }
        }
        include $this->template('news/detail');
    }
    public function doMobileSearch()
    {
        global $_W, $_GPC;
        $acc = WeAccount::create();
        $settings = $this->module['config'];
        $member = member($_W['openid']);
        $hdp = pdo_getall('cyl_vip_video_hdp', array('uniacid' => $_W['uniacid'], 'type' => $_GPC['do']), array() , '', 'sort DESC , id DESC');
        $category = pdo_fetchall("SELECT * FROM " . tablename('cyl_vip_video_category') . " WHERE uniacid = '{$_W['uniacid']}' AND parentid = 0 ORDER BY parentid ASC, displayorder ASC, id ASC ", array(), 'id');
        if (TIMESTAMP > $member['end_time'] && $member['is_pay'] == 1)
        {
            pdo_update('cyl_vip_video_member', array('end_time' => null, 'is_pay' => 0), array('openid' => $member['openid']));
            $data = array('first' => array('value' => '您好,' . $member['nickname'] . '您的会员已到期',
                    'color' => '#ff510'
                    ) ,
                'keyword1' => array('value' => '会员到期',
                    'color' => '#ff510'
                    ) ,
                'keyword2' => array('value' => '到期提醒',
                    'color' => '#ff510'
                    ) ,
                'remark' => array('value' => '点击详情开通',
                    'color' => '#ff510'
                    ) ,
                );
            $url = $_W['siteroot'] . 'app' . ltrim(murl('entry', array('do' => 'member', 'm' => 'cyl_vip_video')) , '.');
            $acc->sendTplNotice($member['openid'], $settings['tpl_id'], $data, $url, $topcolor = '#FF683F');
        }
        $where = ' WHERE uniacid = :uniacid ';
        $params[':uniacid'] = $_W['uniacid'];
        $sql = ' SELECT * FROM ' . tablename('cyl_vip_video_manage') . $where . ' ORDER BY id DESC LIMIT 50';
        $video = pdo_fetchall($sql, $params, 'id');
        $op = $_GPC['op'] ?$_GPC['op'] : 'search';
        $key = $_GPC['key'];
        if ($key)
        {
            $where = ' WHERE uniacid = :uniacid ';
            $where .= ' AND title LIKE :title ';
            $params[':uniacid'] = $_W['uniacid'];
            $params[':title'] = "%{$_GPC['key']}%";
            $sql = ' SELECT * FROM ' . tablename('cyl_vip_video_manage') . $where . ' ORDER BY id DESC ';
            $search = pdo_fetchall($sql, $params, 'id');
            $list = caiji_list($key);
        }
        include $this->template('search');
    }
    public function doMobileClean()
    {
        global $_W, $_GPC;
        $res = pdo_delete('cyl_vip_video', array('uniacid' => $_W['uniacid'], 'openid' => $_W['openid']));
        echo "清空成功";
        exit();
    }
    public function doMobileShare()
    {
        global $_W, $_GPC;
        $acc = WeAccount::create();
        $settings = $this->module['config'];
        $member = member($_W['openid']);
        $uid = $member['uid'];
        $day = $settings['share_day'];
        $data = array('uniacid' => $_W['uniacid'],
            'openid' => $member['openid'],
            'uid' => $uid,
            'time' => TIMESTAMP
            );
        if ($settings['is_credit'] == 1)
        {
            $share = pdo_fetchcolumn("SELECT COUNT(*) FROM " . tablename('cyl_vip_video_share') . " WHERE uniacid = :uniacid AND openid = :openid ", array(':uniacid' => $_W['uniacid'], ':openid' => $_W['openid']));
            if ($share >= $settings['share_click'])
            {
                exit();
            }
        }
        if ($settings['is_credit'] == 2)
        {
            $share = pdo_fetch("SELECT * FROM " . tablename('cyl_vip_video_share') . " WHERE uniacid = :uniacid AND openid = :openid ORDER BY id DESC", array(':uniacid' => $_W['uniacid'], ':openid' => $_W['openid']));
            if (date('Y-m-d') == date('Y-m-d', $share['time']))
            {
                exit();
            }
        }
        pdo_insert('cyl_vip_video_share', $data);
        $data = array('uniacid' => $_W['uniacid'],
            'openid' => $_W['openid'],
            'uid' => $uid,
            'tid' => '分享营销',
            'fee' => $fee,
            'status' => 1,
            'day' => $day,
            'time' => TIMESTAMP
            );
        pdo_insert('cyl_vip_video_order', $data);
        if ($member['end_time'])
        {
            pdo_update('cyl_vip_video_member', array('is_pay' => 1, 'end_time' => strtotime("+{$day} days", $member['end_time'])), array('openid' => $data['openid'], 'uniacid' => $data['uniacid']));
            $time = date('Y-m-d H:i:s', strtotime("+{$day} days", $member['end_time']));
        }
        else
        {
            pdo_update('cyl_vip_video_member', array('is_pay' => 1, 'end_time' => strtotime("+{$day} days")), array('openid' => $data['openid'], 'uniacid' => $data['uniacid']));
            $time = date('Y-m-d H:i:s', strtotime("+{$day} days"));
        }
        if ($settings['tpl_id'])
        {
            $data = array('first' => array('value' => '您好,' . $member['nickname'] . '获得' . $day . '天会员',
                    'color' => '#ff510'
                    ) ,
                'keyword1' => array('value' => '会员开通',
                    'color' => '#ff510'
                    ) ,
                'keyword2' => array('value' => '开通提醒',
                    'color' => '#ff510'
                    ) ,
                'remark' => array('value' => '到期时间' . $time,
                    'color' => '#ff510'
                    ) ,
                );
            $url = $_W['siteroot'] . 'app' . ltrim(murl('entry', array('do' => 'member', 'm' => 'cyl_vip_video')) , '.');
            $acc->sendTplNotice($member['openid'], $settings['tpl_id'], $data, $url, $topcolor = '#FF683F');
            $data = array('first' => array('value' => $member['nickname'] . '分享营销，开通了' . $day . '天会员',
                    'color' => '#ff510'
                    ) ,
                'keyword1' => array('value' => '会员开通',
                    'color' => '#ff510'
                    ) ,
                'keyword2' => array('value' => '开通提醒',
                    'color' => '#ff510'
                    ) ,
                'remark' => array('value' => '到期时间' . $time . '请进入后台查看',
                    'color' => '#ff510'
                    ) ,
                );
            $acc->sendTplNotice($settings['kf_id'], $settings['tpl_id'], $data, $url, $topcolor = '#FF683F');
        }
        exit();
    }
    public function doMobileMember()
    {
        global $_W, $_GPC;
        load()->model('mc');
        $acc = WeAccount::create();
        $settings = $this->module['config'];
        $op = $_GPC['op'] ?$_GPC['op'] : 'member';
        $member = member($_W['openid']);
        if (TIMESTAMP > $member['end_time'] && $member['is_pay'] == 1)
        {
            pdo_update('cyl_vip_video_member', array('end_time' => null, 'is_pay' => 0), array('openid' => $member['openid']));
            $data = array('first' => array('value' => '您好,' . $member['nickname'] . '您的会员已到期',
                    'color' => '#ff510'
                    ) ,
                'keyword1' => array('value' => '会员到期',
                    'color' => '#ff510'
                    ) ,
                'keyword2' => array('value' => '到期提醒',
                    'color' => '#ff510'
                    ) ,
                'remark' => array('value' => '点击详情开通',
                    'color' => '#ff510'
                    ) ,
                );
            $url = $_W['siteroot'] . 'app' . ltrim(murl('entry', array('do' => 'member', 'm' => 'cyl_vip_video')) , '.');
            $acc->sendTplNotice($member['openid'], $settings['tpl_id'], $data, $url, $topcolor = '#FF683F');
        }
        if ($_W['account']['level'] < 4 || !is_weixin())
        {
            checkauth();
        }
        $credit = mc_credit_fetch($member['uid']);
        if ($op == 'open')
        {
            $day = $_GPC['day'];
            $fee = $_GPC['card_fee'];
            $day = $_GPC['card_day'];
            $jifen = $_GPC['card_credit'];
            if (checksubmit('credit'))
            {
                $fee = $jifen;
                if ($fee > $credit['credit1'])
                {
                    message('积分不足', '', 'error');
                }
                if (empty($fee))
                {
                    message('管理员未设置积分，请使用微信支付兑换', '', 'error');
                }
                $data = array('uniacid' => $_W['uniacid'],
                    'openid' => $member['openid'],
                    'uid' => $member['uid'],
                    'tid' => '积分兑换',
                    'fee' => $fee,
                    'status' => 1,
                    'day' => $day,
                    'time' => TIMESTAMP
                    );
                pdo_insert('cyl_vip_video_order', $data);
                mc_credit_update($member['uid'], 'credit1', - $fee, array($member['uid'], '视频会员开通-' . $fee . '积分', 'cyl_vip_video'));
                if ($member['end_time'])
                {
                    pdo_update('cyl_vip_video_member', array('is_pay' => 1, 'end_time' => strtotime("+{$day} days", $member['end_time'])), array('openid' => $data['openid'], 'uniacid' => $data['uniacid']));
                    $time = date('Y-m-d H:i:s', strtotime("+{$day} days", $member['end_time']));
                }
                else
                {
                    pdo_update('cyl_vip_video_member', array('is_pay' => 1, 'end_time' => strtotime("+{$day} days")), array('openid' => $data['openid'], 'uniacid' => $data['uniacid']));
                    $time = date('Y-m-d H:i:s', strtotime("+{$day} days"));
                }
                if ($settings['tpl_id'])
                {
                    $data = array('first' => array('value' => '您好,' . $member['nickname'] . '开通了' . $day . '天会员',
                            'color' => '#ff510'
                            ) ,
                        'keyword1' => array('value' => '会员开通',
                            'color' => '#ff510'
                            ) ,
                        'keyword2' => array('value' => '开通提醒',
                            'color' => '#ff510'
                            ) ,
                        'remark' => array('value' => '花费' . $fee . '积分,到期时间' . $time,
                            'color' => '#ff510'
                            ) ,
                        );
                    $url = $_W['siteroot'] . 'app' . ltrim(murl('entry', array('do' => 'member', 'm' => 'cyl_vip_video')) , '.');
                    $acc->sendTplNotice($member['openid'], $settings['tpl_id'], $data, $url, $topcolor = '#FF683F');
                    $data = array('first' => array('value' => $member['nickname'] . '开通了' . $day . '天会员',
                            'color' => '#ff510'
                            ) ,
                        'keyword1' => array('value' => '会员开通',
                            'color' => '#ff510'
                            ) ,
                        'keyword2' => array('value' => '开通提醒',
                            'color' => '#ff510'
                            ) ,
                        'remark' => array('value' => '花费【' . $fee . '】积分，到期时间' . $time . '请进入后台查看',
                            'color' => '#ff510'
                            ) ,
                        );
                    $acc->sendTplNotice($settings['kf_id'], $settings['tpl_id'], $data, $url, $topcolor = '#FF683F');
                }
                message('会员兑换成功', $this->createMobileUrl('member'), 'success');
            }
            if (checksubmit('submit'))
            {
                $url = $this->createMobileUrl('pay', array('day' => $day, 'fee' => $fee));
                Header("Location: $url");
                exit();
            }
        }
        if ($op == 'my')
        {
            $data = array('uniacid' => $_W['uniacid'],
                'openid' => $member['openid'],
                );
            $list = pdo_getall('cyl_vip_video', $data, array() , '', 'id DESC');
        }
        if ($op == 'card')
        {
            if (checksubmit())
            {
                $data = array('uniacid' => $_W['uniacid'],
                    'pwd' => $_GPC['card'],
                    );
                $card = pdo_get('cyl_vip_video_keyword_id', $data, array() , '', 'id DESC');
                if (!$card)
                {
                    message('兑换码无效', '', 'error');
                }elseif ($card['status'])
                {
                    message('兑换码已使用', '', 'error');
                }
                else
                {
                    $res = pdo_update('cyl_vip_video_keyword_id', array('status' => 1, 'openid' => $_W['openid']), array('id' => $card['id']));
                    if ($res)
                    {
                        if ($member['end_time'])
                        {
                            pdo_update('cyl_vip_video_member', array('is_pay' => 1, 'end_time' => strtotime("+{$card['day']} days", $member['end_time'])), array('openid' => $_W['openid'], 'uniacid' => $data['uniacid']));
                            $time = date('Y-m-d H:i:s', strtotime("+{$card['day']} days", $member['end_time']));
                        }
                        else
                        {
                            pdo_update('cyl_vip_video_member', array('is_pay' => 1, 'end_time' => strtotime("+{$card['day']} days")), array('openid' => $_W['openid'], 'uniacid' => $data['uniacid']));
                            $time = date('Y-m-d H:i:s', strtotime("+{$card['day']} days"));
                        }
                        if ($settings['tpl_id'])
                        {
                            $data = array('first' => array('value' => '您好,' . $member['nickname'] . '开通了' . $card['day'] . '天会员',
                                    'color' => '#ff510'
                                    ) ,
                                'keyword1' => array('value' => '会员开通',
                                    'color' => '#ff510'
                                    ) ,
                                'keyword2' => array('value' => '开通提醒',
                                    'color' => '#ff510'
                                    ) ,
                                'remark' => array('value' => '卡密兑换' . $card['day'] . '天,到期时间' . $time,
                                    'color' => '#ff510'
                                    ) ,
                                );
                            $url = $_W['siteroot'] . 'app' . ltrim(murl('entry', array('do' => 'member', 'm' => 'cyl_vip_video')) , '.');
                            $acc->sendTplNotice($member['openid'], $settings['tpl_id'], $data, $url, $topcolor = '#FF683F');
                            $data = array('first' => array('value' => $member['nickname'] . '开通了' . $card['day'] . '天会员',
                                    'color' => '#ff510'
                                    ) ,
                                'keyword1' => array('value' => '会员开通',
                                    'color' => '#ff510'
                                    ) ,
                                'keyword2' => array('value' => '开通提醒',
                                    'color' => '#ff510'
                                    ) ,
                                'remark' => array('value' => '卡密兑换' . $card['day'] . '天，到期时间' . $time . '请进入后台查看',
                                    'color' => '#ff510'
                                    ) ,
                                );
                            $acc->sendTplNotice($settings['kf_id'], $settings['tpl_id'], $data, $url, $topcolor = '#FF683F');
                        }
                        $data = array('uniacid' => $_W['uniacid'],
                            'openid' => $member['openid'],
                            'uid' => $uid,
                            'tid' => '卡密兑换',
                            'fee' => '',
                            'status' => 1,
                            'day' => $card['day'],
                            'time' => TIMESTAMP
                            );
                        pdo_insert('cyl_vip_video_order', $data);
                        message('兑换成功', $this->createMobileUrl('member'), 'success');
                    }
                }
            }
        }
        if ($op == 'order')
        {
            $data = array('uniacid' => $_W['uniacid'],
                'openid' => $member['openid'],
                );
            $list = pdo_getall('cyl_vip_video_order', $data, array() , '', 'id DESC');
        }
        include $this->template('news/member');
    }
    public function doMobileTv()
    {
        global $_W, $_GPC;
        $settings = $this->module['config'];
        $url = $_GPC['url'];
        $jilu = pdo_getall('cyl_vip_video', array('uniacid' => $_W['uniacid'], 'openid' => $_W['openid'],), array() , '', 'id DESC limit 10');
        $category = pdo_fetchall("SELECT * FROM " . tablename('cyl_vip_video_category') . " WHERE uniacid = '{$_W['uniacid']}' AND parentid = 0 ORDER BY parentid ASC, displayorder ASC, id ASC ", array(), 'id');
        include $this->template('tv');
    }
    public function doMobilePay()
    {
        global $_GPC, $_W;
        $acc = WeAccount::create();
        $settings = $this->module['config'];
        $member = member($_W['openid']);
        $card = iunserializer($settings['card']);
        foreach ($card as $value)
        {
            $card_day .= $value['card_day'] . ',';
            $card_fee .= $value['card_fee'] . ',';
        }
        if ($_GPC['fee'])
        {
            $fee = $_GPC['fee'];
        }
        else
        {
            $fee = floatval($settings['fee']);
        }
        if ($fee < 0.01)
        {
            message('支付错误, 金额小于0.01');
        }
        $id = $_GPC['id'];
        if (empty($member['openid']))
        {
            message('非法进入');
        }
        if ($id)
        {
            $order = pdo_fetch("SELECT * FROM " . tablename('cyl_vip_video_order') . " WHERE id = :id", array(':id' => $id));
            $day = $order['day'];
            $snid = $order['tid'];
        }
        else
        {
            $day = $_GPC['day'];
            $snid = date('YmdHis') . str_pad(mt_rand(1, 99999), 6, '0', STR_PAD_LEFT);
        }
        if ($_GPC['fee'] && strstr($card_day, $day) && strstr($card_fee, $fee))
        {
            $amount = $fee;
        }
        else
        {
            $amount = $settings['fee'] * $day;
        }
        if ($_GPC['op'] == 'shang')
        {
            $amount = $_GPC['fee'];
        }
        if ($amount <= 0)
        {
            message('支付错误, 金额小于0');
        }
        $data = array('uniacid' => $_W['uniacid'],
            'openid' => $member['openid'],
            'uid' => $member['uid'],
            'tid' => $snid,
            'fee' => $amount,
            'status' => 0,
            'day' => $day,
            'time' => TIMESTAMP
            );
        if ($_GPC['op'] == 'shang')
        {
            $data = array('uniacid' => $_W['uniacid'],
                'openid' => $member['openid'],
                'uid' => $member['uid'],
                'tid' => $snid,
                'fee' => $amount,
                'status' => 0,
                'day' => $day,
                'time' => TIMESTAMP
                );
            $data['desc'] = '视频打赏';
        }
        if ($id)
        {
            pdo_update('cyl_vip_video_order', $data, array('id' => $id));
        }
        else
        {
            pdo_insert('cyl_vip_video_order', $data);
        }
        if ($_GPC['op'] == 'shang')
        {
            $params = array('tid' => $snid,
                'ordersn' => 'SN' . $snid,
                'title' => '视频打赏',
                'fee' => $amount,
                'user' => $member['uid'],
                );
        }
        else
        {
            $params = array('tid' => $snid,
                'ordersn' => 'SN' . $snid,
                'title' => '开通会员',
                'fee' => $amount,
                'user' => $member['uid'],
                );
        }
        $this->pay($params);
    }
    public function payResult($params)
    {
        global $_W, $_GPC;
        $acc = WeAccount::create();
        $order = pdo_fetch("SELECT * FROM " . tablename('cyl_vip_video_order') . " WHERE tid = :tid", array(':tid' => $params['tid']
                ));
        $member = pdo_get('cyl_vip_video_member', array('openid' => $order['openid'], 'uniacid' => $order['uniacid']));
        $settings = $this->module['config'];
        if ($params['result'] == 'success' && $params['from'] == 'notify')
        {
            pdo_update('cyl_vip_video_order', array('status' => 1), array('tid' => $order['tid']));
            if ($member['end_time'])
            {
                $day = $order['day'];
                pdo_update('cyl_vip_video_member', array('is_pay' => 1, 'end_time' => strtotime("+{$day} days", $member['end_time'])), array('openid' => $order['openid'], 'uniacid' => $order['uniacid']));
                $time = date('Y-m-d H:i:s', strtotime("+{$day} days", $member['end_time']));
            }
            else
            {
                $day = $order['day'];
                pdo_update('cyl_vip_video_member', array('is_pay' => 1, 'end_time' => strtotime("+{$day} days")), array('openid' => $order['openid'], 'uniacid' => $order['uniacid']));
                $time = date('Y-m-d H:i:s', strtotime("+{$day} days"));
            }
            if ($_W['account']['level'] == 4 && $settings['tpl_id'])
            {
                if ($order['desc'])
                {
                    $data = array('first' => array('value' => '您好,' . $member['nickname'],
                            'color' => '#ff510'
                            ) ,
                        'keyword1' => array('value' => '打赏',
                            'color' => '#ff510'
                            ) ,
                        'keyword2' => array('value' => '打赏提醒',
                            'color' => '#ff510'
                            ) ,
                        'remark' => array('value' => '花费金额【' . $order['fee'] . '】',
                            'color' => '#ff510'
                            ) ,
                        );
                    $url = $_W['siteroot'] . 'app' . ltrim(murl('entry', array('do' => 'member', 'm' => 'cyl_vip_video')) , '.');
                    $acc->sendTplNotice($order['openid'], $settings['tpl_id'], $data, $url, $topcolor = '#FF683F');
                    $data = array('first' => array('value' => $member['nickname'] . '打赏',
                            'color' => '#ff510'
                            ) ,
                        'keyword1' => array('value' => '打赏',
                            'color' => '#ff510'
                            ) ,
                        'keyword2' => array('value' => '打赏提醒',
                            'color' => '#ff510'
                            ) ,
                        'remark' => array('value' => '打赏金额【' . $order['fee'] . '】元，请进入后台查看',
                            'color' => '#ff510'
                            ) ,
                        );
                }
                else
                {
                    $data = array('first' => array('value' => '您好,' . $member['nickname'],
                            'color' => '#ff510'
                            ) ,
                        'keyword1' => array('value' => '会员开通',
                            'color' => '#ff510'
                            ) ,
                        'keyword2' => array('value' => '开通提醒',
                            'color' => '#ff510'
                            ) ,
                        'remark' => array('value' => '花费金额【' . $order['fee'] . '】，到期时间' . $time,
                            'color' => '#ff510'
                            ) ,
                        );
                    $url = $_W['siteroot'] . 'app' . ltrim(murl('entry', array('do' => 'member', 'm' => 'cyl_vip_video')) , '.');
                    $acc->sendTplNotice($order['openid'], $settings['tpl_id'], $data, $url, $topcolor = '#FF683F');
                    $data = array('first' => array('value' => $member['nickname'] . '开通了' . $day . '天会员',
                            'color' => '#ff510'
                            ) ,
                        'keyword1' => array('value' => '会员开通',
                            'color' => '#ff510'
                            ) ,
                        'keyword2' => array('value' => '开通提醒',
                            'color' => '#ff510'
                            ) ,
                        'remark' => array('value' => '花费金额【' . $order['fee'] . '】元，到期时间' . $time . '请进入后台查看',
                            'color' => '#ff510'
                            ) ,
                        );
                }
                $acc->sendTplNotice($settings['kf_id'], $settings['tpl_id'], $data, $url, $topcolor = '#FF683F');
            }
        }
        if (empty($params['result']) || $params['result'] != 'success')
        {
        }
        if ($params['from'] == 'return')
        {
            if ($params['result'] == 'success')
            {
                message('您已支付成功！', $this->createMobileUrl('member') , 'success');
            }
            else
            {
                message('支付失败！', 'error');
            }
        }
    }
    public function doWebManage()
    {
        global $_W, $_GPC;
        $op = $_GPC['op'] ?$_GPC['op'] : 'list';
        $category = pdo_fetchall("SELECT * FROM " . tablename('cyl_vip_video_category') . " WHERE uniacid = '{$_W['uniacid']}' ORDER BY parentid ASC, displayorder ASC, id ASC ", array(), 'id');
        $parent = array();
        $children = array();
        if (!empty($category))
        {
            $children = '';
            foreach ($category as $cid => $cate)
            {
                if (!empty($cate['parentid']))
                {
                    $children[$cate['parentid']][] = $cate;
                }
                else
                {
                    $parent[$cate['id']] = $cate;
                }
            }
        }
        if ($op == 'list')
        {
            $pageindex = max(intval($_GPC['page']), 1);
            $pagesize = 20;
            $starttime = empty($_GPC['time']['start']) ?strtotime('-90 days') : strtotime($_GPC['time']['start']);
            $endtime = empty($_GPC['time']['end']) ?TIMESTAMP + 86399 : strtotime($_GPC['time']['end']) + 86399;
            $where = ' WHERE uniacid = :uniacid AND time >= :starttime AND time <= :endtime';
            $params = array(':uniacid' => $_W['uniacid'],
                ':starttime' => $starttime,
                ':endtime' => $endtime
                );
            $total = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('cyl_vip_video_manage') . $where , $params);
            $pager = pagination($total, $pageindex, $pagesize);
            $sql = ' SELECT * FROM ' . tablename('cyl_vip_video_manage') . $where . ' ORDER BY sort DESC , id DESC LIMIT ' . (($pageindex -1) * $pagesize) . ',' . $pagesize;
            $list = pdo_fetchall($sql, $params, 'id');
        }
        if ($op == 'record')
        {
            $pageindex = max(intval($_GPC['page']), 1);
            $pagesize = 20;
            $starttime = empty($_GPC['time']['start']) ?strtotime('-90 days') : strtotime($_GPC['time']['start']);
            $endtime = empty($_GPC['time']['end']) ?TIMESTAMP + 86399 : strtotime($_GPC['time']['end']) + 86399;
            $where = ' WHERE uniacid = :uniacid AND time >= :starttime AND time <= :endtime AND length(title) <> 0 ';
            $params = array(':uniacid' => $_W['uniacid'],
                ':starttime' => $starttime,
                ':endtime' => $endtime
                );
            $total = pdo_fetchcolumn('SELECT * FROM ' . tablename('cyl_vip_video') . $where . ' GROUP BY video_url ', $params);
            $pager = pagination($total, $pageindex, $pagesize);
            $sql = ' SELECT *,count(video_url) as num FROM ' . tablename('cyl_vip_video') . $where . ' GROUP BY video_url ORDER BY num DESC LIMIT ' . (($pageindex -1) * $pagesize) . ',' . $pagesize;
            $list = pdo_fetchall($sql, $params, 'id');
        }
        if ($op == 'single')
        {
            $pageindex = max(intval($_GPC['page']), 1);
            $pagesize = 20;
            $starttime = empty($_GPC['time']['start']) ?strtotime('-90 days') : strtotime($_GPC['time']['start']);
            $endtime = empty($_GPC['time']['end']) ?TIMESTAMP + 86399 : strtotime($_GPC['time']['end']) + 86399;
            $where = ' WHERE uniacid = :uniacid AND time >= :starttime AND time <= :endtime AND length(title) <> 0';
            $params = array(':uniacid' => $_W['uniacid'],
                ':starttime' => $starttime,
                ':endtime' => $endtime
                );
            if ($_GPC['openid'])
            {
                $where .= ' AND openid = :openid ';
                $params[':openid'] = $_GPC['openid'];
            }
            $total = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('cyl_vip_video') . $where , $params);
            $pager = pagination($total, $pageindex, $pagesize);
            $sql = ' SELECT * FROM ' . tablename('cyl_vip_video') . $where . ' ORDER BY id DESC LIMIT ' . (($pageindex -1) * $pagesize) . ',' . $pagesize;
            $list = pdo_fetchall($sql, $params, 'id');
        }
        if ($op == 'comment')
        {
            $pageindex = max(intval($_GPC['page']), 1);
            $pagesize = 20;
            $starttime = empty($_GPC['time']['start']) ?strtotime('-90 days') : strtotime($_GPC['time']['start']);
            $endtime = empty($_GPC['time']['end']) ?TIMESTAMP + 86399 : strtotime($_GPC['time']['end']) + 86399;
            $where = ' WHERE uniacid = :uniacid AND time >= :starttime AND time <= :endtime ';
            $params = array(':uniacid' => $_W['uniacid'],
                ':starttime' => $starttime,
                ':endtime' => $endtime
                );
            if ($_GPC['openid'])
            {
                $where .= ' AND openid = :openid ';
                $params[':openid'] = $_GPC['openid'];
            }
            $total = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('cyl_vip_video_message') . $where , $params);
            $pager = pagination($total, $pageindex, $pagesize);
            $sql = ' SELECT * FROM ' . tablename('cyl_vip_video_message') . $where . ' ORDER BY id DESC LIMIT ' . (($pageindex -1) * $pagesize) . ',' . $pagesize;
            $list = pdo_fetchall($sql, $params, 'id');
        }
        if ($op == 'add')
        {
            $id = $_GPC['id'];
            if ($id)
            {
                $item = pdo_get('cyl_vip_video_manage', array('id' => $id));
                $pcate = $item['cid'];
                $ccate = $item['pid'];
            }
            if (checksubmit())
            {
                $data = $_GPC['data'];
                $data['cid'] = intval($_GPC['category']['parentid']);
                $data['pid'] = intval($_GPC['category']['childid']);
                $data['thumb'] = $_GPC['thumb'];
                $data['uniacid'] = $_W['uniacid'];
                $data['time'] = TIMESTAMP;
                foreach($_GPC['link'] as $k => $v)
                {
                    $v = trim($v);
                    if (empty($v)) continue;
                    $video_url[] = array('title' => $_GPC['title'][$k],
                        'link' => $v,
                        );
                }
                $data['video_url'] = iserializer($video_url);
                $keyword = $data['keyword'];
                if (!empty($keyword))
                {
                    $rule['uniacid'] = $_W['uniacid'];
                    $rule['name'] = '视频：' . $data['title'] . ' 自定义密码触发';
                    $rule['module'] = 'reply';
                    $rule['status'] = 1;
                    $rule['containtype'] = 'basic,';
                    $reply['uniacid'] = $_W['uniacid'];
                    $reply['module'] = 'reply';
                    $reply['content'] = $data['keyword'];
                    $reply['type'] = 1;
                    $reply['status'] = 1;
                }
                if ($item)
                {
                    pdo_update('cyl_vip_video_manage', $data, array('id' => $id));
                    if (empty($keyword))
                    {
                        pdo_delete('rule', array('id' => $item['rid'], 'uniacid' => $_W['uniacid']));
                        pdo_delete('rule_keyword', array('rid' => $item['rid'], 'uniacid' => $_W['uniacid']));
                        pdo_delete('basic_reply', array('rid' => $item['rid']));
                        pdo_update('cyl_vip_video_manage', array('rid' => ''), array('id' => $item['id']));
                    }elseif ($item['rid'])
                    {
                        pdo_update('rule', $rule, array('id' => $item['rid']));
                        pdo_update('rule_keyword', $reply, array('rid' => $item['rid']));
                        $reply_url = $_W['siteroot'] . 'app/index.php?i=' . $_W['uniacid'] . '&c=entry&id=' . $item['id'] . '&do=detail&m=cyl_vip_video';
                        $li['content'] = '密码：' . $data['password'] . '<br><a href="' . $reply_url . '">点击直达</a>';
                        pdo_update('basic_reply', $li, array('rid' => $item['rid']));
                    }elseif ($keyword)
                    {
                        pdo_insert('rule', $rule);
                        $rid = pdo_insertid();
                        $reply['rid'] = $rid;
                        pdo_insert('rule_keyword', $reply);
                        $li['rid'] = $rid;
                        $reply_url = $_W['siteroot'] . 'app/index.php?i=' . $_W['uniacid'] . '&c=entry&id=' . $item['id'] . '&do=detail&m=cyl_vip_video';
                        $li['content'] = '密码：' . $data['password'] . '<br><a href="' . $reply_url . '">点击直达</a>';
                        pdo_insert('basic_reply', $li);
                        pdo_update('cyl_vip_video_manage', array('rid' => $rid), array('id' => $id));
                    }
                }
                else
                {
                    pdo_insert('cyl_vip_video_manage', $data);
                    $id = pdo_insertid();
                    if (!empty($keyword))
                    {
                        pdo_insert('rule', $rule);
                        $rid = pdo_insertid();
                        $reply['rid'] = $rid;
                        pdo_insert('rule_keyword', $reply);
                        $li['rid'] = $rid;
                        $reply_url = $_W['siteroot'] . 'app/index.php?i=' . $_W['uniacid'] . '&c=entry&id=' . $id . '&do=detail&m=cyl_vip_video';
                        $li['content'] = '密码：' . $data['password'] . '<br><a href="' . $reply_url . '">点击直达</a>';
                        pdo_insert('basic_reply', $li);
                        pdo_update('cyl_vip_video_manage', array('rid' => $rid), array('id' => $id));
                    }
                }
                message('更新成功', $this->createWebUrl('manage'), 'success');
            }
        }
        if ($op == 'huoqu')
        {
            $url = $_GPC['url'];
            $url = explode('http://www.360kan.com', $url);
            $data = pc_caiji_detail($url['1']);
            $data = $data['0'];
            echo json_encode($data);
            exit();
        }
        if ($op == 'piliang')
        {
            $piliang = nl2br($_GPC['piliang']);
            foreach ($data as $key => $value)
            {
                var_dump($value);
            }
            include $this->template('piliang');
            exit();
        }
        if ($op == 'delete')
        {
            $id = $_GPC['id'];
            $row = pdo_fetch("SELECT rid FROM " . tablename('cyl_vip_video_manage') . " WHERE id = :id", array(':id' => $id));
            if (!empty($row['rid']))
            {
                pdo_delete('rule', array('id' => $row['rid'], 'uniacid' => $_W['uniacid']));
                pdo_delete('rule_keyword', array('rid' => $row['rid'], 'uniacid' => $_W['uniacid']));
                pdo_delete('basic_reply', array('rid' => $row['rid']));
            }
            $res = pdo_delete('cyl_vip_video_manage', array('id' => $id));
            if ($res)
            {
                message('删除成功！', $this->createWebUrl('manage'), 'success');
            }
        }
        if ($op == 'shenhe')
        {
            $id = $_GPC['id'];
            $res = pdo_update('cyl_vip_video_message', array('status' => 1), array('id' => $id));
            if ($res)
            {
                message('审核成功！', $this->createWebUrl('manage', array('op' => 'comment')), 'success');
            }
        }
        if ($op == 'comment_del')
        {
            $id = $_GPC['id'];
            $res = pdo_delete('cyl_vip_video_message', array('id' => $id));
            if ($res)
            {
                message('删除成功！', $this->createWebUrl('manage', array('op' => 'comment')), 'success');
            }
        }
        include $this->template('manage');
    }
    public function doWebMember()
    {
        global $_W, $_GPC;
        $op = $_GPC['op'] ?$_GPC['op'] : 'member';
        if ($op == 'member')
        {
            $pageindex = max(intval($_GPC['page']), 1);
            $pagesize = 20;
            $starttime = empty($_GPC['time']['start']) ?strtotime('-90 days') : strtotime($_GPC['time']['start']);
            $endtime = empty($_GPC['time']['end']) ?TIMESTAMP + 86399 : strtotime($_GPC['time']['end']) + 86399;
            $where = ' WHERE uniacid = :uniacid AND time >= :starttime AND time <= :endtime';
            $params = array(':uniacid' => $_W['uniacid'],
                ':starttime' => $starttime,
                ':endtime' => $endtime
                );
            if ($_GPC['keyword'])
            {
                $where .= ' AND nickname LIKE :keyword ';
                $params[':keyword'] = "%{$_GPC['keyword']}%";
            }
            if ($_GPC['is_pay'])
            {
                $where .= ' AND is_pay = :is_pay ';
                $params[':is_pay'] = "{$_GPC['is_pay']}";
            }
            if ($_GPC['is_pay'] == 2)
            {
                $where .= ' AND is_pay = :is_pay ';
                $params[':is_pay'] = 0;
            }
            $total = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('cyl_vip_video_member') . $where , $params);
            $today_member = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('cyl_vip_video_member') . $where , array(':uniacid' => $_W['uniacid'], ':starttime' => strtotime(date('Y-m-d')), ':endtime' => TIMESTAMP));
            $total_member = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('cyl_vip_video_member') . " WHERE uniacid = :uniacid AND is_pay = :is_pay ", array(':uniacid' => $_W['uniacid'], ':is_pay' => 1));
            $pager = pagination($total, $pageindex, $pagesize);
            $sql = ' SELECT * FROM ' . tablename('cyl_vip_video_member') . $where . ' ORDER BY old_time DESC , id DESC LIMIT ' . (($pageindex -1) * $pagesize) . ',' . $pagesize;
            $list = pdo_fetchall($sql, $params, 'id');
        }
        if ($op == 'add')
        {
            $id = $_GPC['id'];
            if ($id)
            {
                $item = pdo_get('cyl_vip_video_member', array('id' => $id));
            }
            if (checksubmit())
            {
                $data = $_GPC['data'];
                $data['avatar'] = $_GPC['avatar'];
                $data['end_time'] = strtotime($_GPC['end_time']);
                pdo_update('cyl_vip_video_member', $data, array('id' => $id));
                message('更新成功', $this->createWebUrl('member'), 'success');
            }
        }
        if ($op == 'delete')
        {
            $id = $_GPC['id'];
            pdo_delete('cyl_vip_video_member', array('id' => $id));
            message('删除成功', $this->createWebUrl('member'), 'success');
        }
        include $this->template('member');
    }
    public function doWebOrder()
    {
        global $_W, $_GPC;
        load()->model('mc');
        $op = $_GPC['op'];
        $settings = $this->module['config'];
        $starttime = empty($_GPC['time']['start']) ?strtotime('-90 days') : strtotime($_GPC['time']['start']);
        $endtime = empty($_GPC['time']['end']) ?TIMESTAMP + 86399 : strtotime($_GPC['time']['end']) + 86399;
        $pindex = max(intval($_GPC['page']), 1);
        $psize = 20;
        $condition = ' WHERE uniacid=:uniacid AND time >= :starttime AND time <= :endtime ';
        $params = array(':uniacid' => $_W['uniacid'],
            ':starttime' => $starttime,
            ':endtime' => $endtime
            );
        if ($_GPC['status'])
        {
            $condition .= ' AND status = :status ';
            $params[':status'] = $_GPC['status'];
        }
        $sql = ' SELECT * FROM ' . tablename('cyl_vip_video_order') . $condition . ' ORDER BY id DESC LIMIT ' . (($pindex -1) * $psize) . ',' . $psize;
        $list = pdo_fetchall($sql, $params, 'id');
        $total_amount = pdo_fetchcolumn('SELECT SUM(fee) as fee FROM ' . tablename('cyl_vip_video_order') . $condition . " AND status = 1 AND tid != '积分兑换' ", $params);
        $total = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('cyl_vip_video_order') . $condition, $params);
        $pager = pagination($total, $pindex, $psize);
        if ($op == 'qingli')
        {
            pdo_delete('cyl_vip_video_order', array('status' => 0, 'uniacid' => $_W['uniacid']));
            message('清理成功', $this->createWebUrl('order'), 'success');
        }
        include $this->template('order');
    }
    public function doWebHdp()
    {
        global $_W, $_GPC;
        $op = $_GPC['op'] ?$_GPC['op'] : 'list';
        if ($op == 'list')
        {
            if (checksubmit('submit'))
            {
                if (!empty($_GPC['sort']))
                {
                    foreach($_GPC['sort'] as $key => $d)
                    {
                        pdo_update('cyl_vip_video_hdp', array('sort' => $d), array('id' => $_GPC['id'][$key]));
                    }
                    message('批量更新排序成功！', $this->createWebUrl('hdp', array('op' => 'list')), 'success');
                }
            }
            $list = pdo_fetchall("SELECT * FROM " . tablename('cyl_vip_video_hdp') . " WHERE uniacid = '{$_W['uniacid']}' $condition ORDER BY sort DESC,id DESC");
        }elseif ($op == 'post')
        {
            $id = intval($_GPC['id']);
            $adv = pdo_fetch("select * from " . tablename('cyl_vip_video_hdp') . " where id=:id and uniacid=:uniacid limit 1", array(":id" => $id, ":uniacid" => $_W['uniacid']));
            if (checksubmit('submit'))
            {
                $data = array('uniacid' => $_W['uniacid'],
                    'sort' => $_GPC['sort'],
                    'title' => $_GPC['title'],
                    'thumb' => $_GPC['thumb'],
                    'type' => $_GPC['type'],
                    'link' => $_GPC['link'],
                    'out_link' => $_GPC['out_link']
                    );
                $link = explode('http://www.360kan.com', $data['link']);
                if (count($link) == 1)
                {
                    $data['link'] = $data['link'];
                }
                else
                {
                    $data['link'] = $link['1'];
                }
                if (!empty($id))
                {
                    pdo_update('cyl_vip_video_hdp', $data, array('id' => $id));
                }
                else
                {
                    pdo_insert('cyl_vip_video_hdp', $data);
                    $id = pdo_insertid();
                }
                message('更新幻灯片成功！', $this->createWebUrl('hdp', array('op' => 'list')), 'success');
            }
        }elseif ($op == 'delete')
        {
            $id = intval($_GPC['id']);
            $adv = pdo_fetch("SELECT id  FROM " . tablename('cyl_vip_video_hdp') . " WHERE id = '$id' AND uniacid=" . $_W['uniacid'] . "");
            if (empty($adv))
            {
                message('抱歉，幻灯片不存在或是已经被删除！', $this->createWebUrl('hdp', array('op' => 'display')), 'error');
            }
            pdo_delete('cyl_vip_video_hdp', array('id' => $id));
            message('幻灯片删除成功！', $this->createWebUrl('hdp', array('op' => 'list')), 'success');
        }
        include $this->template('hdp');
    }
    public function doWebCategory()
    {
        global $_GPC, $_W;
        load()->func('tpl');
        $operation = !empty($_GPC['op']) ?$_GPC['op'] : 'display';
        if ($operation == 'display')
        {
            if (!empty($_GPC['displayorder']))
            {
                foreach ($_GPC['displayorder'] as $id => $displayorder)
                {
                    pdo_update('cyl_vip_video_category', array('displayorder' => $displayorder), array('id' => $id, 'uniacid' => $_W['uniacid']));
                }
                message('分类排序更新成功！', $this->createWebUrl('category', array('op' => 'display')), 'success');
            }
            $children = array();
            $category = pdo_fetchall("SELECT * FROM " . tablename('cyl_vip_video_category') . " WHERE uniacid = '{$_W['uniacid']}' ORDER BY parentid ASC, displayorder DESC");
            foreach ($category as $index => $row)
            {
                if (!empty($row['parentid']))
                {
                    $children[$row['parentid']][] = $row;
                    unset($category[$index]);
                }
            }
            include $this->template('category');
        }elseif ($operation == 'post')
        {
            $parentid = intval($_GPC['parentid']);
            $id = intval($_GPC['id']);
            if (!empty($id))
            {
                $category = pdo_fetch("SELECT * FROM " . tablename('cyl_vip_video_category') . " WHERE id = :id AND uniacid = :weid", array(':id' => $id, ':weid' => $_W['uniacid']));
            }
            else
            {
                $category = array('displayorder' => 0,
                    );
            }
            if (!empty($parentid))
            {
                $parent = pdo_fetch("SELECT * FROM " . tablename('cyl_vip_video_category') . " WHERE id = '$parentid'");
                if (empty($parent))
                {
                    message('抱歉，上级分类不存在或是已经被删除！', $this->createWebUrl('post'), 'error');
                }
            }
            if (checksubmit('submit'))
            {
                if (empty($_GPC['catename']))
                {
                    message('抱歉，请输入分类名称！');
                }
                $data = array('uniacid' => $_W['uniacid'],
                    'name' => $_GPC['catename'],
                    'url' => $_GPC['url'],
                    'displayorder' => intval($_GPC['displayorder']),
                    'parentid' => intval($parentid),
                    );
                if (!empty($id))
                {
                    unset($data['parentid']);
                    pdo_update('cyl_vip_video_category', $data, array('id' => $id, 'uniacid' => $_W['uniacid']));
                    load()->func('file');
                    file_delete($_GPC['thumb_old']);
                }
                else
                {
                    pdo_insert('cyl_vip_video_category', $data);
                    $id = pdo_insertid();
                }
                message('更新分类成功！', $this->createWebUrl('category', array('op' => 'display')), 'success');
            }
            include $this->template('category');
        }elseif ($operation == 'delete')
        {
            $id = intval($_GPC['id']);
            $category = pdo_fetch("SELECT id, parentid FROM " . tablename('cyl_vip_video_category') . " WHERE id = '$id'");
            if (empty($category))
            {
                message('抱歉，分类不存在或是已经被删除！', $this->createWebUrl('category', array('op' => 'display')), 'error');
            }
            pdo_delete('cyl_vip_video_category', array('id' => $id, 'parentid' => $id), 'OR');
            message('分类删除成功！', $this->createWebUrl('category', array('op' => 'display')), 'success');
        }
    }
    public function doWebAd()
    {
        global $_W, $_GPC;
        /**
         * $list = kan360_list(3, 6);
         * $list = $list['data']['res'];
         * $html = kan360('7JN4pybmYNvX');
         * $html = json_decode($html['content'], true);
         * $html = $html['data'];
         */
        $op = $_GPC['op'] ?$_GPC['op'] : 'list';
        if ($op == 'list')
        {
            $list = pdo_fetchall("SELECT * FROM " . tablename('cyl_vip_video_ad') . " WHERE uniacid = '{$_W['uniacid']}' $condition ORDER BY sort DESC,id DESC");
        }elseif ($op == 'post')
        {
            $id = intval($_GPC['id']);
            $adv = pdo_fetch("select * from " . tablename('cyl_vip_video_ad') . " where id=:id and uniacid=:uniacid limit 1", array(":id" => $id, ":uniacid" => $_W['uniacid']));
            if (checksubmit('submit'))
            {
                $data = array('uniacid' => $_W['uniacid'],
                    'sort' => $_GPC['sort'],
                    'thumb' => $_GPC['thumb'],
                    'second' => $_GPC['second'],
                    'link' => $_GPC['link'],
                    'status' => $_GPC['status'],
                    );
                $data['end_time'] = strtotime($_GPC['end_time']);
                if (!empty($id))
                {
                    pdo_update('cyl_vip_video_ad', $data, array('id' => $id));
                }
                else
                {
                    pdo_insert('cyl_vip_video_ad', $data);
                    $id = pdo_insertid();
                }
                message('更新成功！', $this->createWebUrl('ad', array('op' => 'list')), 'success');
            }
        }elseif ($op == 'delete')
        {
            $id = intval($_GPC['id']);
            $adv = pdo_fetch("SELECT id  FROM " . tablename('cyl_vip_video_ad') . " WHERE id = '$id' AND uniacid=" . $_W['uniacid'] . "");
            if (empty($adv))
            {
                message('抱歉，不存在或是已经被删除！', $this->createWebUrl('hdp', array('op' => 'display')), 'error');
            }
            pdo_delete('cyl_vip_video_ad', array('id' => $id));
            message('删除成功！', $this->createWebUrl('ad', array('op' => 'list')), 'success');
        }
        include $this->template('ad');
    }
    public function doWebCard()
    {
        global $_W, $_GPC;
        $op = $_GPC['op'] ?$_GPC['op'] : 'display';
        $id = $_GPC['id'];
        $card = pdo_get('cyl_vip_video_keyword', array('id' => $id), array() , '', 'id DESC');
        if ($op == 'display')
        {
            $pageindex = max(intval($_GPC['page']), 1);
            $pagesize = 20;
            $where = ' WHERE uniacid = :uniacid ';
            $params = array(':uniacid' => $_W['uniacid'],
                );
            $total = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('cyl_vip_video_keyword') . $where , $params);
            $pager = pagination($total, $pageindex, $pagesize);
            $sql = ' SELECT * FROM ' . tablename('cyl_vip_video_keyword') . $where . ' ORDER BY id DESC LIMIT ' . (($pageindex -1) * $pagesize) . ',' . $pagesize;
            $list = pdo_fetchall($sql, $params, 'id');
        }
        if ($op == 'post')
        {
            if (checksubmit('submit'))
            {
                $data = $_GPC['data'];
                if (empty($data['card_id']))
                {
                    message('抱歉，请输入卡密标识！');
                }
                $data['uniacid'] = $_W['uniacid'];
                $card = card($_GPC['weishu'], $data['num']);
                pdo_insert('cyl_vip_video_keyword', $data);
                $id = pdo_insertid();
                foreach ($card as $value)
                {
                    pdo_insert('cyl_vip_video_keyword_id', array('card_id' => $id, 'pwd' => $data['card_id'] . $value, 'uniacid' => $_W['uniacid'], 'day' => $data['day']));
                }
                message('生成成功！', $this->createWebUrl('card'), 'success');
            }
        }
        if ($op == 'delete')
        {
            $id = intval($_GPC['id']);
            pdo_delete('cyl_vip_video_keyword_id', array('card_id' => $id));
            pdo_delete('cyl_vip_video_keyword', array('id' => $id));
            message('删除成功！', $this->createWebUrl('card'), 'success');
        }
        if ($op == 'card')
        {
            $pageindex = max(intval($_GPC['page']), 1);
            $pagesize = 20;
            $where = ' WHERE uniacid = :uniacid AND card_id = :card_id ';
            $params = array(':uniacid' => $_W['uniacid'],
                ':card_id' => $id,
                );
            $total = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('cyl_vip_video_keyword_id') . $where , $params);
            $pager = pagination($total, $pageindex, $pagesize);
            $sql = ' SELECT * FROM ' . tablename('cyl_vip_video_keyword_id') . $where . ' ORDER BY id DESC LIMIT ' . (($pageindex -1) * $pagesize) . ',' . $pagesize;
            $list = pdo_fetchall($sql, $params, 'id');
            if (checksubmit('export_submit', true))
            {
                $sql = "SELECT * FROM " . tablename('cyl_vip_video_keyword_id') . $where . " ORDER BY id DESC";
                $listexcel = pdo_fetchall($sql, $params);
                $header = array('card_id' => '卡密名称',
                    'pwd' => '卡密密码',
                    'status' => '使用状态',
                    );
                $keys = array_keys($header);
                $html = "\xEF\xBB\xBF";
                foreach($header as $li)
                {
                    $html .= $li . "\t ";
                }
                $html .= "\n";
                if (!empty($listexcel))
                {
                    $size = ceil(count($listexcel) / 500);
                    for($i = 0;$i < $size;$i++)
                    {
                        $buffer = array_slice($listexcel, $i * 500, 500);
                        foreach($buffer as $row)
                        {
                            $row['card_id'] = $card['title'];
                            $row['status'] = $card['status'] ?'已兑换': '未兑换';
                            foreach($keys as $key)
                            {
                                $data[] = $row[$key];
                            }
                            $user[] = implode("\t ", $data) . "\t ";
                            unset($data);
                        }
                    }
                    $html .= implode("\n", $user);
                }
                header("Pragma: public");
                header("Expires: 0");
                header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
                header("Content-Type:application/force-download");
                header("Content-Type:application/vnd.ms-execl");
                header("Content-Type:application/octet-stream");
                header("Content-Type:application/download");;
                header('Content-Disposition:attachment;filename="卡密.xls"');
                header("Content-Transfer-Encoding:binary");
                echo $html;
                exit();
            }
        }
        include $this->template('card');
    }
    public function doWebClean()
    {
        global $_W, $_GPC;
        pdo_delete('cyl_vip_video_share', array('uniacid' => $_W['uniacid']));
        message('清理成功', '', 'success');
    }
}

?>