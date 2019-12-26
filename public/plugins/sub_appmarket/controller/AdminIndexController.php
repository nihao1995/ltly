<?php
namespace plugins\sub_appmarket\controller; //Demo插件英文名，改成你的插件英文就行了
use cmf\controller\PluginAdminBaseController;//引入此类
use think\Db;
use think\Cookie;
use plugins\sub_appmarket\model\AdminIndexModel;
use plugins\sub_appmarket\model\ModulePurchaseHistoryModel;
use app\common\lib\AccessAuthorization;

use plugins\sub_appmarket\controller\ApiCommunicationController;  // 链接主系统接口
/**
 * 应用市场控制器
 */
class AdminIndexController extends PluginAdminBaseController
{
    /**
     * coolie过期时间 默认2小时 单位：毫秒
     * @var [type]
     */
    protected $cookieExpire =  7200 ;


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
     * @adminMenu(
     *     'name'   => '应用市场',
     *     'parent' => 'admin/Plugin/default',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 1000,
     *     'icon'   => '',
     *     'remark' => '应用市场',
     *     'param'  => ''
     * )
     **/
    public function index()
    {
        $data = $this->request->param();
        //搜索参数
        $search = [
            'page'          =>      isset( $data [ 'page' ] ) ? $data [ 'page' ] : 1 ,
            'categoryTmp'   =>      isset( $data [ 'categoryTmp' ] ) ? $data [ 'categoryTmp' ] : '0/全部分类' ,
            'category'      =>      isset( $data [ 'categoryTmp' ] ) ? substr( $data ['categoryTmp'] , 0 , strpos( $data ['categoryTmp'] , '/' ) ) : 0 ,
            'categoryName'  =>      isset( $data [ 'categoryTmp' ] ) ? substr( $data ['categoryTmp'] , strpos( $data ['categoryTmp'] , '/' )+1 ) : '全部分类' ,
            'payment'       =>      isset( $data [ 'payment' ] ) ? $data [ 'payment' ] : 100,
            'buy'           =>      isset( $data [ 'buy' ] ) ? $data [ 'buy' ] : 0,
            'search'        =>      isset( $data [ 'search' ] ) ? $data [ 'search' ] : null
        ];

        // 调用接口，进行显示
        $apiCommunication = new ApiCommunicationController();
        $appListData = $apiCommunication->appList($search);
        if($appListData['status']=='error'){
            throw new \Exception('ERROR:'.$appListData['message'], 1);
        }                

        $search = $appListData['data']['search'];
        $list = $appListData['data']['list']['data'];
        $pages = $appListData['data']['list']; 
        $categoryList = $appListData['data']['categoryList'];
        //分页
        $ul = $this->pages( $pages );

        $this->assign( 'pageUl' , $ul );
        $this->assign( 'search' , $search );
        $this->assign( 'searchObj' , json_encode( $search ) );
        $this->assign( 'categoryList' , $categoryList );
        $this->assign( 'list' , $list );
        return $this->fetch();
    }

    /**
     * 分页
     */
    private function pages( $list )
    {
        $currentPage = $list [ 'current_page' ] ;
        $perPage = $list [ 'per_page' ];
        $lastPage = $list [ 'last_page' ];
        $totalPage = (int)( $list[ 'total' ] / $perPage );
        $shwoNum = 5;
        if( $totalPage == 0 ){
            return '';
        }
        $ul = '<ul class="pagination">';
        $perDisabled = ($currentPage == 1)?'class="disabled"':'';
            $ul .='<li '.$perDisabled.' ><a href="?page=1">&laquo;</a></li>';
        for ( $i = 1 ; $i <= $totalPage ; $i ++ ){
            if( ( $totalPage <= 5 ) || ( $i == 1 ) || ( $i == $lastPage )  || ( $i >= $currentPage - $shwoNum && $i <= $currentPage + $shwoNum ) ){
                $active = '';
                if( $i == $currentPage ) $active = 'class="active"';
                $ul .= '<li '.$active.'><a href="?page='.$i.'">'.$i.'</a></li>';
            }else if ( ( $i == $currentPage - $shwoNum - 1 || $i == $currentPage + $shwoNum + 1 ) ) {
                $ul .= '<li><a href="#"><span>...</span></a></li>';
            }  
        }
            $lastDisabled = ($currentPage == $lastPage) ? 'class="disabled"':'';
            $ul .='<li '.$lastDisabled.' ><a href="?page='.$lastPage.'"><span>&raquo;</span></a></li>';
        $ul .= '</ul>';

        return $ul;
    }


