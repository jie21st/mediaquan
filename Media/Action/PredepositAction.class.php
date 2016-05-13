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
        $pdService = new \Common\Service\PredepositService;
        
        // 查询用户信息
        $userInfo = $userService->getUserFullInfo($userId);
        
        // 取今日已提现总额
        $totalAmount = $pdService->getTodayCashTotalAmount($userId);
        
        // 可提现金额    最大上限 - 已申请提现总额
        $avCashAmount = self::CASH_MAX_LIMIT - floatval($totalAmount);
        
        $this->assign('amount_limit', min([$avCashAmount, floatval($userInfo['available_predeposit'])]));
        $this->assign('user_info', $userInfo);
        $this->display();
    }
    
    /**
     * 充值
     */
    /*
    public function rechargeOp()
    {
        if (IS_POST) {
            $pdrAmount = abs(floatval(I('post.amount')));
            if ($pdrAmount <= 0) {
                showMessage('充值金额不能小于或等于0');
            }

            $pdModel = new \Common\Model\PredepositModel();
            $pdService = new \Common\Service\PredepositService();
            $data = array();
            $data['pdr_sn'] = $paySn = $pdService->makeSn();
            $data['pdr_user_id'] = session('user.user_id');
            $data['pdr_amount'] = $pdrAmount;
            $data['pdr_create_time'] = NOW_TIME;
            $insert = $pdModel->addPdRecharge($data);
            if ($insert) {
                // 转向到支付页面
                redirect(C('APP_SITE_URL').'/buy/pd_pay?pay_sn='.$paySn);
            } else {
                showMessage('创建充值订单失败');
            }
        }
        
        // 重定向链接，充值完成后跳转
        if(empty($_GET['redirect_url'])) {
            cookie('_redirectUrl_', getReferer());
        } else {
            cookie('_redirectUrl_', $_GET['redirect_url']);
        }
        
        // 查询用户信息
        $userService = new \Common\Service\UserService;
        $userInfo = $userService->getUserFullInfo($this->user['user_id']);

        $this->assign('user_info', $userInfo);
        $this->display();
    }
    */
    
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
            $count = $pdService->getTodayCashCount($this->user['user_id']);
            if ($count >= self::CASH_NUM_LIMIT) {
                exit('今日提现次数已达上限');
            }
            
            $totalAmount = $pdService->getTodayCashTotalAmount($this->user['user_id']);
            if ((floatval($totalAmount) + $pdcAmount) > self::CASH_MAX_LIMIT) {
                exit('今日提现金额已达上限');
            }
            //验证金额是否足够
            if (floatval($userInfo['available_predeposit']) < $pdcAmount){
                exit('可用余额不足');
            }
            
            try {
                $pdModel->startTrans();
                $pdcSn = $pdService->makeSn();
                
                $data = array();
                $data['pdc_sn'] = $pdcSn;
                $data['pdc_user_id'] = $this->user['user_id'];
                $data['pdc_user_name'] = $pdcUserName;
                $data['pdc_amount'] = $pdcAmount;
                $data['pdc_create_time'] = time();
                $data['pdc_payment_state'] = 0;
                $insert = $pdModel->addPdCash($data);
                if (! $insert) {
                    throw new \Exception('提现申请添加失败');
                }
                //冻结可用预存款
                $data = array();
                $data['user_id'] = $this->user['user_id'];
                $data['amount'] = $pdcAmount;
                $data['order_sn'] = $pdcSn;
                $pdService->changePd('cash_apply',$data);
                $pdModel->commit();
                
                // 内部消息通知
                $tplmsgService = new \Common\Service\TemplateMessageService;
                $tplmsgService->notify('500001005010010', '', 3, [
                    'name' => $userInfo['user_name'],
                    'amount' => glzh_price_format($pdcAmount),
                    'time' => date('Y-m-d H:i:s'),
                ]);
                
                // 显示成功页面
                $this->display('cashsuccess');
            } catch (\Exception $e) {
                $pdModel->rollback();
                exit($e->getMessage());
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
        $this->display('cashsuccess');
    }
    
    /**
     * 资产
     */
    public function assetsOp()
    {
        $userId = session('user.user_id');
        // 查询账户余额
        $userService = new \Common\Service\UserService;
        $userInfo = $userService->getUserFullInfo($userId);
        $this->assign('available_predeposit', $userInfo['available_predeposit']);
        
        // 查询今日收益
        $pdService = new \Common\Service\PredepositService();
        $todayIncomeTotals = $pdService->getTodayIncomeTotalAmount($userId);
        $this->assign('income_today', $todayIncomeTotals);
        
        // 查询总收益
        $incomeTotals = $pdService->getIncomeTotalsAmount($userId);
        $this->assign('income_total', $incomeTotals);
        
        // 查询所有收支明细
        $pdModel = new \Common\Model\PredepositModel;
        $condition = array();
        $condition['lg_user_id'] = $userId;
        $condition['lg_av_amount'] = ['neq', 0];
//        $condition['lg_type'] = ['in', ['order_pay', 'recharge', 'sale_income', 'cash_apply', 'cash_fail', 'cash_del']];
        $field = 'lg_id,lg_user_id,lg_name,lg_av_amount,lg_create_time';
        $logList = $pdModel->getPdLogList($condition, $field, 'lg_create_time desc');
        $this->assign('pdlog_list', $logList);
        
        $this->display();
    }
    
    /**
     * 收支明细
     */
    public function recordOp()
    {
        $flow = I('get.flow');
        if (empty($flow) || ! in_array($flow, ['out', 'in'])) {
            $flow = $_GET['flow'] = 'in';
        }
        
        $condition = array();
        $condition['lg_user_id'] = session('user.user_id');
        switch ($flow) {
            case 'in':
//                $condition['lg_type'] = ['in', ['recharge', 'sale_income', 'cash_fail']];
                $condition['lg_av_amount'] = ['gt', 0];
                break;
            case 'out':
//                $condition['lg_type'] = ['in', ['order_pay', 'cash_apply']];
                $condition['lg_av_amount'] = ['lt', 0];
                break;
        }
        
        // 查询所有收支明细
        $pdModel = new \Common\Model\PredepositModel;
        $field = 'lg_id,lg_user_id,lg_name,lg_av_amount,lg_create_time';
        $logList = $pdModel->getPdLogList($condition, $field, 'lg_create_time desc');
        $this->assign('pdlog_list', $logList);
        $this->display();
        
    }
}

