<?php
/*
 * ECMS7.0 for UEditor1.4.0 develop
 * controller.php
 * UEditor1.4.0未发布正试版本，我会持续更新
 * pkkgu 910111100@qq.com
 *  2014年5月9日 15:46:27

	ue.ready(function(){
		ue.execCommand('serverparam', {
			'classid': '<?=$classid?>',
			'filepass': '<?=$filepass?>',
			'isadmin': '<?=$isadmin?>', //0前台 1后台
			'userid': '<?=$userid?>',
			'username': '<?=$username?>',
			'rnd': '<?=$rnd?>'
		});
	});

 */
require('../../../class/connect.php'); //引入数据库配置文件和公共函数文件
require('../../../class/db_sql.php'); //引入数据库操作文件
require("../../../data/dbcache/class.php");

$link=db_connect(); //连接MYSQL
$empire=new mysqlquery(); //声明数据库操作类
$editor=1; //声明目录层次

// 必须参数
$action      = $_GET['action'];
$classid     = (int)$_GET['classid'];
$filepass    = (int)$_GET['filepass'];
$isadmin     = (int)$_GET['isadmin']; // 0前台 1后台
$userid      = (int)$_GET['userid'];
$username    = RepPostVar($_GET['username']);
$rnd         = RepPostVar($_GET['rnd']);
$loginin     = $isadmin?$username:'[Member]'.$username;

// 配置
$CONFIG = json_decode(preg_replace("/\/\*[\s\S]+?\*\//", "", file_get_contents("config.json")), true);


