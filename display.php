<!DOCTYPE html PUBLIC "-//WAPFORUM//DTD XHTML Mobile 1.0//EN" "http://www.wapforum.org/DTD/xhtml-mobile10.dtd">
<?php 
require_once('SolrUtil.php');
$solrEvent= new SolrUtil('http://127.0.0.1:8983/solr/', 'events');
$solrUser = new SolrUtil('http://127.0.0.1:8983/solr/', 'users');
$userid=$_GET['userid'];
$eventid=$_GET['eventid'];
// $params['index']='user';
// $params['type']='weixin';
// $params['body']['query']['term']['userid']=$userid;
$flag=0;
$user_result=$solrUser->select(array('q'=>"id:$userid"));
if(isset($user_result['data']['response']["docs"][0]["save"]))
{
  $save=$user_result['data']['response']["docs"][0]["save"];
  if(substr_count($save,$eventid)==0)
  {
    $flag=0;
  }
  else
  {
    $flag=1;
  }
}
if(isset($user_result['data']['response']["docs"][0]["address"]))
  {
    $user_address=$user_result['data']['response']["docs"][0]["address"];
    $user_latitude=$user_result['data']['response']["docs"][0]["latitude"];
    $user_longitude=$user_result['data']['response']["docs"][0]["longitude"];
  }
// $param['index']='bird';
// $param['body']['query']['term']['eventid']=$eventid;
// $client->search($param);
$bird_result=$solrEvent->select(array('q'=>"id:$eventid"));

