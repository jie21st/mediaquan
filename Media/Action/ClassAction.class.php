<?php
namespace Media\Action;

use \Common\Service\UserService;
use \Common\Model\ClassModel;
use \Common\Vendor\Images\ImagesMerger as Images;

class ClassAction extends CommonAction
{
    /**
     * 导出数量限制
     */
    const EXPORT_SIZE = 1000;
    
    /**
     * 是否需要登录
     * @var boolean
     */
    protected $needAuth = true;

    /**
     * 构造方法
     */
    public function __construct() {
        
        if (in_array(strtolower(ACTION_NAME), array('index', 'list', 'getapplyedlist', 'export'))) {
            $this->needAuth = false;
        }
        
        parent::__construct();
    }
    
    /**
     * 课程详情
     */
    public function detailOp()
    {
        $classId = I('get.id', 0, 'intval');
        if ($classId <= 0) {
            showMessage('参数错误');
        }
        $classModel = new ClassModel();
        $classInfo = $classModel->getClassInfo(['class_id' => $classId]);
        if (empty($classInfo)) {
            showMessage('课程不存在');
        }
        
        // 获取课程章节列表
        $chapterModel = new \Common\Model\ChapterModel();
        $classInfo['chapter_list'] = $chapterModel->getCourseList(['class_id' => $classId]);
        
        $this->assign('class_info', $classInfo);
        $this->display();
    }
    
    /**
     * 课程购买
     */
    public function buyOp()
    {
        $classId = I('id', 0, 'intval'); 
        $userId  = session('user_id');
        if ($classId <= 0) {
            exit('参数错误'); 
        }
        
        $userService = new \Common\Service\UserService;
        $classService = new \Common\Service\ClassService;
        
        // 取得课程信息
        $classModel = new \Common\Model\ClassModel();
        $classInfo = $classModel->getClassInfo(['class_id' => $classId]);
        if (empty($classInfo)) {
            showMessage('课程不存在');
        }
        if ($classInfo['class_price'] > 0) {
            $classInfo['_is_free'] = false;
        } else {
            $classInfo['_is_free'] = true;
        }

        // 验证用户是否已报名
        $applyed = $classService->checkClassUser($classId, $userId);
        if ($applyed) {
            redirect(C('APP_SITE_URL') . "/class/ticket?class_id={$classId}");
        }
        if (IS_POST) {
            // 更新用户信息
            $update = array();
            $update['user_truename']    = I('post.username');
            $update['user_mobile']      = I('post.mobile');
            $update['company_name']     = I('post.company_name');
//            $update['Place']      = I('post.job');
            $update['user_wx']          = I('post.wechat_id');
            $update['user_areainfo']    = I('post.area_info', '', 'trim');
            $areaIds = I('post.area_ids');
            if ($areaIds != '') {
                $split = explode(',', $areaIds); 
                $update['user_provinceid'] = $split[0];
                $update['user_cityid'] = $split[1];
            }
            $result = $userService->updateUserInfo($update, $userId);
            if (! $result) {
                showMessage('更新信息失败');
            }
            
            // 创建订单
            $result = $classService->buy(I('post.'), $userId);
            if (isset($result['error'])) {
                showMessage($result['error']);
            }
            
            // 转向到支付页面
            $pay_url = '/buy/course_buy?order_sn='.$result['order_sn'];
            redirect($pay_url);
        }
        // 获取用户信息
        $userInfo = $userService->getUserFullInfo($userId);
        $this->assign('user_info', $userInfo);
        $this->assign('class_info', $classInfo);
        
        $this->display();
    }
    
    /**
     * 电子听课证
     */
    public function ticketOp()
    {
        $classId = I('get.class_id', 0, 'intval'); 
        $userId  = session('user.user_id');
        if ($classId <= 0) {
            exit('参数错误');
        }
         
        // 验证课程是否存在
        $classModel = D('Class');
        $classInfo = $classModel->getClassInfo(['class_id' => $classId]);
        if (empty($classInfo)) {
            exit('课程不存在'); 
        }

        // 验证用户是否已报名
        $classService = D('Class', 'Service');
        $applyed = $classService->checkClassUser($classId, $userId);
        if (! $applyed) {
            redirect(C('APP_SITE_URL') . "/class/buy?id={$classId}");
        }
        // 获取报名用户信息
        $classUserInfo = $classModel->getClassUser(['class_id' => $classId, 'user_id' => $userId]);
        if ($classUserInfo['reseller_id']) {
            // 分销商，如果分销商自主维护报名用户，则显示分销商班级信息
            $resellerModel = D('Reseller'); 
            $resellerInfo = $resellerModel->getResellerInfo(['reseller_id' => $classUserInfo['reseller_id']]);
            if ($resellerInfo && $resellerInfo['reseller_govern']) {
                // 自维护报名用户
                $this->assign('reseller_info', $resellerInfo);
                // 获取所在分销商课程班级
                $classGroupInfo = $resellerModel->getClassGroupInfo([
                    'group_id' => $classUserInfo['group_id']
                ]);
                $this->assign('reseller_class_group_info', $classGroupInfo);
            }
        }
        // 找不到对应的分销商课程班级
        if (empty($classGroupInfo)) {
            $classGroupInfo = $classModel->getClassGroupInfo(
                ['group_id' => $classUserInfo['group_id']],
                'group_code,group_name,qrcode,kf_wechat'
            );
            $this->assign('class_group_info', $classGroupInfo);
        }
        // 获取用户信息
        $userService = D('User', 'Service');
        $userInfo = $userService->getUserInfo($userId);
        
        $this->assign('user_info', $userInfo);
        $this->assign('class_info', $classInfo);
        $this->display();
    }
    
