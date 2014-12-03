<?php
class SolrUtil
{
    private $solrserver = '';
    private $core = '';
     
    /**
     * 
     * @param unknown_type $core $multicore中的索引实例名称
     * @param unknown_type $solrserver solr服务器地址
     * @return boolean
     */
    public function __construct($solrserver, $core = null)
    {
        $this->solrserver = $solrserver;
        if(isset($core) == true)
        {
            $this->core = $core;
        }
    }
     
    public function setCore($core){
        $this->core = $core;
    }
    public function getCore(){
        return $this->core;
    }
    /**
     * 
     * @param unknown_type $data
     * $data['q'] 查询关键词
     * $data['page'] 当前页
     * $data['pageSize'] 每页数据量
     * $data['fl'] 查询结果返回的字段,
     * $data['sort'] 排序字段,
     * $data['wt'] 返回结果格式,可选值json或xml,默认返回json格式
     * $data['hl.fl'] 指定高亮字段,
     * $data['facet.field'] 分组统计
     * hl=true&hl.fl=name,features
     */
    public function select($data)
    {
        $result = array('success'=>0,'info'=>'操作失败');
        if(empty($data['q']) == true)
        {
            $result = array('success'=>0,'info'=>'关键词不能为空');
        }
        else
        {
            $parame = array();
            $parame['q'] = urlencode($data['q']);
            // $rows = isset($data['pageSize']) == true ? intval($data['pageSize']) : 25;
            // $page = isset($data['page']) == true ? intval($data['page']) : 1;
            // $start = $page > 0 ? ($page - 1) * $rows : 0;
            $parame['start'] = isset($data['start']) == true ? intval($data['start']) : 0;
            $parame['rows'] = isset($data['rows']) == true ? intval($data['rows']) : 25;
            if(empty($data['sfield']) == false)
            {
            $parame['sfield']=$data['sfield'];
            }
            if(empty($data['pt']) == false)
            {
                 $parame['pt']=$data['pt'];
            }
            if(empty($data['fq']) == false)
            {
                 $parame['fq']=$data['fq'];
            }
            if(empty($data['fl']) == false)
            {
                $parame['fl'] = urlencode($data['fl']);
                 
            }
            if(empty($data['sort']) == false)
            {
                $parame['sort'] = $data['sort'];//modify
            }
            if(empty($data['hl.fl']) == false)
            {
                $parame['hl.fl'] = urlencode($data['hl.fl']);
            }
            if(empty($data['facet.field']) == false)
            {
                $fields = $data['facet.field'];
                $fieldStr = '';
                foreach($fields as $field)
                {
                    if($fieldStr == ''){
                        $fieldStr = $field;
                    }
                    else
                    {
                        $fieldStr .= '&facet.field='.$field;
                    }
                }
                $parame['facet'] = 'true';
                $parame['facet.field'] = $fieldStr;
            }
             
            $method = 'select';
            $solrData = $this->httpGet($method, $parame);
             
            if(empty($solrData) == false)
            {
                $data = json_decode($solrData, true);
                if(isset($data['responseHeader']['status']) == true && $data['responseHeader']['status'] == 0)
                {
                    $result = array('success'=>1,'info'=>'操作成功', 'data'=>$data);
                }
                else
                {
                    $result = array('success'=>0,'info'=>'查询失败', 'error'=>$solrData);
                }
            }
            else
            {
                $result = array('success'=>0,'info'=>'网络错误,服务器繁忙');
            }
        }
        return $result;
    }
     
    /**
     * 分词
     * $data['q'] 查询关键词
     */
    public function analysis($data){
        $result = array('success'=>0,'info'=>'操作失败');
        if(empty($data['q']) == true)
        {
            $result = array('success'=>0,'info'=>'关键词不能为空');
        }
        else
        {
            $parame = array();
            $parame['q'] = urlencode($data['q']);
            $method = 'analysis/field';
            $solrData = $this->httpGet($method, $parame);
             
            if(empty($solrData) == false)
            {
                $data = json_decode($solrData, true);
                if(isset($data['responseHeader']['status']) == true && $data['responseHeader']['status'] == 0)
                {
                    $result = array('success'=>1,'info'=>'操作成功', 'data'=>$data);
                }
                else
                {
                    $result = array('success'=>0,'info'=>'分词失败', 'error'=>$solrData);
                }
            }
            else
            {
                $result = array('success'=>0,'info'=>'网络错误,服务器繁忙');
            }
 
        }
        return $result;
    }
     