    /**
     * 查看模块详情页面
     *
     */
    public function particular( $id )
    {

        // 调用接口，进行显示
        $apiCommunication = new ApiCommunicationController();
        $appListData = $apiCommunication->particular(['id'=>$id]);
        if($appListData['status']=='error'){
            throw new \Exception('ERROR:'.$appListData['message'], 1);
        }

        $module = $appListData['data']['module'];
        $previewList = $appListData['data']['previewList'];
        if( !empty($module['preview']) ){
            $previewList = explode(',', $module['preview']);
        }
        $this->assign( 'previewList' , $previewList );
        $this->assign( 'module' , $module );
        return $this->fetch();
    }
    
    /**
     * 登录页面
     * @return [type] [description]
     */
    public function login()
    {
       return $this->fetch();
    }

    /**
     * 执行登录
     */
    public function doLogin()
    {
        // 调用接口，进行显示

        if( !$this->request->isPost() ){
            //$this->error('请求类型错误，请检查！');
            return zy_json_echo( false , '请求类型错误，请检查！' );
        }

        $company_data = Db::name('company')->where('id',1)->find();

        $data = $this->request->param();
        $company_id = $company_data ['company_id'];
        $name = $data ['name'];
        $pwd = cmf_password($data['password']);
        $operator = base64_encode($name.','.$pwd);
        $operateType = 1; //1购买操作 2安装操作
        if( empty( $name ) || empty( $pwd ) ){
           // $this->error('账号和密码不能为空！');
           return zy_json_echo( false , '账号和密码不能为空！' );
        }
        //请求主系统验证密码账号
        $apiCommunication = new ApiCommunicationController();
        $appListData = $apiCommunication->operatorPermission(['operator'=>$operator,'cid'=>$company_id]);
        //dump($appListData);exit;
        if($appListData['status']=='error'){
            return zy_json_echo( false , $appListData['message'] );
        }

        $userId = $appListData['data']['permissionCode'];
        $permissionCode = $appListData['data']['permissionCode'];
        $ID = self::userInfoEncode( $userId );
        $UID = self::userInfoEncode( cmf_get_current_admin_id() );
        $CODEID = self::userInfoEncode( $permissionCode );  // 新加 凭证
        Cookie::set( 'app_login_info' , [ 'ID' => $ID , 'UID' => $UID , 'CODEID'=>$CODEID ] , $this->cookieExpire );
        return zy_json_echo( true , '登录成功！' );       
    }

