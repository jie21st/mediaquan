<?php
namespace Media\Action;

class MyAction extends \Media\Action\CommonAction
{
    protected $needAuth = true;
    
    /**
     * 个人中心主页
     */
    public function indexOp()
    {
        $userModel = new \Common\Model\UserModel;
        $userService = new \Common\Service\UserService;
        
        $chapterUserModel = new \Common\Model\ChapterUserModel;
        
        // 获取用户信息
        $userInfo = $userService->getUserFullInfo(session('user_id'));
        // 统计粉丝数
        $userInfo['fans_num'] = $userModel->where(['parent_id' => session('user_id')])->count();
        // 统计总金额
        $userInfo['total_predeposit'] = $userInfo['available_predeposit'] + $userInfo['freeze_predeposit'];
        // 获取听课历史记录
//        $historyInfo = $chapterUserModel->getCoursesClientTime(['user_id' => session('user_id')], '', 'create_time desc');
//        if (!empty($historyInfo)) {
//            $chapterModel = new \Common\Model\ChapterModel;
//            $historyInfo['chapter_info'] = $chapterModel->getCourseInfo(['chapter_id' => $historyInfo['chapter_id']]);
//        }
        $redis = \Think\Cache::getInstance('redis');
        $historyInfo = $redis->hGetAll('courses:histoty:' . session('user_id'));
        if (!empty($history)) {
            dump($history);
            $chapterModel = new \Common\Model\ChapterModel;
            $historyInfo['chapter_info'] = $chapterModel->getCourseInfo(['chapter_id' => $historyInfo['chapter_id']]);
        }
        
        $this->assign('user_info', $userInfo);
        $this->assign('history_info', $historyInfo);
        $this->display();
    }
    
    /**
     * 我的订单
     */
    public function orderOp()
    {
        $orderService = new \Common\Service\OrderService;
        $orderModel = new \Common\Model\OrderModel;
        $condition = array();
        $condition['buyer_id'] = session('user_id');
        
        $orderList = $orderModel->getOrderList($condition);
        foreach ($orderList as &$order) {
            $order['state_desc'] = orderState($order);
            $order['payment_name'] = orderPaymentName($order['payment_code']);
            
            //显示取消订单
            $order['if_cancel'] = $orderService->getOrderOperateState('buyer_cancel', $order);
            
            //显示去听课
            $order['if_learn'] = $orderService->getOrderOperateState('learn', $order);
            
            //显示支付
            $order['if_pay'] = $orderService->getOrderOperateState('pay', $order);
            
        }
        
        $this->assign('order_list', $orderList);
        $this->display();
    }
    
    /**
     * 我的东家
     * 
     */
    public function parentOp()
    {
        $userModel = new \Common\Model\UserModel();
        $userInfo = $userModel->getUserInfo(['user_id' => session('user_id')]);
        $parentId = $userInfo['parent_id'];
        
        $parentInfo = $userModel->getUserInfo(['user_id' => $parentId]);
        $this->assign('parent_info', $parentInfo);
        $this->display();
    }
    
    /**
     * 我的粉丝
     */
    public function fansOp()
    {
        $userModel = new \Common\Model\UserModel();
        $condition = array();
        $condition['parent_id'] = session('user_id');
        
        $count = $userModel->where($condition)->count();
        $userList = $userModel->where($condition)->select();
        
        $this->assign('count', $count);
        $this->assign('user_list', $userList);
        $this->display();
    }
    
    /**
     * 我的课程
     */
    public function courseOp()
    {
        $classModel = new \Common\Model\ClassModel();
        $applyList = $classModel->getClassUserList(['user_id' => session('user_id')]);
        $classIds = array();
        foreach ($applyList as &$apply) {
            $apply['class_info'] = $classModel->getClassInfo(['class_id' => $apply['class_id']]);
        }
        $this->assign('apply_list', $applyList);
        $this->display();
    }

    /**
     * 推广 
     */
    public function posterOp()
    {
        $posterService = new \Media\Service\CreatePosterService();
        $imagePath = $posterService->getPoster(session('user_id'), true);

        if($imagePath['pathName'] == '') {
            //dump($imagePath);
            exit('获取海报失败');
        } else {
            $data = base64_encode(json_encode($imagePath));
            $this->assign('data', $data);
            $this->assign('imageSrc', $imagePath['pathName']);
            $this->display();
        }
    }
}