    /**
     * 毕业证
     */
    public function diplomaOp()
    {

        $classId = I('class_id', 0, 'intval');
        $type = I('type_id', 1, 'intval');

        if ($classId <= 0) {
            exit('参数错误');
        }

        $userId = session('user.user_id');

        // 取得课程信息
        $classModel = new ClassModel();
        $classInfo = $classModel->getClassInfo(['class_id' => $classId]);

        if (empty($classInfo) || !is_array($classInfo)) {
            exit('课程不存在');
        }

        // 获取用户报名信息
        $classUserInfo = $classModel->getClassUser([
            'class_id' => $classId,
            'user_id' => $userId
        ]);

        if (empty($classUserInfo)) {
            exit('您尚未报名，请点击<a href="' . C('APP_SITE_URL') .'/class/buy?id=' . $classId . '">报名</a>');
        }

        // 获取所在班级信息
        $groupInfo = $classModel->getClassGroupInfo(['group_id' => $classUserInfo['group_id']]);
        if (!$groupInfo || !$groupInfo['is_ending']) {
            exit('您暂未毕业。');
        }
        
        // 获取用户信息
        $userService = new UserService();
        $userInfo = $userService->getUserInfo($userId);
        $cretNo = \Common\Service\ClassService::getCertificateNo($classUserInfo);

        if($type == '2') {
            // 实时获取微信头像
            $key = 'is_flush_headimg';
            if (! session("?{$key}")) {
                session($key, true);
                $returnUrl = urlencode(C('APP_SITE_URL') . $_SERVER['REQUEST_URI']);
                redirect(C('PASSPORT_SITE_URL') . '/wechat/getUserInfo?returnUrl=' . $returnUrl);
            }
            session($key, null);
            if (! empty($_GET['headimgurl'])) {
                $info['userPath'] = downloadFiles($_GET['headimgurl'], $userId .'_head', DIR_UPLOAD . DS . ATTACH_AVATAR );
            } else {
                $info['userPath'] = DIR_RESOURCE .DS. 'images' .DS. 'personImg.jpg';
            }
            $info['user_id'] = $userId;
            $info['short_title'] = $classInfo['short_title'];
            $info['class_id'] = $classId;
            $path = DIR_UPLOAD .'/'. ATTACH_CLASS . '/';
            $info['teacher_signature'] =  $path . $classInfo['teacher_signature'];
            $info['class_qrcode'] = $path . $classInfo['class_qrcode'];
            $info['user_name'] = (preg_match('/[\x7f-\xff]/', $userInfo['user_name'])) ? mb_substr($userInfo['user_name'], 0, 4) : substr($userInfo['user_name'], 0, 8) ;
            $info['user_number'] = (preg_match('/[\x7f-\xff]/', $info['user_name'])) ? mb_strlen($info['user_name']) : strlen($info['user_name']);
            $info['cretNo'] = $cretNo;
            $info['end_time'] = date('Y年m月d日', $groupInfo['end_time']);
            $info['savePath']  = DIR_UPLOAD . '/' . ATTACH_DIPLOMA . '/' . $info['class_id'] .'/';
            $md5 = md5($classId . $userId . C('DIPLOMA'));
            $this->_createDiploma($info);
            $tpl = ['class_id'=>$classId, 'user_id'=>$userId, 'diploma' => $md5, 'short_title'=>$info['short_title']];
            $this->assign('tpl', $tpl);
            $this->display('Class/diploma_img');
        } else {
            $this->assign('certificate_no', $cretNo);
            $this->assign('class_info', $classInfo);
            $this->assign('user_info', $userInfo);
            $this->assign('group_info', $groupInfo);
            $this->display();
        }
    }

