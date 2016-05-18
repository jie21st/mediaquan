<?php
namespace Common\Service;

class OrderService
{
    /**
     * 返回是否允许某些操作
     * 
     * @param type $operate
     * @param type $orderInfo
     * @return boolean
     */
    public function getOrderOperateState($operate, $orderInfo)
    {
        if (!is_array($orderInfo) || empty($orderInfo)) return false;
        switch ($operate) {
            // 支付
            case 'pay':
                $state = ($orderInfo['order_state'] == ORDER_STATE_NEW);
                break;
            // 买家取消订单
            case 'buyer_cancel':
                $state = ($orderInfo['order_state'] == ORDER_STATE_NEW);
        	break;
            // 学习
    	    case 'learn':
    	        $state = ($orderInfo['order_state'] == ORDER_STATE_PAY);
    	        break;
        }
        return $state;
    }
    
    /**
     * 取消订单操作
     * 
     * @param array $orderInfo
     */
    public function memberChangeStateOrderCancel($orderInfo) {
        $orderId = $orderInfo['order_id'];
        $if_allow = $this->getOrderOperateState('buyer_cancel', $orderInfo);
        if (! $if_allow) {
            throw new \Exception('非法访问');
        }

        // 更新学习人数
//        $classModel = new \Common\Model\ClassModel();
//        $data = array();
//        $data['study_num'] = ['exp', 'study_num-1'];
//        $update= $classModel->editClass($data, ['class_id' => $orderInfo['class_id']]);
//        if (!$update) {
//            throw new \Exception('保存失败');
//        }
 
        // 更新订单信息
        $orderModel = new \Common\Model\OrderModel;
        $update_order = array('order_state' => ORDER_STATE_CANCEL);
        $update = $orderModel->editOrder($update_order,array('order_id'=>$orderId));
        if (!$update) {
            throw new \Exception('保存失败');
        }

        //添加订单日志
        $log_data = array();
        $log_data['order_id'] = $orderId;
        $log_data['log_role'] = 'buyer';
        $log_data['log_msg'] = '取消了订单';
        $log_data['log_orderstate'] = ORDER_STATE_CANCEL;
        $orderModel->addOrderLog($log_data);
    }
}
