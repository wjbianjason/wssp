<?php
require_once('SolrUtil.php');
$type=$_POST['type'];
$eventid=$_POST['eventid'];
$eventid=$eventid."|";
$userid=$_POST['userid'];
$solrUser = new SolrUtil('http://127.0.0.1:8983/solr/', 'users');
// $params['index']="user";
// $params['type']="weixin";
// $params['body']['query']['term']['userid']=$userid;
$rtn=$solrUser->select(array('q'=>"id:$userid"));
if(isset($rtn['data']['response']["docs"][0]["save"]))
{
	$save=$rtn['data']['response']["docs"][0]["save"];
}
else
$save="";
// $_id=$rtn['hits']['hits'][0]['_id'];
// $params['id']=$_id;
if($type=='0')
{
	$save=str_replace($eventid,"",$save);
	// $params['body']=array(
	// 	'doc'=>array(
	// 		'save'=>$save));
	// $client->update($params);
	$solrUser->update(array('id'=>$userid,'save'=>array('set'=>$save)));
}
else
{
	$save=$save.$eventid;
	// $params['body']=array(
	// 	'doc'=>array(
	// 		'save'=>$save));
	// $client->update($params);
	$solrUser->update(array('id'=>$userid,'save'=>array('set'=>$save)));
}
        ?>