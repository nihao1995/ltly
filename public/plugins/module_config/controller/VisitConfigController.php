<?php 
namespace plugins\module_config\controller; 
use cmf\controller\PluginAdminBaseController;//引入此类
use think\Db;
use mindplay\annotations\Annotations;

/**
 * 模块间访问配置控制器
 */
class VisitConfigController extends PluginAdminBaseController
{

	/**
	 *配置首页 
	 *@adminMenu(
     *     'name'   => '模块访问配置管理',
     *     'parent' => 'AdminIndex/index',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 1000,
     *     'icon'   => '',
     *     'remark' => '模块访问配置管理',
     *     'param'  => ''
     * )
	 */
	public function index()
	{
		$supplyList = array_reverse($this->getConfigList());
		$supplyList = $this->checkApiListStatus($supplyList);
		$supplyList = $this->sortList($supplyList,'status');
		$moduleList = $this->getModuleInfo();
		$this->assign('moduleList',$moduleList);
		$this->assign('supplyList',$supplyList);
		//检索所有对外配置的数据
		return $this->fetch("/visitConfig/index");
	}


	/**
	 * 检查接口状态
	 */
	protected function checkApiListStatus($list)
	{	
		foreach ($list as $key => $value) {
			//api demo : plugins\module_config\visit_config\methodExists
			$value['status'] = -1 ;
			if( isset($value['api']) && !empty($value['api']) ){
				$value ['status'] = $this->methodExists($value['api']);
			}  			
			$list [$key] = $value;
		}

		return $list;
	}

	/**
	 * check mothod is exists?
	 */
	protected function methodExists($api)
	{
		$isClass = $this->classExists($api);
		if( $isClass != 0 && $isClass != -1 ){
			$object = new $isClass['class'];
			if( is_object( $object ) &&  method_exists( $object, $isClass['method'] ) ){
				return 1;
			}
		}
		return 0;
	}

	/**
	 * check class is exists ?
	 */
	protected function classExists($api)
	{
		// the api address convert to class reference address . 
		//api address: plugins\module_config\visit_config\methodExists
		//eg: \plugins\module_config\controller\VisitConfigController
		//$str = "/plugins/module_config/visit_config/methodExists/";
		$str = trim($api,'/');
		$arr = explode('/',$str);
		if( count($arr) != 4 ){
			return -1;
		}
		$app = $arr[0].'\\'.cmf_parse_name($arr[1]);
		$controller = cmf_parse_name($arr[2],1);
		$method = $arr[3];
		$class = "\\$app\\controller\\$controller"."Controller";
		if( class_exists( $class) ){
			return ['class'=> $class, 'method' => $method];
		}
		return 0;
	}



	/**
	 * 获取所有模块对外接口列表数据
	 */
	protected function getConfigList($fileName = "supply.json" ,$hasKeyforModuleSymbol = false)
	{
		$moduleRootPath = PLUGINS_PATH;

        $directorys = scandir($moduleRootPath);

        $list = [];
        //traversal directory 
        foreach ($directorys as $key => $dir) {
        	if( $dir == '.' || $dir == '..' ){ continue; }
        	$content = getModuleConfig($dir,'config',$fileName);
        	$content = json_decode($content,true);
        	if( !empty($content) && is_array($content) ){
        		if( $hasKeyforModuleSymbol == true ){
        			$list [$dir] = $content;
        		}else{
        			$list = array_merge($list,$content);
        		}
        	}
        }
        return $list;
	} 


	/**
	 * 获取所有模块名字
	 */
	protected function getModuleInfo()
	{
		$moduleInfoList = [];
		Annotations::$config['cache']                 = false;
        $annotationManager                            = Annotations::getManager();
        $annotationManager->registry['pluginInfo']     = 'app\admin\annotation\PluginInfoAnnotation';

        $moduleRootPath = PLUGINS_PATH;
        //dump($moduleRootPath);exit;
        $directorys = scandir($moduleRootPath);
        //traversal directory 
        foreach ($directorys as $key => $dir) {

        	if( $dir == '.' || $dir == '..' ){ continue; }

        	$moduleName = cmf_parse_name($dir,1);

        	$class = "\plugins\\$dir\\$moduleName"."Plugin";

        	if( !class_exists($class) ){ continue; }

        	$infoList = Annotations::ofClass($class,'@pluginInfo');

        	if( !empty($infoList) ){
        		$list = $infoList[0];
        		if( is_object($list) ){
        			$list = (array)$list;
        		}
        		//去重复
        		if( !in_array( $list , $moduleInfoList ) ){
					$moduleInfoList[] = $list;
        		}
        	}
        }

        return $moduleInfoList ;
	}

