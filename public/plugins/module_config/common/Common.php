<?php 
namespace plugins\module_config\common;
use mindplay\annotations\Annotations;
/**
 * 
 */
class Common
{
	/**
     * 获取配置接口数据
     *@param  $sysmbol    string     调用模块标识 用于读取配置文件
     *@param  $id   string      需求配置标识  控制器/方法_标识
     *@param  $param        array       请求参数 一维数组 []
     *@return 接口数据
     */
    public function getModuleApiData( $symbol = "", $id = "", $param = [] )
    {
        if( !is_array($param) ){
            return  $this->debugEcho('参数应为索引数组形式' );
        }
        //读取本模块的接口配置数据
        $demandConfig = getModuleConfig($symbol , "config" , "demand.json");
        $demandConfig = json_decode( $demandConfig,true );
        if( !is_array( $demandConfig ) || !isset( $demandConfig [$id] ) ){
            return $this->debugEcho( '未获取到数据，检查标识、id是否传递正确' );
        }
        $config = $demandConfig [$id];
        if( !isset( $config['api'] ) || empty( $config['api'] ) ){
            return $this->debugEcho( '未获取到api地址，去配置模块检查是否已配置了接口地址' );
        }
        $api = $config['api'];

        //解析接口配置地址  format: \app\xxxxController\controller\method
        $res = $this->classExists($api);

        if( $res == -1 ){
            return $this->debugEcho( '接口地址不合法,错误地址:'.$api );
        }else if( $res == 0 ){
			return $this->debugEcho( '类不存在:'.$this->classExists($api,true) );
        }else{
        	$resData = $this->methodExistsCheck( $res['class'] ,$res['method'] );
        	if( $resData == 0 ){
        		return $this->debugEcho('方法不存在：'.$resData['method'] );
        	}
        }
        $class = $res['class'];
        $method = $res['method'];

        //读取接口数据
        $obj = new $class;
        $res = call_user_func_array( [ $obj , $method ] , $param );
        return $res;
    }

    /**
     *调试数据
     */
    private function debugEcho( $str='调试提示信息' , $result = [] )
    {
        return  zy_json_echo(false,$str,null,104);
    }


	/**
	 * check mothod is exists?
	 */
	public function methodExists($api)
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
	 * 检查类方法是否存在
	 * @param  [type] $obj    [description]
	 * @param  [type] $method [description]
	 * @return [type]         [description]
	 */
	public function methodExistsCheck( $class, $method ){
		$obj = new $class;
		if( is_object( $obj ) &&  method_exists( $obj, $method ) ){
			return 1;
		}	
		return 0;
	}

	/**
	 * check class is exists ?
	 */
	public function classExists($api,$getClassAddress = false)
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
		if( $getClassAddress == true ){
			return $class;
		}
		return 0;
	}


	/**
	 * 获取所有模块信息
	 */
	public static function getModuleInfo()
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







}