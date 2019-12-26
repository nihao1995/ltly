<?php 

namespace plugins\module_config\controller; 
use cmf\controller\PluginAdminBaseController;//引入此类
use think\Db;
use plugins\module_config\controller\VisitConfigController;
use org\Baksql;


/**
 * 数据表配置管理控制器
 */
class TableConfigController extends VisitConfigController
{
	/**
	 *配置首页 
	 *@adminMenu(
     *     'name'   => '数据表配置管理',
     *     'parent' => 'AdminIndex/index',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 1000,
     *     'icon'   => '',
     *     'remark' => '数据表配置管理',
     *     'param'  => ''
     * )
	 */
	public function index()
	{
		//操作思路：
		//1、先读当前模块下的config.php文件里面的数据（应该读取所有模块下的这个文件）
		//2、在把字段、表名进行和数据库比对，如果缺少，那么就显示`缺少表`，`字段缺失`
		//3、展示到页面上
		
		//1、获取所有模块的名字
		$all_module = parent::getModuleInfo();
		$list_info = [];	//当面页面显示数据

		//2、验证模块数据库字段是否存在
		foreach ($all_module as $k => $v) {
			$module_info = getModuleConfig($all_module[$k]['symbol'],'data','config.php');

			$list_info[$k]['module_name'] = $all_module[$k]['name'];	//当前模块名称
			$list_info[$k]['module_key'] = $all_module[$k]['symbol'];	//当前模块关键词
			if (!$module_info || !is_array( $module_info ) ) continue;

			foreach ($module_info as $kk => $vv) {	//模块名
				if( !is_array( $vv ) ) continue;
				foreach ($vv as $kkk => $vvv) {	//表
					if( !is_array( $vvv ) ) continue;
					$list_info[$k]['data'][$kkk]['ss_module']= $vvv['module'];	//所属模块信息
					$list_info[$k]['data'][$kkk]['ss_modulename']= $kk;	//所属模块名
					$list_info[$k]['data'][$kkk]['ss_table']= $vvv['tableName'];	//所属表名
					$list_info[$k]['data'][$kkk]['ss_table_annotation']= $vvv['tableAnnotation'];	//所属模块注释
					$list_info[$k]['data'][$kkk]['ss_describe']= $vvv['describe'];	//所属表名

					$field_name = array_keys($vvv['data']);

					//sql语句：查找表里所有字段信息
					$sql_table = $this->fieldSql('query',$vvv['tableName']);

					//查找某个值是否存在于多维数组中
					for ($i=0; $i < count($field_name); $i++) { 
						$field_off = $this->deep_in_array($field_name[$i],$sql_table);
						if($field_off==false){
							$list_info[$k]['data'][$kkk]['status']=4;	//1正常/2模块缺失/3表缺失/4字段缺失
							break;
						}else{
							$list_info[$k]['data'][$kkk]['status']=1;
						}
					}
				}

			}
		}

		// dump($list_info);
		//3、展示到页面上
		$this->assign('list_info',$list_info);
		return $this->fetch("/tableConfig/index");
	}


