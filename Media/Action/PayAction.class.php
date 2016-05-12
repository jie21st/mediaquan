<?php
namespace Media\Action;

/**
 * 支付入口
 */
class PayAction extends CommonAction
{
    protected $needAuth = true;
    
    public function __construct()
    {
        if (in_array(strtolower(ACTION_NAME), array('notify', 'failed', 'queryOrderState'))) {
            $this->needAuth = false;
        }
        parent::__construct();
    }
    
    public function indexOp()
    {
        if (I('post.order_type') === 'course_buy') {
            $this->_course_buy();
        } else {
            showMessage('参数错误');
        }
    }
    
    private function _course_buy()
    {
        $orderSn = I('post.order_sn');
        $paymentCode = 'wxpay';
        if (! preg_match('/^\d{10,18}$/', $orderSn)){
            $this->returnJson(0, '参数错误');
        }
        
        $payService = new \Common\Service\PayService;
        $result = $payService->courseBuy($_POST, $orderSn, $paymentCode, session('user_id'));
        if (isset($result['error'])) {
            $this->returnJson(0, $result['error']);
        }
        
        //第三方API支付
        $this->_api_pay($result['order_info'], $result['payment_info']);
    }

    protected function _api_pay($orderInfo, $paymentInfo)
    {
        vendor("Payment.Wxpay.WxPayPubHelper");
        //$paymentInfo['payment_config'] = unserialize($paymentInfo['payment_config']);
        //$paymentApi = new \$paymentInfo['payment_code']($paymentInfo, $orderInfo);

        /* 取得用户的openid */
        $userModel = new \Common\Model\UserModel;
        $userInfo = $userModel->getUserInfo($orderInfo['buyer_id']);

        /* 交易类型 */
        $trade_type = (isset($_GET['trade_type']) && in_array(strtoupper($_GET['trade_type']), array('JSAPI', 'NATIVE')))
            ? strtoupper($_GET['trade_type'])
            : 'JSAPI';
        /* 商品价格，以分为单位 */
        $totalfee = floatval($orderInfo['pay_amount']) * 100;
        /* 通知地址 */
        $notify_url = C('APP_SITE_URL') . '/pay/notify';
        $unifiedOrder = new \UnifiedOrder_pub();
        $unifiedOrder->setParameter("openid", $userInfo['user_wechatopenid']); // OpenId
        $unifiedOrder->setParameter("body", $orderInfo['subject']);//商品描述
        $unifiedOrder->setParameter("out_trade_no", $orderInfo['pay_sn']);//商户订单号 
        $unifiedOrder->setParameter("total_fee", "$totalfee");//总金额.注意单位是分
        $unifiedOrder->setParameter("notify_url", $notify_url);//通知地址 
        $unifiedOrder->setParameter("trade_type", $trade_type);//交易类型
        $unifiedOrder->setParameter("attach",$orderInfo['order_type']);//附加数据 
        if ($trade_type == 'JSAPI') {
            $prepay_id = $unifiedOrder->getPrepayId();
            if (! $prepay_id) {
                \Think\Log::write('订单支付: prepay_id获取失败,支付单号'.$orderInfo['pay_sn']);
                $this->returnJson(0, '支付prepay_id获取失败');
            }
            $jsApi = new \JsApi_pub();
            $jsApi->setPrepayId($prepay_id);
            $jsApiParameters = $jsApi->getParameters();

            $this->returnJson(1, 'SUCCESS', $jsApiParameters);
        } else {
            $unifiedOrder->setParameter("product_id",$orderInfo['product_id']);//附加数据 
            $code_url = $unifiedOrder->getCodeUrl();
            $this->returnJson(1, 'SUCCESS', $code_url);
        }
        $this->assign("order_info", $orderInfo);
        $this->display();
    }

