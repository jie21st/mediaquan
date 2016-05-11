<?php
namespace Media\Action;

use Think\Log;

class LoginAction extends CommonAction
{
    public function indexOp()
    {
        $wechat = new \Org\Util\Wechat;
        cookie('returnUrl', $_GET['returnUrl']);
        // 生成唯一随机串防CSRF攻击
        $state = md5(uniqid(rand(), TRUE));
        session('state', $state);
        // 构造请求参数列表
        $login_url = $wechat->getOauthRedirect(C('MEDIA_SITE_URL') . '/login/callback', $state);
        echo $login_url;exit();
        redirect($login_url);
    }
    
    public function callbackOp()
    {
        $wechat = new \Org\Util\Wechat;
        // 验证state防止CSRF攻击
        if(session('state') != I('get.state', '')) {
            Log::write('回调随机串不一致, 回调:'. I('get.state') . 'SESSION:' . session('state'), 'ALERT');
            exit('The state does not match. You may be a victim of CSRF.');
        }
        $result = $wechat->getOauthAccessToken();
        if (false === $result) {
            Log::write('未获取到回调数据', 'ALERT');
            exit('AuthToken error');
        }

        // 查询本地用户
        $userModel = new \Common\Model\UserModel;
        $userInfo = $userModel->getUserInfo(['user_wechatopenid' => $result['openid']]);
        if (empty($userInfo)) {
            Log::write('用户信息不存在, 注册用户', 'INFO');
            // 注册用户
            $authUserInfo = $wechat->getOauthUserinfo($result['access_token'], $result['openid']);
            
            Log::write('用户信息: '. json_encode($authUserInfo), 'INFO', true);
            // 判断是否关注
//            $wechatUserInfo = $wechat->getUserInfo($result['openid']);
            //$isSubscribe = ($wechatUserInfo && $wechatUserInfo['subscribe'] != 0) ? 1 : 0;
            
            // 写入数据
            $userInfo['user_wechatopenid']     = $authUserInfo['openid'];
            $userInfo['user_nickname']         = remove_emoji($authUserInfo['nickname']);
            $userInfo['user_sex']              = $authUserInfo['sex'];
            $userInfo['user_wechatinfo']       = serialize($authUserInfo);
            // 地区信息
//            if ($authUserInfo['province'] != '' && $authUserInfo['city'] != '') {
//                $region = array(
//                    'province' => getRegionName(strtolower($authUserInfo['province'])),
//                    'city'     => getRegionName(strtolower($authUserInfo['city']))
//                );
//                $insert['ZipCode'] = implode('/', $region);
//            }
            // 用户头像
            $avatarName = uniqid();
            $avatarSavePath = DIR_UPLOAD . DS . ATTACH_AVATAR;
            $avatarPath = downloadFiles($authUserInfo['headimgurl'], $avatarName, $avatarSavePath, 'jpg');
            if ($avatarPath) {
                $userInfo['user_avatar'] = $avatarName . '.jpg';
            }
            
            $userId = $userModel->addUser($userInfo);
            if (! $userId) {
                Log::write('注册失败'.$userModel->_sql(), 'ALERT', true);
                exit('登录失败');
            }
            $userInfo['user_id'] = $userId;
            Log::write('新注册用户, id为'. $userId, 'INFO', true);
        } else {
            $userId = $userInfo['user_id'];
            Log::write('已存在用户, id为'. $userId, 'INFO', true);
        }
        
        // 登录
        $accountService = new \Media\Service\AccountService();
        $accountService->createSession($userInfo);
        redirect(cookie('returnUrl'));
    }
}
