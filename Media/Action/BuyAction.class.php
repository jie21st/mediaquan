<?php
namespace Media\Action;

/**
 * 购买流程
 */
class BuyAction extends CommonAction
{
    /**
     * 是否需要登录
     * @var type 
     */
    protected $needAuth = true;
    
    /**
     * 课程购买
     */
    public function course_buyOp()
    {
        $orderSn = I('get.order_sn');
        if (! preg_match('/^\d{15}$/', $orderSn)){
            showMessage('参数错误');
        }
        
        $orderModel = new \Common\Model\OrderModel;
        $classModel = new \Common\Model\ClassModel();
        
        // 取订单信息
        $condition = array();
        $condition['order_sn'] = $orderSn;
        $condition['order_state'] = array('in', array(ORDER_STATE_NEW,ORDER_STATE_PAY));
        $orderInfo = $orderModel->getOrderInfo($condition,'*');
        if (empty($orderInfo)) {
            showMessage('未找到需要支付的订单');
        }
        
        $classInfo = $classModel->getClassInfo(['class_id' => $orderInfo['class_id']]);
        
        $this->assign('order_info', $orderInfo);
        $this->assign('class_info', $classInfo);
        
        $this->display();
    }
    
    /**
     * 课程支付成功页面
     */
    public function course_okOp()
    {
        $orderSn = I('get.order_sn');
        if (! preg_match('/^\d{10,18}$/', $orderSn)){
            showMessage('参数错误');
        }
        
        // 查询课程订单
        $classModel = new \Common\Model\ClassModel();
        $orderInfo = $classModel->getOrderInfo(['order_sn' => $orderSn, 'buyer_id' => session('user.user_id')]);
        if (empty($orderInfo)) {
            showMessage('支付单信息不存在');
        }
        
        // 查询课程用户
        $classInfo = $classModel->getClassInfo(['class_id' => $orderInfo['class_id']]);
        
        // 跳转到电子听课证
        sleep(2);
        if ($orderInfo['order_state'] == ORDER_STATE_PAY) {
            redirect(C('APP_SITE_URL') . "/class/ticket?class_id={$classInfo['class_id']}");
        } else {
            echo '正在验证订单支付状态，稍后请手动刷新页面';
        }
    }
    
    /**
     * 加入训练营支付
     */
    public function camp_buyOp()
    {
        $orderSn = I('get.order_sn');
        if (! preg_match('/^\d{15}$/', $orderSn)){
            showMessage('参数错误');
        }
        // 查询订单信息
        $campModel = new \Common\Model\CampModel;
        $condition = array();
        $condition['order_sn'] = $orderSn;
        $orderInfo = $campModel->getCampOrderInfo($condition,'*');
        if (empty($orderInfo)) {
            showMessage('参数错误');
        }
        if (intval($orderInfo['payment_state'])) {
            showMessage('您的订单已经支付，请勿重复支付');
        }
        $this->assign('order_info', $orderInfo);
        // 查询训练营信息
        $campInfo = $campModel->getCampInfo(['camp_id' => $orderInfo['camp_id']]);
        if (empty($campInfo)) {
            showMessage('训练营不存在');
        }
        $this->assign('camp_info', $campInfo);
        // 查询用户信息
        $userService = new \Common\Service\UserService();
        $userInfo = $userService->getUserFullInfo(session('user.user_id'));
        $this->assign('available_predeposit', $userInfo['available_predeposit']);
        
        $this->display();
    }
    
    /**
     * 训练营支付成功页面
     */
    public function camp_okOp()
    {
        $orderSn = I('get.order_sn');
        if (! preg_match('/^\d{15}$/', $orderSn)){
            showMessage('参数错误');
        }
        
        $campModel = new \Common\Model\CampModel();
        $orderInfo = $campModel->getCampOrderInfo(['order_sn' => $orderSn, 'user_id' => session('user.user_id')]);
        if (empty($orderInfo)) {
            showMessage('订单不存在');
        }
        
        $campInfo = $campModel->getCampInfo(['camp_id' => $orderInfo['camp_id']]);
        if (empty($campInfo)) {
            showMessage('训练营不存在');
        }
        $this->assign('camp_info', $campInfo);
        
        $placeInfo = $campModel->getCampPlaceInfo([
            'place_id' => $orderInfo['place_id']
        ]);
        $this->assign('place_info', $placeInfo);
        
        $this->display();
    }
    
    /**
     * 开通VIP下单时支付页面
     */
    public function vippayOp()
    {
        $orderSn = I('get.order_sn');
        if (! preg_match('/^\d{15}$/', $orderSn)){
            showMessage('参数错误');
        }
        // 查询支付单信息
        $serviceModel = new \Common\Model\ServiceModel();
        //$vipOrderModel = new \Common\Model\OrderVipModel;
        $orderInfo = $serviceModel->getOrderInfo([
            'order_sn' => $orderSn,
            'user_id' => session('user.user_id'),
        ]);
        if (empty($orderInfo)) {
            showMessage('参数错误');
        }
        if (intval($orderInfo['payment_state'])) {
            showMessage('您的订单已经支付，请勿重复支付');
        }
        $this->assign('order_info', $orderInfo);
        
        $userService = new \Common\Service\UserService();
        $userInfo = $userService->getUserFullInfo(session('user.user_id'));
        $this->assign('available_predeposit', $userInfo['available_predeposit']);
        
        $this->display();
    }
    
    /**
     * vip支付成功页面
     */
    public function vip_okOp()
    {
        $this->display();
    }
    
    /**
     * 预存款充值支付
     */
    public function pd_payOp()
    {
        $paySn	= I('get.pay_sn'); //$_GET['pay_sn'];
        if (! preg_match('/^\d{15}$/',$paySn)){
            showMessage('参数错误');
        }
        // 查询支付单信息
        $orderModel= new \Common\Model\PredepositModel();
        $pdrInfo = $orderModel->getPdRechargeInfo(['pdr_sn' => $paySn, 'pdr_user_id' => session('user.user_id')]);
        if(empty($pdrInfo)){
            showMessage('参数错误');
        }
        if (intval($pdrInfo['pdr_payment_state'])) {
            showMessage('您的订单已经支付，请勿重复支付');
        }
        $this->assign('pdr_info', $pdrInfo);
        $this->display();
    }
    
    /**
     * 充值成功页面
     */
    public function pd_okOp()
    {
        $this->display();
    }

    /**
     * 付款
     */
    public function agentpayOp()
    {
        $apSn = I('get.ap_sn');
        if (empty($apSn)) {
            showMessage('参数错误');
        }
        $payagentModel = new \Common\Model\PayagentModel();
        $condition = array();
        $condition['ap_sn'] = $apSn;
        $payagentInfo = $payagentModel->getPayagentInfo($condition);
        if (empty($payagentInfo)) {
            showMessage('记录不存在');
        }
        if (intval($payagentInfo['payment_state'])) {
            redirect('/payagent/result?ap_sn='.$payagentInfo['ap_sn']);
        }
        $this->assign('agentpay_info', $payagentInfo);
        $this->display();
    }
}
