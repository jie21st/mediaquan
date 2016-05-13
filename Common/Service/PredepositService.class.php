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
                $data_log['lg_trade_no'] = $data['order_sn'];
                $data_log['lg_desc'] = '下单，支付预存款，订单号: '.$data['order_sn'];
                $data_pd['available_predeposit'] = array('exp','available_predeposit-'.$data['amount']);
                break;
            case 'reward':
                $data_log['lg_name'] = $data['name'] ? : '打赏';
                $data_log['lg_av_amount'] = -$data['amount'];
                $data_log['lg_trade_no'] = $data['order_sn'];
                $data_log['lg_desc'] = '打赏，打赏单号: '.$data['order_sn'];
                $data_pd['available_predeposit'] = array('exp','available_predeposit-'.$data['amount']);
                break;
            case 'reward_income':
                $data_log['lg_name'] = $data['name'] ? : '打赏收益';
                $data_log['lg_av_amount'] = $data['amount'];
                $data_log['lg_trade_no'] = $data['order_sn'];
                $data_log['lg_desc'] = '打赏收益，打赏单号: '.$data['order_sn'];
                $data_pd['available_predeposit'] = array('exp','available_predeposit+'.$data['amount']);
                break;
//            case 'order_freeze':
//                $data_log['lg_av_amount'] = -$data['amount'];
//                $data_log['lg_freeze_amount'] = $data['amount'];
//                $data_log['lg_desc'] = '下单，冻结预存款，订单号: '.$data['order_sn'];
//                $data_pd['freeze_predeposit'] = array('exp','freeze_predeposit+'.$data['amount']);
//                $data_pd['available_predeposit'] = array('exp','available_predeposit-'.$data['amount']);
//                break;
//            case 'order_cancel':
//                $data_log['lg_av_amount'] = $data['amount'];
//                $data_log['lg_freeze_amount'] = -$data['amount'];
//                $data_log['lg_desc'] = '取消订单，解冻预存款，订单号: '.$data['order_sn'];
//                $data_pd['freeze_predeposit'] = array('exp','freeze_predeposit-'.$data['amount']);
//                $data_pd['available_predeposit'] = array('exp','available_predeposit+'.$data['amount']);
//                break;
//            case 'order_comb_pay':
//                $data_log['lg_freeze_amount'] = -$data['amount'];
//                $data_log['lg_desc'] = '下单，支付被冻结的预存款，订单号: '.$data['order_sn'];
//                $data_pd['freeze_predeposit'] = array('exp','freeze_predeposit-'.$data['amount']);
//                break;
            case 'recharge':
                $data_log['lg_name'] = '充值';
                $data_log['lg_av_amount'] = $data['amount'];
                $data_log['lg_trade_no'] = $data['pdr_sn'];
                $data_log['lg_desc'] = '充值，充值单号: '.$data['pdr_sn'];
                //$data_log['lg_admin_name'] = $data['admin_name'];
                $data_pd['available_predeposit'] = array('exp','available_predeposit+'.$data['amount']);
                break;
            case 'sale_income':
                $data_log['lg_name'] = $data['name'];
                $data_log['lg_av_amount'] = $data['amount'];
                $data_log['lg_desc'] = '分销收益，订单号: '.$data['order_sn'];
                $data_log['lg_trade_no'] = $data['order_sn'];
                $data_pd['available_predeposit'] = array('exp','available_predeposit+'.$data['amount']);
                break;
//            case 'refund':
//                $data_log['lg_av_amount'] = $data['amount'];
//                $data_log['lg_desc'] = '确认退款，订单号: '.$data['order_sn'];
//                $data_pd['available_predeposit'] = array('exp','available_predeposit+'.$data['amount']);
//                break;
            case 'cash_apply':
                $data_log['lg_name'] = '提现';
                $data_log['lg_av_amount'] = -$data['amount'];
                $data_log['lg_freeze_amount'] = $data['amount'];
                $data_log['lg_desc'] = '申请提现，冻结预存款，提现单号: '.$data['order_sn'];
                $data_log['lg_trade_no'] = $data['order_sn'];
                $data_pd['available_predeposit'] = array('exp','available_predeposit-'.$data['amount']);
                $data_pd['freeze_predeposit'] = array('exp','freeze_predeposit+'.$data['amount']);
                break;
    	    case 'cash_pay':
                $data_log['lg_name'] = '提现成功';
    	        $data_log['lg_freeze_amount'] = -$data['amount'];
    	        $data_log['lg_desc'] = '提现成功，提现单号: '.$data['order_sn'];
                $data_log['lg_trade_no'] = $data['order_sn'];
    	        //$data_log['lg_admin_name'] = $data['admin_name'];
    	        $data_pd['freeze_predeposit'] = array('exp','freeze_predeposit-'.$data['amount']);
    	        break;
            case 'cash_fail':
                $data_log['lg_name'] = '提现退回';
                $data_log['lg_av_amount'] = $data['amount'];
                $data_log['lg_freeze_amount'] = -$data['amount'];
                $data_log['lg_desc'] = '提现失败，解冻预存款，提现单号: '.$data['order_sn'];
                $data_log['lg_trade_no'] = $data['order_sn'];
                $data_pd['available_predeposit'] = array('exp','available_predeposit+'.$data['amount']);
                $data_pd['freeze_predeposit'] = array('exp','freeze_predeposit-'.$data['amount']);
                break;
            case 'cash_del':
                $data_log['lg_name'] = '提现取消';
                $data_log['lg_av_amount'] = $data['amount'];
                $data_log['lg_freeze_amount'] = -$data['amount'];
                $data_log['lg_trade_no'] = $data['order_sn'];
                $data_log['lg_desc'] = '取消提现申请，解冻预存款，提现单号: '.$data['order_sn'];
                $data_pd['available_predeposit'] = array('exp','available_predeposit+'.$data['amount']);
                $data_pd['freeze_predeposit'] = array('exp','freeze_predeposit-'.$data['amount']);
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
}

