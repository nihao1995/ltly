<?php 
namespace plugins\sub_appmarket\controller;
use think\Db;
use think\Config;
use cmf\controller\PluginAdminBaseController;//引入此类
use plugins\sub_appmarket\controller\AdminIndexController;
use app\admin\model\PluginDbTableModel;
use plugins\sub_appmarket\model\CheckFileModel;
use plugins\sub_appmarket\lib\Download;
use plugins\sub_appmarket\controller\ApiCommunicationController;  // 链接主系统接口

/**
 * 检查控制器
 */
class InstallCheckController extends PluginAdminBaseController
{

	/**
	 * 配置检查项
	 */
	protected $checkItem = [
		0=>[
			'item' => 'isBought',
		],
		1=>[
			'item' => 'moduleFileExist',
		],
		/*2=>[
			'item' => 'installDataTable',
		],*/
	];
	/**
	 * 获取检查项
	 * @return [type] [description]
	 */
	public function getCheckItem()
	{
		return $this->checkItem ;
	}

	/**
	 * 开启检查项
	 * @param  integer $index [description]
	 * @return [type]         [description]
	 */
	public function check( $index = 0 )
	{	
		$data = $this->request->post();
		$param = isset( $data['param'] ) ? $data['param'] : [];
		$method = $this->checkItem[ $index ][ 'item' ];
		return call_user_func_array( [ $this , $method ] , $param );
	}

	/**
	 * 是否购买
	 */
	public function isBought( $moduleId = 0 )
	{
		$adminIndex = new AdminIndexController();
		$userId = $adminIndex->checkLogin();
		if( false == $userId ){
			self::returnStr('用户信息检查','用户信息失效',100);
			return zy_json_echo( false , self::$returnStrArray );
		}
		if( false == $adminIndex->isBought( $moduleId ) ){
			self::returnStr('产品授权信息检查','未获取到授权记录',101);
			return zy_json_echo( false , self::$returnStrArray );
		}	
		self::returnStr('产品授权信息检查','已授权');
		return zy_json_echo( true , self::$returnStrArray );
	}

	/**
	 * 检查文件是否存在
	 */
	public function moduleFileExist( $moduleId = 0 )
	{
		// 获取详细信息
        $apiCommunication = new ApiCommunicationController();
        $module = $apiCommunication->particular(['id'=>$moduleId]);	// 获取下载地址
        $module = $module['data']['module'];

		if( empty( $module )){
			self::returnStr('模块文件检查','模块信息错误', 100 );
			return zy_json_echo( false , self::$returnStrArray  );
		}
		$fileName = $this->checkModuleFileIsExist( $module['name'] , cmf_parse_name( $module ['name'] ).'Plugin.php') ;
		//$fileName = PLUGINS_PATH.DS.cmf_parse_name( $module['name'] ).DS.cmf_parse_name( $module['name'] ).'Plugin.php';
		if( !file_exists( $fileName )  ){
			self::returnStr('模块文件检查','模块文件不存在', 201 );
			self::returnStr('执行模块文件下载','模块文件下载中，请稍后',201,cmf_plugin_url('SubAppmarket://InstallCheck/download').'?moduleId='.$moduleId);
			return zy_json_echo( false , self::$returnStrArray );
		}
		//return zy_json_echo( true , '模块文件已存在' );
		self::returnStr('模块文件是否存在','模块文件已存在' );
		return zy_json_echo( true , self::$returnStrArray );
	}


	/**
	 * 执行文件下载
	 */
	public function download( $moduleId )
	{
        $company_data = Db::name('company')->where('id',1)->find();

		$adminIndex = new AdminIndexController();
		$userId = $adminIndex->checkLogin();
		$datas = [
			'id'=>$moduleId,
			'pid'=>$userId,
			'cid'=>$company_data['company_id'],
		];

        $apiCommunication = new ApiCommunicationController();
        $downloadHost = $apiCommunication->getSourceAddr($datas);	// 获取下载地址

        // 如果备用地址为空，那么就用主地址
        if(empty($downloadHost['data']['minor'])){
	        $downloadHost= $downloadHost['data']['main'];
        }else{
	        $downloadHost= $downloadHost['data']['minor'];
        }


        if( empty( $downloadHost ) ){
            //return zy_json_echo( false , '未获取到主机地址！' );
            self::returnStr('模块文件下载','未获取到主机地址！',100);
            return zy_json_echo( true , self::$returnStrArray );
        }
        //检查是否存在该模块文件，如果不存在就去下载并解压文件
        $pluginsPath = PLUGINS_PATH;

        $url = $downloadHost;

		$res = Download::fileDownload( $url , $pluginsPath );


		if( isset( $res [ 'status' ] ) && $res [ 'status' ] == false ){
			self::returnStr( '模块文件下载' , $res [ 'message' ] ,102 );
			return zy_json_echo( true , self::$returnStrArray );
		}
		self::returnStr('模块文件下载','下载完成' );
		self::returnStr('模块文件下载','文件解压中',201,cmf_plugin_url('SubAppmarket://InstallCheck/unZip').'?moduleId='.$moduleId.'&source='.$res);
		return zy_json_echo( true , self::$returnStrArray );
	}

