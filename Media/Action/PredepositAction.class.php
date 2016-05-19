<?php
namespace Media\Action;

class PredepositAction extends CommonAction
{
    const CASH_MAX_LIMIT = 20000;  // 单笔单日限额
    const CASH_MIN_LIMIT = 1;  // 最小限额
    const CASH_NUM_LIMIT = 10; // 单日限次数
    
    protected $needAuth = true;
    
    /**
     * 我的资产
     */
    public function indexOp()
    {
        $userId = session('user_id');
        $userService = new \Common\Service\UserService;
        
        // 查询用户信息
        $userInfo = $userService->getUserFullInfo($userId);
        $userInfo['total_predeposit'] = $userInfo['available_predeposit'] + $userInfo['freeze_predeposit'];
        
        // 查询所有收支明细
        $pdModel = new \Common\Model\PredepositModel;
        $condition = array();
        $condition['lg_user_id'] = $userId;
        $condition['lg_av_amount'] = ['neq', 0];
        $field = 'lg_id,lg_user_id,lg_name,lg_av_amount,lg_create_time';
        $logList = $pdModel->getPdLogList($condition, $field, 'lg_create_time desc');
        
        $this->assign('pdlog_list', $logList);
        $this->assign('user_info', $userInfo);
        $this->display();
    }
    
    /**
     * 提现
     */
    public function cashOp()
    {
        $userService = new \Common\Service\UserService;
        $pdService = new \Common\Service\PredepositService;
        
        // 查询用户信息
        $userInfo = $userService->getUserFullInfo(session('user_id'));
        // 取今日已提现总额
        $totalAmount = $pdService->getTodayCashTotalAmount(session('user_id'));
        
        if (IS_POST) {
            $pdModel = D('Predeposit');
            
            $pdcAmount = abs(floatval($_POST['pdc_amount']));
            $pdcUserName = I('post.pdc_user_name', '', 'trim');
            if ($pdcUserName === '') {
                exit('请输入真实姓名');
            }
            if ($pdcAmount < self::CASH_MIN_LIMIT){
                exit('提现金额不能小于最低限额');
            }
            if ($pdcAmount > self::CASH_MAX_LIMIT) {
                exit('提现金额不能大于最高限额');
            }
            $count = $pdService->getTodayCashCount(session('user_id'));
            if ($count >= self::CASH_NUM_LIMIT) {
                exit('今日提现次数已达上限');
            }
            
            $totalAmount = $pdService->getTodayCashTotalAmount(session('user_id'));
            if ((floatval($totalAmount) + $pdcAmount) > self::CASH_MAX_LIMIT) {
                exit('今日提现金额已达上限');
            }
            //验证金额是否足够
            if (floatval($userInfo['available_predeposit']) < $pdcAmount){
                exit('可用余额不足');
            }
            
            try {
                $pdModel->startTrans();
                
                // 生成提现单
                $pdcSn = $pdService->makeSn();
                $cash_data = array();
                $cash_data['pdc_sn'] = $pdcSn;
                $cash_data['pdc_user_id'] = session('user_id');
                $cash_data['pdc_user_name'] = $pdcUserName;
                $cash_data['pdc_amount'] = $pdcAmount;
                $cash_data['pdc_create_time'] = time();
                $cash_data['pdc_payment_state'] = 1;
                $pdcId = $pdModel->addPdCash($cash_data);
                if (! $pdcId) {
                    throw new \Exception('提现申请添加失败');
                }
                $cash_data['pdc_id'] = $pdcId;
                
                // 扣除预存款
                $data = array();
                $data['user_id'] = session('user_id');
                $data['amount'] = $pdcAmount;
                $data['order_sn'] = $pdcSn;
                $pdService->changePd('cash', $data);
                
                // 支付提现
                $pdService->cashPay($cash_data);
                
                // 提交事务
                $pdModel->commit();
                
                // 消息通知
                $wechatService = new \Common\Service\WechatService();
                $wechatService->sendCustomMessage([
                    'touser' => session('openid'),
                    'msgtype' => 'text',
                    'text' => [
                        'content' => '提现成功，提现金额'.glzh_price_format($pdcAmount)
                    ]
                ]);
                
                $this->returnJson(1, 'SUCCESS');
            } catch (\Exception $e) {
                $pdModel->rollback();
                \Think\Log::write('提现失败: '.$e->getMessage());
                $this->returnJson(0, '提现失败');
            }
        } else {
            // 可提现金额    最大上限 - 已申请提现总额
            $avCashAmount = self::CASH_MAX_LIMIT - floatval($totalAmount);
            $this->assign('amount_limit', min([$avCashAmount, floatval($userInfo['available_predeposit'])]));
            $this->assign('user_info', $userInfo);
            $this->display();
        }
    }
    
    /**
     * 提现成功
     */
    public function cash_okOp()
    {
        $this->display('cash.success');
    }
}

