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
        $wechatModel = M('store_wechat');
        $appInfo = $wechatModel->where(['store_id' => session('current_store_id')])->find();
        if (empty($appInfo)) {
            exit('app not exists');
        }
        $component = new \Org\Util\Component;
        if (isset($_GET['state'])) {
            $oauth = $component->getOauthAccessToken($appInfo['appid']);
            if ($oauth) {
                if ($appInfo['mp_type'] == 4) {
                    $fansModel = new \Common\Model\FansModel();
                    $fansInfo = $fansModel->where(['openid' => $oauth['openid']])->find();
                    if ($fansInfo) {
                        $fansModel->where(['openid' => $oauth['openid']])->setField('user_id', session('user_id'));
                        session('store_fans_'.session('current_store_id'), $fansInfo['fans_id']);
                    } else {
                        $weObj = new \Org\Util\Wechat();
                        $userinfo = $weObj->getOauthUserinfo($appInfo['access_token'], $oauth['openid']);
                        dump($userinfo);
                        session('store_fans_'.session('current_store_id'), -1);
                    }
                }
                redirect(cookie('returnUrl'));
            } else {
                echo '系统错误';
            }
        } else {
            cookie('returnUrl', $_GET['returnUrl']);
            $state = md5(uniqid(rand(), TRUE));
            session('state', $state);
            $loginUrl = $component->getOauthRedirect($appInfo['appid'], C('MEDIA_SITE_URL').'/login/bindStoreUser', $state, 'snsapi_base');
            redirect($loginUrl);
        }
    }
    
    public function logoutOp()
    {
        session('[destroy]');
    }
}