    /**
     * 购买
     * @param  $id model_store 模块id
     */
    public function bought()
    {
        // 调用接口，进行显示
        $data = $this->request->param();
        $moduleId = $data ['moduleId'];
        //检查是否登录
        $userId = $this->checkLogin();
        if( false === $userId ){
            return zy_json_echo( false , '用户信息错误！' , null , 404 );
        }


        $company_data = Db::name('company')->where('id',1)->find();
        $user_data = Db::name('user')->where('id',$company_data['super_admin'])->find();
        $operator = base64_encode($company_data['super_login'].",".$user_data['user_pass']);

        $data = [
            'id'=>$moduleId,
            'pid'=>$userId,
            'operator'=>$operator,
            'cid'=>$company_data['company_id'],
        ];
        $apiCommunication = new ApiCommunicationController();
        $appListData = $apiCommunication->boughtHistory($data);
        /*if($appListData['status']=='error'){
            return zy_json_echo( false , $appListData['message'] );
        }*/


        if($appListData['code']==200){
            //免支付购买  购买记录中添加一条数据
            //$this->success('开发者操作，购买成功！');
           //return $this->boughtSuccess('开发者操作，购买成功！');
            $temp = Db::name( 'user_attach' )->where( 'user_id' , cmf_get_current_admin_id() )->find();
            $userId = 1 ;
            if( $temp ['company_id'] != 8 ){
                $userId = Db::name( 'company' )->where( 'id' , $temp ['company_id'] )->value( 'admin_id' );
            }
            return zy_json_echo( false , '开发者无需购买产品！' , null , 201  );
        }else if($appListData['code']==201){
            return zy_json_echo( true , '进入支付页面' , 201);
        }

    }
    /**
     * 购买成功页面
     */
    public function boughtSuccess( $success = '购买成功！' )
    {
        $this->assign( 'success' , $success );
        return $this->fetch( 'admin_index/success' );
    }

    /**
     * 支付页面
     */
    public function pay( $moduleId )
    {

        $moduleId = $moduleId;
        $apiCommunication = new ApiCommunicationController();
        $module = $apiCommunication->particular(['id'=>$moduleId]);
        $title = '购买插件-'.$module['data']['module']['title'];
        $money = $module['data']['module']['price'];

        // 生成订单
        $company_data = Db::name('company')->where('id',1)->find();
        $user_data = Db::name('user')->where('id',$company_data['super_admin'])->find();
        $operator = base64_encode($company_data['super_login'].",".$user_data['user_pass']);
        $CODEID = $this->checkLogin();

        // 参数
        $data = [
            'mid'=>$moduleId,
            'title'=>$title,
            'operator'=>$operator,
            'cid'=>$company_data['company_id'],
            'pid'=>$CODEID,
        ];
        // 获取微信的支付码
        $apiCommunication = new ApiCommunicationController();
        $appListData = $apiCommunication->wechatpayCode($data);
        $url = $appListData['data']['qsrc'];
        $order_sn = $appListData['data']['order_num'];

        $info = [
            'moduleId'=>$moduleId,
            'title'=>$title,
            'order_sn'=>$order_sn,
            'money'=>$money,
            'url'=>$url,
        ];
        $this->assign( 'info' , $info );
        return $this->fetch( 'admin_index/pay' );
    }

    /**
     * 支付确认
     * @return [type] [description]
     */
    public function paySuccess($order_num)
    {
        $apiCommunication = new ApiCommunicationController();
        $module = $apiCommunication->getOrderStatus(['order_num'=>$order_num]);
        return $module['code'];
    }

    /**
     * 检查用户
     * @return [type] [description]
     */
    private function checkUserInfo( $name , $pwd )
    {
        $user = Db::name( 'user' )->where( [ 'user_login' => $name , 'user_pass' => $pwd ] )->find();
        if( empty( $user ) ){
            return false;
        }
        return $user[ 'id' ];
    }


    /**
     * 检查是否登录
     */
    public function checkLogin()
    {
        $appLoginInfo = []; 
        //读取cookie数据  app_login_info = ['ID'=>]
        //ID = base_encode64( mt_rand(100000,999999).$id )  6位随机数 + $userid
        if( Cookie::has( 'app_login_info' ) ){
            $appLoginInfo = Cookie::get( 'app_login_info' );
        }
        if( !isset( $appLoginInfo['ID'] ) || !isset( $appLoginInfo['UID'] ) || !isset( $appLoginInfo['CODEID'] ) ){
            return false;
        }
        $userId = self::userInfoDecode( $appLoginInfo['ID'] );
        $UID = self::userInfoDecode( $appLoginInfo['UID'] );
        $CODEID = self::userInfoDecode( $appLoginInfo['CODEID'] );
        return empty( $CODEID ) ? false : $CODEID;
    }   

