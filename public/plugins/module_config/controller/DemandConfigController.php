<?php 
namespace plugins\module_config\controller; 
use think\Db;
use mindplay\annotations\Annotations;
use plugins\module_config\controller\VisitConfigController;

/**
 * 模块需求配置控制器
 */
class DemandConfigController extends VisitConfigController
{
	/**
	 * 需求配置首页
	 *@adminMenu(
     *     'name'   => '需求配置',
     *     'parent' => 'AdminIndex/index',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 1000,
     *     'icon'   => '',
     *     'remark' => '需求配置',
     *     'param'  => ''
     * )
	 */
	public function index()
	{	
		$demandList = parent::getConfigList('demand.json',true);
		$demandList = $this->selfCheckApiListStatus($demandList);
		//sort
		foreach ($demandList as $key => $value) {
			$value = parent::sortList( $value , 'status');
			$demandList [$key] = $value;
		}
		//$demandList = parent::sortList($demandList,'status');
		//get all module info
		$moduleList = parent::getModuleInfo();
		//$res = getModuleApiData( "","","",[[11,25,555],3,4]);
		$this->assign('demandList',$demandList);
		$this->assign('moduleList',$moduleList);
		return $this->fetch('/visitConfig/demand_index');
	}

	/**
	 *  $[name] [<description>]
	 *检查
	 */
	private function selfCheckApiListStatus ($list)
	{
		foreach ($list as $key => $value) {
			foreach ($value as $vk => $vv) {
				$status = -1;
				if( $vv['api'] != '' && !empty($vv['api']) ){
					$status = parent::methodExists($vv['api']);
				}
				$list [$key] [$vk] ['status'] = $status;
			}
		}
		return $list;
	}


	/**
	 * 获取系统所有模块信息
	 *
	 */
	protected function getModuleInfo()
	{
		return parent::getModuleInfo();
	}

	/**
	 * 更新接口配置数据
	 * @adminMenu(
     *     'name'   => '更新接口配置数据',
     *     'parent' => 'AdminIndex/index',
     *     'display'=> true,
     *     'hasView'=> false,
     *     'order'  => 1000,
     *     'icon'   => '',
     *     'remark' => '更新接口配置数据',
     *     'param'  => ''
     * )
	 */
	public function updateDemandConfigData()
	{
		try {
			if( !$this->request->isAjax() ){
				return zy_json_echo(false,'请求类型错误！');
			}
			$res = $this->getDemandAnntationInfo( true );
			if( isset( $res ['error'] ) ){
				return zy_json_echo(false,$res[ 'message' ]);
			}
			if( false === $res ){
				return zy_json_echo(false,'配置数据更新失败，请稍后再试！');
			}
			return zy_json_echo(true,'配置数据更新成功！');
		} catch (\Exception $e) {
			return zy_json_echo(false, '更新异常,'.$e->getMessage());
		}
			
	}

	/**
	 * 需求配置 api地址
	 */
	public function configApi()
	{
		$data = $this->request->param();
		$moduleName = $data['moduleName']; //模块标识
		$api = $data['api'];
		//get supply list by module symbol 
		$list = getModuleConfig($moduleName,'config','supply.json');
		$list = json_decode($list,true);
		if( empty($list) ){
			return '未获取到数据';
		}
		$list = autoHtmlspecialcharsDecode($list);
		//check api status
		$list = parent::checkApiListStatus($list);
		$this->assign('api',$api);
		$this->assign('supplyList',$list);
		return $this->fetch('/visitConfig/config_api');
	}


	/**
	 * 显示配置详细
	 */
	public function configParcular($moduleName,$id)
	{
		// read module demand config by module symbol and config id
		$configList = getModuleConfig($moduleName,'config','demand.json');
		$configList = json_decode( $configList, true );
		if( empty( $configList ) || !isset( $configList [$id] ) ){
			return "未找到配置： 配置标识($moduleName 、 $id) ";
		}

		$config =  $configList [$id] ;
		$this->assign('id',$id);
		$config['remark'] = autoHtmlspecialcharsDecode($config['remark']);
		$this->assign( 'config', $config );
		return $this->fetch('/visitConfig/config_particular');
	}