	/**
	 * 获取配置
	 */
	private function getConfig($module,$directory,$fileName)
	{
		$list = getModuleConfig($module,$directory,$fileName);
		$list = json_decode($list,true);
		if( !is_array($list) ){
			return false;
		}
		return $list;
	}

	/**
	 * 添加配置
	 */
	public function addConfig()
	{
		if(!$this->request->isPost()){
			return zy_json_echo(false,'请求类型错误！');
		}
		$data = $this->request->param();

		$list  = $data['data'];
		$groupConfig = [];
		$classIsExists = 0;
		$errorApi = '';
		foreach ($list as $key => $value) {
			$temporary ['name'] = $value['moduleName'];
			$temporary ['symbol'] = $value['symbol'];
			$temporary ['api'] =  str_replace( '\\' , '/' , $value ['api'] );
			$classIsExists = $this->classExists( $temporary['api'] );
			if( 0 == $classIsExists || -1 == $classIsExists ){
				$errorApi = $temporary['api'];
				break;
			}
			$temporary ['explain'] = $value['explain'];
			$temporary ['remark'] = '';
			$id = time().mt_rand(10000,99999);
			$groupConfig [ $value['symbol'] ][$id] = $temporary; 
		}
		if( -1 == $classIsExists ){
			return zy_json_echo(false,'配置保存失败，接口地址配置错误：'.$errorApi);
		}else if( 0 == $classIsExists ){
			return zy_json_echo(false,'配置保存失败，接口所在类不存在：'.$errorApi);
		}
		$isOk = true;
		try {
			foreach ($groupConfig as $key => $value) {
				//读取模块配置
				$content = getModuleConfig($key,'config','supply.json');

				$content = json_decode($content,true);
				$insert = [];
				if(empty($content)){
					$insert = $value;
				}else{
					$insert = array_merge($content,$value);
				}
	    		
	    		//saveModuleConfigData($moduleName = "",$directory = "config",$fileName = "",$data = "")
	    		$insert = json_encode($insert,JSON_UNESCAPED_UNICODE);
	    		$res = saveModuleConfigData($key,'config','supply.json',$insert);
	    		if(!$res){
	    			$isOk = false;
	    		}
			}
		} catch (\Exception $e) {
			return zy_json_echo(false,'配置保存失败请稍后再试！x');
		}
		
		if(!$isOk){
			return zy_json_echo(false,'部分数据保存成功，请检查后再提交！');
		}
		return zy_json_echo(true,'配置保存成功！');
	}


	/**
	 * 删除配置
	 *@adminMenu(
     *     'name'   => '删除配置',
     *     'parent' => 'VisitConfig/index',
     *     'display'=> true,
     *     'hasView'=> false,
     *     'order'  => 1000,
     *     'icon'   => '',
     *     'remark' => '删除配置',
     *     'param'  => ''
     * )
	 */
	public function deleteConfig()
	{
		$data = $this->request->param();
		$list = $this->getConfig($data['symbol'],'config','supply.json');
		if(false === $list){
			$this->error('删除失败，请稍后再试！');
		}
		if( !isset($list[$data['id']]) ){
			$this->error('删除失败，请稍后再试！');
		}
		$api = $list [ $data ['id'] ] ['api'];
		//删除已经配置的接口
		$this->deleteDemaindConfig( $data['symbol'] , $api );
 		unset($list[$data['id']]);
		$list = json_encode($list,JSON_UNESCAPED_UNICODE);
	    $res = saveModuleConfigData($data['symbol'],'config','supply.json',$list);
	    if(!$res){
	    	$this->error('删除失败，请稍后再试！');
	    }
	    $this->success('删除成功！');
	}