    /**
     * 搜索建议
     * @param array $data
     * $data['q'] 关键词
     */
    public function suggest($data)
    {
        $result = array('success'=>0,'info'=>'操作失败');
        if(empty($data['q']) == true)
        {
            $result = array('success'=>0,'info'=>'关键词不能为空');
        }
        else
        {
            $parame = array();
            $parame['spellcheck.q'] = urlencode($data['q']);
            $parame['spellcheck'] = 'true';
            $method = 'suggest';
            $solrData = $this->httpGet($method, $parame);
             
             
            if(empty($solrData) == false)
            {
                $data = json_decode($solrData, true);
                if(isset($data['responseHeader']['status']) == true && $data['responseHeader']['status'] == 0)
                {
                    $result = array('success'=>1,'info'=>'操作成功', 'data'=>$data);
                }
                else
                {
                    $result = array('success'=>0,'info'=>'获取搜索 建议失败', 'error'=>$solrData);
                }
            }
            else
            {
                $result = array('success'=>0,'info'=>'网络错误,服务器繁忙');
            }
 
        }
        return $result;
    }
     
    /**
     * 相似搜索
     * @param unknown_type $data
     * $data['q'] 查询关键词
     */
    public function moreLike($data)
    {
        $result = array('success'=>0,'info'=>'操作失败');
        if(empty($data['q']) == true)
        {
            $result = array('success'=>0,'info'=>'关键词不能为空');
        }
        else{
            if(isset($data) == false){
                $data = array();
            }
            $data['command'] = 'status';
            $method = 'dataimport';
            $solrData = $this->httpGet($method, $data);
 
            if(empty($solrData) == false)
            {
                $data = json_decode($solrData, true);
                if(isset($data['responseHeader']['status']) == true && $data['responseHeader']['status'] == 0)
                {
                    $result = array('success'=>1,'info'=>'操作成功', 'data'=>$data);
                }
                else
                {
                    $result = array('success'=>0,'info'=>'增量更新索引失败', 'error'=>$solrData);
                }
            }
            else
            {
                $result = array('success'=>0,'info'=>'网络错误,服务器繁忙');
            }
        }
        return $result;
    }
     
    /**
     * 全量导入索引
     * @param array $data
     * $data['clean']    可选参数,为true时删除原有索引,false不删除,默认值为true
     * $data['wt']       可选参数,返回的数据格式,值为json或xml
     * $data['entity']   可选参数,document下面的标签（data-config.xml）,使用这个参数可以有选择的执行一个或多个entity。如果不选择此参数那么所有的都会被运行
     * $data['commit']   可选参数,选择是否在索引完成之后提交。默认为true
     * $data['optimize'] 可选参数,默认为true
     * $data['debug']    可选参数,是否以调试模式运行,如果以调试模式运行，那么默认不会自动提交，请加参数“commit=true”
     */
    public function fullImport($data = null)
    {
        if(isset($data) == false){
            $data = array();
        }
        $data['command'] = 'full-import';
        $method = 'dataimport';
        $solrData = $this->httpGet($method, $data);
         
        if(empty($solrData) == false)
        {
            $data = json_decode($solrData, true);
            if(isset($data['responseHeader']['status']) == true && $data['responseHeader']['status'] == 0)
            {
                $result = array('success'=>1,'info'=>'操作成功', 'data'=>$data);
            }
            else
            {
                $result = array('success'=>0,'info'=>'网络错误,服务器繁忙', 'error'=>$solrData);
            }
        }
        else
        {
            $result = array('success'=>0,'info'=>'网络错误,服务器繁忙');
        }
        return $result;
    }
     
    /**
     * 增量更新索引
     * @param array $data
     * $data['wt'] 返回的数据格式,值为json或xml,默认json
     */
    public function deltaImport($data = null)
    {
        if(isset($data) == false){
            $data = array();
        }
        $data['command'] = 'delta-import';
        $method = 'dataimport';
        $solrData = $this->httpGet($method, $data);
        if(empty($solrData) == false)
        {
            $result = array('success'=>1,'info'=>'操作成功', 'data'=>json_decode($solrData, true));
        }
        else
        {
            $result = array('success'=>0,'info'=>'增量更新索引失败,服务器繁忙', );
        }
        return $result;
    }
     
    /**
     * 查看当前dataimport索引更新状态
     * @param unknown_type $data
     */
    public function statusImport($data = null)
    {
        if(isset($data) == false){
            $data = array();
        }
        $data['command'] = 'status';
        $method = 'dataimport';
        $solrData = $this->httpGet($method, $data);
        if(empty($solrData) == false)
        {
            $result = array('success'=>1,'info'=>'操作成功', 'data'=>json_decode($solrData, true));
        }
        else
        {
            $result = array('success'=>0,'info'=>'增量更新索引失败,服务器繁忙');
        }
        return $result;
    }
     
    /**
     * 终止当前执行的dataimport任务
     * @param array $data
     */
    public function abortImport($data = null)
    {
        if(isset($data) == false){
            $data = array();
        }
        $data['command'] = 'abort';
        $method = 'dataimport';
        $solrData = $this->httpGet($method, $data);
        if(empty($solrData) == false)
        {
            $result = array('success'=>1,'info'=>'操作成功', 'data'=>json_decode($solrData, true));
        }
        else
        {
            $result = array('success'=>0,'info'=>'增量更新索引失败,服务器繁忙');
        }
        return $result;
    }
     
