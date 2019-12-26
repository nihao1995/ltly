<?php 
namespace plugins\sub_appmarket\lib;

use think\Config;
/**
 * 文件下载公共类
 */
class Download
{
	/**
	 * 文件下载方法
	 * exception 下载文件出错
	 *
	 * $url 下载地址
	 * $path 保存地址 	
	 */
	public static function fileDownload( $url = '' , $path = '' )
	{
		$res = [ 'status' => true , 'message' => '下载完成' ];
		$arr = parse_url( $url ); 
        $fileName = basename( $arr['path'] );
        try {
            $file = file_get_contents( $url );
        } catch ( \Exception $e ) {
        	return [ 'status' => false , 'message' => '文件下载错误！'.$e->getMessage() ];
            //throw new \Exception ('文件下载错误！');
        }
        file_put_contents( $path.$fileName , $file );
        return $path.$fileName;
	}
	
	/**
	 * 文件解压方法
	 * $fileName文件地址
	 * $outPath 文件解压地址
	 */
	public static function unZip( $fileName = "" , $outPath = '' )
	{
		$res = [ 'status' => true , 'message' => '解压完成' ];
        $zip = new \ZipArchive();
        try {
        	if( !file_exists( $outPath ) ){
        		mkdir( $outPath , 0777 , true ); 
        	}
            $openRes = $zip->open( $fileName );
            if ($openRes === TRUE) {
              $zip->extractTo($outPath);
              $zip->close();
              unlink( $fileName );
            }else{
            	$res [ 'status' ] = false;
            	$res [ 'message' ] = '压缩文件打开失败';
            }
        } catch ( \Exception $e ) {
        	$res [ 'status' ] = false;
        	$res[ 'message' ] = '解压异常：'.$e->getMessage() ;
            //throw new \Exception( '解压异常：'.$e->getMessage() ) ;
        }
       	return $res ;
	}
	
}