<?php
namespace Common\Service;

/**
 * 课程服务类 
 * 
 * @author Wang Jie <wangj@guanlizhihui.com> 2016-05-15
 */
class ClassService
{
    /**
     * 购买：保存订单入库，产生订单号
     * 
     * @param type $post
     * @param type $userId
     * @return type
     */
    public function buy($post, $userId)
    {
        $classModel = new \Common\Model\ClassModel();
        $classInfo = $classModel->getClassInfo(['class_id' => $post['id']], 'class_id,class_title,class_image,teacher_id,teacher_name,class_price,commis_rate');
        
        try {
            // 开始事务
            $classModel->startTrans();

            // 生成订单
            list($orderSn,$orderInfo) = $this->createOrder($classInfo, $userId);

            // 记录订单日志
            $this->addOrderLog($orderInfo);
            
            // 提交事务
            $classModel->commit();
        } catch (\Exception $e) {
            // 回滚事务
            $classModel->rollback();
            return array('error' => $e->getMessage());
        }
        
        return array('order_sn' => $orderSn);
    }
    
    /**
     * 创建订单
     * 
     * @param type $input
     * @param type $userId
     * @return type
     */
    public function createOrder($input, $userId)
    {
        $orderModel = new \Common\Model\OrderModel();
        
        $orderSn = $this->makeOrderSn($userId);
        $order = [
            'buyer_id'      => $userId,
            'order_sn'      => $orderSn,
            'class_id'      => $input['class_id'],
            'class_title'   => $input['class_title'],
            'class_image'   => $input['class_image'],
            'class_price'   => $input['class_price'],
            'class_teacher' => $input['teacher_name'],
            'order_amount'  => $input['class_price'],
            'expire_time'   => (time() + ORDER_EXPIRE),
            'payment_code'  => '',
            'order_state'   => $input['_is_free'] ? ORDER_STATE_PAY : ORDER_STATE_NEW,
            'from_seller'   => session('from_seller') ? : 0,
            'commis_rate'   => $input['commis_rate'],
        ];
        
        $orderId = $orderModel->addOrder($order);
        if(! $orderId){
            throw new \Exception('订单保存失败');
        }
        $order['order_id'] = $orderId;
        
        return array($orderSn, $order);
    }
    
        
    /**
     * 记录订单日志
     * @param array $orderInfo
     */
    public function addOrderLog($orderInfo = array()) {
        if (empty($orderInfo) || !is_array($orderInfo)) return;
        $orderModel = new \Common\Model\OrderModel;
        $data = array();
        $data['order_id'] = $orderInfo['order_id'];
        $data['log_role'] = 'buyer';
        $data['log_msg'] = '提交了订单';
        $data['log_orderstate'] = ORDER_STATE_NEW;
        $orderModel->addOrderLog($data);
    }

    /**
     * 生成支付单编号 (业务编码+年的后2位+月+日+随机5位+用户ID%1000)
     * 长度 1位 + 2位 + 2位 + 2位 + 随机5位 + 3位 = 15位
     * @param type $userId
     * @return type
     */
    public function makeOrderSn($userId)
    {
        return date('y') . date('md')
              . sprintf('%06d', mt_rand(1, 999999))
              . sprintf('%03d', (int) $userId % 1000);
    }
    
    /**
     * 检查用户是否报名 
     * 
     * @param mixed $classId 
     * @param mixed $userId 
     * @access public
     * @return void
     */
    public function checkClassUser($classId, $userId)
    {
        $model = D('Class');
        $result = $model->getClassUser([
            'class_id'  => $classId,
            'user_id'   => $userId,
            'apply_state' => 1,
        ]);
        if ($result) {
            return true; 
        } else {
            return false; 
        }
    }

    /**
     * 购买成功插入课程用户
     * 
     * @param array $classOrder 课程订单
     * @throws \Exception
     */
    public function addClassUser(array $classOrder)
    {
        $classModel = new \Common\Model\ClassModel;
        $userModel = new \Common\Model\UserModel;

        // 插入课程报名表
        $data               = array();
        $data['class_id']   = $classOrder['class_id'];
        $data['user_id']    = $classOrder['buyer_id'];
        $data['order_id']   = $classOrder['order_id'];
        $data['apply_amount'] = $classOrder['order_amount'];
        $data['apply_time'] = time();
        $data['apply_state']= 1;
        $result = $classModel->addClassUser($data);
        if (! $result) {
            throw new \Exception('插入课程用户信息失败');
        }
        
        // 增加课程学习人数
        $data = array();
        $data['study_num'] = ['exp', 'study_num+1'];
        $update = $classModel->editClass($data, ['class_id' => $classOrder['class_id']]);
        if (! $update) {
            throw new \Exception('更新课程学习人数失败');
        }
        
        // 增加用户购买次数
        $data = array();
        $data['buy_num'] = ['exp', 'buy_num+1'];
        $result = $userModel->editUser($data, ['user_id' => $classOrder['buyer_id']]);
        if (! $result) {
            throw new \Exception('更新用户购买次数失败');
        }
        
        // 通知购买人
        $buyerInfo = $userModel->getUserInfo(['user_id' => $classOrder['buyer_id']]);
        $wechatService = new \Common\Service\WechatService;
        $wechatService->sendCustomMessage([
            'touser' => $buyerInfo['user_wechatopenid'],
            'msgtype' => 'text',
            'text' => [
                'content' => '感谢您购买《'.$classOrder['class_title'].'》，推荐朋友购买课程可获得收益分成，'
                . '<a href="'.C('MEDIA_SITE_URL').'/class/'.$classOrder['class_id'].'.html?share=1">点击推荐</a>'
            ]
        ]);
        
        // 通知粉丝 3级
        function notifyFans($openid, $buyerInfo, $orderInfo) {
            //$wechatService->sendCustomMessage([
            print_r([
                'touser' => $openid,
                'msgtype' => 'text',
                'text' => [
                    'content' => sprintf(
                                    '您的推荐人%s购买了《%s》，课程很实用，快去和他一起学习吧，<a href="%s">点击听课</a>',
                                    $buyerInfo['user_nickname'],
                                    $orderInfo['class_title'],
                                    C('MEDIA_SITE_URL').'/class/'.$orderInfo['class_id'].'.html'
                                )
                ]
            ]);
        }
        $users = $userModel->where(['parent_id' => $buyerInfo['user_id']])->select();
        foreach ($users as $user) {
            notifyFans($user['user_wechatopenid'], $buyerInfo, $classOrder);
            $result = $userModel->where(['parent_id' => $user['user_id']])->select();
            foreach ($result as $value) {
                notifyFans($value['user_wechatopenid'], $buyerInfo, $classOrder);
                $result3 = $userModel->where(['parent_id' => $value['user_id']])->select();
                foreach ($result3 as $value3) {
                    notifyFans($value3['user_wechatopenid'], $buyerInfo, $classOrder);
                }
            }
        }
//        if (! empty($fansList)) {
//            foreach ($fansList as $fansInfo) {
//                $wechatService->sendCustomMessage([
//                    'touser' => $fansInfo['user_wechatopenid'],
//                    'msgtype' => 'text',
//                    'text' => [
//                        'content' => sprintf(
//                                        '您的推荐人%s购买了《%s》，课程很实用，快去和他一起学习吧，<a href="%s">点击听课</a>',
//                                        $buyerInfo['user_nickname'],
//                                        $classOrder['class_title'],
//                                        C('MEDIA_SITE_URL').'/class/'.$classOrder['class_id'].'.html'
//                                    )
//                    ]
//                ]);
//            }
//        }
    }
    
