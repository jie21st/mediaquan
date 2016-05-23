<?php
namespace Common\Service;

/**
 * 课程服务类 
 * 
 * @package 
 * @version $id$
 * @copyright 1997-2005 The PHP Group
 * @author Wang Jie <wangj@guanlizhihui.com> 2015-11-06 
 * @license PHP Version 3.0 {@link http://www.php.net/license/3_0.txt}
 */
class ClassService
{
    /**
     * 微信模板消息url字段格式内容
     */
    CONST WECHAT_NOTIFY_URL_FORMAT = '/courses/list?class_id=%d';
    
    /**
     * 微信模板消息remark字段格式内容
     */
    CONST WECHAT_NOTIFY_REMARK_FORMAT = '您已成功购买《%s》课程，点击下方“详情”收听全部课程内容，进入班级群可以和更多同学一起学习交流哦~~';
    
    /**
     * 查看课程报名用户列表链接
     */
    CONST CLASS_APPLY_USERS_URL_FORMAT = '/class/list?id=%d';
    
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
        $classInfo = $classModel->getClassInfo(['class_id' => $post['id']]);
        
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
            'class_teacher' => $input['class_teacher'],
            'order_amount'  => $input['class_price'],
            'expire_time'   => (time() + C('ORDER_EXPIRE')),
            'payment_code'  => '',
            'order_state'   => $input['_is_free'] ? ORDER_STATE_PAY : ORDER_STATE_NEW,
//            'from_seller'   => $input['_is_free'] ? 0 : I('post.dcp', 0, 'intval'),
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
        
