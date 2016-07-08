<?php
namespace Media\Action;

class LoginAction extends \Think\Action
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
        redirect($login_url);
    }
    
    public function callbackOp()
    {
        $wechat = new \Org\Util\Wechat;
        // 验证state防止CSRF攻击
        if(session('state') != I('get.state', '')) {
            exit('The state does not match. You may be a victim of CSRF.');
        }
        $result = $wechat->getOauthAccessToken();
        if (false === $result) {
            exit('AuthToken error');
        }

        // 查询本地用户
        $userModel = new \Common\Model\UserModel;
        $userInfo = $userModel->getUserInfo(['user_wechatopenid' => $result['openid']]);
        if (empty($userInfo)) {
            // 注册用户
            $authUserInfo = $wechat->getOauthUserinfo($result['access_token'], $result['openid']);
            // 判断是否关注
            $wechatUserInfo = $wechat->getUserInfo($result['openid']);
            $isSubscribe = ($wechatUserInfo && $wechatUserInfo['subscribe'] != 0) ? 1 : 0;
            $authUserInfo['nickname'] = remove_emoji($authUserInfo['nickname']);
            // 写入数据
            $userInfo = array();
            $userInfo['user_wechatopenid']     = $authUserInfo['openid'];
            $userInfo['user_nickname']         = $authUserInfo['nickname'];
            $userInfo['user_sex']              = $authUserInfo['sex'];
            $userInfo['user_wechatinfo']       = serialize($authUserInfo);
            $userInfo['subscribe_state']       = $isSubscribe;
            
            // 取的默认推荐人
//            $parents = C('USER_DEFAULT_PARENT');
//            if (is_array($parents) && !empty($parents)) {
//                shuffle($parents);
//                $userInfo['parent_id'] = end($parents);
//            }
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
                exit('注册失败');
            }
            $userInfo['user_id'] = $userId;
        } else {
            $userId = $userInfo['user_id'];
        }
        
        // 登录
        $accountService = new \Media\Service\AccountService();
        $accountService->createSession($userInfo);
        redirect(cookie('returnUrl'));
    }
    
    public function bindStoreUserOp(){
        
        $storeId = I('get.store_id');
        $code = I('get.code');
        $scope = I('get.scope');
        
        if (session('state') !=  I('get.state') || empty($code)) {
            exit('通信错误，请在微信中重新发起请求');
        }
        
        $wechatModel = M('store_wechat');
        $appInfo = $wechatModel->where(['store_id' => $storeId])->find();
        if (empty($appInfo)) {
            exit('app not exists');
        }
        $wechatPlatform = new \Org\Util\WechatPlatform();
        $oauth = $wechatPlatform->getOauthAccessToken($appInfo['appid']);
        if ($oauth) {
            if ($appInfo['mp_type'] == 4) {
                $fansModel = new \Common\Model\FansModel();
                $fansInfo = $fansModel->where(['openid' => $oauth['openid']])->find();
                if ($fansInfo) {
                    // 已存在粉丝
                    session('openid', $oauth['openid']);
                    $fansModel->where(['openid' => $oauth['openid']])->setField('user_id', session('user_id'));
                    session('store_fans_'.$storeId, $fansInfo['fans_id']);
                } else {
                    $weObj = new \Org\Util\WechatPlatform($appInfo);
                    $userinfo = $weObj->getUserInfo($oauth['openid']);
                    if($userinfo && !empty($userinfo) && !empty($userinfo['subscribe'])) {
                        if (!empty($userinfo['headimgurl'])) {
                                $userinfo['headimgurl'] = rtrim($userinfo['headimgurl'], '0') . 132;
                        }
                        
                        $insert = array(
                                'openid' => $userinfo['openid'],
                                'user_id' => session('user_id'),
                                'store_id' => $appInfo['store_id'],
                                'fans_nickname' => $userinfo['nickname'],
                                'fans_sex' => $userinfo['sex'],
                                'fans_avatar' => $userinfo['headimgurl'],
                                'fans_province' => $userinfo['province'],
                                'fans_city' => $userinfo['city'],
                                'fans_remark' => $userinfo['remark'],
                                'subscribe_state' => $userinfo['subscribe'],
                                'subscribe_time' => $userinfo['subscribe_time'],
                                'unsubscribe_time' => 0,
                                'fans_info' => serialize($userinfo),
                        );

                        $fansId = $fansModel->add($insert);
                        session('store_fans_'.session('current_store_id'), $fansId);
                    } else {
                        session('store_fans_'.session('current_store_id'), -1);
                    }
                }
            }
        }
        
        // 跳转
        $forward = urldecode(session('_dest_url'));
        $forward = str_exists($forward, 'store_id=') ? $forward : "{$forward}&i={$storeId}";
        redirect($forward . '&wxref=mp.weixin.qq.com#wechat_redirect');
    }
    
    public function logoutOp()
    {
        session('[destroy]');
    }
}
