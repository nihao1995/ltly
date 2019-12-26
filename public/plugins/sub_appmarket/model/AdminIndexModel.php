<?php 
namespace plugins\sub_appmarket\model;
use think\Model;
use think\Db;

/**
 * 
 */
class AdminIndexModel extends Model
{
	protected $pageNum  = 20 ;
	/**
	 * 获取模块市场数据
	 * $userId 用户id
	 */
	public function appList( $userId , $field = "" , $search = [] )
	{
		$purchaseModuleIds = $this->getPurchaseHistory( $userId );
		$moduleList = Db::name( 'module_store' )->where( 'putway' , 1 );
		if( !empty($field) ){
			$field = is_array( $field ) ? implode(',' , $field ) : $field ; 
			$moduleList = $moduleList->field( $field );
		}

		//搜索
		$query = [] ;
		if( !empty ( $search ['search']  ) ){
			$query = [ 'query' => $search ];
			//是否免费
			if( $search['payment'] == 0 ){
				$moduleList = $moduleList->where( 'price' , '=' , 0  );
			}else if( $search ['payment'] == 1 ){
				$moduleList = $moduleList->where( 'price' , '>' , 0  );
			}
			//分类
			if( $search ['category'] != 0 ){
				$moduleList = $moduleList->where( 'category' , '=' , $search ['category']  );
			}
			//是否上架
			if( $search [ 'putway' ] != 100 ){
				$moduleList = $moduleList->where( 'putway' , '=' , $search ['putway']  );
			}
			//我购买的
			if( $search [ 'buy' ] != 0 ){
				$moduleList = $moduleList->where( 'id' , 'IN' , $purchaseModuleIds  );
			}
		}

		//$moduleList = $moduleList->paginatePlus(20);
		
		$moduleList = $moduleList->paginate( $this->pageNum , false , $query );
		foreach ($moduleList as $key => $value) {
			//标记已购买的
			$isBuy = 0;
			if( in_array( $value['id'] , $purchaseModuleIds ) ){
				$isBuy = 1;
			}
			$value ['bought'] = $isBuy;
			//分类名称
			$value ['category'] = Db::name( 'module_category' )->where( 'id' , $value ['category'] )->value( 'name' );
            $name = cmf_parse_name( $value ['name'] , 1 );
            //是否安装
            $plugin = Db::name('plugin')->where( 'name' , $name )->find();
            $value ['installed'] = !empty($plugin) ? 1 : 0;

			$moduleList [ $key ] = $value;
		}
		return $moduleList ;
	}


	/**
	 * 获取购买的模块数据
	 */
	public function getPurchaseModule( $userId )
	{
		$purchaseModuleIds = $this->getPurchaseModule( $userId );
		$list = Db::name( 'module_store' )->where( 'id' , $purchaseModuleIds )->select()->toArray();
		return $list ;
	}

	/**
	 * 根据用户id 获取购买的模块id
	 * @param  [type] $userId [description]
	 * @return [type]         [description]
	 */
	public function getPurchaseHistory( $userId)
	{
		$purchaseModuleIds = Db::name( 'module_purchase_history' )->where( 'user_id' , $userId )->column('module_id');
		return $purchaseModuleIds;
	}

	/**
	 * 购买操作 记录购买数据
	 * dbtable: cmf_module_purchase_history
	 */

}