        // 购买通知
        $buyerInfo = $userModel->getUserInfo(['user_id' => $classOrder['buyer_id']]);
        $wechatService = new \Common\Service\WechatService;
        $wechatService->sendCustomMessage([
            'touser' => $buyerInfo['user_wechatopenid'],
            'msgtype' => 'text',
            'text' => [
                'content' => '课程《'.$classOrder['class_title'].'》报名成功'
            ]
        ]);
    }
    
    /**
     * 获取毕业证编号
     * 
     * @param type $classUserInfo  课程报名信息
     */
    public function getCertificateNo($classUserInfo)
    {
        return sprintf('%07d', $classUserInfo['rec_id']);
    }

    /**
     * 根据条件取得课程报名用户列表 
     * 
     * @param mixed $condition 
     * @param string $order 
     * @param mixed $limit 
     * @access public
     * @return void
     */
    public function getClassUserList($condition, $order = '', $limit)
    {
        $classUserModel = M('glzhClassUser'); 
        $list = $classUserModel->where($condition)->order($order)->limit($limit)->select();
        if (empty($list)) {
            return null;
        }

        $classModel     = D('Class');
        $resellerModel  = new \Common\Model\ResellerModel;
        $userModel      = new \Common\Model\UserModel;
        $userService    = D('User', 'Service');
        
        $userIds = array();  // 报名用户编号
        $orderIds = array();  // 订单编号
        $groupIds = array();  // 班级编号
        $resellerIds = array(); // 分销商编号
        $userKeys = array();
        $areaIds = array();
        foreach($list as $key => $classUser) {
            $userId = $classUser['user_id'];
            $userIds[]  = $userId;
            $orderIds[] = $classUser['order_id'];
            $groupIds[] = $classUser['group_id'];
            if ($classUser['reseller_id']) {
                $resellerIds[] = $classUser['reseller_id'];
            }
            if ($classUser['address']) {
                $areaIds[] = $classUser['address'];
            }
            $userKeys[$userId] = $key; 
        }
        // 用户信息
        $condition = array();
        $condition['ID'] = array('in', $userIds);
        $field = 'id,clientname,nickname,sex,age,mobile,email,degree,headurl,company,place,level,area_info';
        $userList = $userModel->getUserList($condition, $field);
        
        if (! empty($userList)) {
            foreach ($userList as $user) {
                $userId = $user['user_id'];
                $key = $userKeys[$userId];
                $user['level_desc'] = $userService->userLevelDesc($user['level']);
                $list[$key]['user_info'] = $user;
            }
        }
        // 订单信息
        $condition = array();
        $condition['order_id'] = array('in', $orderIds);
        $orderList = $classModel->getOrderList($condition, 'order_id,buyer_id,order_amount,pd_amount,payment_code');
        $orderArr = array();
        if (! empty($orderList)) {
            foreach ($orderList as $order) {
                $orderArr[$order['order_id']] = $order;
            }
        }
        // 班级信息
        $condition = array();
        $groupList = $classModel->getClassGroupList(['group_id' => array('in', array_unique($groupIds))], 'group_id,group_code,group_name');
        if (! empty($groupList)) {
            $groupArr = array();
            foreach ($groupList as $group) {
                $groupArr[$group['group_id']] = $group;
            }
        }
        // 分销商信息
        $resellerArr = array();
        $governResellerIdsArr = array();
        if (count($resellerIds) > 0) {
            $resellerList = $resellerModel->getResellerList(['reseller_id' => array('in', array_unique($resellerIds))]);
            foreach ($resellerList as $reseller) {
                if ($reseller['reseller_govern'] == 1) {
                    $governResellerIdsArr[] = $reseller['reseller_id'];
                }
                $resellerArr[$reseller['reseller_id']] = $reseller;
            }
        }
        
        // 分销商班级
        if (count($governResellerIdsArr) > 0) {
            $resellerGroupList = M('glzh_reseller_class_group')->where(['reseller_id' => ['in', array_unique($governResellerIdsArr)]])->select();
        }
        
        foreach ($list as $key => $classUser) {
            $list[$key]['order_info'] = $orderArr[$classUser['order_id']];
            $list[$key]['group_info'] = $groupArr[$classUser['group_id']];
            $list[$key]['reseller_info'] = isset($resellerArr[$classUser['reseller_id']]) ? $resellerArr[$classUser['reseller_id']] : null;
            if (isset($resellerArr[$classUser['reseller_id']])) {
                
            }
        }
        
        return $list;
    }

    /**
     * 导出课程excel 
     * 
     * @param mixed $list 
     * @access public
     * @return void
     */
    public function createExcel($list)
    {
        $data = array();
        foreach ($list as $key => $item) {
            $data[$key][] = $item['user_info']['user_id'];
            $data[$key][] = $item['user_info']['level_desc'];
            $data[$key][] = $item['user_info']['user_name'];
            $data[$key][] = isset($item['user_info']['nick_name']) ? $item['user_info']['nick_name'] : '';
            $data[$key][] = $item['user_info']['mobile'];
            $data[$key][] = $item['user_info']['wechat_id'];
            $data[$key][] = $item['user_info']['job'];
            $data[$key][] = $item['user_info']['company_name'];
            $data[$key][] = $item['user_info']['area_info'];
            $data[$key][] = $item['group_info']['group_name'];
            $data[$key][] = date('Y-m-d H:i:s', $item['apply_time']);
            $data[$key][] = $item['order_info']['order_amount'];
            $data[$key][] = orderPaymentName($item['order_info']['payment_code']);
            $data[$key][] = $item['order_info']['pd_amount'];
            $data[$key][] = $item['reseller_info']['reseller_name'];
        }
        
        Vendor('PHPExcel.PHPExcel');
        $PHPExcel = new \PHPExcel();

        // 设置标题
        $PHPExcel->getActiveSheet()->setCellValue('A1','用户编号');
        $PHPExcel->getActiveSheet()->setCellValue('B1','会员等级');
        $PHPExcel->getActiveSheet()->setCellValue('C1','姓名');
        $PHPExcel->getActiveSheet()->setCellValue('D1','昵称');
        $PHPExcel->getActiveSheet()->setCellValue('E1','手机号');
        $PHPExcel->getActiveSheet()->setCellValue('F1','微信');
        $PHPExcel->getActiveSheet()->setCellValue('G1','职位');
        $PHPExcel->getActiveSheet()->setCellValue('H1','公司');
        $PHPExcel->getActiveSheet()->setCellValue('I1','地区');
        $PHPExcel->getActiveSheet()->setCellValue('J1','班级');
        $PHPExcel->getActiveSheet()->setCellValue('K1','报名时间');
        $PHPExcel->getActiveSheet()->setCellValue('L1','报名费用');
        $PHPExcel->getActiveSheet()->setCellValue('M1','支付方式');
        $PHPExcel->getActiveSheet()->setCellValue('N1','包子币支付金额');
        $PHPExcel->getActiveSheet()->setCellValue('O1','分销商');

        $PHPExcel->getActiveSheet()->FreezePane('A2');
        $PHPExcel->getDefaultStyle()->getFont()->setName('微软雅黑');    //默认字体
        $PHPExcel->getDefaultStyle()->getFont()->setSize(12);        //默认字体大小
        $PHPExcel->getActiveSheet()->getDefaultRowDimension()->setRowHeight(20); //行高

        $PHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(12);
        $PHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(14);
        $PHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(25);
        $PHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(16);
        $PHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth(18);
        $PHPExcel->getActiveSheet()->getColumnDimension('G')->setWidth(18);
        $PHPExcel->getActiveSheet()->getColumnDimension('H')->setWidth(34);
        $PHPExcel->getActiveSheet()->getColumnDimension('I')->setWidth(22);
        $PHPExcel->getActiveSheet()->getColumnDimension('J')->setWidth(20);
        $PHPExcel->getActiveSheet()->getColumnDimension('K')->setWidth(20);
        $PHPExcel->getActiveSheet()->getColumnDimension('N')->setWidth(20);

        $PHPExcel->getActiveSheet()->getStyle('A1')->getFont()->setBold(true);//字体加粗
        $PHPExcel->getActiveSheet()->getStyle('B1')->getFont()->setBold(true);//字体加粗
        $PHPExcel->getActiveSheet()->getStyle('C1')->getFont()->setBold(true);//字体加粗
        $PHPExcel->getActiveSheet()->getStyle('D1')->getFont()->setBold(true);//字体加粗
        $PHPExcel->getActiveSheet()->getStyle('E1')->getFont()->setBold(true);//字体加粗
        $PHPExcel->getActiveSheet()->getStyle('F1')->getFont()->setBold(true);//字体加粗
        $PHPExcel->getActiveSheet()->getStyle('G1')->getFont()->setBold(true);//字体加粗
        $PHPExcel->getActiveSheet()->getStyle('H1')->getFont()->setBold(true);//字体加粗
        $PHPExcel->getActiveSheet()->getStyle('I1')->getFont()->setBold(true);//字体加粗
        $PHPExcel->getActiveSheet()->getStyle('J1')->getFont()->setBold(true);//字体加粗
        $PHPExcel->getActiveSheet()->getStyle('K1')->getFont()->setBold(true);//字体加粗
        $PHPExcel->getActiveSheet()->getStyle('L1')->getFont()->setBold(true);//字体加粗
        $PHPExcel->getActiveSheet()->getStyle('M1')->getFont()->setBold(true);//字体加粗
        $PHPExcel->getActiveSheet()->getStyle('N1')->getFont()->setBold(true);//字体加粗
        $PHPExcel->getActiveSheet()->getStyle('O1')->getFont()->setBold(true);//字体加粗

        $PHPExcel->getActiveSheet()->getStyle('A')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
        $PHPExcel->getActiveSheet()->getStyle('E')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
        $PHPExcel->getActiveSheet()->getStyle('F')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
        $PHPExcel->getActiveSheet()->getStyle('L')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
        $PHPExcel->getActiveSheet()->getStyle('E')->getNumberFormat()->setFormatCode(\PHPExcel_Style_NumberFormat::FORMAT_TEXT);

        $letter = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z');
        $i = 2;
        foreach ($data as $key => $value) {
            $j = 0;
            foreach ($value as $k => $val) {
                $index = $letter[$j]."$i";
                $PHPExcel->setActiveSheetIndex()->setCellValue($index, $val);
                $j++;
            }
            $i++;
        }

        $writer = new \PHPExcel_Writer_Excel5($PHPExcel);
        $filename = $_GET['class_title'].'报名用户列表'.$_GET['curpage'].'-'.date('Y-m-d-H').'.xls';
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="'.$filename.'"');
        header('Cache-Control: max-age=0');
        $writer->save('php://output');
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
                                        '您的粉丝 %s 购买了 %s 课程，获得收益%s元，<a href="%s">查看账单明细</a>',
                                        $buyerInfo['user_nickname'],
                                        $orderInfo['class_title'],
                                        glzh_price_format($commisAmount),
                                        C('MEDIA_SITE_URL').'/predeposit/'
                                    )
                    ]
                ]);
            }
        }
    }
    
    private function getUserParents($userId, $level = 3) {
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
