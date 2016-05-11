<?php
namespace Common\Service;

use \Common\Service\ApiService;

/**
 * 用户服务类
 */
class UserService
{
    private $userLevel = array(
        0 => '普通会员',
        1 => '实战会员',
        2 => 'VIP会员',
        3 => '私塾塾友',
        99 => '内部会员',
        100 => '测试会员',
    );

    /**
     * 获取用户详细信息
     *
     * @param mixed $uid
     * @access public
     * @return void
     */
    public function getUserInfo($uid) {
        static $cache = [];
        if (! isset($cache[$uid])) {
            $cache[$uid] = $this->getUserBaseInfo($uid);
        }
        return $cache[$uid];
    }

    /**
     * 获取用户基本信息 
     * 
     * @param mixed $userId 
     * @access public
     * @return void
     */
    public function getUserBaseInfo($userId)
    {
        $model = new Common\Model\UserModel;
        $field = 'user_id,user_nickname,user_truename,user_sex,user_mobile,user_wx,user_avatar';
        $data = $model->getUserInfo(['user_id' => $userId], $field);
        return $data;
    }

    /**
     * 获取用户全部信息 
     * 
     * @param mixed $userId 
     * @access public
     * @return void
     */
    public function getUserFullInfo($userId)
    {
        $model = D('User');
        return $model->getUserInfo(['user_id' => $userId], '*');
    }

    /**
     * 更新用户信息和缓存 
     * 
     * @param mixed $data 
     * @param mixed $userId 
     * @access public
     * @return boolean
     */
    public function updateUserInfo($data, $userId)
    {
        // 更新用户数据表
        $userModel = new \Common\Model\UserModel();
        $result = $userModel->editUser($data, ['user_id' => $userId]);
        if ($result) {
            // 更新用户缓存信息
            $this->updateUserInfoCache($userId);
            return true;
        }
        return ($result === false) ? false : true;
    }
    
    /**
     * 更新用户缓存信息
     * 
     * @param mixed $uid 
     * @access public
     * @return void
     */
    public function updateUserInfoCache($uid)
    {
        return;
    }

    public function getUserDetail($userId)
    {
        $model = D('User');
        return $model->getUserByUid($userId);
    }

    /**
     * 用户会员等级描述 
     * 
     * @param int $level 
     * @access public
     * @return void
     */
    public function userLevelDesc($level = 0) {
        $model = new \Common\Model\UserModel();
        $info = $model->getUserLevelInfo(['level_id' => $level], 'level_name');
        return $info['level_name'];
    }

    /**
     * 获取用户徽章 
     *
     * @todo 目前通过接口判断是否有那叫什么玩意的徽章
     * 
     * @access public
     * @return void
     */
    public function getUserMedal($userId) {
        $medalModel = D('Medal');
        $apiService = new ApiService;
        // 去获取徽章列表，接口那边是检查有没有那个徽章
        $result = $apiService->getUserMedal($userId);
        $medalList = array();
        if ($result) {
            $userMedalIds = array(1);
            $medalList = $medalModel->getMedalList([
                'medalid' => array('in', $userMedalIds)
            ]);
        }
        return $medalList;
    }
    
