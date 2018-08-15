<?php
include IA_ROOT . "/addons/cyl_vip_video/QL/QueryList.class.php";
use QL\QueryList;
load()->func('communication');
function caiji_list($keyword)
{
    $url = 'https://so.360kan.com/index.php?kw='.$keyword;
	$html = file_get_contents($url);
    $data = QueryList::Query($html, array(
            'link' => array('.b-mainpic a', 'href', '', function($link)
                {
                    $link = explode('http://www.360kan.com', $link);
                    return $link['1'];
                }
                ),
            'title' => array('.title a', 'text', '', function($title)
                {
                    if ($title)
                    {
                        return $title;
                    }
                }
                ),
            'p1' => array('ul:first', 'text'),
            'p2' => array('ul:eq(1)', 'text'),
            'p3' => array('ul:eq(2) li:first', 'text'),
            'actor' => array('ul:eq(2) .actor', 'text'),
            'director' => array('ul:eq(2) .director', 'text'),
            'btn' => array('.button-container', 'text'),
            'img' => array('img', 'src'),
            'type' => array('h3 span', 'text'),
            'tabs-items' => array('.active a:eq(0)', 'text'),
            ), '#js-longvideo .g-clear')->data;
    return $data;
}
function dianying($num, $type)
{
    $url = "http://m.360kan.com/list/" . $type . "Data?pageno=$num";
    $html = file_get_contents($url);
    $htmll = json_decode($html, true);
    $data = QueryList::Query($htmll['data']['list'], array(
            'link' => array('li a', 'href'),
            'html' => array('li a', 'html'),
            ))->data;
    return $data;
}
function caiji($url)
{
    $url = 'http://m.360kan.com' . $url;
    $html = file_get_contents($url);
    $data = QueryList::Query($html, array(
            'nav' => array('.b-nav', 'text'),
            'title' => array('.cp-detail-box', 'html'),
            'play' => array('.btn-play', 'href'),
            'desc' => array('.cp-detail-description', 'html'),
            ), '.p-body')->data;
    return $data;
}
function pc_caiji_detail($url)
{
    $url = 'http://www.360kan.com' . $url;
    $data = QueryList::Query($url, array(
            'nav' => array('.b-nav', 'text'),
            'title' => array('h1', 'text'),
            'star' => array('.s', 'text'),
            'thumb' => array('.top-left img', 'src'),
            'director' => array('#js-desc-switch p:eq(3)','text'),
            'year' => array('#js-desc-switch p:eq(0)', 'text'),
            'area' => array('#js-desc-switch p:eq(1)', 'text'),
            'type' => array('.tag', 'text'),
            'actor' => array('#js-desc-switch p:eq(2)', 'text'),
            'desc' => array('.js-close-wrap', 'text'),
            ), '.p-top')->data;
    if (empty($data))
    {
    $data = QueryList::Query($url, array(
            'nav' => array('.b-nav', 'text'),
            'title' => array('h1', 'text'),
            'star' => array('.s', 'text'),
            'thumb' => array('.top-left img', 'src'),
            'director' => array('#js-desc-switch p:eq(4)','text'),
            'year' => array('#js-desc-switch p:eq(0)', 'text'),
            'area' => array('#js-desc-switch p:eq(1)', 'text'),
            'type' => array('.tag', 'text'),
            'actor' => array('#js-desc-switch p:eq(5)', 'text'),
            'desc' => array('.js-close-wrap', 'text'),
            ), '.s-top')->data;
    }
    return $data;
}
function pc_caiji_detail_tuijian($url)
{
    $url = 'http://www.360kan.com' . $url;
    $data = QueryList::Query($url, array(
            'title' => array('.s1', 'text'),
            'link' => array('a', 'href'),
            'thumb' => array('img', 'data-src'),
            ), '.tuijian:eq(1) .tuijian-list li')->data;
    return $data;
}
function pc_caiji_detail_daoyan($url)
{
    $url = 'http://www.360kan.com' . $url;
    $data = QueryList::Query($url, array(
            'title' => array('.s1', 'text'),
            'link' => array('a', 'href'),
            'thumb' => array('img', 'data-src'),
            ), '.tuijian:eq(0) .tuijian-list li')->data;
    return $data;
}
function str_substr($start, $end, $str)
{
    $temp = explode($start, $str, 2);
    $content = explode($end, $temp[1], 2);
    return $content[0];
}
function caiji_url($url)
{
    $url1 = 'http://www.360kan.com' . $url;
      $data = QueryList::Query($url1, array(
            'link' => array('.top-list-zd:eq(1) a', 'href'),
            'title' => array('.top-list-zd:eq(1) a', 'text'),
            ))->data;
    if (empty($data))
    {
        $data = QueryList::Query($url1, array(
                'link' => array('.top-list-zd a', 'href'),
                'title' => array('.top-list-zd a', 'text'),
                ))->data;
    }
	  if (empty($data))
    {
		$site = site();
		$id = explode('/', str_substr('/', '.', $url));
		foreach($site as $k => $v)
		{
	    $url = 'http://www.360kan.com/cover/switchsite?site=' . $k . '&id=' . $id['1'] . '&category=2';
	    $html = file_get_contents($url);
		$html = json_decode($html, true);
        $html = $html['data'];
        if ($html)
	    {
		$data[] =['link'=>'#','title'=>$v]; 
	    }	
	
		}
    }
	return $data;
}

