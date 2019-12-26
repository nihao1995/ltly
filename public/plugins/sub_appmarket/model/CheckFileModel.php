<?php 
namespace plugins\sub_appmarket\model;

/**
 * 文件检查
 */
class CheckFileModel 
{
	/**
	 * 检查自身模块文件是否存在
	 */
	public static function checkSelfModuleFileExist( $symbol = '' )
	{
		$file = cmf_parse_name( $symbol );
		return  self::checkFileExist( $file ) ;
	}

	/**
	 * 检查所需配置模块是否存在
	 */
	public static function checkDemandModuleFileExist( $symbol = '' )
	{
		$isExist = '';
		$isNoExist = '';
		//读取模块配置 需求配置
		$content = getModuleConfig( $symbol , 'config' , 'demand.json' );
		$content = json_decode( $content , true );
		if( empty( $content ) ) return true ;
		$path = PLUGINS_PATH;
		$demandSymbol = [] ;
		foreach ( $content as $key => $value ) {
			$demandSymbol [] ['symbol'] = $value [ 'demandSymbol' ];
			$demandSymbol [] ['name'] = $value [ 'demandName' ] ;
		}
		foreach ( $demandSymbol as $key => $value ) {
			$path = $path.DS.cmf_parse_name( $value [ 'symbol' ] );
			if( !file_exists( $path ) ){
				$isNoExist .= '<div>所需模块：'.$value['name'].'('.$value['symbol'].')'.'不存在<div>' ;
			}else{
				$isExist .= '<div>所需模块：'.$value['name'].'('.$value['symbol'].')'.'存在<div>'  ;
			}
		}
		return [ 'isExist'=> $isExist , 'isNoExist'=> $isNoExist ] ;
	}
	
	/**
	 * 检查所需数据表是否存在
	 */
	public static function checkDemandDbTableExist( $symbol = '' )
	{
		$isNoExist = true;
		$config = getModuleConfig( $symbol , 'data' , 'config.php' );
		if( !is_array( $config ) ) return $isNoExist;
		$path = PLUGINS_PATH;
		foreach ($config as $key => $value) {
			$tables = array_keys( $value );
			foreach ($tables as $tableName ) {
				$path = $path.DS.cmf_parse_name( $value ).DS.'data'.DS.$tableName.'.sql';
				if( !file_exists( $path ) ){
					$isNoExist .= '<div>所需'.$key.'模块的'.$tableName.'表不存在<div>' ;
				}
			}
		}
		return $isNoExist;
	}


	/**
	 * 检查文件是否存在
	 * $file 文件地址
	 * $root 根目录
	 * return  存在返回路径  不存在返回false  
	 */
	private static function checkFileExist(  $file = '' , $root = PLUGINS_PATH )
	{	
		$path = $root.DS.$file;
		if( !file_exists( $path ) ){
			return false ;
		}
		return $path;
	}	


}