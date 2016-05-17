<?php
namespace Crontab\Action;

class PredepositAction extends \Think\Action
{
    /**
     * 初始化对象
     */
    public function __construct(){
        parent::__construct();

        register_shutdown_function(array($this,"shutdown"));
    }
    
    /**
     * 提现
     */
    public function cashPayOp()
    {
        $pdModel = new \Common\Model\PredepositModel();
        
        $condition = array();
        $condition['pdc_payment_state'] = 0;
        $unixtime = strtotime(date('Y-m-d')." -1 day"); //1天前一秒时unix时间戳
        //$condition['pdc_create_time'] = array('elt', $unixtime);
        
        $cashCount = $pdModel->getPdCashCount($condition);
        $page = ceil($cashCount / 100);
        // 分配支付，每批100个
        for ($i = 1; $i <= $page; $i++) {
            $cashList = $pdModel->getPdCashList($condition, $i, '', 'pdc_create_time asc', 100);
            print_r($cashList);
            if ($cashList) {
                $tempArr = array(); // 由于微信给统一用户操作频繁限制，采取同一用户每次只处理一条提现记录
                foreach ($cashList as $key => $cashInfo) {
                    if (! in_array($cashInfo['pdc_user_id'], $tempArr)) {
                        $tempArr[] = $cashInfo['pdc_user_id'];
                        $this->pay($cashInfo);
                    }
                }
            }
        }
    }
    
    /**
     * 提现支付
     * 
     * @param type $cashInfo
     * @throws \Exception
     */
    private function pay($cashInfo)
    {
        vendor("Payment.Wxpay.WxPayPubHelper");
        
        $pdModel = new \Common\Model\PredepositModel();
        $pdService = new \Common\Service\PredepositService();
        $userService = new \Common\Service\UserService();
        
        /* 取得用户信息 */
        $userInfo = $userService->getUserDetail($cashInfo['pdc_user_id']);
        
        $partner_trade_no = (string) $cashInfo['pdc_sn'];
        /* 企业付款金额，以分为单位 */
        $amount = floatval($cashInfo['pdc_amount']) * 100;
        
        $mchPay = new \Mch_pay_pub();
        $mchPay->setParameter("openid", $userInfo['openid']);
        $mchPay->setParameter("partner_trade_no", $partner_trade_no);
        $mchPay->setParameter("check_name", "OPTION_CHECK");
        $mchPay->setParameter("re_user_name", $cashInfo['pdc_user_name']);
        $mchPay->setParameter("amount", "{$amount}");
        $mchPay->setParameter("desc", "提现");
        $result = $mchPay->request();
        
        // 记录结果日志
        \Think\Log::write('提现支付返回结果: '.  json_encode($result), \Think\Log::INFO);

        $condition = array();
        $condition['pdc_id'] = $cashInfo['pdc_id'];
        $condition['pdc_payment_state'] = 0;
        if ($result['return_code'] === 'SUCCESS' && $result['result_code'] == 'SUCCESS') {
            
            $update = array();
            $update['pdc_payment_state'] = 1;
            $update['pdc_payment_time'] = time();
            $update['pdc_trade_no'] = $result['payment_no'];
            try {
                $pdModel->startTrans();
                
                // 更改提现为支付状态
                $res = $pdModel->editPdCash($update, $condition);
                if (! $res) {
                    throw new \Exception('更新提现记录失败: 提现单号:'.$cashInfo['pdc_sn']);
                }
                
                // 扣除冻结的预存款
                $data = array();
                $data['user_id'] = $userInfo['user_id'];
                $data['amount'] = $cashInfo['pdc_amount'];
                $data['order_sn'] = $cashInfo['pdc_sn'];
                $pdService->changePd('cash_pay', $data);
                
                $pdModel->commit();
            } catch (\Exception $e) {
                $pdModel->rollback();
                echo "\n修改提现记录失败: " . $e->getMessage();
            }
            
            // 微信消息模板通知
//            $tempMsgService = new \Common\Service\TemplateMessageService;
//            $tempMsgService->cashSuccessNotify($userInfo['user_id'], [
//                'amount' => $cashInfo['pdc_amount'],
//                'date' => date('Y-m-d H:i:s'),
//            ]);
        } else {
            // 付款失败
            try {
                $pdModel->startTrans();
                
                $update = array();
                $update['pdc_payment_state'] = 2;
                $update['pdc_fail_reason'] = $result['err_code'];
            
                $res = $pdModel->editPdCash($update, $condition);
                if (! $res) {
                    throw new \Exception('更新提现记录失败: 提现单号:'.$cashInfo['pdc_sn']);
                }
                // 退还冻结的预存款
                $data = array();
                $data['user_id'] = $userInfo['user_id'];
                $data['amount'] = $cashInfo['pdc_amount'];
                $data['order_sn'] = $cashInfo['pdc_sn'];
                $pdService->changePd('cash_fail', $data);
                
                $pdModel->commit();
            } catch (\Exception $e) {
                $pdModel->rollback();
                echo "\n取消提现失败: " . $e->getMessage();
            }
            
            // 微信消息模板通知
//            $tempMsgService = new \Common\Service\TemplateMessageService;
//            $tempMsgService->cashFailNotify($userInfo['user_id'], [
//                'amount' => $cashInfo['pdc_amount'],
//                'date' => date('Y-m-d H:i:s'),
//                'reason' => $this->codeDesc($result['err_code']),
//            ]);
        }
    }
    
    /**
     * 提现支付错误码文字输出
     * 
     * @param type $code
     */
    public function codeDesc($code)
    {
        $desc = '';
        switch ($code) {
            case 'NAME_MISMATCH':
                $desc = '真实姓名不一致';
                break;
            case 'AMOUNT_LIMIT':
                $desc = '提现金额小于最低限额';
                break;
            case 'SENDNUM_LIMIT':
                $desc = '提现次数已达上限';
                break;
            case 'NOAUTH':
            case 'PARAM_ERROR':
            case 'OPENID_ERROR':
            case 'NOTENOUGH':
            case 'SYSTEMERROR':
            case 'SIGN_ERROR':
            case 'XML_ERROR':
            case 'FATAL_ERROR':
            case 'CA_ERROR':
                $desc = '系统错误，请重试！';
                break;
            default:
                $desc = '未知';
                break;
        }
        return $desc;
    }
    
    public function shutdown()
    {
        exit("\n". date('Y-m-d H:i:s') . "\tsuccess");
    }
}
