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
        
        $this->assign('order_info', $orderInfo);
        
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
        sleep(2);
        
        // 查询课程订单
        $orderModel = new \Common\Model\OrderModel();
        $orderInfo = $orderModel->getOrderInfo(['order_sn' => $orderSn, 'buyer_id' => session('user_id')]);
        if (empty($orderInfo)) {
            showMessage('支付单信息不存在');
        }
        $this->assign('order_info', $orderInfo);
        
        // 跳转到电子听课证
        if ($orderInfo['order_state'] == ORDER_STATE_PAY) {
            $this->display();
        } else {
            echo '正在验证订单支付状态，稍后请手动刷新页面';
        }
    }
}