	/**
	 *配置首页-添加表
	 */
	public function addTable()
	{
		$request = request();
		if ($request->isPost()) {
			var_dump('11');
			exit();
		} else {
			return $this->fetch("/tableConfig/addTable");
		}
	}
	/**
	 *配置首页-删除表
	 *@adminMenu(
     *     'name'   => '删除表',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 1000,
     *     'icon'   => '',
     *     'remark' => '删除表',
     *     'param'  => ''
     * )
	 */
	public function clearTable()
	{
		$data = $this->request->param();
	    $this->success('清空成功！');
	}
	/**
	 *配置首页-更新文件到本地
	 *@adminMenu(
     *     'name'   => '更新本地文件',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 1000,
     *     'icon'   => '',
     *     'remark' => '更新本地文件',
     *     'param'  => ''
     * )
	 */
	public function updateFile()
	{
		//操作思路：
		//1、获取所有模块
		//2、根据所选模块，更新相对应的sql文件到本地来
		//3、提示操作成功
		

		//1、获取所有模块
		$all_module = parent::getModuleInfo();
		// dump($all_module);

		//2、根据所选模块，更新相对应的sql文件到本地来
		$PluginDbTableModel = new \app\admin\model\PluginDbTableModel;
		$backup = new Baksql(\think\Config::get("database"));
        foreach ($all_module as $k => $v) {
        	$module_name = cmf_parse_name($all_module[$k]['symbol']);
        	$sql_name = $PluginDbTableModel->getDataPathFile($module_name,'sql',true);

        	if(!$sql_name){
        		continue;
        		$this->error('更新失败！'.$module_name.'模块数据操作不规范！');
        	}

        	// sql文件下载到本地
        	foreach ($sql_name as $kk => $vv) {
        		$path = ROOT_PATH . 'public' . DS .'plugins'. DS .$module_name. DS .'data/';
		        $fileName = $vv.'.sql';
		        $info = $backup->backup([$vv],$path,$fileName);
        	}

        }

        //3、提示操作成功
	    $this->success('更新成功！');
	}
	/**
	 *配置首页-字段缺失
	 */
	public function fieldLose()
	{
		$request = request();
		//操作思路：
		//1、获取get值
		//2、查看数据库这张表，获取所有字段
		//3、对数据库的所有字段和配置文件里面的字段进行比较
		//4、字段都给列出来，显示在页面上
		
		//1、获取get值
		$modulekey = $request->get('modulekey');
		$moduleName = $request->get('moduleName');
		$tableName = $request->get('tableName');

		//2、查看数据库这张表，获取所有字段
		$sql_table = $this->fieldSql('query',$tableName);
		// var_dump($sql_table);

		//3、对数据库的所有字段和配置文件里面的字段进行比较
		$module_info = getModuleConfig($modulekey,'data','config.php');
		$field_name = $module_info[$moduleName][$tableName]['data'];
		$field_name1 = array_keys($field_name);

		for ($i=0; $i < count($field_name1); $i++) { 
			$field_off = $this->deep_in_array($field_name1[$i],$sql_table);

			if ($field_off==false) {
				$field_name[$field_name1[$i]]['status']=2;
			}else{
				$field_name[$field_name1[$i]]['status']=1;
			}
		}

		// dump($field_name);
		//4、字段都给列出来，显示在页面上
		$field_info = $field_name;
		$module_info = [$modulekey,$moduleName,$tableName];
		$this->assign('module_info',$module_info);
		$this->assign('field_info',$field_info);
		return $this->fetch("/tableConfig/fieldLose");
	}
	/**
	 *字段管理 
	 */
	public function fieldManage()
	{
		return $this->fetch("/tableConfig/fieldManage");
	}
	/**
	 *字段管理-添加
	 */
	public function addField()
	{
		$request = request();
		if ($request->isPost()) {
			//操作思路：
			//1、获取模块、表名、字段名
			//2、通过以上获取到值进行查看配置文件里字段详细的值
			//3、执行sql增加操作，返回操作成功
			
			//1、获取模块、表名、字段名
			$modulekey = $request->post('modulekey');
			$moduleName = $request->post('moduleName');
			$tableName = $request->post('tableName');
			$fieldArr = $request->post('field');

			if (!$fieldArr){
				return zy_json_echo(false,'操作失败！','',-1);
			};

			$fieldArr = rtrim($fieldArr, ',');
			$fieldArr = explode(",", $fieldArr);

			//2、通过以上获取到值进行查看配置文件里字段详细的值
			$module_info = getModuleConfig($modulekey,'data','config.php');
			$field_name = $module_info[$moduleName][$tableName]['data'];

			//3、执行sql增加操作，返回操作成功
			for ($i=0; $i < count($fieldArr); $i++) { 
				
				$field_name_arr = $field_name[$fieldArr[$i]];

				if($field_name_arr){
					$add = $field_name_arr['name']." ";//字段名称

					if($field_name_arr['length']){
						$add.=$field_name_arr['type']."(".$field_name_arr['length'].") ";//类型+长度
					}else{
						$add.=$field_name_arr['type']." ";//类型
					}

					if($field_name_arr['default']=="无"){//默认:无
						$add.= "NOT NULL ";//默认
					}elseif($field_name_arr['default']=="NULL"){//默认:NULL
						$add.= 'DEFAULT NULL ';//默认
					}elseif(!empty($field_name_arr['default'])){//默认:定义
						$add.= "NOT NULL default '".$field_name_arr['default']."' ";//默认
					}
					if($field_name_arr['auto']==1){
						$add.= "PRIMARY KEY ";//主键
					}
					if($field_name_arr['increase']==1){
						$add.= "AUTO_INCREMENT ";//自增
					}
					if($field_name_arr['annotation']){
						$add.= "COMMENT '".$field_name_arr['annotation']."'";//自增
					}

					$this->fieldSql('addField',$tableName,$add);
					// var_dump($add);
				}

			}

			return zy_json_echo(true,'添加成功！','',200);
		}

	}
	/**
	 *字段管理-移除
	 */
	public function delField()
	{
		$data = $this->request->param();
	    $this->success('操作成功！');
	}


	private function fieldSql($type,$table,$fieldinfo=''){

		switch ($type) {
			case 'query':
				$result = Db::query('SHOW FULL COLUMNS FROM `'.$table.'`');

				return $result;
				break;
			
			case 'addField':

				$result = Db::execute("ALTER TABLE `$table` ADD $fieldinfo;");

				return $result;
				break;
			
			default:
				# code...
				break;
		}
	}



	/**
	 * 查找某个值是否存在于多维数组中
	 * @param  [type] $value [需要查找的值]
	 * @param  [type] $array [在数组中进行查找]
	 * @return [bool]        [true:存在;false:不存在]
	 */
	private function deep_in_array($value, $array) {
	    foreach($array as $item) {
	        if(!is_array($item)) {
	            if ($item == $value) {
	                return true;
	            } else {
	                continue;
	            }
	        }

	        if(in_array($value, $item)) {
	            return true;
	        } else if($this->deep_in_array($value, $item)) {
	            return true;
	        }
	    }
	    return false;
	}


}