    /**
     * 会员变更
     * 
     * @param type $userId
     */
    public function changeVip($data = array())
    {   
        $userModel = new \Common\Model\UserModel;
        $tempMsgService = new \Common\Service\TemplateMessageService;
        $userInfo = $userModel->getUserInfo(['ID' => $data['user_id']], '*');
        
        $update = array();
        $update['vip_change_time'] = date('Y-m-d H:i:s');
        
        // 非首次开通
        if (empty($userInfo['vip_first_start_time'])) {
            // 首次开通时间
            $update['vip_first_start_time'] = date('Y-m-d H:i:s');
            // 会员分销来源
            $distributorModel = new \Common\Model\DistributorModel();
            $distributor = $distributorModel->getDistributorInfo(['user_id' => $data['user_id']]);
            if (!empty($distributor)) {
                if (intval($distributor['parent_user_id'])) {
                    $sellerId = $distributor['parent_user_id'];
                } else {
                    $sellerId = '';
                }
            } else {
                $sellerId = $data['seller_id'];
            }
            $update['vip_from_seller'] = $sellerId;
        }
        
        if ($userInfo['level'] == $data['level_id']) {
            // 续费
            $expireTimestamp = $userInfo['vip_expire_date'] ? strtotime($userInfo['vip_expire_date']) : time();
            $update['vip_expire_date'] = date('Y-m-d', strtotime('+1 year', $expireTimestamp));
            
            $result = $userModel->editUser($update, ['ID' => $data['user_id']]);
            if (! $result) {
                throw new \Exception('更新用户失败');
            }
            // 通知
            $tempMsgService->vipRenewSuccessNotify($data['user_id'], [
                'first' => '您的会员已经续费成功',
                'level_name' => $this->userLevelDesc($userInfo['level']),
                'expire_time' => $update['vip_expire_date'],
                'remark' => '点击“详情”进入会员中心',
                'url' => 'http://bzt.guanlizhihui.com/courses/mypaper.jsp?state=1',
            ]);
            $tempMsgService->notify('500001001001001', null, 3, [
                'first' => sprintf(
                        "%s(ID: %s, 昵称:%s,微信号:%s, 手机号:%s)",
                        $userInfo['user_name'],
                        $userInfo['user_id'],
                        $userInfo['nick_name'],
                        $userInfo['wechat_id'],
                        $userInfo['mobile']
                ),
                'first_color' => "#ff0000",
                'level_name' => $this->userLevelDesc($userInfo['level']),
                'expire_time' => $update['vip_expire_date']
            ]);
        } else {
            // 开通
            $expireTimestamp = strtotime('+1 year');
            $update['Level'] = $data['level_id'];
            $update['vip_start_date'] = date('Y-m-d');
            $update['vip_expire_date'] = date('Y-m-d', $expireTimestamp);
            
            $result = $userModel->editUser($update, ['ID' => $data['user_id']]);
            if (! $result) {
                \Think\Log::write('开通会员: 更新用户信息失败,用户ID:'.$data['user_id']);
                throw new \Exception('更新用户失败');
            }
            
            // 记录等级变更日志
            $data_log = array();
            $data_log['OpenID'] = $userInfo['openid'];
            $data_log['UserID'] = $data['user_id'];
            $data_log['OldLevel'] = $userInfo['level'];
            $data_log['NowLevel'] = $update['Level'];
            $data_log['Origin'] = 0;
            $data_log['OrderID'] = $data['order_id'];
            $data_log['CreateTime'] = date('Y-m-d H:i:s');
            $insert = (new \Think\Model)->table('wechat_level_log')->add($data_log);
            if (! $insert) {
                \Think\Log::write('开通会员: 记录变更日志失败,用户ID:'.$data['user_id']);
                throw new \Exception('记录变更日志失败');
            }
        
            // 微信消息模板通知
            $tempMsgService->vipLevelChangeNotify($data['user_id'], [
                'first' => '您的等级已变更',
                'old_level_desc' => $this->userLevelDesc($userInfo['level']),
                'now_level_desc' => $this->userLevelDesc($update['Level']),
                'change_time' => date('Y年m月d日 H:i'),
                'remark' => '添加微信号baozibzt为好友，加入VIP会员专属群，每天与包老师零距离互动',
                'url' => 'http://bzt.guanlizhihui.com/courses/mypaper.jsp?state=1',
            ]);
            $tempMsgService->notify('500001001001001', '', 1, [
                'memberId'      => $userInfo['user_id'],
                'memberName'    => $userInfo['user_name'],
                'memberNickName'=> $userInfo['nick_name'],
                'wechatId'      => $userInfo['wechat_id'],
                'mobile'        => $userInfo['mobile'],
                'levelDesc'     => $this->userLevelDesc($update['Level']),
                'startTime'     => date('Y年m月d日 H:i'),
                'expireTime'    => date('Y年m月d日', $expireTimestamp),
                'payAmount'     => $data['order_amount'],
            ]);
        }
        
        return true;
    }

    public function getUserLevelArr()
    {
        return $this->userLevel;
    }
}