    /**
     * 支付异步通知接口
     */
    public function notifyOp()
    {
        // 导入类库
        vendor("Payment.Wxpay.WxPayPubHelper");

        // 获取通知的数据
        $xml = file_get_contents("php://input");

        // 记录日志
        \Think\Log::record('支付通知: ' . $xml , 'INFO');

        // 实例化响应类
        $wxpayServer = new \Wxpay_server_pub();

        // 解析xml数据
        $wxpayServer->saveData($xml);
        $responseData = $wxpayServer->getData();
        
        // 参数判断
        if (strtoupper($responseData['result_code']) != 'SUCCESS') {
            // 支付失败直接返回
            $this->wxpayReturn('FAIL', '支付未成功');
        }
        if (! in_array($responseData['attach'], array('predeposit','vip_buy','course_buy', 'camp_buy', 'agent_pay'))) {
            $this->wxpayReturn('FAIL', '参数错误');
        }
        
        $out_trade_no = $responseData['out_trade_no'];
        
        if ($responseData['attach'] == 'course_buy') {
            $orderModel = new \Common\Model\OrderModel();
            $orderPayInfo = $orderModel->getOrderPayInfo(['pay_sn' => $out_trade_no]);
            // 对订单信息进行非空判断
            if (empty($orderPayInfo)) {
                // 失败处理
                $this->wxpayReturn('FAIL', '支付单不存在', $out_trade_no);
            }
            if (intval($orderPayInfo['api_pay_state'])) {
                // 已支付返回成功
                $this->wxpayReturn('SUCCESS', '已支付', $out_trade_no);
            }
            $orderInfo = $orderModel->getOrderInfo([
                'order_sn' => $orderPayInfo['order_sn'],
                'order_state' => ORDER_STATE_NEW,
            ]);
            if(empty($orderInfo)){
                // 未查到未支付订单返回成功
                $this->wxpayReturn('SUCCESS', '未查询到未支付的订单', $out_trade_no);
            }
            $payAmount = $orderInfo['order_amount'];
        } elseif ($responseData['attach'] == 'predeposit') {
            $pdModel = new \Common\Model\PredepositModel();
            $orderInfo = $pdModel->getPdRechargeInfo(['pdr_sn'=>$out_trade_no]);
            if (empty($orderInfo) || !is_array($orderInfo)) {
                $this->wxpayReturn('FAIL', '支付单不存在', $out_trade_no);
            }
            if (intval($orderInfo['pdr_payment_state'])) {
                $this->wxpayReturn('SUCCESS', '已支付', $out_trade_no);
            }
            $payAmount = $orderInfo['pdr_amount'];
        }
        
        // todo 验证签名时 total_fee应该为订单内价格去生成签名更安全
        if (! $wxpayServer->checkSign()) {
            $this->wxpayReturn('FAIL', '签名验证失败', $out_trade_no);
        }
        
        if ($payAmount != $responseData['total_fee'] / 100) {
            $this->wxpayReturn('FAIL', '支付金额不等于订单金额, 订单金额为'.$payAmount .',支付金额:'.$responseData['total_fee']/100, $out_trade_no);
        }
        
        $orderType = $responseData['attach'];
        $payService = new \Common\Service\PayService();
        if ($orderType == 'course_buy')
        {
            $result = $payService->updateCourseBuy($out_trade_no, 'wxpay', $orderInfo, $responseData['transaction_id']);
            if (! empty($result['error'])) {
                $this->wxpayReturn('FAIL', '处理订单信息失败', $out_trade_no);
            }
        }
        elseif ($orderType == 'predeposit')
        {
            $result = $payService->updatePdRecharge($out_trade_no, 'wxpay', $orderInfo, $responseData['transaction_id']);
            if (! empty($result['error'])) {
                $this->wxpayReturn('FAIL', '处理订单信息失败', $out_trade_no);
            }
        }
        
        $this->wxpayReturn('SUCCESS', '支付成功', $out_trade_no);
    }
    