	/**
	 * 删除供应配置的时候 删除已经配置的数据
	 * $symbol  需求模块
	 * $api 	api地址
	 */
	private function deleteDemaindConfig( $symbol , $api )
	{	
		$symbol = cmf_parse_name( $symbol );
		//读取所有demaind.json的配置
		$list = $this->getConfigList('demand.json',true);
		if( empty($list) ||  !$list ){
			return true;
		}
		foreach ($list as $key => $value) {
			$newData = $value ;
			foreach ($value as $ko => $vo ) {
				if( $vo [ 'demandSymbol' ] == $symbol ){
					if( $vo [ 'api' ] == $api ){
						$newData [$ko] ['api'] = ''; 
					}
				}
			}
			saveModuleConfigData( $key, "config", 'demand.json', $newData );	
		}
	}


	/**
	 * 修改配置
	 */
	public function editConfig()
	{
		$data = $this->request->param();
		$list = $this->getConfig($data['newConfig']['symbol'],'config','supply.json');
		if(false === $list){
			return zy_json_echo(false,'修改失败，请稍后再试！');
		}
		if( !isset($list[$data['id']]) ){
			return zy_json_echo(false,'修改失败，请稍后再试！');
		}
		$oldConfig = $list[$data['id']];
		$newApi = $data['newConfig']['api'];

		//接口详情 remark 不能替换
		$data['newConfig']['remark'] = ( isset ( $list[$data['id']]['remark'] )) ? $list[$data['id']]['remark'] : '';

		$list[$data['id']] = $data['newConfig'];
		$list = json_encode($list,JSON_UNESCAPED_UNICODE);
	    $res = saveModuleConfigData($data['newConfig']['symbol'],'config','supply.json',$list);
	    if(!$res){
	    	return zy_json_echo(false,'修改失败，请稍后再试！');
	    }
	    if( $oldConfig['api'] != $newApi ){
	    	$this->modifyDemandReferenceConfig($oldConfig,$newApi);
	    }
	    return zy_json_echo(true,'修改成功！');
	}

	/**
	 * 修改被引用的地址
	 */
	private function modifyDemandReferenceConfig($oldConfig,$newApi){
		$oldApi = $oldConfig['api'];
		$demandList = $this->getConfigList('demand.json',true);
		foreach ($demandList as $key => $value) {
			$have = false;
			foreach ( $value as $vk => $vv ) {
				if( $oldApi == $vv['api'] ){
					$vv['api'] = $newApi;
					$demandList [$key] [$vk] = $vv;
					$have = true;
				}
			}
			if($have){
				$res = saveModuleConfigData($key,'config','demand.json',$demandList[$key]);
			}
		}
	}

	/**
	 * 根据数组某个字段排序
	 */
	protected function sortList($list,$filed,$type = 'ASC')
	{
		$status = array_column($list,$filed);
		if( $type == 'ASC' ){
			array_multisort($status,SORT_ASC,$list);
		}else{
			array_multisort($status,SORT_DESC,$list);
		}
		return $list;
	}



	/**
	 * 查看配置详情
	 */
	public function supplyParticular($moduleName , $id)
	{
		$configList = getModuleConfig($moduleName,'config','supply.json');
		$configList = json_decode( $configList, true );
		if( empty( $configList ) || !isset( $configList [$id] ) ){
			return "未找到配置： 配置标识($moduleName 、 $id) ";
		}
		$config =  $configList [$id] ;
		$this->assign('id',$id);
		$config['remark'] = autoHtmlspecialcharsDecode($config['remark']);
		$this->assign( 'config', $config );
		return $this->fetch('visitConfig/supply_particular');
	}

	/**
	 *保存配置详情
	 */
	public function saveConfigParticular()
	{
		if( !$this->request->isPost() ){
			return zy_json_echo(false,'请求类型错误！');
		}
		$data = $this->request->param();
		$id = $data['id'];
		$symbol = $data['moduleName'];
		$remark = $data ['remark'];
		$configList = getModuleConfig($symbol,'config','supply.json');
		$configList = json_decode( $configList, true );

		$configList [$id] ['remark'] = $remark; 

		$res = saveModuleConfigData($symbol,'config','supply.json',$configList);
		if( !$res ){
			return zy_json_echo(false,'保存失败，请稍后再试!');
		}
		return zy_json_echo(true,'保存成功！!');
	}


}