$title=$bird_result['data']['response']["docs"][0]["title"];
$imgSrc=$bird_result['data']['response']["docs"][0]["imgSrc"];
$info=$bird_result['data']['response']["docs"][0]["description"];
$price=$bird_result['data']['response']["docs"][0]["price_info"];
$category=$bird_result['data']['response']["docs"][0]["category"][0];
$time=$bird_result['data']['response']["docs"][0]["happentime"];
$address=$bird_result['data']['response']["docs"][0]["location_description"][0];
$location=explode(',',$bird_result['data']['response']["docs"][0]["location"]);
$latitude=$location[0];
$url=$bird_result['data']['response']["docs"][0]["url"];
if($latitude!="0.0")
$longitude=$location[1];
?>
<html xmlns="http://www.w3.org/1999/xhtml"><head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    
    <meta http-equiv="Cache-Control" content="no-cache">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="-1">
    <meta name="viewport" content="width=device-width, minimum-scale=1.0, initial-scale=1.0, maximum-scale=1.0, user-scalable=1">
    <meta name="format-detection" content="telephone=no">
    <title><?php echo $title; ?></title>
    <link rel="stylesheet" type="text/css" href="src/core.css">
  <script type="text/javascript" src="http://api.map.baidu.com/api?v=2.0&ak=DDa0545d1e03780687ead34bc4701ee1"></script>
  <style type="text/css">
  #allmap {width: 90%;height: 220px;overflow: hidden;margin:0 auto;}
  h1, h2 { font-size: 17px; }
  h2{ font-size: 16px; }
  .info { color: #666; font-size: 16px; }
  .mod { clear: both; }
  .event-detail .pic { float: left; margin: 3px 10px 10px 0; }
  .event-desc .bd { font-size: 15px; } 
  .actions { margin: 20px 0; color: #999; }
  .small { font-size: 14px; }
  #bd, #ft { margin: 0; border: 0; }

  .app-banner,
  .app-banner:link,
  .app-banner:hover,
  .app-banner:visited,
  .app-banner:active {
    color: #543;
    display: block;
    padding: 20px 1em 20px 60px;
    margin: 0 0 10px;
    border: 1px solid #dd9;
    border-width: 1px 0;
    background: #fffee3 no-repeat 10px 50%;
  }
</style>

</head>
<body>
    <div class="wrapper">
        <div id="hd">
            
<div class="logo">
  <a href="http://www.zhaole365.com">
    <img alt="找乐" height="17" src="src/1.png" width="90">
  </a>
</div>
    <div class="nav">
        <a>欢迎使用找乐</a> &nbsp;&nbsp;|&nbsp;&nbsp;
        <a><?php if(!isset($user_address))echo "您还没有提交位置信息";else{echo $user_address;} ?>
        </a> 
    </div>

        </div>

        <div id="bd">
            
  <h1><?php echo $title; ?></h1>
  <div class="event-detail mod">
      <div class="pic">
        <img  src="<?php echo "http://115.159.31.63/showPic.php?picurl=".$imgSrc;?>" height="200" width="112" alt="活动海报">
      </div>
    <div class="info">
      <span>时间</span><?php echo $time ;?><br>
      <span>地点:</span><?php echo $address ;?><br>
      <span>费用:</span><?php echo $price;?><br>
      <span>类型:</span><?php echo $category;?><br>
      <?php 
      if(isset($user_address)&&isset($longitude))
      {
            $getLocation="&origins=".$user_latitude.",".$user_longitude;
            $aimLocation="&destinations=".$latitude.",".$longitude;
            $apiUrl="http://api.map.baidu.com/direction/v1/routematrix?ak=DDa0545d1e03780687ead34bc4701ee1&output=xml&tatics=11";
            $resUrl=$apiUrl.$getLocation.$aimLocation;
            $rtn=file_get_contents($resUrl);
            $rtnCnt=simplexml_load_string($rtn,'SimpleXMLElement',LIBXML_NOCDATA);
            $rtnDistance=$rtnCnt->result->elements->distance->text;
            $rtnTime=$rtnCnt->result->elements->duration->text;
            echo "<span>距离:</span>".$rtnDistance."<br/>";
            echo "<span>抵达耗时:</span>".$rtnTime."<br/>";
      } 
      if($flag)
        echo "<span id='save_exist'><button type='button' onclick='save_exist()' style='background:#C7EDCC'><font size='4'>取消收藏</font></button></span>";
      else
        echo "<span id='save'><button type='button' onclick='save()'><font size='4'>&nbsp;&nbsp;收&nbsp;&nbsp;&nbsp;藏&nbsp;&nbsp;</font></button></span>";
      ?>
<script type="text/javascript">

  function save()
  {
    var xmlhttp;
    if(window.XMLHttpRequest)
    {
      xmlhttp=new XMLHttpRequest();
      // document.write("1");
    }
    xmlhttp.onreadystatechange=function()
    {
      if(xmlhttp.readyState==4)
      {
        //document.write("1");
        // document.getElementById("save").innerHTML=xmlhttp.responseText;
        document.getElementById("save").innerHTML="<span id='save_exist'><button type='button' onclick='save_exist()' style='background:#C7EDCC'><font size='4'>取消收藏</font></button></span>";
      }
    }
    xmlhttp.open("POST","http://115.159.31.63/save.php",true);
    xmlhttp.setRequestHeader("Content-type","application/x-www-form-urlencoded");
    xmlhttp.send('<?php echo "type=1&eventid=".$eventid."&userid=".$userid;?>');
  }
  function save_exist()
  {
    var xmlhttp;
    if(window.XMLHttpRequest)
    {
      xmlhttp=new XMLHttpRequest();
      // document.write("1");
    }
    xmlhttp.onreadystatechange=function()
    {
      if(xmlhttp.readyState==4)
      {
        //document.write("1");
        document.getElementById("save_exist").innerHTML="<span id='save'><button type='button' onclick='save()'><font size='4'>&nbsp;&nbsp;收&nbsp;&nbsp;&nbsp;藏&nbsp;&nbsp;</font></button></span>";
      }
    }
    xmlhttp.open("POST","http://115.159.31.63/save.php",true);
    xmlhttp.setRequestHeader("Content-type","application/x-www-form-urlencoded");
    xmlhttp.send('<?php echo "type=0&eventid=".$eventid."&userid=".$userid;?>');
  }
  </script>
    </div>
  </div>
  <div class="mod event-desc">
    <h3>活动介绍</h3>
    <div class="bd">
    <?php echo $info ;?>
    </div>
  </div>
</div>
</div>
<div class="mod event-desc">
<h3>活动链接</h3>
<div class="bd">
  <a href="<?php echo $url ;?>"><?php echo $url ;?></a>
</div>
</div>
<?php 
if(isset($longitude))
{
  echo "<h3>活动地图</h3><div id='allmap' ></div><br/>
  <script type='text/javascript'>
  var map = new BMap.Map('allmap');         
  var point = new BMap.Point(".(float)$longitude.",".(float)$latitude.");    
  map.centerAndZoom(point,18);
  map.addOverlay(new BMap.Marker(point));                  
  map.enableScrollWheelZoom();                           
  </script>";
}
else
echo "<br/>";
?>
</body></html>