    /**
     * 毕业证分享
     * @return [type] [description]
     */
    public function fxDiplomaOp()
    {
        $userId = I('get.user_id', 0, 'intval');
        $diploma = I('diploma');
        $classId = I('get.class_id', 0, 'intval');
        $md5 = md5($classId . $userId . C('DIPLOMA'));
        if ($userId and $classId and $diploma == $md5) {

            // 取得课程信息
            $classModel = new ClassModel();
            $classInfo = $classModel->getClassInfo(['class_id' => $classId],'short_title');

            $tpl = ['class_id'=>$classId, 'user_id'=>$userId, 'diploma' => $md5, 'short_title' => $classInfo['short_title']];
            $this->assign('tpl', $tpl);
            $this->display('Class/diploma_img');
        } else {
            exit('参数错误');
        }
        
    }

    /**
     * 生成毕业证
     * @param  [type] $info [description]
     * @return [type]       [description]
     */
    private function _createDiploma($info)
    {
        $config = array(
            'dst'       =>  COMMON_PATH . 'Resource/Diploma/Images/diploma_tpl.jpg',
            'isPrint'   =>  false,
            'isSave'    =>  true,
            'savePath'  =>  $info['savePath'],
            'saveName'  =>  $info['user_id'],
            'src' => array(
                // 用户头像
                array(
                    'srcPath'   =>  $info['userPath'], 
                    'srcX'      =>  '284',
                    'srcY'      =>  '280',
                    'srcW'      =>  '150', 
                    'srcH'      =>  '150',
                ),
                // 课程
                array(
                    'srcPath'   =>  $info['class_qrcode'], 
                    'srcX'      =>  '111',
                    'srcY'      =>  '738',
                    'srcW'      =>  '200', 
                    'srcH'      =>  '200',
                ),
                array(
                    'srcPath'   =>  $info['teacher_signature'], 
                    'srcX'      =>  '415',
                    'srcY'      =>  '740',
                    'srcW'      =>  '200', 
                    'srcH'      =>  '100',
                ),
            ),
            'font' => array(
                array(
                    'text'      => '《'.$info['short_title'].'》',
                    'fontPath'  => COMMON_PATH . 'Resource/Diploma/Font/SourceHanSansK-Medium.ttf',
                    'fontSize'  => '42',
                    'fontColor' => '0,129,204',
                    'fontX'     => 'center',
                    'fontY'     => '570',
                    'adjust'    => '30'
                ),
                array(
                    'text'      => $info['user_name'],
                    // 'text'      => '申利超号',
                    'fontPath'  => COMMON_PATH . 'Resource/Diploma/Font/simkai.ttf',
                    'fontSize'  => '32',
                    'fontColor' => '0,0,0',
                    'fontX'     => 'center',
                    'fontY'     => '498',
                    'adjust'    => (100+30*$info['user_number']) - 3*$info['user_number'],
                ),
                array(
                    'text'      => $info['cretNo'],
                    'fontPath'  => COMMON_PATH . 'Resource/Diploma/Font/SourceHanSansK-Medium.ttf',
                    'fontSize'  => '18',
                    'fontColor' => '0,0,0',
                    'fontX'     => '225',
                    'fontY'     => '978',
                ), 
                array(
                    'text' => $info['end_time'],
                    'fontPath' => COMMON_PATH . 'Resource/Diploma/Font/SourceHanSansK-Medium.ttf',
                    'fontSize' => '18',
                    'fontColor' => '0,0,0',
                    'fontX' => '225',
                    'fontY' => '1016',
                ), 
            ),
        );

        $images = new Images($config);
        $images->start();
    }
    
    /**
     * 临时课程购买用户列表 
     * 
     * @access public
     * @return void
     */
    public function listOp()
    {
        $classId = I('id', 0, 'intval'); 
        if ($classId <= 0) {
            exit('参数错误');
        }
        $classModel = new ClassModel();
        $classUserModel = M('glzh_class_user');
        
        // 验证课程是否存在
        $classInfo = $classModel->getClassInfo(['class_id' => $classId]);
        if (empty($classInfo)) {
            exit('记录不存在'); 
        }
        // 统计
        $condition = array();
        $condition['class_id'] = $classId;
        $condition['apply_state'] = 1;
        
        $totalCount = $classUserModel->where($condition)->count();
        $totalAmount = $classUserModel->where($condition)->sum('apply_amount');
        
        $todayBeginTime = strtotime(date('Y-m-d'));
        $todayEndTime   = strtotime(date('Y-m-d 23:59:59'));
        $condition['apply_time'] = [['egt', $todayBeginTime],['lt', $todayEndTime]];
        $todayCount = $classUserModel->where($condition)->count();
        $todayAmount = $classUserModel->where($condition)->sum('apply_amount');
        
        $statistics = array(
            'total_count'   => $totalCount,
            'total_amount'  => $totalAmount,
            'today_count'   => $todayCount,
            'today_amount'  => $todayAmount,     
        );

        $this->assign('statistics', $statistics);
        $this->assign('class_info', $classInfo);
        $this->display();
    }

