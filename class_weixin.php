<?php

define('COOKIE',dirname(__FILE__)."/cookie.txt");
class WX_Remote_Opera{
private $token;

public function init($user,$password){  //初始化，登录微信平台
$url="https://mp.weixin.qq.com/cgi-bin/login?lang=zh_CN";
$ch=curl_init($url);
$post['username']=$user;
$post['pwd']=md5($password);
$post['f']='json';
$post['imgcode']='';
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);    //验证证书
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);        //验证HOST
curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);      //对推送来的消息进行设置   1不自动输出任何内容 0输出返回的内容
curl_setopt($ch,CURLOPT_HEADER,1);      //是否返回头文件
curl_setopt($ch,CURLOPT_REFERER,'https://mp.weixin.qq.com/cgi-bin/loginpage?t=wxm2-login&lang=zh_CN');
curl_setopt($ch,CURLOPT_USERAGENT,'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:20.0) Gecko/20100101 Firefox/20.0');
curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,5);  //数据传输最大允许时间
curl_setopt($ch,CURLOPT_POST,1);
curl_setopt($ch,CURLOPT_POSTFIELDS,$post);
curl_setopt($ch,CURLOPT_COOKIEJAR,COOKIE);
$html=curl_exec($ch);
// echo $html;
preg_match('/[\?\&]token=(\d+)"/',$html,$t);        //匹配到token
$token=$t[1];
curl_close($ch);
$this->token=$token;
echo $token;
return $token;
}
public function sendmsg($content,$fromfakeid,$token){ //发送消息给指定人
$url="https://mp.weixin.qq.com/cgi-bin/singlesend";
$ch=curl_init($url);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
curl_setopt($ch, CURLOPT_COOKIEFILE, COOKIE);
curl_setopt($ch,CURLOPT_REFERER,'https://mp.weixin.qq.com/cgi-bin/message?t=message/list&count=20&day=7&token='.$this->token.'&lang=zh_CN');
curl_setopt($ch,CURLOPT_USERAGENT,'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:20.0) Gecko/20100101 Firefox/20.0');
$post['t']='ajax-response';
$post['imgcode']='';
$post['mask']=false;
$post['random']=math_random();
$post['lang']='zh_CN';
$post['tofakeid']=$fromfakeid;
$post['type'] =1;
$post['content']=$content;
$post['token']=$this->token;
curl_setopt($ch,CURLOPT_POST,1);
curl_setopt($ch,CURLOPT_POSTFIELDS,$post);
$html=curl_exec($ch);
curl_close($ch);
}
/*发送图文消息*/
public function setimgmsg($appid,$fromfakeid){
    $url="https://mp.weixin.qq.com/cgi-bin/singlesend";
    $ch=curl_init($url);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    curl_setopt($ch, CURLOPT_COOKIEFILE, COOKIE);
    curl_setopt($ch,CURLOPT_REFERER,'https://mp.weixin.qq.com/cgi-bin/message?t=message/list&count=20&day=7&token='.$this->token.'&lang=zh_CN');
    curl_setopt($ch,CURLOPT_USERAGENT,'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:20.0) Gecko/20100101 Firefox/20.0');

    $post['t']='ajax-response';
    $post['imgcode']='';
    $post['mask']=false;
    $post['random']=math_random();
    $post['lang']='zh_CN';
    $post['tofakeid']=$fromfakeid;
    $post['type'] =10;
    $post['content']='';
    $post['appmsgid'] = $appid;
    $post['token']=$this->token;
    curl_setopt($ch,CURLOPT_POST,1);
    curl_setopt($ch,CURLOPT_POSTFIELDS,$post);
    $html=curl_exec($ch);
    curl_close($ch);
}
/**获取图文消息素材**/
public function getimgmaterial(){
    $url="https://mp.weixin.qq.com/cgi-bin/appmsg?begin=0&count=10&t=media/appmsg_list&type=10&action=list&token=".$this->token."&lang=zh_CN";
    $ch=curl_init($url);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
    curl_setopt($ch, CURLOPT_COOKIEFILE,COOKIE);
    curl_setopt($ch,CURLOPT_REFERER,$url);
    curl_setopt($ch,CURLOPT_USERAGENT,'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:20.0) Gecko/20100101 Firefox/20.0');
    $html=curl_exec($ch);
    curl_close($ch);
    preg_match('%(?<=item\"\:)(.*)(?=,\"file_cnt)%', $html, $result);
    return json_decode($result[1],true);
}


