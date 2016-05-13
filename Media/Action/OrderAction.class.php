<?php
namespace Media\Action;

class OrderAction extends CommonAction
{
    protected $needAuth = true;
    
    /**
     * 订单列表
     */
    public function listOp()
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
     * 取消订单
     */
    public function cancelOp()
    {
        $orderId = I('request.order_id', 0, 'intval');
        
        if ($orderId <= 0) {
            $this->returnJson(0, '参数错误');
        }

        $orderModel = new \Common\Model\OrderModel();

        $condition = array();
        $condition['order_id'] = $orderId;
        $condition['buyer_id'] = session('user_id');
        $orderInfo = $orderModel->getOrderInfo($condition);

        try {
            $orderModel->startTrans();
            
            $orderService = new \Common\Service\OrderService;
            $orderService->memberChangeStateOrderCancel($orderInfo);
            
            $orderModel->commit();
            
            $this->returnJson(1, '取消成功');
        } catch (\Exception $e) {
            $orderModel->rollback();
            \Think\Log::write('取消订单: '.$e->getMessage());
            $this->returnJson(0, '取消失败');
        }
    }
}
