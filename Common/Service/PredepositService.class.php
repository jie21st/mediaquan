<?php
namespace Common\Service;

class PredepositService
{
    /**
     * 生成充值编号
     * 
     * @return string
     */
    public function makeSn()
    {
       return '9' . date('y') . date('md') 
              . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT)
              . sprintf('%03d', (int) session('user.user_id') % 1000);
    }
    
    /**
     * 变更预存款
     * 
     * @param string $change_type
     * @param array $data
     * @throws Exception
     * @return unknown
     */
    public function changePd($change_type,$data = array(), $ctime = '') {
        $data_log = array();
        $data_pd = array();
        $data_log['lg_user_id'] = $data['user_id'];
        $data_log['lg_create_time'] = ($ctime === '') ? time() : $ctime;
        $data_log['lg_type'] = $change_type;
        switch ($change_type){
            case 'order_pay':
                $data_log['lg_name'] = $data['name'];
                $data_log['lg_av_amount'] = -$data['amount'];
                $data_log['lg_desc'] = '下单，支付预存款，订单号: '.$data['order_sn'];
                $data_pd['available_predeposit'] = array('exp','available_predeposit-'.$data['amount']);
                break;
            case 'recharge':
                $data_log['lg_name'] = '充值';
                $data_log['lg_av_amount'] = $data['amount'];
                $data_log['lg_desc'] = '充值，充值单号: '.$data['pdr_sn'];
                $data_pd['available_predeposit'] = array('exp','available_predeposit+'.$data['amount']);
                break;
            case 'sale_income':
                $data_log['lg_name'] = $data['name'];
                $data_log['lg_av_amount'] = $data['amount'];
                $data_log['lg_desc'] = '分销收益，订单号: '.$data['order_sn'];
                $data_pd['available_predeposit'] = array('exp','available_predeposit+'.$data['amount']);
                break;
    	    case 'cash':
                $data_log['lg_name'] = '提现';
    	        $data_log['lg_av_amount'] = -$data['amount'];
    	        $data_log['lg_desc'] = '提现，提现单号: '.$data['order_sn'];
    	        $data_pd['available_predeposit'] = array('exp','available_predeposit-'.$data['amount']);
    	        break;
            default:
                throw new \Exception('参数错误');
        }
        $update = (new \Think\Model)->table('__USERS__')->where(array('user_id'=>$data['user_id']))->save($data_pd);
        if (!$update) {
            throw new \Exception('操作失败');
        }
        $insert = (new \Think\Model)->table('__PD_LOG__')->add($data_log);
        if (!$insert) {
            throw new \Exception('操作失败');
        }
        return $insert;
    }
    
    /**
     * 获取用户今日提现次数
     * 
     * @param type $userId  用户id
     */
    public function getTodayCashCount($userId)
    {
        $pdModel = new \Common\Model\PredepositModel;
        $starttime = strtotime(date('Y-m-d'));
        $endtime = strtotime(date('Y-m-d 23:59:59'));
        $condition =array();
        $condition['pdc_user_id'] = $userId;
        $condition['pdc_create_time'] = [['egt', $starttime], ['elt', $endtime]];
        return $pdModel->getPdCashCount($condition);
    }
    
    /**
     * 获取今日提现总额
     * 
     * @param type $userId
     */
    public function getTodayCashTotalAmount($userId)
    {
        $pdModel = new \Common\Model\PredepositModel;
        $starttime = strtotime(date('Y-m-d'));
        $endtime = strtotime(date('Y-m-d 23:59:59'));
        $condition =array();
        $condition['pdc_user_id'] = $userId;
        $condition['pdc_create_time'] = [['egt', $starttime], ['elt', $endtime]];
        return $pdModel->getPdCashSum($condition);
    }
    
    public function getTodayIncomeTotalAmount($userId)
    {
        $pdModel = new \Common\Model\PredepositModel;
        $starttime = strtotime(date('Y-m-d'));
        $endtime = strtotime(date('Y-m-d 23:59:59'));
        
        $condition =array();
        $condition['lg_type'] = 'sale_income';
        $condition['lg_user_id'] = $userId;
        $condition['lg_create_time'] = [['egt', $starttime], ['elt', $endtime]];
        
        return $pdModel->getPdLogAmountSum($condition, 'lg_av_amount');
    }
    
    public function getIncomeTotalsAmount($userId)
    {
        $pdModel = new \Common\Model\PredepositModel;
        $condition =array();
        $condition['lg_type'] = 'sale_income';
        $condition['lg_user_id'] = $userId;
        
        return $pdModel->getPdLogAmountSum($condition, 'lg_av_amount');
    }
    
    /**
     * 提现支付
     * 
     * @param type $cashInfo
     * @throws \Exception
     */
    public function cashPay($cashInfo)
    {
        vendor("Payment.Wxpay.WxPayPubHelper");
        
        $pdModel = new \Common\Model\PredepositModel();
        $userModel = new \Common\Model\UserModel();
        
        /* 取得用户信息 */
        $userInfo = $userModel->getUserInfo(['user_id' => $cashInfo['pdc_user_id']]);
        
        $partner_trade_no = (string) $cashInfo['pdc_sn'];
        /* 企业付款金额，以分为单位 */
        $amount = floatval($cashInfo['pdc_amount']) * 100;
        
        $mchPay = new \Mch_pay_pub();
        $mchPay->setParameter("openid", $userInfo['user_wechatopenid']);
        $mchPay->setParameter("partner_trade_no", $partner_trade_no);
        $mchPay->setParameter("check_name", "OPTION_CHECK");
        $mchPay->setParameter("re_user_name", $cashInfo['pdc_user_name']);
        $mchPay->setParameter("amount", "{$amount}");
        $mchPay->setParameter("desc", "提现");
        $result = $mchPay->request();
        
        // 记录结果日志
        \Think\Log::write('提现支付返回结果: '.  json_encode($result), \Think\Log::INFO);

        if ($result['return_code'] === 'SUCCESS' && $result['result_code'] == 'SUCCESS') {
            // 更改提现为支付状态
            $update = $pdModel->editPdCash(['pdc_trade_no' => $result['payment_no']], ['pdc_sn' => $cashInfo['pdc_sn']]);
            if (!$update) {
                \Think\Log::write('提现: 提现支付成功但更新tradeno失败, tradeno='. $result['payment_no']);
            }
            return true;
        } else {
            throw new \Exception('提现支付失败: '.$result['err_code']);
        }
    }  
}

