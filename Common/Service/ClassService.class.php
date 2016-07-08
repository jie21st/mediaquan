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
        $classInfo = $classModel->getClassInfo(['class_id' => $post['id']], 'class_id,class_title,store_id,store_name,class_image,teacher_id,teacher_name,class_price,commis_rate');
        
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
    public function createOrder($input, $userId, $userNickname)
    {
        $orderModel = new \Common\Model\OrderModel();
        
        $orderSn = $this->makeOrderSn($userId);
        $order = [
            'buyer_id'      => $userId,
            'order_sn'      => $orderSn,
            'store_id'      => $input['store_id'],
            'store_name'    => $input['store_name'],
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
        $storeId = $classOrder['store_id'];
        $classId = $classOrder['class_id'];
        $buyerId = $classOrder['buyer_id'];
        $className = $classOrder['class_title'];
        
        // 插入课程报名表
        $data               = array();
        $data['class_id']   = $classId;
        $data['store_id']   = $storeId;
        $data['store_name'] = $classOrder['store_name'];
        $data['user_id']    = $buyerId;
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
        $update = $classModel->editClass($data, ['class_id' => $classId]);
        if (! $update) {
            throw new \Exception('更新课程学习人数失败');
        }
        
        // 增加用户购买次数
        $data = array();
        $data['buy_num'] = ['exp', 'buy_num+1'];
        $result = $userModel->editUser($data, ['user_id' => $buyerId]);
        if (! $result) {
            throw new \Exception('更新用户购买次数失败');
        }
        // 通知
        $storeFansService = new StoreFansService();
        $fans = $storeFansService->getFansByUserId($storeId, $buyerId);
        if($fans) {  
            $fansId = $fans['fans_id'];
            $fansNickname = $fans['fans_nickname'];
            // 通知自己
            $storeService = new StoreService();
            $storeService->sendMessage(
                $storeId,
                $fansId,
                'user_buy',
                [
                    'class_name' => $classOrder['class_title'],
                    'recom_url' => C('MEDIA_SITE_URL').'/class/'.$classId.'.html?share=1',
                ]
            );
            // 通知粉丝
            $storeFansService->getFans(
                $fansId,
                3,
                ['openid'],
                function($fans) use($storeService, $storeId, $fansNickname, $classId, $className) {
                    $storeService->sendMessage(
                            $storeId,
                            $fans['fans_id'],
                            'user_buy_notify_fans',
                            [
                                'buyer_name' => $fansNickname,
                                'class_name' => $className,
                                'class_url' => C('MEDIA_SITE_URL').'/class/'.$classId.'.html',
                            ]
                    );
                }
            );
        }
    }
    
    /**
     * 分佣
     * 
     * @param type $order 订单详细信息
     * @return boolean
     */
    public function orderCommission($order)
    {
        $storeId = $order['store_id'];
        $orderId = $order['order_id'];
        $buyerId = $order['buyer_id'];
        
        $storeService = new StoreService();
        
        if ($order['commis_rate'] == 0) {
            return false;   //未设置分销比例
        }
        
        // 获取分销设置
        $setting = M('store_distribution')->where(['store_id'])->find();
        if (empty($setting) || $setting['state'] != '1') {
            return false;   // 未设置分销或未启用
        }
        
        // 购买粉丝信息
        $storeFansService = new StoreFansService();
        $fans = $storeFansService->getFansByUserId($storeId, $buyerId);
        if (empty($fans) || $fans['parent_id'] == 0) {
            return false;   // 粉丝不存在或无上级
        }
        
        $commisTotal =  $order['order_amount'] * $order['commis_rate'] / 100;
        
        // 获取该粉丝的上级
        $parentsIds = $storeFansService->getParents($fans['fans_id'], 3);
        if (empty($parentsIds)) {
            return false;
        }

        $i = 1;
        $pdService = new PredepositService;
        $fansModel = new \Common\Model\FansModel();
        foreach ($parentsIds as $parentId) {
            // 获取上级粉丝信息
            $parentInfo = $fansModel->getFansInfo(['fans_id' => $parentId]);
            
            if ($setting['level'.$i.'_commis_type'] == 1) {
                $commisAmount = $commisTotal * $setting['level'.$i.'_commis_val'] / 100;
            } else {
                $commisAmount = 0;
            }
            M('order_commission')->add([
                'order_id' => $orderId,
                'store_id' => $storeId,
                'buyer_id' => $buyerId,
                'user_id'  => $parentInfo['user_id'],
                'fans_id'  => $parentId,
                'order_amount' => $order['order_amount'],
                'commis_amount' => $commisAmount,
                'level' => $i,
            ]);
            if ($commisAmount > 0) {
                $pdService->changePd('commission', [
                    'name' => $fans['fans_nickname'].'购买《'.$order['class_title'].'》',
                    'user_id' => $parentInfo['user_id'],
                    'amount' => $commisAmount,
                    'order_sn' => $order['order_sn'],
                ]);
                $storeService->sendMessage(
                    $storeId,
                    $parentInfo['fans_id'],
                    'user_buy_parents_gains',
                    [
                        'buyer_name' => $fans['fans_nickname'],
                        'amount'     => $commisAmount,
                        'class_name' => $order['class_title'],
                        'class_url'  => C('MEDIA_SITE_URL').'/class/'.$order['class_id'].'.html',
                    ]
                );
            }
            
            $i++;
        }
    }
}
