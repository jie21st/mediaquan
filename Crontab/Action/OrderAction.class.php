<?php
namespace Crontab\Action;

class OrderAction extends \Think\Action
{
    /**
     * 初始化对象
     */
    public function __construct()
    {
        parent::__construct();

        register_shutdown_function(array($this, "shutdown"));
    }
    
    /**
     * 订单过期处理
     */
    public function order_expireOp()
    {
        $orderModel = new \Common\Model\OrderModel();
        $condition = array();
        $condition['order_state'] = ORDER_STATE_NEW;
        $condition['create_time'] = ['gt', time() - 7200];
        
        $orderList = $orderModel->where($condition)->select();
        echo $orderModel->_sql();
        print_r($orderList);
        foreach ($orderList as $orderInfo) {
            $data = array();
            $data['order_state'] = ORDER_STATE_CANCEL;
            $update = $orderModel->editOrder($data, ['order_id' => $orderInfo['order_id']]);
            if ($update) {
                $this->addOrderLog($orderInfo);
            }
        }
    }
    
    /**
     * 记录订单日志
     * @param array $orderInfo
     */
    private function addOrderLog($orderInfo = array()) {
        if (empty($orderInfo) || !is_array($orderInfo)) return;
        $orderModel = new \Common\Model\OrderModel;
        $data = array();
        $data['order_id'] = $orderInfo['order_id'];
        $data['log_role'] = 'system';
        $data['log_msg'] = '取消了订单';
        $data['log_orderstate'] = ORDER_STATE_CANCEL;
        $orderModel->addOrderLog($data);
    }

    public function shutdown()
    {
        exit("\n" . date('Y-m-d H:i:s') . "\tsuccess");
    }
}
