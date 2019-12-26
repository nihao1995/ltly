<?php
namespace plugins\module_config\controller; 

use cmf\controller\PluginAdminBaseController;//引入此类
use think\Db;

//AdminIndexController类和类的index()方法是必须存在的 index() 指向admin_index.html模板也就是模块后台首页
// 并且继承PluginAdminBaseController
class AdminIndexController extends PluginAdminBaseController
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
     * 演示插件 菜单注解 一般修改name remark内容就行
     * @adminMenu(
     *     'name'   => '模块配置',
     *     'parent' => 'admin/Plugin/default',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 1000,
     *     'icon'   => '',
     *     'remark' => '模块配置',
     *     'param'  => ''
     * )
     */
    public function index()
    {
        $visiConfig = new \plugins\module_config\controller\VisitConfigController();
        return $visiConfig->index();

    }
}