function juji_url($url, $site)
{
    $id = explode('/', str_substr('/', '.', $url));
    $url = 'http://www.360kan.com/cover/switchsite?site=' . $site['0'] . '&id=' . $id['1'] . '&category=2';
    $html = file_get_contents($url);
    $html = json_decode($html, true);
    $html = $html['data'];
    if (empty($html))
    {
        $url = 'http://www.360kan.com/cover/switchsite?site=leshi&id=' . $id['1'] . '&category=2';
        $html = file_get_contents($url);
        $html = json_decode($html, true);
        $html = $html['data'];
    }
	
    $data = QueryList::Query($html, array(
            'link' => array('.num-tab-main:eq(1) a', 'href'),
            ))->data;

    if (empty($data))
    {
        $data = QueryList::Query($html, array(
                'link' => array('.js-tab a', 'href'),
                'jishu' => array('.js-tab a', 'text'),
                ))->data;
    }
    return $data;
}
function dongman_url($url, $site)
{
    $id = explode('/', str_substr('/', '.', $url));
    if ($site['0'] == 'levp')
    {
        $site['0'] = 'leshi';
    }
    $url = 'http://www.360kan.com/cover/switchsite?site=' . $site['0'] . '&id=' . $id['1'] . '&category=4';
    $html = file_get_contents($url);
    $html = json_decode($html, true);
    $html = $html['data'];
    $data = QueryList::Query($html, array(
            'link' => array('.num-tab-main a', 'href'),
            ))->data;
    if (count($data) < 100)
    {
        $data = QueryList::Query($html, array(
                'link' => array('.num-tab-main a', 'href'),
                ))->data;
    }
    else
    {
        $data = QueryList::Query($html, array(
                'link' => array('.num-tab-main:gt(0) a', 'href'),
                'jishu' => array('.num-tab-main:gt(0) a', 'text'),
                ))->data;
    }
    return $data;
}
function zongyi_url($url)
{
    $url = 'http://www.360kan.com' . $url;
    $data = QueryList::Query($url, array(
            'link' => array('.zd-down a', 'href'),
            'title' => array('.zd-down a', 'text'),
            ))->data;
    if (empty($data))
    {
        $data = QueryList::Query($url, array(
                'link' => array('.ea-site', 'href'),
                'title' => array('#js-siteact .ea-site', 'text'),
                ))->data;
    }
    return $data;
}
function zongyi_year_url($url)
{
    $url = 'http://www.360kan.com' . $url;
    $html = file_get_contents($url);
    $data = QueryList::Query($html, array(
            'date' => array('#js-year a', 'text'),
            ))->data;
    return $data;
}
function zongyi_juji_url($url, $site, $year = 'false')
{
    $id = explode('/', str_substr('/', '.', $url));
    if ($site['0'] == 'levp')
    {
        $site['0'] = 'leshi';
    }
    if ($year == 'false')
    {
        $year = 'isByTime=false';
    }
    else
    {
        $year = 'year=' . $year;
    }
    $url = 'http://www.360kan.com/cover/zongyilist?id=' . $id['1'] . '&site=' . $site['0'] . '&do=switchyear&' . $year;
    $html = file_get_contents($url);
    $html = json_decode($html, true);
    $html = $html['data'];
    if (!$html)
    {
        if ($site['0'] == 'leshi')
        {
            $site['0'] = 'levp';
        }
        $url = 'http://www.360kan.com/cover/zongyilist?id=' . $id['1'] . '&site=' . $site['0'] . '&do=switchyear&' . $year;
        $html = file_get_contents($url);
        $html = json_decode($html, true);
        $html = $html['data'];
    }
    if (!$html)
    {
        $url = 'http://www.360kan.com' . $url;
        $html = file_get_contents($url);
    }
    $data = QueryList::Query($html, array(
            'link' => array('.js-year-page a', 'href'),
            'year' => array('.js-year-page li .w-newfigure-hint', 'text'),
            'title' => array('.js-year-page li .title', 'text'),
            ))->data;
    return $data;
}
function member($openid)
{
    global $_W, $_GPC;
    $data = array(
        'uniacid' => $_W['uniacid'],
        'openid' => $openid,
        );
    $member = pdo_get('cyl_vip_video_member', $data);
    return $member;
}
function isUrl($s)
{
    return preg_match('/^http[s]?:\/\/' . '(([0-9]{1,3}\.){3}[0-9]{1,3}' . '|' . '([0-9a-z_!~*\'()-]+\.)*' . '([0-9a-z][0-9a-z-]{0,61})?[0-9a-z]\.' . '[a-z]{2,6})' . '(:[0-9]{1,4})?' . '((\/\?)|' . '(\/[0-9a-zA-Z_!~\*\'\(\)\.;\?:@&=\+\$,%#-\/]*)?)$/',
        $s) == 1;
}
function category()
{
    $data = array(
        'dianying' => '电影',
        'dianshi' => '电视',
        'zongyi' => '综艺',
        'dongman' => '动漫',
        );
    return $data;
}
function site()
{
    $data = array(
        'youku' => '优酷',
        'sohu' => '搜狐',
        'qq' => '腾讯',
        'pptv' => 'pptv',
        'imgo' => '芒果TV',
        'levp' => '乐视',
        'leshi' => '乐视',
        'fengxing' => '风行',
        'qiyi' => '爱奇艺',
        'huashu' => '华数TV',
        'tudou' => '土豆',
        'cntv' => 'CNTV',
        'pptv' => 'PPTV',
        'bilibili' => '哔哩哔哩',
        'kankan' => '看看',
        'zgltv' => '中国蓝TV',
        'yingshi360'=> '360影视');
    return $data;
}
function discover($url)
{
   /* $link = $_SERVER["HTTP_HOST"];
    $loginurl = 'http://cyl.go8goo.com/caiji/caiji.php?link=' . $link . '&url=' . $url;
    $response = ihttp_get($loginurl);
    return json_decode($response['content'], true);
    */
    $data = QueryList::Query($url, array('link' => array('.item .js-tongjic', 'href'), 'img' => array('.list li img', 'src'), 'hint' => array('.hint', 'text'), 's2' => array('.item .s2', 'text'), 'title' => array('.item .s1', 'text'), 'star' => array('.item .star', 'text'),))->data;

    return $data;

}
function category_list($url)
{
    /*$loginurl = 'http://cyl.go8goo.com/caiji/category_list.php?url=' . $url;
    $response = ihttp_get($loginurl);
    return json_decode($response['content'], true);
    */
    $array = array();
     $category = QueryList::Query($url, array('link' => array('.s-filter dl:eq(1) dd a', 'href'), 'title' => array('.s-filter dl:eq(1) dd a', 'text'), 'on' => array('.s-filter dl:eq(1) dd b', 'text'),))->data;
    $year = QueryList::Query($url, array('link' => array('.s-filter dl:eq(2) dd a', 'href'), 'title' => array('.s-filter dl:eq(2) dd a', 'text'), 'on' => array('.s-filter dl:eq(2) dd b', 'text'),))->data;
    $area = QueryList::Query($url, array('link' => array('.s-filter dl:eq(3) dd a', 'href'), 'title' => array('.s-filter dl:eq(3) dd a', 'text'), 'on' => array('.s-filter dl:eq(3) dd b', 'text'),))->data;
    $star = QueryList::Query($url, array('link' => array('.s-filter dl:eq(4) dd a', 'href'), 'title' => array('.s-filter dl:eq(4) dd a', 'text'), 'on' => array('.s-filter dl:eq(4) dd b', 'text'),))->data;
    $array = array($category, $year, $area, $star);
    return $array;
}
function category_index($rank)
{
    $url = "http://www.360kan.com/" . $rank . "/list.php?cat=all&year=all&area=all&act=all&rank=";
    $category = QueryList::Query($url, array(
            'link' => array('.s-filter dl:eq(1) dd a', 'href'),
            'title' => array('.s-filter dl:eq(1) dd a', 'text'),
            'on' => array('.s-filter dl:eq(1) dd b', 'text'),
            ))->data;
    return $category;
}
function index_rank($rank)
{
    $url = "http://video.so.com/" . $rank;
    if ($rank == 'dianying')
    {
        $data = QueryList::Query($url, array(
                'link' => array('a', 'href'),
                'title' => array('a', 'text'),
                'click' => array('.vv', 'text'),
                ), '.rank-content>ul>li')->data;
    }elseif ($rank == 'dongman')
    {
        $data = QueryList::Query($url, array(
                'link' => array('a', 'href'),
                'title' => array('.s1', 'text'),
                'click' => array('.w-newfigure-hint', 'text'),
                ), '.content1>ul>li')->data;
    }
    else
    {
        $data = QueryList::Query($url, array(
                'link' => array('a', 'href'),
                'title' => array('.s1', 'text'),
                'click' => array('.w-newfigure-hint', 'text'),
                ), '.content:eq(1)>ul>li')->data;
    }
    return $data;
}
function index_list($url , $r , $rank)
{
    $data = array();
    $url = "http://www.360kan.com/" . $rank . "/list.php?cat=all&year=all&area=all&act=all&rank=" . $r;
    $data = QueryList::Query($url, array(
            'link' => array('.js-tongjic', 'href'),
            'img' => array('img', 'src'),
            'hint' => array('.hint', 'text'),
            's2' => array('.s2', 'text'),
            'title' => array('.s1', 'text'),
            'star' => array('.star', 'text'),
            ), '.s-tab-main>ul>li')->data;
//	file_put_contents(IA_ROOT."/log--res.txt","\n 2old:".(is_array($data) ? json_encode($data, JSON_UNESCAPED_UNICODE) : $data) ,FILE_APPEND);		
    return $data;
}
function kan360_list($op, $cid= '', $page= '')
{
    if (!$page)
    {
        $url = "http://www.v1.cn/" . $op;
        $data = QueryList::Query($url, array(
                'link' => array('.tit a', 'href'),
                'img' => array('img', 'src'),
                'hint' => array('.userName', 'text'),
                'title' => array('.tit', 'text'),
                ), '.colConBox>ul>li')->data;
    }
    else
    {
        $data = array();
        $loginurl = 'http://www.v1.cn/index/getList4Ajax?cid=' . $cid . '&page=' . $page;
        $response = ihttp_get($loginurl);
        $response = $response['content'];
        $response = json_decode($response, true);
		$array = $response['list'];
		//print_r($response);
        foreach ($array as $key => $value)
        {
            $data[] = array(
                'title' => $value['title'],
                'img' => $value['pic'],
                'hint' => $value['nickname'],
                'link' => '',
				'vid' => $value['vid'],
                );
        }
    }
    return $data;
}
function kan360($url='',$vid = '')
{
	if (!empty($url))
	{$url = "http://www.v1.cn" . $url;
    }else{
	 $url = 'http://www.v1.cn/video/'.$vid.'.shtml';	
	}
    $data = QueryList::Query($url, array(
            'title' => array('h2', 'text'),
            'thumb' => array('.videoBox', 'html', '', function($link)
                {
                    $link = str_substr('cover="', '"', $link);
                    return $link;
                }
                ),
            'mp4' => array('.videoBox', 'html', '', function($link)
                {
                    $link = str_substr('videoUrl=', '"', $link);
                    return $link;
                }
                ),
            ), '.mainBox')->data;
//	file_put_contents(IA_ROOT."/kan360.txt","\n 2old:".(is_array($data) ? json_encode($data, JSON_UNESCAPED_UNICODE) : $data) ,FILE_APPEND);		
    return $data['0'];
}
function is_weixin()
{
    $agent = $_SERVER ['HTTP_USER_AGENT'];
    if (!strpos ($agent, "icroMessenger"))
    {
        return false;
    }
    return true;
}
function card($digit = 6, $num = 100)
{
    $numLen = $digit;
    $pwdLen = $digit;
    $c = $num;
    $sNumArr = range(0, 9);
    $sPwdArr = array_merge($sNumArr, range('a', 'z'));
    $cards = array();
    for($x = 0;$x < $c;$x++)
    {
        $tempNumStr = array();
        for($i = 0;$i < $numLen;$i++)
        {
            $tempNumStr[] = array_rand($sNumArr);
        }
        $tempPwdStr = array();
        for($i = 0;$i < $pwdLen;$i++)
        {
            $tempPwdStr[] = $sPwdArr[array_rand($sPwdArr)];
        }
        $cards[$x] = implode('', $tempPwdStr);
    }
    array_unique($cards);
    return $cards;
}
function trimall($str)
{
    $qian = array(" ", "　", "\t", "\n", "\r");
    $hou = array("", "", "", "", "");
    return str_replace($qian, $hou, $str);
}

?>