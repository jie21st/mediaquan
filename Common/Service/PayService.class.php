<?php
namespace Common\Service;

/**
 * 支付服务类
 * 
 * @author Wang Jie <im.wjie@gmail.com>
 */
class PayService
{
    /**
     * 课程购买
     * 
     * @param type $input
     * @param type $orderSn
     * @param type $paymentCode
     * @param type $userId
     * @return type
     */
    public function courseBuy($input, $orderSn, $paymentCode, $userId)
    {
        $paymentModel = new \Common\Model\PaymentModel();
        $condition['payment_code'] = $paymentCode;
        $paymentInfo = $paymentModel->getPaymentOpenInfo($condition);
        if (! $paymentInfo) {
            return array('error' => '系统不支持选定的支付方式');
        }
        
        // 验证订单信息
        $orderModel = new \Common\Model\OrderModel();
        $orderInfo = $orderModel->getOrderInfo([
            'order_sn'  => $orderSn,
            'buyer_id'  => $userId,
            'order_state' => ORDER_STATE_NEW
        ]);
        if(empty($orderInfo)){
            return array('error' => '该订单不存在');
        }

        // 创建支付单信息
        $orderPayInfo = array(
            'pay_sn' => $this->makePaySn(),
            'buyer_id' => $orderInfo['buyer_id'],
            'order_sn' => $orderInfo['order_sn'],
        );
        $orderPayId = $orderModel->addOrderPay($orderPayInfo);
        if (! $orderPayId) {
            return array('error' => '创建支付单信息失败');
        }
        // 查询订单课程信息
        $orderPayInfo['subject'] = $orderInfo['class_title'];
        $orderPayInfo['order_type'] = 'course_buy';
        $orderPayInfo['product_id'] = $orderInfo['class_id'];
        $orderPayInfo['pay_amount'] = $orderInfo['order_amount'];
        
        return array(
            'order_info' => $orderPayInfo,
            'payment_info' => $paymentInfo
        );
    }
    
    public function makePaySn()
    {
        return date('Ymd')
            .substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);
    }

    /**
     * 购买订单支付成功后修改订单状态 
     * 
     * @param mixed $paySn 支付单号    预存款支付时为空
     * @param string $paymentCode 支付方式代码
     * @param mixed $orderInfo 订单信息
     * @access public
     * @return void
     */
    public function updateCourseBuy($paySn, $paymentCode, $orderInfo, $tradeNo = '')
    {
        $orderModel = D('Order');
        $classModel = new \Common\Model\ClassModel;
        try {
            $orderModel->startTrans();
            // 更新支付单信息
            $data = array();
            $data['api_pay_state'] = 1;
            $data['trade_no'] = $tradeNo;
            $update = $orderModel->editOrderPay($data, ['pay_sn' => $paySn]);
            if (! $update) {
                throw new \Exception('更新支付单状态失败');
            }

            // 更新订单信息
            $data = array();
            $data['pay_sn']         = $paySn;
            $data['order_state']    = ORDER_STATE_PAY;
            $data['payment_code']   = $paymentCode;
            $data['payment_time']   = time();
            $update = $orderModel->editOrder($data, [
                'order_sn'      => $orderInfo['order_sn'],
                'order_state' => ORDER_STATE_NEW
            ]);
            if (! $update) {
                throw new \Exception('更新订单状态失败');
            }

            // 添加课程用户
            $classService = new \Common\Service\ClassService();
            $classService->addClassUser($orderInfo);

            // 订单分销结算
//            $billService = new \Common\Service\BillService();
//            $billService->courseOrderBill($orderInfo);
            
            // 提交事务
            $orderModel->commit();
        } catch(\Exception $e) {
            // 回滚修改
            $orderModel->rollback(); 
            // 记录日志
            \Think\Log::write($e->getMessage());
            // 返回错误信息
            return ['error' => $e->getMessage()];
        }
    }
}