	/**
	 * 执行文件解压
	 */
	public function unZip( $moduleId , $source )
	{
		// 获取详细信息
        $apiCommunication = new ApiCommunicationController();
        $module = $apiCommunication->particular(['id'=>$moduleId]);	// 获取模块详情
        $module = $module['data']['module'];

		if( empty( $module )){
			self::returnStr('模块文件解压','读取模块信息时发生错误', 100 );
			return zy_json_echo( false , self::$returnStrArray  );
		}
		// $outPath = PLUGINS_PATH.cmf_parse_name( $module [ 'name' ]  );
		$outPath = PLUGINS_PATH;
		self::returnStr('模块文件解压', $outPath  );
		$res = Download::unZip(  $source , $outPath );
		if( $res [ 'status' ] == false ){
			self::returnStr('模块文件解压', $res [ 'message' ] , 101 );
			return zy_json_echo( true , self::$returnStrArray );
		}
		self::returnStr('模块文件解压',$res [ 'message' ] );
		self::returnStr('执行数据表检查','检查中',201,cmf_plugin_url('SubAppmarket://InstallCheck/installDataTable').'?moduleId='.$moduleId);
		return zy_json_echo( true , self::$returnStrArray );
	}


	/**
	 * 检查数据表
	 */
	public function installDataTable( $moduleId = 0 )
	{
		// $module = Db::name( 'module_store' )->where( 'id' , $moduleId )->find();
		// 获取详细信息
        $apiCommunication = new ApiCommunicationController();
        $module = $apiCommunication->particular(['id'=>$moduleId]);	// 获取模块详情
        $module = $module['data']['module'];


		//模块文件不存在无法检测表数据
		if( !$this->checkModuleFileIsExist( $module['name'] ) ){
			self::returnStr('数据表配置检查','模块文件不存在',100);
			return zy_json_echo( true , self::$returnStrArray );
		}

		$pluginDbTableModel = new PluginDbTableModel();
		$res = $pluginDbTableModel->chechModuleAndTableIsExists( $module['name'] ) ;
		if(  !$res  ){
			self::returnStr('数据表配置检查','模块数据表配置错误',101);
			return zy_json_echo( true , self::$returnStrArray );
		}

		//检查所需配置模块
		$res = CheckFileModel::checkDemandModuleFileExist( $module [ 'name' ] );
		if( $res != true && !empty( $res [ 'isExist'] ) ){
			self::returnStr( '检查模块所需配置' , $res [ 'isExist' ] );
		}
		if( $res != true && !empty( $res [ 'isNoExist'] ) ){
			self::returnStr( '检查模块所需配置' , $res [ 'isNoExist' ] );
			return zy_json_echo( true , self::$returnStrArray );
		}

		//检查所需配置模块数据表
		$res = CheckFileModel::checkDemandDbTableExist( $module [ 'name' ] );
		if( $res != true ){
			self::returnStr( '检查模块所需表配置' , $res );
			return zy_json_echo( true , self::$returnStrArray );
		}
			
		//return zy_json_echo( true , '数据表配置正确' );
		self::returnStr('数据表配置检查','数据表配置正确');
		return zy_json_echo( true , self::$returnStrArray );
	}

	/**
	 * 执行安装方法
	 */
	public function install( $moduleId )
	{
		// $module = Db::name( 'module_store' )->where( 'id' , $moduleId )->find();
		// 获取详细信息
        $apiCommunication = new ApiCommunicationController();
        $module = $apiCommunication->particular(['id'=>$moduleId]);	// 获取模块详情
        $module = $module['data']['module'];



		//执行安装操作
        $pluginInstallObj = new \app\admin\controller\PluginController();
        $res = $pluginInstallObj -> install( $module[ 'name' ] );

		self::returnStr('执行数据安装',json_encode($res));
		return zy_json_echo( true , self::$returnStrArray );
	}
	
	/**
	 * 检查文件是否存在
	 */
	private function checkModuleFileIsExist( $symbol , $file = '' )
	{
		$fileName = PLUGINS_PATH.DS.cmf_parse_name( $symbol );
		if( !empty( $file ) ){
			$fileName = $fileName.DS.$file ;
		}	
		if( !file_exists($fileName) ){
			return false;
		}
		return $fileName;
	}

	/**
	 * 状态返回
	 * @return [type] [description]
	 */
	protected static $returnStrArray = [];
	private static function returnStr( $item='' , $msg='' , $code = 200 , $operate = 'empty' )
	{
		$data = [
			'item' 	=> $item,
			'msg'	=> $msg,
			'code' 	=> $code,
			'operate' => $operate
		];
		self::$returnStrArray [] = $data;
	}
}
