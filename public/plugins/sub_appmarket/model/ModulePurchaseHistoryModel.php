<?php 
namespace plugins\sub_appmarket\model;
use think\Model;
use think\Db;
/**
 * 
 */
class ModulePurchaseHistoryModel extends Model
{
	protected $table = 'cmf_module_purchase_history' ;

	/**
	 * 创建购买记录
	 * @return [type] [description]
	 */
	public function bought( $data )
	{
		return  $this->insertGetId( $data );
	}

	/**
	 * 获取订单信息
	 */
	public function getPurchaseHistory( $where )
	{
		return $this->where( $where )->find();
	}


}