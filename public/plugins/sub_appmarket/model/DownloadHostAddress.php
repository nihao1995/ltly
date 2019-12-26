<?php 
namespace plugins\sub_appmarket\model;

/**
 * 下载地址配置模型
 */
class DownloadHostAddress
{
	public $name = '';

	public $address = '';

	public $status = '';

	public static function initail( $name = '' , $address = '' , $status = '' )
	{
		$obj = new DownloadHostAddress();
		$obj->name = $name;
		$obj->address = $address;
		$obj->status = $status;
		return $obj;
	}
}