    /**
     * 
     * @param array $datas 格式
     * 批量更新$datas=array(array('id'=>xx, 'app_name'=>array('set'=>'测试')))),id为索引主键字段，必须包含主键值
     * 单个更新$datas=array('id'=>xx, 'app_search_text'=>array('add'=>'测试'), 'look_count'=>array('inc'=>10))
     * set 设置或替当前值,null清空当前值
     * add 如果字段属性为multi-valued，添加一个值
     * inc 设置自增加值
     */
    public function update($datas)
    {
        $parame = '';
        $result = array('success'=>0,'info'=>'操作失败');
        if(isset($datas[0]) == false || is_array($datas[0]) == false){
            $datas = array($datas);
        }
        // 确定只做更新操作，如果不带set则会删除旧索引，再重新创建
        foreach($datas as $data)
        {
            $hasSet = false;
            foreach($data as $key=>$value)
            {
                if(empty($value['set']) == false)
                {
                    $hasSet = true;
                    continue;
                }
            }
            if($hasSet == false)
            {
                $result = array('success'=>0,'info'=>'参数错误，缺少set参数，请修改');
                return $result;
            }
        }
 
        $method = '/update?commit=true';
        $solrData = $this->httpPost($method, $datas);
         
        if(empty($solrData) == false)
        {
            $data = json_decode($solrData, true);
            if(isset($data['responseHeader']['status']) == true && $data['responseHeader']['status'] == 0)
            {
                $result = array('success'=>1,'info'=>'操作成功', 'data'=>json_decode($solrData, true));
            }
            else
            {
                $result = array('success'=>0,'info'=>'更新失败', 'error'=>$solrData);
            }
        }
        else
        {
            $result = array('success'=>0,'info'=>'网络错误,服务器繁忙');
        }
        return $result;
    }
     
    /**
     * 删除索引 
     * @param unknown_type $data $data=array('id'=>xx),id为索引主键字段
     * @return multitype:number string
     */
    public function delete($data)
    {
        $data = array('delete'=>$data);
 
        $method = '/update?commit=true';
        $solrData = $this->httpPost($method, $data);
        if(empty($solrData) == false)
        {
            $data = json_decode($solrData, true);
            if(isset($data['responseHeader']['status']) == true && $data['responseHeader']['status'] == 0)
            {
                $result = array('success'=>1,'info'=>'操作成功', 'data'=>json_decode($solrData, true));
            }
            else
            {
                $result = array('success'=>0,'info'=>'更新失败', 'error'=>$solrData);
            }
        }
        else
        {
            $result = array('success'=>0,'info'=>'网络错误,服务器繁忙');
        }
        return $result;
    }
     
    /**
     * 添加索引(不建议在程序中调用 ),注意:如果主键值相同，则会先删除旧索引,再添加新数据
     * @param unknown_type $datas
     *  批量添加$datas=array(array('id'=>xx, 'app_name'=>'测试测试'),id为索引主键字段，必须包含主键值
     * @return multitype:number string
     */
    public function add($datas)
    {
        $parame = '';
        $result = array('success'=>0,'info'=>'操作失败');
        if(isset($datas[0]) == false || is_array($datas[0]) == false){
            $datas = array($datas);
        }
 
        $method = '/update?commit=true';
        $solrData = $this->httpPost($method, $datas);
        if(empty($solrData) == false)
        {
            $data = json_decode($solrData, true);
            if(isset($data['responseHeader']['status']) == true && $data['responseHeader']['status'] == 0)
            {
                $result = array('success'=>1,'info'=>'操作成功', 'data'=>json_decode($solrData, true));
            }
            else
            {
                $result = array('success'=>0,'info'=>'更新失败', 'error'=>$solrData);
            }
        }
        else
        {
            $result = array('success'=>0,'info'=>'网络错误,服务器繁忙');
        }
        return $result;
    }
     
    private function httpPost($method, $data){
        $data_string = json_encode($data);
//      $url = $this->solrserver . $this->core . '/update?commit=true';
        $url = $this->solrserver . $this->core . $method;
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");  // 更新需要post提交
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Content-Length: ' . strlen($data_string))
        );
        $solrData = curl_exec($ch);
        return $solrData;
    }
     
    private function httpGet($method, $parame)
    {
        $url = $this->solrserver . $this->core."/".$method;
        $data = "";
        $wt = 'json';
        if(empty($parame['wt']) == false)
        {
            $wt = $parame['wt'];
            unset($parame['wt']);
        }
        $url .= "?wt=".$wt;
        foreach($parame as $key=>$value)
        {
            $data .= "&". $key."=".$value;
        }
        $url .= $data;
        // echo $url."\n";
        $result = file_get_contents($url);
        return $result;
    }
 
}