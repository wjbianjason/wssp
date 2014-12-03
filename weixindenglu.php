<?
require_once ('class_weixin.php');
require_once('SolrUtil.php');
ini_set('max_execution_time','50000');
header("Content-Type:text/html;charset=utf8");
$solrUser= new SolrUtil('http://127.0.0.1:8983/solr/', 'users');
$wxlogin = new WX_Remote_Opera();
$wxlogin->init('username','password');//--------------输入账号密码
$flag=$_GET['flag'];
$id=$_GET['id'];
$createTime=$_GET['createTime'];
if($flag==1)
{
    $message=$wxlogin->getmsglist();
    $timestamp=$message[0]['date_time'];
    $solrUser->update(array('id'=>3, 'username' =>array('set'=>'9')));
    if($timestamp==$createTime)
    {
        // $params['body']=array(
        //     'doc'=>array(
        //         'fakeid'=>$message[0]['fakeid'],
        //         'username'=>$message[0]['nick_name']));
        $solrUser->update(array('id'=>$id,'fakeid'=>array('set'=>$message[0]['fakeid']),'wechat_name'=>array('set'=>$message[0]['nick_name'])));
        // $client->update($params);
    }
}
else
{
    // $params['id']=$id;
    // =$client->get($params);
    $result=$solrUser->select(array('q'=>"id:$id"));
    $fromuser=$result['data']['response']['docs'][0]['wechat_name'];
    $fromid=$result['data']['response']['docs'][0]['fakeid'];
    $friendid="";
    if(isset($_GET['friend']))
    {
        $friend=$_GET['friend'];
        $sResult = $wxlogin->getsumcontactlist();
        $sum = 0;
        for($i = 0;$i < count($sResult);$i++){
            $sum  =$sum + $sResult[$i]['cnt'];
        }
        $page = ceil($sum/10);
        for($i = 0;$i < $page;$i++){
        $grest = $wxlogin->getcontactlist(10,$i);
            for($m = 0;$m < count($grest);$m++){
                if($friend==$grest[$m]['nick_name'])
                {
                    $friendid=$grest[$m]['id'];
                    goto a;
                }
            }
        }
        a:
        // $params['body']=array(
        //     'doc'=>array(
        //         'friendid'=>$friendid));
        // $client->update($params);
        $solrUser->update(array('id'=>$id,'friendid'=>array('set'=>$friendid)));
        // $params['index']='user';
        // $params['type']="weixin";
        // $params['body']['query']['term']['fakeid']=$friendid;
        $resultfriend=$solrUser->select(array('q'=>"fakeid:$friendid"));
        // $params['id']=$resultfriend['hits']['hits'][0]['_id'];
        // $params['body']=array(
        //     'doc'=>array(
        //         'friendid'=>$fromid));
        // $client->update($params);
        $solrUser->update(array('id'=>$resultfriend['data']['response']['docs'][0]['id'],'friendid'=>array('set'=>$fromid)));

    }
    else
    {
        $friendid=$result['data']['response']['docs'][0]['friendid'];
    }
    $wxlogin->sendmsg($fromuser.":".$message,$friendid,"");
}
    

?>