    public function getApplyedListOp()
    {
        $classId = I('get.id', 0, 'intval'); 
        if ($classId <= 0) {
            $this->returnJson(10010, '参数错误');
        }

        $pageIndex = I('get.pageIndex', 1, 'intval');
        $pageSize  = I('get.pageSize', 20, 'intval');

        // 验证课程是否存在
        $classModel = D('Class');
        $classInfo = $classModel->getClassInfo(['class_id' => $classId]);
        if (empty($classInfo)) {
            $this->returnJson(10011, '记录不存在');
        }

        // 获取报名用户
        $condition = array();
        $condition['class_id'] = $classId;
        $condition['apply_state'] = 1;
        $classUserList = $classModel->getClassUserList($condition, '*', 'apply_time desc', $pageIndex, $pageSize);
        $userService = D('User', 'Service');
        $resellerModel = D('Reseller');

        foreach ($classUserList as &$classUser) {
            // 获取班级信息 
            $groupInfo = $classModel->getClassGroupInfo([
                'group_id'  => $classUser['group_id']
            ]);
            // 获取用户信息
            $userInfo = $userService->getUserBaseInfo($classUser['user_id']);
            $userInfo['level_desc'] = $userService->userLevelDesc($userInfo['level']);
            $userInfo['user_name'] = $userInfo['user_name'] ? : '未填写';
            $userInfo['mobile'] = $userInfo['mobile'] ? : '未填写';
            $userInfo['wechat_id'] = $userInfo['wechat_id'] ? : '未填写';
            $userInfo['company_name'] = $userInfo['company_name'] ? : '未填写';
            // 查询订单信息
            $orderInfo = $classModel->getOrderInfo([
                'order_id' => $classUser['order_id']
            ], 'order_amount, payment_code');
            $orderInfo['payment_name'] = orderPaymentName($orderInfo['payment_code']);

            // 分销商信息
            if ($classUser['reseller_id']) {
                $resellerInfo = $resellerModel->getResellerInfo(['reseller_id' => $classUser['reseller_id']]);
                $classUser['reseller_info'] = $resellerInfo;
            }

            $classUser['group_info'] = $groupInfo;
            $classUser['user_info'] = $userInfo;
            $classUser['order_info'] = $orderInfo;
        }

        $this->returnJson(1, '成功', [
            'list'  => $classUserList
        ]);
    }
    
    /**
     * 导出 
     * 
     * @access public
     * @return void
     */
    public function exportOp()
    {
        $classId = I('get.class_id', 0, 'intval');
        if ($classId <= 0) {
            exit('参数错误'); 
        }
        $classModel = D('Class');
        $classInfo = $classModel->getClassInfo(['class_id' => $classId]);
        if (empty($classInfo)) {
            exit('课程不存在'); 
        }

        $_GET['class_title'] = $classInfo['class_title'];

        $condition = array();
        $condition['class_id'] = $classId;
        $condition['apply_state'] = 1;

        $classUserModel = M('glzhClassUser'); 
        $classService   = D('Class', 'Service');

        if (! is_numeric($_GET['curpage'])) {
            $count = $classUserModel->where($condition)->count();
            if ($count > self::EXPORT_SIZE) {
                // 显示下载链接
                $page = ceil($count / self::EXPORT_SIZE);
                $array = array();
                for ($i = 1; $i <= $page; $i++) {
                    $limit1 = ($i-1) * self::EXPORT_SIZE + 1;
                    $limit2 = $i * self::EXPORT_SIZE > $count ? $count : $i * self::EXPORT_SIZE;
                    $array[$i] = $limit1 . '~' . $limit2;
                }
                $this->assign('list', $array);
                $this->display();
            } else {
                // 直接下载 
                $list = $classService->getClassUserList($condition, 'apply_time desc', self::EXPORT_SIZE);
                $classService->createExcel($list);
            }
        } else {
            // 分段下载
            $limit1 = ($_GET['curpage']-1) * self::EXPORT_SIZE;
            $limit2 = self::EXPORT_SIZE;
            $list = $classService->getClassUserList($condition, 'apply_time desc', "{$limit1},{$limit2}");
            $classService->createExcel($list);
        }
    }
}
