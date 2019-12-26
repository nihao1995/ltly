<?php 
namespace plugins\appmarket\lib;

/**
 * 文件上传公共类
 *属性：
 *	$rootPath	文件存储目录
 *	$fileName 	存储文件名称
 *	$exts 		允许文件格式
 *
 *方法：
 *
 *	setSize( (int) $size );			设置文件限制大 单位MB
 * 	setResourceInfo( $resource )	设置资源文件信息  （资源文件大小  资源文件名称  临时文件路径）
 * 	uploadFile()					文件上传方法
 * 
 */
class Upload
{
	/**
	 * 存放根目录
	 * @var string
	 */
	public $rootPath = 'upload';
	/**
	 * 文件名
	 * @var string
	 */
	public $fileName = '';

	/**
	 * 允许文件大小 单位：MB	
	 */
	protected $size = 0;
	/**
	 * 允许文件格式
	 * @var array
	 */
	public $exts = ['zip'];
	
	/**
	 * 资源临时文件位置
	 */
	protected $tmpName = '';
	/**
	 * 资源文件大小 单位：MB
	 * @var integer
	 */
	protected $fileSize = 0;
	/**
	 * 资源文件名字
	 */
	protected $name = '';

	/**
	 * 文件
	 */

	/**
	 * 设置文件限制大小 单位：MB
	 */
	public function setSize( $size = 10 )
	{
		$size = intval( $size , 0 );
		$this->size = $size;
	}

	/**
	 * 获取资源信息
	 */
	public function setResourceInfo( $resource )
	{
		if ( empty( $resource ) ) {
			throw new \Exception( '未找到资源文件' );
		}
		//转换文件带中文字符为gbk
		//$this->name = iconv( "UTF-8", "GBK", $resource ['file'] ['name'] );
		//$this->tmpName = iconv( "UTF-8", "GBK", $resource ['file'] ['tmp_name'] );
		$this->name = $resource ['file'] ['name'] ;
		$this->tmpName = $resource ['file'] ['tmp_name'] ;
		//资源文件单位 B 转换为 MB
		$this->fileSize = $resource ['file'] ['size'] / (1024*1024);
	}

	/**
	 * 保存文件
	 * $cover 是否覆盖同名文件
	 */
	public function uploadFile( $cover = false )
	{
		if( empty( $this->rootPath ) ) return zy_json_echo( false , '未定文件存放路径！');

		$path = $this->rootPath;
        try {
        	if( !file_exists( $path ) ){
	            mkdir( $path , 0777 , true );
	        }
        } catch (\Exception $e) {
        	return  zy_json_echo(false,'创建文件异常：'.$e->getMessage() );
        }

        $exts = pathinfo( $this->name );

        if(!isset($exts['extension'])){

            return  zy_json_echo(false,'文件格式不合法。');

        }


		//文件格式
        if( !in_array( $exts['extension'] , $this->exts ) ){

            return   zy_json_echo( false , '文件允许格式：'.implode( '|', $this->exts ) );

        }

        //限制文件大小  <= 上传文件大小
        if( $this->size <= $this->fileSize ){

            return  zy_json_echo(false,'文件大小不超过：'.$this->size.'MB,请检查!');

        }

        try {
        	//存储位置 upload
        	$name = ( empty( $this->fileName ) ) ? $this->name : $this->fileName.'.'.$exts['extension'];
	        $fileName = $this->rootPath.DS.$name;

	        //避免文件覆盖
	        if( $cover == false && file_exists( $fileName ) ){
	        	return zy_json_echo(false,'上传的文件已存在，请检查!');
	        }
	        $this->tmpName = iconv( "UTF-8", "GBK", $this->tmpName );
	        $newFile = iconv( "UTF-8","GBK", $fileName);
	        if( !move_uploaded_file( $this->tmpName , $newFile ) ){
	            return   zy_json_echo(false,'数据上传失败，请稍后再试!');
	        }
        } catch (\Exception $e) {

        	throw new \Exception( $e->getMessage() );
        }
        return zy_json_echo(true,'上传成功！', $fileName );
	}
}