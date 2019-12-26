<?php
namespace plugins\module_config\controller;
/**
 * @Author: user
 * @Date:   2019-03-07 16:21:19
 * @Last Modified by:   user
 * @Last Modified time: 2019-03-20 09:56:01
 */
use cmf\controller\PluginRestBaseController;//引用插件基类
use plugins\module_config\Model\PluginApiIndexModel;
use think\Db;

/**
 * api控制器
 */
class ApiIndexController extends PluginRestBaseController
{
	protected $apiMode=null;
	protected function _initialize()
    {
        $this->apiMode=new PluginApiIndexModel();
    }

	/**
	 * 根据关键词获取接口数据
	 * @return [type] [description]
	 */
    public function getApiDataByKeywords($keywords='',$param='')
    {
    	$data=$this->request->param();
       if(!isset($data['keywords']) ){
       		$data['keywords']=$keywords;
       		if(empty($data['keywords'])){
       			return zy_json(false,'参数缺省,请检查！',null,-1);
       		}

       }
       //获取api
       $api=$this->apiMode->getApiByKeywords($data['keywords']);
       //未找到api
       if(empty($api)){
       		return zy_json(false,'请求失败，未找到相应配置！',null,-2);
       }
       //发起请求获取数据
		$datas=$param;//参数
		if(isset($data['param'])){
			$datas=$data['param'];
		}
    $datas=str_replace('/','&',$datas);
		$url=$api."?".$datas;

		/***********使用file_get_contents**************/
/*		$result=file_get_contents($url);
		return $result;*/

		/*********使用curl**************/
		// 1. 初始化
		 $ch = curl_init();
		 // 2. 设置选项，包括URL
		 curl_setopt($ch,CURLOPT_URL,$url);
		 curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
		 curl_setopt($ch,CURLOPT_HEADER,0);
		 // 3. 执行并获取HTML文档内容
		 $output = curl_exec($ch);
		 if($output === FALSE ){
		 	return zy_json(false,"CURL Error:".curl_error($ch),null,-3);
		 }
		 // 4. 释放curl句柄
		 curl_close($ch);
		 $output=$this->getSubstr($output);
		 return $output;
	}

		/**
	 * 根据关键词获取接口地址
	 * @return [type] [description]
	 */
    public function getApiAddressByKeywords($keywords='')
    {
    	$data=$this->request->param();
       if(!isset($data['keywords']) ){
       		$data['keywords']=$keywords;
       		if(empty($data['keywords'])){
       			return zy_json(false,'参数缺省,请检查！',null,-1);
       		}
       }
       //获取api
       $api=$this->apiMode->getApiByKeywords($data['keywords']);
              //未找到api
       if(empty($api)){
       		return zy_json(false,'请求失败，未找到相应配置！',null,-2);
       }
       $api=$this->getSubstr($api);
       return zy_json(true,'成功！',$api);
   }

   /**
    * 调试开关打开时过滤调试信息
    * @param  string  要处理的字符串
    * @return 	返回处理后的字符串
    */
   public function getSubstr($str){
	   	if(APP_DEBUG){
	   		$pos=strpos($str,'<div',0);
		 	$str=substr($str,0,$pos);
	   	}
	   	return $str;
   } 

  /**
   * 根据关键词获取类引用地址
   *@param  string $keywords 
   * @return string 类引用地址
   */
  public  function getClassNameByKeywords($keywords='')
  {
        $data=$this->request->param();
        if(!isset($data['keywords']) ){
          $data['keywords']=$keywords;
          if(empty($data['keywords'])){
            return zy_json(false,'参数缺省,请检查！',null,-1);
          }
        }
        //获取类引用地址
        $class=$this->apiMode->getClassByKeywords($data['keywords']);
        if(empty($class)){
          return zy_json(false,'请求失败，未找到相应配置！',null,-2);
        }
        return zy_json(true,'成功！',$class);
  }
}