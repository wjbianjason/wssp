<?php
/**
 * 预处理
 */
require_once('SolrUtil.php');
ini_set('max_execution_time','50000');
header("Content-Type:text/html;charset=utf8");
error_reporting(E_ALL&~E_NOTICE);
ini_set('error_log','/var/www/php-errer.log');
ini_set('log_errors',true);
// $userid;//微信用户的ID
// // $_id;
// $page=0;
// $userlatitude;
// $userlongitude;
// $flag;
/**
 * 调用
 */
$wechatObj = new wechat();
$wechatObj->responseMsg();
/**
 * [微信公共平台]
 * @var wechat
 */
class wechat {
/*    public $help="欢迎使用找乐微信平台!
    我们为您提供各类线下活动
    主要活动类型有：
    1.I T 风云   2.亲子家庭
    3.游园集市 4.文艺演出
    5.体育赛事 6.电影电视
    7.博物博览 8.留学移民

    以下为可键入命令信息
    =>“s#内容(#地点#类型)”:检索,括号内为可不填项,类型填序号,如“s#互联网”或“s#互联网#北京”
    =>“n”:获取下页结果
    =>“c”:获取您收藏的活动
    =>发送位置:服务更优质
    提醒:“#”键在数字键旁边,位置在输入栏旁边的“+”里";*/
    public $solrEvent;
    public $solrUser;
    public $userid;
    public $page=0;
    public $flag;
    public $textTpl = "<xml>
        <ToUserName><![CDATA[%s]]></ToUserName>
        <FromUserName><![CDATA[%s]]></FromUserName>
        <CreateTime>%s</CreateTime>
        <MsgType><![CDATA[text]]></MsgType>
        <Content><![CDATA[%s]]></Content>
        <FuncFlag>0</FuncFlag>
        </xml>";
    public $textImg = "<xml>
        <ToUserName><![CDATA[%s]]></ToUserName>
        <FromUserName><![CDATA[%s]]></FromUserName>
        <CreateTime>%s</CreateTime>
        <MsgType><![CDATA[news]]></MsgType>
        <ArticleCount>%s</ArticleCount>
        <Articles>%s</Articles>
        </xml>";
    public $textItem="<item>
        <Title><![CDATA[%s]]></Title> 
        <Description><![CDATA[]]></Description>
        <PicUrl><![CDATA[%s]]></PicUrl>
        <Url><![CDATA[%s]]></Url>
        </item>";
    /**
     * [getBird elasticsearch检索]
     * @param  [type] $content [检索内容]
     * @param  [type] $city    [检索城市]
     * @param  [type] $sort    [检索类型]
     * @return [array]$result  [返回标题、图片、活动id]
     */
    function __construct(){
        $this->solrEvent = new SolrUtil('http://127.0.0.1:8983/solr/', 'events');
        $this->solrUser = new SolrUtil('http://127.0.0.1:8983/solr/', 'users');
    }
    public function getBird($content,$city,$sort)
    {

        $params=array();
        $params['q']="(title:$content OR description:$content)";
        // $this->solrEvent->select(array('q'=>"title:$content OR ",))
        $params['start']=$this->page;
        $params['rows']=4;
        // $params['body']['query']['bool']['must']['bool']['should'][]['match_phrase']['title']=$content;
        // $params['body']['query']['bool']['must']['bool']['should'][]['match_phrase']['info']=$content;
        if($city!="")
        { 
        if($city!="其他")
         $params['q']="(title:$content OR description:$content)AND location_description:$city";
        else
        {
            $params['q']=$params['q']."NOT location_description:北京";
            $params['q']=$params['q']."NOT location_description:上海";
            $params['q']=$params['q']."NOT location_description:广州";
            $params['q']=$params['q']."NOT location_description:武汉";
        }
        }
        if($sort!="")
        {
            if($sort!='5')
            $params['q']=$params['q']."AND type:$sort";
            else
            {
              // $params['body']['query']['bool']['must']['bool']['must'][]['match']['sort']=$sort;
            }
        }
        $params['fl']="id,title,imgSrc";


        $bird_result=$this->solrEvent->select($params);
        $rtn=array();
        if($bird_result['data']['response']['numFound']==0)
        {
            $rtn['error']="很抱歉，暂时没有您想关注的活动，我们将尽快添加！谢谢支持";
        }
        else {
        for($i=0;$i<4;$i++)
        {
            $rtn['title'][]=$bird_result['data']['response']["docs"][$i]["title"];
            $rtn['imgSrc'][]=$bird_result['data']['response']["docs"][$i]["imgSrc"];
            $rtn['eventid'][]=$bird_result['data']['response']["docs"][$i]["id"];
        }}
        return $rtn;
    }
    /**
     * [getUser 判断是否为新用户，并将用户id存入公有变量]
     * @return [type] [无]
     */
    public function getUser()
    {
        $params=array();
        $params['q']="id:$this->userid";
        // $params['q']="id:3";
        // $params['type']='weixin';
        // $params['body']['query']['term']['userid']=$userid;
        $user_result=$this->solrUser->select($params);
        if($user_result['data']['response']['numFound']==0)
        {
            $this->solrUser->add(array('id'=>$this->userid,'userid'=>$this->userid,'page'=>0));
            $this->page=0;
            $this->flag=1;
        }
        else
        {
            // $page=$user_result['hits']['hits'][0]['_source']['page'];
            if(!isset($user_result['data']['response']['docs'][0]['fakeid']))
            {
                $this->flag=1;
            }
        }
    }
    /**
     * [setAddress 存入用户的位置信息，并调用百度API获取用户的经纬度]
     * @param [type] $address   [所在位置的label]
     * @param [type] $latitude  [纬度]
     * @param [type] $longitude [经度]
     */
    public function setAddress($address,$latitude,$longitude)
    {
        $url="http://api.map.baidu.com/geocoder/v2/?ak=DDa0545d1e03780687ead34bc4701ee1&callback=renderReverse&location=".$latitude.",".$longitude."&output=xml&pois=1";
        $rtn=file_get_contents($url);
        $rtnCnt=simplexml_load_string($rtn,'SimpleXMLElement',LIBXML_NOCDATA);
        $usercity=trim($rtnCnt->result->addressComponent->city);
        // $area=trim($rtnCnt->result->addressComponent->district);
        $usercity=str_replace("市", "",(string)$usercity);
        // $params['body']=array(
        //     'doc'=>array(
        //         'address'=>$address,
        //         'latitude'=>$latitude,
        //         'longitude'=>$longitude,
        //         'usercity'=>(string)$usercity,
        //         'area'=>(string)$area));
        $params=array('id'=>$this->userid,'address'=>array('set'=>$address),'latitude'=>array('set'=>$latitude),'longitude'=>array('set'=>$longitude),'usercity'=>array('set'=>(string)$usercity));
        $this->solrUser->update($params);
    }
    /**
     * [getSave 返回用户的收藏]
     * @return [type] [返回标题、图片、活动id]
     */
    public function getSave(){
        $params['q']="id:$this->userid";
        $params['fl']="save";
        $save_result=$this->solrUser->select($params);
        // if(isset($save_result['hits']['hits'][0]['_source']['save']))
        $save_id=$save_result['data']['response']['docs'][0]['save'];
        if(empty($save_id))
        {
            $rtn['error']="您目前还没有收藏活动或收藏的活动已结束";
        }
        else{
            $eventid=explode('|',$save_id);
            $params=array();
            $params['fl']="id,title,imgSrc";
            for($i=0;$i<(count($eventid)-1);$i++)
            {
                $params['q']="id:".(string)$eventid[$i];
                $save_bird=$this->solrEvent->select($params);
                $rtn['title'][]=$save_bird['data']['response']["docs"][0]["title"];
                $rtn['imgSrc'][]=$save_bird['data']['response']["docs"][0]["imgSrc"];
                $rtn['eventid'][]=$save_bird['data']['response']["docs"][0]["id"];
            }
        }
        return $rtn;
    }
    /**
     * [nextPage 返回下一页的结果]
     * @param  [type] $fromUsername [来源用户的id]
     * @param  [type] $toUsername   [目标用户的id]
     * @param  [type] $time         [时间戳]
     * @return [type]               [返回标题、图片、活动id]
     */
    public function nextPage($fromUsername,$toUsername,$time){
        $page_result=$this->solrUser->select(array('q'=>"id:$this->userid"));
        $operation=$page_result['data']['response']['docs'][0]['operation'];
        $this->page=$page_result['data']['response']['docs'][0]['page']+4;
        // $params['body']=array(
        //     'doc'=>array(
        //         'page'=>$page));
        $this->solrUser->update(array('id'=>$this->userid,'page'=>array('set'=>$this->page))); 
        if($operation=="search")
        {
        $keyContent=$page_result['data']['response']['docs'][0]['title'];
        $keyloc=$page_result['data']['response']['docs'][0]['city'];
        $keysort=$page_result['data']['response']['docs'][0]['sort'];
        $result=self::getBird($keyContent,$keyloc,$keysort);
        }
        else if($operation=='today')
        {
            $result=self::getToday();
        }
        else if($operation=='around')
        {
            $result=self::getAround();
        }
        if(isset($result['error']))
        {
            $resultStr=sprintf($this->textTpl,$fromUsername,$toUsername,$time,$result['error']);
        }
        else { 
        $num=4;
        $resultItem="";
        for($i=0;$i<4;$i++)
        {   
            $url="http://115.159.31.63/display.php?eventid=".$result['eventid'][$i]."&userid=".$this->userid;
            $resultItem .=sprintf($this->textItem,$result['title'][$i],$result['imgSrc'][$i],$url);
        }
        $resultStr = sprintf($this->textImg,$fromUsername,$toUsername,
        $time,$num,$resultItem);
        }
        return $resultStr;
    }
    public function getContact($friend,$message,$createTime){
            $errno = 0; 
            $errstr = "";
            $timeout=5; 
            $fp=fsockopen('localhost',80,$errno,$errstr,$timeout);
            if(empty($friend))
            fputs($fp,"GET /weixindenglu.php?flag=0&message=$message&id=$this->userid&createTime=$createTime\r\n");
            else
            fputs($fp,"GET /weixindenglu.php?flag=0&message=$message&friend=$friend&id=$this->userid&createTime=$createTime\r\n");
            fclose($fp);
    }
    public function setfake($createTime)
    {   
            $errno = 0; 
            $errstr = "";
            $timeout=5; 
            $fp=fsockopen('localhost',80,$errno,$errstr,$timeout);
            fputs($fp,"GET /weixindenglu.php?id=$this->userid&createTime=$createTime&flag=1\r\n");
            fclose($fp);

    }
    /**
     * [getToday 获取今天的活动，默认选择用户所在的城市]
     * @return [type] [返回标题、图片、活动id]
     */
    public function getToday(){
        // global $client;
        // global $userid;
        // global $_id;
        // global $page;
        // $params=array();
        // $params['index']='user';
        // $params['type']="weixin";
        // $params['body']['query']['term']['userid']=$userid;
        // $today_result=$client->search($params);
        // $content=$today_result['hits']['hits'][0]['_source']['title'];
        // $usercity=$today_result['hits']['hits'][0]['_source']['usercity'];
        // $city=$today_result['hits']['hits'][0]['_source']['city'];
        // $params=array();
        // $params['index']="bird";
        // $params['body']['from']=$page;
        // $params['body']['size']=4;
        // $params['body']['query']['bool']['must']['bool']['should'][]['match']['title']=$content;
        // $params['body']['query']['bool']['must']['bool']['should'][]['match']['info']=$content;
        // $params['body']['query']['bool']['must'][]['match']['today']=1;
        // if($usercity!="")
        // {
        //  $params['body']['query']['bool']['must']['bool']['must'][]['match']['city']=$usercity; 
        // }
        // else if($city!="")
        // {
        //  $params['body']['query']['bool']['must']['bool']['must'][]['match']['city']=$city; 
        // }
        // $resultToday=$client->search($params);
        // if($resultToday['hits']['total']==0)
        // {
        //     $answer['error']="今天您所在城市没有任何活动";
        // }
        // else{
        //     // $num=$resultToday['hits']['total'];
        //     // $params['body']['size']=$num;
        //     // $resultToday=$client->search($params);
        //     for($i=0;$i<4;$i++)
        //     {
        //         $answer['title'][]=$resultToday['hits']['hits'][$i]['_source']['title'];
        //         $answer['imgSrc'][]=$resultToday['hits']['hits'][$i]['_source']['imgSrc'];
        //         $answer['eventid'][]=$resultToday['hits']['hits'][$i]['_source']['eventid'];
        //     }
        // }
        $answer['error']="该按键已被打入天牢";
        return $answer;
    }
    /**
     * [getdistance 通过经纬度计算距离]
     * @param  [type] $lng1 [经度1]
     * @param  [type] $lat1 [纬度1]
     * @param  [type] $lng2 [经度2]
     * @param  [type] $lat2 [纬度2]
     * @return [type]       [距离（米）]
     */
    // public function getdistance($lng1,$lat1,$lng2,$lat2){
    //     $radLat1=deg2rad($lat1);//deg2rad()函数将角度转换为弧度
    //     $radLat2=deg2rad($lat2);
    //     $radLng1=deg2rad($lng1);
    //     $radLng2=deg2rad($lng2);
    //     $a=$radLat1-$radLat2;
    //     $b=$radLng1-$radLng2;
    //     $s=2*asin(sqrt(pow(sin($a/2),2)+cos($radLat1)*cos($radLat2)*pow(sin($b/2),2)))*6378.137*1000;
    //     return $s;
    // }
    /**
     * [getAround 获取周围活动]
     * @return [type] [返回标题、图片、活动id]
     */
    public function getAround(){

        $result_user=$this->solrUser->select(array('q'=>"id:$this->userid"));
        $usercity=$result_user['data']['response']['docs'][0]['usercity'];
        if(empty($usercity))
        {
            $answer['error']="您还没有发送位置";
            return $answer;
        }
        $latitude=$result_user['data']['response']['docs'][0]['latitude'];
        $longitude=$result_user['data']['response']['docs'][0]['longitude'];
        // $userarea=$result_user['data']['response']['docs'][0]['_source']['area'];
        $params=array();
        $params['q']="location_description:$usercity";
        $params['sort']="geodist()+asc";
        $params['sfield']="location";
        $params['pt']=$latitude.",".$longitude;
        $params['start']=0;
        $params['rows']=40;
        // $params['fq']="{!geofilt}";
        $params['fl']="id,title,imgSrc";
        // $params['body']['query']['bool']['must'][]['match']['area']=$userarea;
        $resultAround=$this->solrEvent->select($params);
        if($resultAround['data']['response']['numFound']==0)
        {
            $answer['error']="您附近没有任何活动";
        }
        else{
            for($i=$this->page;$i<($this->page+4);$i++)
            {
                $answer['title'][]=$resultAround['data']['response']['docs'][$i]['title'];
                $answer['imgSrc'][]=$resultAround['data']['response']['docs'][$i]['imgSrc'];
                $answer['eventid'][]=$resultAround['data']['response']['docs'][$i]['id'];
            }

        } 
        return $answer;
    }
    /**
     * [responseEvent 对msg类型为event的消息进行回复]
     * @param  [type] $postObj      [返回的微信消息的对象]
     * @param  [type] $fromUsername [来源用户的id]
     * @param  [type] $toUsername   [目标用户的id]
     * @param  [type] $time         [时间戳]
     * @return [type]               [返回已经格式的回复信息]
     */
    public function responseEvent($postObj,$fromUsername,$toUsername,$time){
        $eventType=$postObj->Event;
        if($eventType='CLICK')
        {
            $eventKey=explode("|",$postObj->EventKey);
            if($eventKey[0]=="sort")
            {
                // $params['body']=array(
                //     'doc'=>array(
                //         'sort'=>(string)$eventKey[1]));
                // $client->update($params);
                $this->solrUser->update(array('id'=>$this->userid,'sort'=>array('set'=>(string)$eventKey[1])));
                switch ($eventKey[1]) {
                      case '1':
                          $answer="您已记录类型为IT风云";
                          break;
                      case '2':
                          $answer="您已记录类型为文艺演出";
                          break;
                      case '3':
                          $answer="您已记录类型为体育赛事";
                          break;
                      case '4':
                          $answer="您已记录类型为电影电视";
                          break;
                      case '5':
                          $answer="您已记录类型为其他";
                          break;                                                                              
                      default:
                          break;
                  }
                $resultStr=sprintf($this->textTpl,$fromUsername,$toUsername,$time,$answer);
            }
            else if($eventKey[0]=="city")
            {
                // $params['body']=array(
                //     'doc'=>array(
                //         'city'=>(string)$eventKey[1]));
                $this->solrUser->update(array('id'=>$this->userid,'city'=>array('set'=>(string)$eventKey[1])));
                $answer="您已记录城市为".$eventKey[1];
                $resultStr=sprintf($this->textTpl,$fromUsername,$toUsername,$time,$answer);
            }
            else if($eventKey[0]="menu")
            {
                switch($eventKey[1])
                {
                    case 'today':
                    // $params['body']=array(
                    //     'doc'=>array(
                    //         'operation'=>'today',
                    //         'page'=>0));
                    $this->solrUser->update(array('id'=>$this->userid,'operation'=>array('set'=>"today"),'page'=>array('set'=>0)));
                    $result=self::getToday();
                    if(isset($result['error']))
                    {
                        $resultStr =  sprintf($this->textTpl,$fromUsername,$toUsername,$time,$result['error']);
                    }
                    else
                    {
                        $num=count($result['title']);
                        $resultItem="";
                        for($i=0;$i<$num;$i++)
                        {   
                            $url="http://115.159.31.63/display.php?eventid=".$result['eventid'][$i]."&userid=".$this->userid;
                            $resultItem .=sprintf($this->textItem,$result['title'][$i],$result['imgSrc'][$i],$url);
                        }
                        $resultStr = sprintf($this->textImg,$fromUsername,$toUsername,
                        $time,$num,$resultItem);
                    }
                    break;       
                    case 'save':
                    $result=self::getSave();
                    if(isset($result['error']))
                    {
                        $resultStr =  sprintf($this->textTpl,$fromUsername,$toUsername,$time,$result['error']);
                    }
                    else
                    {
                        $num=count($result['title']);
                        $resultItem="";
                        for($i=0;$i<$num;$i++)
                        {   
                            $url="http://115.159.31.63/display.php?eventid=".$result['eventid'][$i]."&userid=".$this->userid;
                            $resultItem .=sprintf($this->textItem,$result['title'][$i],$result['imgSrc'][$i],$url);
                        }
                        $resultStr = sprintf($this->textImg,$fromUsername,$toUsername,
                        $time,$num,$resultItem);
                    }
                    break;
                    case 'next':
                    $resultStr=self::nextPage($fromUsername,$toUsername,$time);
                    break;
                    case 'around':
                    // $params['body']=array(
                    //     'doc'=>array(
                    //         'operation'=>'around',
                    //         'page'=>0));
                    // $client->update($params);
                    $this->solrUser->update(array('id'=>$this->userid,'operation'=>array('set'=>"around"),'page'=>array('set'=>0)));
                    $result=self::getAround();
                    if(isset($result['error']))
                    {
                        $resultStr =  sprintf($this->textTpl,$fromUsername,$toUsername,$time,$result['error']);
                    }
                    else
                    {
                        $num=count($result['title']);
                        $resultItem="";
                        for($i=0;$i<$num;$i++)
                        {   
                            $url="http://115.159.31.63/display.php?eventid=".$result['eventid'][$i]."&userid=".$this->userid;
                            $resultItem .=sprintf($this->textItem,$result['title'][$i],$result['imgSrc'][$i],$url);
                        }
                        $resultStr = sprintf($this->textImg,$fromUsername,$toUsername,
                        $time,$num,$resultItem);
                    }
                    break;
                    case 'contact':
                    $resultOpe=$this->solrUser->select(array('q'=>"id:$this->userid"));
                    $ope=$resultOpe['data']['response']['docs'][0]['operation'];
                    if($ope=="contact")
                    {
                        // $params['body']=array(
                        // 'doc'=>array(
                        //     'operation'=>'search'));
                        $this->solrUser->update(array('id'=>$this->userid,'operation'=>array('set'=>"search")));
                        $answer="您已取消对话模式";
                    }
                    else
                    {
                        // $params['body']=array(
                        // 'doc'=>array(
                        //     'operation'=>'contact'));
                        $this->solrUser->update(array('id'=>$this->userid,'operation'=>array('set'=>"contact")));
                        $answer="您已记录为对话模式";
                    }
                    $resultStr=sprintf($this->textTpl,$fromUsername,$toUsername,$time,$answer);
                    default:
                    break;                                                                      
                }
             }
        }
        else if($eventType=="location_select")
        {
            $getX=$postObj->SendLocationInfo->Location_X;
            $getY=$postObj->SendLocationInfo->Location_Y;
            $getlocation=$postObj->SendLocationInfo->Label;
            self::setAddress($getlocation,$getX,$getY);
            $content="您好,在详细信息里您将获得位置服务!";
            $resultStr = sprintf($this->textTpl,$fromUsername,$toUsername,$time,$content);
        }
        return $resultStr;
    }
    /**
     * [responseMsg 处理来源信息并回复]
     * @return [type] [返回格式化的回复信息]
     */
    public function responseMsg() {
        $postStr = $GLOBALS["HTTP_RAW_POST_DATA"]; //获取POST数据
        $postObj = simplexml_load_string($postStr,'SimpleXMLElement',LIBXML_NOCDATA);
        //---------- 接 收 数 据 ---------- //
        $fromUsername = $postObj->FromUserName;
        $createTime = $postObj->CreateTime;
        $this->userid=(string)$fromUsername;
        self::getUser();
        $toUsername = $postObj->ToUserName; //获取接收方账号
        $time = time(); //获取当前时间戳
        $msgtype=$postObj->MsgType;
        if($msgtype=="location")
        {
            $getX=(string)($postObj->Location_X);
            $getY=(string)($postObj->Location_Y);
            $getlocation=(string)($postObj->Label);
            self::setAddress($getlocation,$getX,$getY);
            $content="您好,在详细信息里您将获得位置服务!";
            $resultStr = sprintf($this->textTpl,$fromUsername,$toUsername,$time,$content);
        }
        else if($msgtype=="event")
        {
            $resultStr=self::responseEvent($postObj,$fromUsername,$toUsername,$time);//call back the function that deal with event
        }
        else if($msgtype=="text")
        { 

            if($this->flag=1)
            {
                self::setfake($createTime);
            }
            $keyContent = trim($postObj->Content); //获取消息内容
            // $params['index']="user";
            // $params['type']="weixin";
            // $params['body']['query']['term']['userid']=$userid;
            $resultOpe=$this->solrUser->select(array('q'=>"id:$this->userid"));
            $ope=$resultOpe['data']['response']['docs'][0]['operation'];
            if($ope=="contact")
            {
                $comunication=explode('@',$keyContent);
                if(!isset($comunication[1]))
                {
                    $comunication[1]="";
                    self::getContact($comunication[1],$comunication[0],$createTime);
                }
                else
                self::getContact($comunication[0],$comunication[1],$createTime);
                $resultStr="";
            }
            else
            {
                // $params['body']=array(
                //     'doc'=>array(
                //         'page'=>0,
                //         'title'=>$keyContent,
                //         'operation'=>'search'));
                // $client->update($params);
                $this->solrUser->update(array('id'=>$this->userid,'operation'=>array('set'=>"search"),'page'=>array('set'=>0),'title'=>array('set'=>$keyContent)));
                // $params=array();
                // $params['index']="user";
                // $params['type']="weixin";
                // $params['body']['query']['term']['userid']=$userid;
                $page_result=$this->solrUser->select(array('q'=>"id:$this->userid"));
                $keyloc=$page_result['data']['response']['docs'][0]['city'];
                $keysort=$page_result['data']['response']['docs'][0]['sort'];
                $result=self::getBird($keyContent,$keyloc,$keysort);
                if(isset($result['error']))
                {
                    $resultStr=sprintf($this->textTpl,$fromUsername,$toUsername,$time,$result['error']);
                }
                else {  
                $num=4;
                $resultItem="";
                for($i=0;$i<4;$i++)
                {   
                    $url="http://115.159.31.63/display.php?eventid=".$result['eventid'][$i]."&userid=".$this->userid;
                    $resultItem .=sprintf($this->textItem,$result['title'][$i],$result['imgSrc'][$i],$url);
                }
                $resultStr = sprintf($this->textImg,$fromUsername,$toUsername,
                $time,$num,$resultItem);
            }
            // }
        }}
            echo $resultStr; //输出结果
        }
}?>