	/**
	 * 保存配置详情数据
	 */
	public function saveConfigPostData()
	{
		if( !$this->request->isPost() ){
			return zy_json_echo(false,'请求类型错误！');
		}
		$data = $this->request->param();
		$id = $data['id'];
		$symbol = $data['moduleName'];
		$update = $data ['update'];
		$this->saveConfig($symbol,$id,$update);
	}



	/**
	 * 配置保存
	 */
	public  function saveConfig($symbol,$id,$data)
	{
		$configList = getModuleConfig($symbol,'config','demand.json');
		$configList = json_decode( $configList, true );
		if( empty( $configList ) || !isset( $configList [$id] ) ){
			return zy_json_echo(false,"未找到配置： 配置标识($symbol 、 $id");
		}
		foreach ($data as $key => $value) {
			$configList [$id] [$key] = $value;
		}
		$res = saveModuleConfigData($symbol,'config','demand.json',$configList);
		if( !$res ){
			return zy_json_echo(false,'保存失败，请稍后再试!');
		}
		return zy_json_echo(true,'保存成功！!');
	}

	/**
	 * 获取所需配置注释信息
	 * $update bool 是否更新配置文件
	 * ==this method return true or false or demand config list
	 * ==demand list is a multidimensional array
	 */
	protected function getDemandAnntationInfo( $update = false )
	{
		try {

			$moduleInfoList = [];
			Annotations::$config['cache']                 = false;
	        $annotationManager                            = Annotations::getManager();
	        $annotationManager->registry['actionInfo']     = 'app\admin\annotation\ActionInfoAnnotation';

	        $moduleRootPath = PLUGINS_PATH;

	        $directorys = scandir($moduleRootPath);
	        //traversal directory 
	        foreach ($directorys as $key => $dir) {
	        	if( $dir == '.' || $dir == '..' ){ continue; }

	        	$moduleName = cmf_parse_name($dir,1);
	        	$root = "plugins\\$dir\\controller";
	        	$class  =  "$root\\AdminIndexController";

	        	if( !class_exists($class) ){ continue ; }
	        	try {
	        		$infoList = Annotations::ofClass( $class , '@actionInfo' );
	        	} catch (\Exception $e) {
	        		throw new \Exception("位置：$class".$e);	
	        	}
	        	if( !empty($infoList) ){
	        		$list = $infoList[0];
	        		if( is_object($list) ){
	        			$list = (array)$list;
	        		}
	        		$moduleInfoList = $this->reassemblyConfig( $moduleInfoList , $list );
	        	}
	        }
        } catch (\Exception $e) {
			return [ 'error' => false  , 'message' => "Exception:配置更新异常,".$e->getMessage() ];
		}

        //Enable update operation  ,write config content to a file in demand.json
        if( true === $update ){
        	//this method return true or false
        	return $this->updateDemandFile( $moduleInfoList );
        }
        return $moduleInfoList;
	}

	/**
	 * 数据重装 
	 * data format: ['module_name'=>[config content( is An associative array )]]
	 *
	 * if shortage required field ,this config will be ignore  ,FLAG [**F001**]
	 */
	private function reassemblyConfig( $moduleInfoList = [] , $configList )
	{
		$initArray = ['demandName','demandSymbol','explain'] ;
		sort($initArray);
		$list = $configList['list'];
		$name = $configList[ 'name' ] ; 		// 配置模块名字
		$symbol = cmf_parse_name( $configList ['symbol'] ) ; 		//配置模块标识 (带下划线)
		// list like => [ id=>[ 'demandName' =>'', 'demandSymbol'=>, 'explain'=>''] ]
		foreach ( $list as $id => $lv ) {
			$keys = array_intersect($initArray, array_keys($lv));
			sort($keys);
			/*if($initArray != $keys)	continue; //缺少必要字段  ignore config  [**F001**]*/
			if( $initArray != $keys ){
				throw new \Exception( '配置确少关键字段，请检查是否存在如下字段：demandName、demandSymbol、explain' );
			}
			//id = list's key  
			$temportary ['name'] = $name ;
			$temportary ['symbol'] = $symbol;
			$temportary ['demandName'] = $lv['demandName'];
			$temportary ['demandSymbol'] = $lv['demandSymbol']; 
			$temportary ['api'] = '';
			$temportary ['explain'] = $lv['explain'];
			$temportary ['remark'] = '';
			$moduleInfoList [$symbol] [$id] = $temportary;        
		}
		return $moduleInfoList;
	} 