$isadmin     = (int)$_POST['isadmin']; // 0前台 1后台
if(empty($action))
{
    Ue_Print('请求类型不能明确');
}
else if(empty($classid)||empty($filepass)||empty($userid)||empty($username))
{
    Ue_Print("上传参数不正确！栏目ID：$classid，信息ID：$filepass，会员ID：$userid，会员名称：$username");
}
$pr=$empire->fetch1("select * from {$dbtbpre}enewspublic");
if(empty($isadmin)) // 前台
{
    if($pr['addnews_ok']==1)
    {
        Ue_Print("管理员关闭了投稿功能");
    }
    else if(($action=='uploadimage'||$action=='uploadscrawl'||$action=='catchimage')&&empty($pr['qaddtran']))
    {
        Ue_Print("管理员关闭了会员上传图片功能");
    }
    else if(($action=='uploadvideo'||$action=='uploadfile')&&empty($pr['qaddtranfile']))
    {
        Ue_Print("管理员关闭了会员上传图片功能");
    }
    $qaddtransize = $pr['qaddtransize']*1024;
    $CONFIG['imageMaxSize'] = $qaddtransize;
    $CONFIG['scrawlMaxSize'] = $qaddtransize;
    $CONFIG['catcherMaxSize'] = $qaddtransize;
    $qaddtranimgtype = substr($pr['qaddtranimgtype'],1,strlen($pr['qaddtranimgtype'])-2);
    $qaddtranimgtype = explode('|',$qaddtranimgtype);
    $CONFIG['imageAllowFiles'] = $qaddtranimgtype;
    $CONFIG['imageManagerAllowFiles'] = $qaddtranimgtype;
    $CONFIG['catcherAllowFiles'] = $qaddtranimgtype;
    
    $qaddtranfilesize = $pr['qaddtranfilesize']*1024;
    $CONFIG['fileMaxSize'] = $qaddtranfilesize;
    $CONFIG['videoMaxSize'] = $qaddtranfilesize;
    $qaddtranfiletype = substr($pr['qaddtranfiletype'],1,strlen($pr['qaddtranfiletype'])-2);
    $qaddtranfiletype = explode('|',$qaddtranfiletype);
    $CONFIG['fileAllowFiles'] = $qaddtranfiletype;
    $CONFIG['fileManagerAllowFiles'] = $qaddtranfiletype;
    $CONFIG['videoAllowFiles'] = array(".flv",".swf",".mkv",".avi",".rm",".rmvb",".mpeg",".mpg",".ogg",".ogv",".mov",".wmv",".mp4",".webm",".mp3",".wav",".mid");
}
else if($isadmin==1) // 后台
{
    $filesize = $pr['filesize']*1024;
    $CONFIG['imageMaxSize'] = $filesize;
    $CONFIG['scrawlMaxSize'] = $filesize;
    $CONFIG['catcherMaxSize'] = $filesize;
    $CONFIG['fileMaxSize'] = $filesize;
    $CONFIG['videoMaxSize'] = $filesize;
}

    //目录
    $classpath   = ReturnFileSavePath($classid); //栏目附件目录
    $timepath    = "/".$classpath['filepath']."{yyyy}-{mm}-{dd}/{time}{rand:6}"; //日期栏目目录
    // 重定义存放目录
    $CONFIG['imagePathFormat']      = $timepath;
    $CONFIG['scrawlPathFormat']     = $timepath;
    $CONFIG['videoPathFormat']      = $timepath;
    $CONFIG['filePathFormat']       = $timepath;
    $CONFIG['imageManagerListPath'] = "/".$classpath['filepath'];
    $CONFIG['fileManagerListPath']  = "/".$classpath['filepath'];
    $CONFIG['catcherPathFormat']    = $timepath;
    
    switch ($action) {
        case 'config':
            $result = json_encode($CONFIG);
            break;
    
        /* 上传图片 */
        case 'uploadimage':
            $type=1;
            $result = include("action_upload.php");
            break;
    
        /* 上传涂鸦 */
        case 'uploadscrawl':
            $type=1;
            $result = include("action_upload.php");
            break;
    
        /* 上传视频 */
        case 'uploadvideo':
            $type=3;
            $result = include("action_upload.php");
            break;
    
        /* 上传文件 */
        case 'uploadfile':
            $type=0;
            $result = include("action_upload.php");
            break;
    
        /* 列出图片 */
        case 'listimage':
            $result = include("action_list.php");
            break;
        /* 列出文件 */
        case 'listfile':
            $result = include("action_list.php");
            break;
    
        /* 抓取远程文件 */
        case 'catchimage':
            $type=1;
            $result = include("action_crawler.php");
            break;
    
        default:
            $result = json_encode(array('state'=> '请求地址出错'));
            break;
    }

    // 文件名、文件大小，存放日期目录，上传者，栏目id,文件编号,文件类型,信息ID,文件临时识别编号(原文件名称),文件存放目录方式,信息公共ID,归属类型,附件副表ID
    // 文件类型:1为图片，2为Flash文件，3为多媒体文件，0为附件
    // 归属类型:0信息，4反馈，5公共，6会员，其他
    // 文件临时识别编号:0非垃圾信息
    // 文件存放目录方式:0为栏目目录，1为/d/file/p目录，2为/d/file目录
    
    //写入数据库
    $file_r   = json_decode($result,true);
    if(($action=="uploadimage"||$action=="uploadscrawl"||$action=="uploadvideo"||$action=="uploadfile")&&$file_r['state']=="SUCCESS")
    {
        $title    = RepPostStr(trim($file_r[title]));
        $filesize = (int)$file_r[size];
        $filepath = date("Y-m-d");
        $username = RepPostStr(trim($username));
        $loginin  = $isadmin?$username:'[Member]'.$username;        
        $classid  = (int)$classid;
        $original = RepPostStr(trim($file_r[original]));
        $type     = (int)$type;
        $filepass = (int)$filepass;
        eInsertFileTable($title,$filesize,$filepath,$username,$classid,$original,$type,$filepass,$filepass,$public_r[fpath],0,0,0);

        // 反馈附件入库
        //eInsertFileTable($tfr[filename],$filesize,$filepath,'[Member]'.$username,$classid,'[FB]'.addslashes(RepPostStr($add[title])),$type,$filepass,$filepass,$public_r[fpath],0,4,0);
    }

/* 输出结果 */
if (isset($_GET["callback"])) {
    echo $_GET["callback"] . '(' . $result . ')';
} else {
    echo $result;
}


db_close(); //关闭MYSQL链接
$empire=null; //注消操作类变量

// 提示
function Ue_Print($msg="SUCCESS"){
    echo '{"state": "'.$msg.'"}';
    db_close(); //关闭MYSQL链接
    $empire=null; //注消操作类变量
    exit();
}