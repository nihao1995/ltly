<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2018 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: Dean <zxxjjforever@163.com>
// +----------------------------------------------------------------------
namespace plugins\module_config\model;//Demo插件英文名，改成你的插件英文就行了
use think\Model;

//Demo插件英文名，改成你的插件英文就行了,插件数据表最好加个plugin前缀再加表名,这个类就是对应“表前缀+plugin_demo”表
class PluginApiIndexModel extends Model
{
	protected $table="cmf_module_config";
	/**
	 * 获取api
	 * @param  [type] $keyword [description]
	 * @return 返回查询的api地址  否则返回null
	 */
   public function getApiByKeywords($keywords)
   {
   		$res=$this->where('keywords',$keywords)->value('api');
   		return empty($res)?null:$res;
   }
      /**
    * 获取类引用地址
    * @param  [type] $keywords [description]
    * @return [type]           [description]
    */
   public function getClassByKeywords($keywords)
   {
   		$res=$this->where('keywords',$keywords)->value('class');
   		return empty($res)?null:$res;
   }
}