    /**
     * cookie用户信息加密
     */
    private static function userInfoEncode( $userId )
    {
        $rand = mt_rand( 100000 , 999999 );
        return base64_encode( $rand.$userId );
    }
    /**
     * cookie用户信息解密
     */
    private static function userInfoDecode( $ciphertext = '' )
    {
        if( empty( $ciphertext ) ) return '';
        $ciphertext = base64_decode( $ciphertext );
        if( strlen( $ciphertext ) <= 6 ) return '';
        return substr( $ciphertext , 6 );
    }

    /**
     * 登录页面
     * @return [type] [description]
     */
    public function islogin()
    {
       return $this->fetch('admin_index/login');
    }   

    //安装
    //////////////////////////////////////////////////////////////
    /**
     *安装方法
     * @return [type] [description]
     */
    public function installCheck()
    {
        $data = $this->request->param();
        $id = $data['id'];
        //检查是否登录
        $userId = $this->checkLogin();
        if( false === $userId ){
            //跳转登录页面
            return zy_json_echo( false , '请登录' , null , 101 );
        }


        //检查是否购买
        $moduleId = $id;
        $isBought = $this->isBought( $moduleId );
        if( false === $isBought ){
            return zy_json_echo( false , '还未购买' , null , 102);
        }

        //安装页面
        return zy_json_echo( false , '可以安装' , null , 103);
    }

    /**
     * 是否购买
     * return bool  未购买false 购买true
     */
    public function isBought( $moduleId )
    {
       
        $company_data = Db::name('company')->where('id',1)->find();
        $user_data = Db::name('user')->where('id',$company_data['super_admin'])->find();
        $operator = base64_encode($company_data['super_login'].",".$user_data['user_pass']);
        $CODEID = $this->checkLogin();
        $data = [
            'id'=>$moduleId,
            'pid'=>$CODEID,
            'operator'=>$operator,
            'cid'=>$company_data['company_id'],
        ];
        $apiCommunication = new ApiCommunicationController();
        $appListData = $apiCommunication->boughtHistory($data);
        if($appListData['code']==201){
            return false;
            // return zy_json_echo( false , $appListData['message'] );
            
        }else if($appListData['code']==200){
            return true;
        }else{
            return zy_json_echo( false , '请登录' , null , 101 );
        }

        
    }


    /**
     * 安装页面
     */
    public function install( $moduleId )
    {
        $this->assign( 'moduleId' , $moduleId );
        return $this->fetch('admin_index/install');
    }  
    /**
     * 安装操作
     */
    public function installOperate( $moduleId )
    {
        // $module = Db::name( 'module_store' )->where( 'id' , $moduleId )->find();
        // 获取详细信息
        $apiCommunication = new ApiCommunicationController();
        $module = $apiCommunication->particular(['id'=>$moduleId]); // 获取模块详情
        $module = $module['data']['module'];

        //执行安装操作
        $pluginInstallObj = new \app\admin\controller\PluginController();
        $res = $pluginInstallObj -> install( $module[ 'name' ] );
        $status = $res [ 'code' ] ? true : false;
        return zy_json_echo( $status , $res [ 'msg' ] ); 
    }

    /**
     *卸载模块操作
     */
    public function unInstallOperate( $moduleId )
    {
        // $module = Db::name( 'module_store' )->where( 'id' , $moduleId )->find();
        // 获取详细信息
        $apiCommunication = new ApiCommunicationController();
        $module = $apiCommunication->particular(['id'=>$moduleId]); // 获取模块详情
        $module = $module['data']['module'];

        $id = Db::name( 'plugin' )->where( 'name' , $module [ 'name' ] )->value('id');
        if( empty( $id ) ){
            $this->error('模块不存在！');
        }
        //执行卸载操作
        $pluginInstallObj = new \app\admin\controller\PluginController();
        $res = $pluginInstallObj -> uninstall( $id );
    }

    /////////////////////////////////////////////////////////////


}