    /**
     * 查询订单状态
     */
    public function queryOrderStateOp()
    {
        if (I('get.order_type') === 'vip_buy') {
            $model = new \Common\Model\ServiceModel();
            $orderInfo = $model->getOrderInfo(['order_sn' => I('get.order_sn')], 'payment_state');
            if (intval($orderInfo['payment_state'])) {
                $this->returnJson(1, '已支付');
            }
        } elseif (I('get.order_type') === 'course_buy') {
            $model = new \Common\Model\OrderModel();
            $orderInfo = $model->getOrderInfo(['order_sn' => I('get.order_sn')], 'order_state');
            if ($orderInfo['order_state'] == ORDER_STATE_PAY) {
                $this->returnJson(1, '已支付');
            }
        } elseif (I('get.order_type') === 'camp_buy') {
            $model = new \Common\Model\CampModel();
            $orderInfo = $model->getCampOrderInfo(['order_sn' => I('get.order_sn')], 'payment_state');
            if (intval($orderInfo['payment_state'])) {
                $this->returnJson(1, '已支付');
            }
        } else {
            $this->returnJson(0, '未知类型');
        }
        $this->returnJson(0, '等待支付');
    }
    
    private function wxpayReturn($code = 'SUCCESS', $msg = '支付成功', $order_sn = '')
    {
        if ($order_sn != '') {
            \Think\Log::write("支付通知: 订单{$order_sn}({$code}: {$msg})", \Think\Log::INFO);
        }
        echo "<xml><return_code><![CDATA[{$code}]]></return_code><return_msg><![CDATA[{$msg}]]></return_msg></xml>";
        exit();
    }
    
    /**
     * 订单支付失败通知(内部)
     */
    public function failedOp()
    {
        // 初始化参数
        $orderType = I('request.order_type');
        $orderSn = I('request.order_sn');
        $productName = I('request.product_name');
        $reason = I('request.reason', 'unknown');
        if (empty($orderType) || !preg_match('/^\d{15}$/', $orderSn)){
            $this->returnJson(0, '参数错误');
        }
        
        // 查询订单信息
        $opId = '';
        switch ($orderType) {
            case 'course_buy':
                $model = new \Common\Model\ClassModel();
                $fields = 'order_id,buyer_id as user_id,order_amount,class_id';
                $orderInfo = $model->getOrderInfo(['order_sn' => $orderSn], $fields);
                if($orderInfo) {
                    $opId = $orderInfo['class_id'];
                }
                break;
            case 'camp_buy':
                $model = new \Common\Model\CampModel();
                $fields = 'camp_id,user_id,order_amount';
                $orderInfo = $model->getCampOrderInfo(['order_sn' => $orderSn], $fields);
                if($orderInfo) {
                    $opId = $orderInfo['camp_id'];
                }
                break;
            case 'vip_buy':
                $model = new \Common\Model\ServiceModel();
                $orderInfo = $model->getOrderInfo(['order_sn' => $orderSn], 'user_id,order_amount');
                break;
            case 'pd_rechange':
                $model = new \Common\Model\PredepositModel;
                $fields = 'pdr_user_id as user_id,pdr_amount as order_amount';
                $orderInfo = $model->getPdRechargeInfo(['pdr_sn' => $orderSn], $fields);
                break;
            default :
                $this->returnJson(0, '不支持该订单类型');
        }
        if (empty($orderInfo) || !is_array($orderInfo)) {
            $this->returnJson(0, '订单不存在');
        }
        // 查询用户信息
        $userService = new \Common\Service\UserService;
        $userInfo = $userService->getUserInfo($orderInfo['user_id']);
        
        $bizCode = array(
            'camp_buy' => '500001100010001',
            'course_buy' => '500001100001001',
            'vip_buy' => '500001001001001',
            'pd_rechange' => '500001005010020',
        );
        
        $data = [
            'order_sn' => $orderSn,
            'product_name' => $productName,
            'amount' => $orderInfo['order_amount'],
            'user_name' => $userInfo['user_name'],
            'user_id' => $userInfo['user_id'],
            'reason' => $reason,
            'mobile' => $userInfo['mobile'],
        ];
        
        // 微信消息模板通知
        $tempMsgService = new \Common\Service\TemplateMessageService;
        $tempMsgService->notify($bizCode[$orderType], $opId, 2, $data);
        
        $this->returnJson(1, 'SUCCESS');
    }
}