	/**
	 * 清空所有模块下的demand.json配置
	 */
	private function clearnDemandConfig()
	{
		$moduleList = parent::getModuleInfo();
		foreach ($moduleList as $key => $value) {
			saveModuleConfigData( $value ['name'] , "config", 'demand.json', [] );
		}
	}

	/**
	 * 更新模块配置文件信息 
	 * 将新的数据保存到配置文件
	 * 更新要求：1、以注释数据为主
	 * 			2、同一条记录更新，保留配置文件中的api/remark数据
	 * 			3、去除配置文件中多余的数据
	 */
	public function updateDemandFile( $configList )
	{
		//获取所有模块 并且遍历
		$moduleList = parent::getModuleInfo();

		foreach ( $moduleList as $key => $value ) {
			$moduleName = cmf_parse_name( $value [ 'symbol' ] ); //模块名字 下划线格式
			$insert = false ; //最新数据
			//判断当前模块是否有数据
			try {
				if( isset( $configList[ $moduleName ] ) ){
					//存在数据，更新数据 , 更新数据原则，不存在添加，存在的时候保持 api  remark字段数据不变
					//获取旧数据
					$old = $content = getModuleConfig( $moduleName ,'config', 'demand.json' );
	        		$old = json_decode($content,true);
	        		//考虑到没有加上配置文件的时候，需要添加数据，所以不能直接continue
	        		if( !is_array( $old ) ) $old = [] ;
	        		//遍历当前模块的最新数据
					foreach ( $configList[ $moduleName ] as $id => $mv ) {
						if( isset( $old [ $id ] )  ){
							//保留当前旧数据的api  remark值
							$mv [ 'api' ] = $old [ $id ] ['api'];
							$mv [ 'remark' ] = $old [ $id ] ['remark'];
						}
						$insert [ $id ]  = $mv;
					}
				}else{
					//删除以前的数据 ,如果配置文件存在，则清空
					$demandPath = PLUGINS_PATH.$moduleName.'/config/demand.json';
					if( file_exists(  $demandPath ) ){
						$insert = [];
					}
				}
				//保存数据
				if( false !==  $insert ){
					saveModuleConfigData( $moduleName, "config", 'demand.json',  $insert );
				}
			} catch (\Exception $e) {
				//throw new \Exception($e->getMessage);
				return false;
			}
		}
	}

	/**
	 * 查看接口信息
	 */
	public function showApiInfo()
	{
		$data = $this->request->param();
		$symbol = $data['moduleName'];
		$id = $data['id'];
		//get supply config
		$supply = getModuleConfig( $symbol , 'config', 'supply.json');
		$supply = json_decode( $supply ,true);
		$this->assign('moduleName',$symbol);
		$this->assign('id',$id);
		if( is_array($supply) &&  isset($supply[$id]) ){
			$this->assign('supplyInfo' , autoHtmlspecialcharsDecode( $supply[$id] ) );
			return $this->fetch('/visitConfig/demand_particular');
		}else{
			return '未获取到数据！';
		}
	}

	/**
	 * 清除接口地址
	 */
	public function clearApi( $id , $symbol )
	{
		//获取配置数据
		$list = getModuleConfig( $symbol , 'config', 'demand.json');
		$list = json_decode( $list ,true);
		if( !empty( $list ) && isset( $list [$id] ['api'] ) ){
			$list [ $id ] ['api'] = '';
			saveModuleConfigData( $symbol, "config", 'demand.json', $list );
		}else{
			$this->error('清除接口地址失败，未找到配置！');
		}
		$this->success('清除接口地址成功！');
	}

}