public function getcontactinfo($fromfakeid){
$url="https://mp.weixin.qq.com/cgi-bin/getcontactinfo";
$ch=curl_init($url);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
curl_setopt($ch, CURLOPT_COOKIEFILE,COOKIE);
curl_setopt($ch,CURLOPT_REFERER,$url);
curl_setopt($ch,CURLOPT_USERAGENT,'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:20.0) Gecko/20100101 Firefox/20.0');
$post['t']='ajax-getcontactinfo';
$post['fakeid'] =$fromfakeid;
$post['token']=$this->token;
curl_setopt($ch,CURLOPT_POST,1);
curl_setopt($ch,CURLOPT_POSTFIELDS,$post);
$html=curl_exec($ch);
curl_close($ch);
$arr=json_decode($html,true);
return $arr['contact_info'];
}

// public function getheadimg($fromfakeid){
// $url="https://mp.weixin.qq.com/cgi-bin/getheadimg";
// $ch=curl_init($url);
// curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
// curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
// curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
// curl_setopt($ch, CURLOPT_COOKIEFILE,COOKIE);
// curl_setopt($ch,CURLOPT_REFERER,$url);
// curl_setopt($ch,CURLOPT_USERAGENT,'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:20.0) Gecko/20100101 Firefox/20.0');
// $post['fakeid'] =$fromfakeid;
// $post['token']=$this->token;
// curl_setopt($ch,CURLOPT_POST,1);
// curl_setopt($ch,CURLOPT_POSTFIELDS,$post);
// $headimg=curl_exec($ch);
// curl_close($ch);
// //$PNG_SAVE_DIR = S_ROOT.'uploads'.DIRECTORY_SEPARATOR.'weixin_headimg'.DIRECTORY_SEPARATOR;
// //$file = fopen($PNG_SAVE_DIR.$fromfakeid.".png","w");//打开文件准备写入
// //fwrite($file,$headimg);//写入
// //fclose($file);//关闭
// echo $headimg;
// }

public function getcontactlist($pagesize,$page){
$url="https://mp.weixin.qq.com/cgi-bin/contactmanage?t=user/index&pagesize=".$pagesize."&pageidx=".$page."&type=0&groupid=0&token=".$this->token."&lang=zh_CN";
$ch=curl_init($url);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
curl_setopt($ch, CURLOPT_COOKIEFILE,COOKIE);
curl_setopt($ch,CURLOPT_REFERER,$url);
curl_setopt($ch,CURLOPT_USERAGENT,'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:20.0) Gecko/20100101 Firefox/20.0');
$html=curl_exec($ch);
curl_close($ch);
preg_match('%(?<=\"contacts\"\:)(.*)(?=}\)\.contacts)%', $html, $result);
print_r($result);
return json_decode($result[1],true);
}
/*获取各分组人数*/
public function getsumcontactlist(){
    $url="https://mp.weixin.qq.com/cgi-bin/contactmanage?t=user/index&pagesize=0&pageidx=0&type=0&groupid=0&token=".$this->token."&lang=zh_CN";
    $ch=curl_init($url);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
    curl_setopt($ch, CURLOPT_COOKIEFILE,COOKIE);
    curl_setopt($ch,CURLOPT_REFERER,$url);
    curl_setopt($ch,CURLOPT_USERAGENT,'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:20.0) Gecko/20100101 Firefox/20.0');
    $html=curl_exec($ch);
    curl_close($ch);
    preg_match('%(?<=\"groups\"\:)(.*)(?=}\)\.groups)%', $html, $result);
    return json_decode($result[1],true);
}

public function getmsglist(){
$url="https://mp.weixin.qq.com/cgi-bin/message?t=message/list&count=20&day=7&token=".$this->token."&lang=zh_CN";
$ch=curl_init($url);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
curl_setopt($ch, CURLOPT_COOKIEFILE,COOKIE);
curl_setopt($ch,CURLOPT_REFERER,$url);
curl_setopt($ch,CURLOPT_USERAGENT,'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:20.0) Gecko/20100101 Firefox/20.0');
$html=curl_exec($ch);
preg_match('%(?<=\"msg_item\"\:)(.*)(?=}\)\.msg_item)%', $html, $result);
curl_close($ch);
return json_decode($result[1],true);
}

}
function math_random () {
    return (float)rand()/(float)getrandmax();
}

?>