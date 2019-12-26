<?php
namespace plugins\sub_appmarket\controller; //Demo插件英文名，改成你的插件英文就行了
use cmf\controller\PluginAdminBaseController;//引入此类
use think\Db;
use app\api\controller\ApiAccessAuthorityController;


/**
 * 发布管理控制器
 */
class ApiCommunicationController extends PluginAdminBaseController
{

    private $appListURL = "https://gl.300c.cn/api/api_module_store/appList?";    // 应用市场列表
    private $particularURL = "https://gl.300c.cn/api/api_module_store/particular?";    // 应用市场列表-详情
    private $operatorPermissionURL = "https://gl.300c.cn/api/api_module_store/operatorPermission?";    // 用户获取许可方法
    private $boughtHistoryURL = "https://gl.300c.cn/api/api_module_store/boughtHistory?";    // 获取购买记录接口
    private $wechatpayCodeURL = "https://gl.300c.cn/api/api_module_store/wechatPayCode?";    // 获取微信支付码方法
    private $getOrderStatusURL = "https://gl.300c.cn/api/api_module_store/getOrderStatus?";    // 获取订单信息
    private $getSourceAddrURL = "https://gl.300c.cn/api/api_module_store/getSourceAddr?";    // 获取模块下载地址

   	protected function _initialize()
    {
        parent::_initialize();
        $adminId = cmf_get_current_admin_id();
        //获取后台管理员id，可判断是否登录
        if (!empty($adminId)) {
            $this->assign("admin_id", $adminId);
        }
    }


    /**
     * 
     * 发布管理首页
     * @adminMenu(
     *     'name'   => '发布管理',
     *     'parent' => 'AdminIndex/index',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 1000,
     *     'icon'   => '',
     *     'remark' => '发布管理',
     *     'param'  => ''
     * )
     */
    public function index()
    {
        $AccessAuthority = new ApiAccessAuthorityController();
        dump($AccessAuthority->accessToken());
        die("a");

    }



    /**
     * 应用列表
     * @return [type] [description]
     */
    public function appList($parameter = []){
        // 实例化加密规则类
        $AccessAuthority = new ApiAccessAuthorityController();        
        $parameter = $AccessAuthority->signCodeEn($parameter);

        $paramString = http_build_query($parameter);

        $urlData = cmf_curl_get($this->appListURL.$paramString);
        $info = json_decode($urlData,true);
        return $info;
        // dump($info);exit();
    }

    /**
     * 应用列表-详情
     * @return [type] [description]
     */
    public function particular($parameter = []){
        if(empty($parameter['id'])){
            throw new \Exception('ERROR: Id cannot be empty', 1);
        }
        // 实例化加密规则类
        $AccessAuthority = new ApiAccessAuthorityController();        
        $parameter = $AccessAuthority->signCodeEn($parameter);

        $paramString = http_build_query($parameter);

        $urlData = cmf_curl_get($this->particularURL.$paramString);
        $info = json_decode($urlData,true);
        return $info;
        // dump($info);exit();
    }

    /**
     * 用户获取许可方法
     * @return [type] [description]
     */
    public function operatorPermission($parameter = []){
        if(empty($parameter['operator'])){
            throw new \Exception('ERROR: Operator cannot be empty', 1);
        }
        // 实例化加密规则类
        $AccessAuthority = new ApiAccessAuthorityController();        
        $parameter = $AccessAuthority->signCodeEn($parameter);

        $paramString = http_build_query($parameter);
        $urlData = cmf_curl_get($this->operatorPermissionURL.$paramString);
        $info = json_decode($urlData,true);
        return $info;
        // dump($info);exit();
    }

    /**
     * 获取购买记录接口
     * @return [type] [description]
     */
    public function boughtHistory($parameter = []){
        // 实例化加密规则类
        $AccessAuthority = new ApiAccessAuthorityController();        
        $parameter = $AccessAuthority->signCodeEn($parameter);

        $paramString = http_build_query($parameter);
        $urlData = cmf_curl_get($this->boughtHistoryURL.$paramString);
        $info = json_decode($urlData,true);
        return $info;
        // dump($info);exit();
    }

    /**
     * 获取微信支付码方法
     * @return [type] [description]
     */
    public function wechatpayCode($parameter = []){
        // 实例化加密规则类
        $AccessAuthority = new ApiAccessAuthorityController();        
        $parameter = $AccessAuthority->signCodeEn($parameter);

        $paramString = http_build_query($parameter);
        $urlData = cmf_curl_get($this->wechatpayCodeURL.$paramString);
        $info = json_decode($urlData,true);
        return $info;
        // dump($info);exit();
    }

    /**
     * 获取订单信息
     * @return [type] [description]
     */
    public function getOrderStatus($parameter = []){
        // 实例化加密规则类
        $AccessAuthority = new ApiAccessAuthorityController();        
        $parameter = $AccessAuthority->signCodeEn($parameter);

        $paramString = http_build_query($parameter);
        $urlData = cmf_curl_get($this->getOrderStatusURL.$paramString);
        $info = json_decode($urlData,true);
        return $info;
        // dump($info);exit();
    }

    /**
     * 获取模块下载地址
     * @return [type] [description]
     */
    public function getSourceAddr($parameter = []){
        // 实例化加密规则类
        $AccessAuthority = new ApiAccessAuthorityController();        
        $parameter = $AccessAuthority->signCodeEn($parameter);

        $paramString = http_build_query($parameter);
        $urlData = cmf_curl_get($this->getSourceAddrURL.$paramString);
        $info = json_decode($urlData,true);
        return $info;
        // dump($info);exit();
    }


}