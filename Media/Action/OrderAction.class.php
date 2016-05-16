<?php
namespace Media\Action;

class OrderAction extends CommonAction
{
    protected $needAuth = true;
    
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