    /**
     * 订单结算
     * 
     * @param type $orderInfo
     */
    public function orderBill($orderInfo)
    {
        if ($orderInfo['commis_rate'] == 0) {
            \Think\Log::write('订单结算: 失败 '.$orderInfo['order_sn'].'该订单佣金比例为0');
            return;
        }
        
        $userModel = new \Common\Model\UserModel;
        $buyerInfo = $userModel->getUserInfo(['user_id' => $orderInfo['buyer_id']]);
        if ($buyerInfo['parent_id'] == 0) {
            \Think\Log::write('订单结算: 失败 '.$orderInfo['order_sn'].'该用户没有推荐人');
            return;
        }
        
        $seller_level_rate = C('SELLER_LEVEL_RATE');
        if (empty($seller_level_rate)) {
            \Think\Log::write('订单结算: 失败 '.$orderInfo['order_sn'].'未设置销售员分销比例');
            return;
        }
        
        $parents = $this->getUserParents($orderInfo['buyer_id'], 3);
        if (is_array($parents) && !empty($parents)) {
            $parentsCount = count($parents);
            $model = D('orderBill');
            $pdService = new PredepositService();
            $wechatService = new \Common\Service\WechatService;
            
            for ($i = 0; $i < $parentsCount; $i++) {
                $sellerId = $parents[$i];
                // 销售员信息
                $parentInfo = $userModel->getUserInfo(['user_id' => $sellerId]);
                // 销售员分配比例
                $sellerRate = $seller_level_rate[$parentsCount-1][$i]/100;
                // 订单佣金金额
                $orderCommisAmount = $orderInfo['order_amount'] * $orderInfo['commis_rate'] / 100;
                // 获得佣金金额
                $commisAmount = round($orderCommisAmount * $sellerRate, 2, PHP_ROUND_HALF_DOWN);
                // 收益记录
                $insertId = $model->add([
                    'user_id' => $sellerId,
                    'buyer_id' => $orderInfo['buyer_id'],
                    'order_id' => $orderInfo['order_id'],
                    'gains_amount' => $commisAmount,
                    'gains_time' => time(),
                    'level_val'  => $i+1,
                    'level_rate' => $sellerRate*100,
                ]);
                if (! $insertId) {
                    throw new \Exception('分销收益记录失败');
                }
                
                // 收益入账
                $pdData = array();
                $pdData['user_id'] = $sellerId;
                $pdData['amount'] = $commisAmount;
                $pdData['name'] = $buyerInfo['user_nickname'].' 购买了 '.$orderInfo['class_title'];
                $pdData['order_sn'] = $orderInfo['order_sn'];
                $pdService->changePd('sale_income', $pdData);
                
                // 收益通知
                $wechatService->sendCustomMessage([
                    'touser' => $parentInfo['user_wechatopenid'],
                    'msgtype' => 'text',
                    'text' => [
                        'content' => sprintf(
                                        '%s购买了《%s》，您获得收益%s元，课程很受欢迎，快去介绍给你的好友吧，<a href="%s">点击分享这门课程</a>',
                                        $buyerInfo['user_nickname'],
                                        $orderInfo['class_title'],
                                        glzh_price_format($commisAmount),
                                        C('MEDIA_SITE_URL').'/class/'.$orderInfo['class_id'].'.html?share=1'
                                    )
                    ]
                ]);
            }
        }
    }
    
    public function getUserParents($userId, $level = 3) {
        static $list=array();
        if ($level-- > 0) {
            $userModel = new \Common\Model\UserModel;
            $childInfo = $userModel->getUserInfo(['user_id' => $userId]);
            if (intval($childInfo['parent_id']) != 0) {
                $list[] = $childInfo['parent_id'];
                return $this->getUserParents($childInfo['parent_id'], $level);
            }
        }
        return $list;
    }
}
