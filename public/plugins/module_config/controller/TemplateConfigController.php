<?php
namespace plugins\module_config\controller; 

use cmf\controller\PluginAdminBaseController;//引入此类
use think\Db;
use plugins\module_config\common\Common;

//AdminIndexController类和类的index()方法是必须存在的 index() 指向admin_index.html模板也就是模块后台首页
// 并且继承PluginAdminBaseController
class TemplateConfigController extends PluginAdminBaseController
{
    protected function _initialize()
    {
        parent::_initialize();
        $adminId = cmf_get_current_admin_id();//获取后台管理员id，可判断是否登录
        if (!empty($adminId)) {
            $this->assign("admin_id", $adminId);
        }
    }

    /**
     * @adminMenu(
     *     'name'   => '前端模板供应配置',
     *     'parent' => 'AdminIndex/index',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 1000,
     *     'icon'   => '',
     *     'remark' => '前端模板供应配置',
     *     'param'  => ''
     * )
     */
    public function supply()
    {
        //前端模板供应配置首页
        $moduleList = Common::getModuleInfo();
        $tempSupplyList = [] ;
        $this->assign( 'moduleList' , $moduleList );
        $this->assign( 'tempSupplyList' , $tempSupplyList );
        return $this->fetch( 'template/supply_index' );
    }

    /**
     * @adminMenu(
     *     'name'   => '前端模板需求配置',
     *     'parent' => 'AdminIndex/index',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 1000,
     *     'icon'   => '',
     *     'remark' => '前端模板需求配置',
     *     'param'  => ''
     * )
     */
    public function demand()
    {
        //前端模板需求配置首页
        return $this->fetch('template/demand_index');
    }

}