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
        $userService = new \Common\Service\UserService;
        $userInfo = $userService->getUserFullInfo(session('user_id'));
        
        $userModel = new \Common\Model\UserModel;
        $userInfo['fans_num'] = $userModel->where(['parent_id' => session('user_id')])->count();
        $userInfo['total_predeposit'] = $userInfo['available_predeposit'] + $userInfo['freeze_predeposit'];
        $this->assign('user_info', $userInfo);
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
            
            //如果有在线支付且未付款的订单则显示合并付款链接
            if ($order['order_state'] == ORDER_STATE_NEW) {
                $order['pay_amount'] = $order['order_amount'];
            }
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
}