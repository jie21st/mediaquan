<?php
namespace Media\Service;

/**
 * 账号服务类 
 * 
 * @package 
 * @author Wang Jie <wangj@guanlizhihui.com> 2015-09-22 
 */
class AccountService
{
    /**
     * 是否已登录
     * 
     * @access public
     * @return void
     */
    public function isLogin()
    {
        
    }

    public function createSession($userInfo)
    {
        if (empty($userInfo) || !is_array($userInfo) || !isset($userInfo['user_id'])) {
            return;
        }
        
        session('is_login', '1');
        session('user_id', $userInfo['user_id']);
        session('nickname', $userInfo['user_nickname']);
        session('truename', $userInfo['user_truename']);
        session('avatar', $userInfo['user_avatar']);
        if (trim($userInfo['user_wechatopenid'])) {
            session('openid', $userInfo['user_wechatopenid']);
        }
        //登录时间更新
        if (! empty($userInfo['user_login_time'])) {
            $update = array(
                'user_login_num'=> ($userInfo['user_login_num'] + 1),
                'user_login_time'=> time(),
                'user_old_login_time'=> $userInfo['user_login_time'],
            );
            $userModel = new \Common\Model\UserModel;
            $userModel->editUser($update, $userInfo['user_id']);
        }
    }
}
