<?php
namespace Media\Action;

use \Org\Util\Component;

class ComponentAction extends CommonAction
{
    protected $needAuth = false;
    
    /**
     * 授权推送事件接收
     * 
     * @TODO 删除缓存的token
     */
    public function receiveOp()
    {
        $cp = new Component();
        $cp->valid();
        $type = $cp->getRev()->getRevInfoType();
        switch($type) {
            case Component::INFOTYPE_VERIFY_TICKET:
                $ticket = $cp->getRev()->getRevVerifyTicket();
                $redis = \Think\Cache::getInstance('redis');
                $redis->set('component:verify_ticket', $ticket);
                exit('success');
                break;
            // 授权
            case Component::INFOTYPE_AUTHORIZED:
                // TODO 授权处理
                break;
            // 更新授权
            case Component::INFOTYPE_UPDATEAUTHORIZED:
                $data = $cp->getRevData();
                $appid = $data['AuthorizerAppid'];
                $auth = $cp->queryAuth($data['AuthorizationCode']);
                $authorizationInfo = $auth['authorization_info'];
                // 更新权限
                $funcInfo = array();
                foreach ($authorizationInfo['func_info'] as $func) {
                    $funcInfo[] = $func['funcscope_category']['id'];
                }
                
                $update = array();
                $update['func_info']        = implode(',', $funcInfo);
                $update['refresh_token']    = $authorizationInfo['authorizer_refresh_token'];
                $update['update_time']      = time();
                $result = M('store_wechat')->where(['appid' => $appid])->save($update);
                if ($result === false) {
                    \Think\Log::write('微信第三方推送更新授权失败 appid='.$appid);
                }
                break;
            // 取消授权
            case Component::INFOTYPE_UNAUTHORIZED:
                // TODO 取消授权处理
                $data = $cp->getRevData();
                $appid = $data['AuthorizerAppid'];
                
                M('store_wechat')->where(['appid' => $appid])->setField('auth_state', 0);
                \Think\Log::write('微信第三方推送取消授权 appid='.$appid);
                break;
            default:
                \Think\Log::write('微信第三方推送类型未定义'.$type);
        }
    }

    /**
     * 公众号授权
     * @throws \Exception
     */
    public function authOp()
    {
        $wechatPlatform = new \Org\Util\WechatPlatform();
        if (empty($_GET['auth_code'])) {
            showMessage('授权登录失败，请重试');
        }
        $auth_info = $wechatPlatform->getAuthInfo($_GET['auth_code']);
        $auth_refresh_token = $auth_info['authorization_info']['authorizer_refresh_token'];
        $auth_appid = $auth_info['authorization_info']['authorizer_appid'];

	$accountInfo = $wechatPlatform->getAuthorizerInfo($auth_appid);
	if (!$accountInfo) {
            showMessage('授权登录新建公众号失败，请重试');
	}
            
        if ($accountInfo['authorizer_info']['service_type_info']['id'] == '0' || $accountInfo['authorizer_info']['service_type_info']['id'] == '1') {
            // 订阅号
            if ($accountInfo['authorizer_info']['verify_type_info']['id'] > '-1') {
                    $type = '2';   // 认证的订阅号
            } else {
                    $type = '1';   // 未认证的订阅号
            }
        } elseif ($accountInfo['authorizer_info']['service_type_info']['id'] == '2') {
            // 服务号
            if ($accountInfo['authorizer_info']['verify_type_info']['id'] > '-1') {
                    $type = '4';   // 认证的服务号
            } else {
                    $type = '3';   // 未认证的服务号
            }
        }

        $model = M('store_wechat');

        // 取得授权app绑定的店铺公众号信息
        $appInfo = $model->where(['appid' => $auth_appid])->find();
        if ($appInfo && ($appInfo['store_id'] != session('store_id'))) {
            showMessage('该公众号已绑定其他店铺');
        }

        // 取得当前店铺的公众号信息  公众号一致性校验
        $currentStoreWechatInfo = $model->where(['store_id' => session('store_id')])->find();
        if ($currentStoreWechatInfo && $currentStoreWechatInfo['appid'] != $auth_appid) {
            showMessage('店铺已绑定'.$currentStoreWechatInfo['mp_username'].'的公众号，无法绑定其他公众号');
        }

        $funcInfo = array();
        foreach ($accountInfo['authorization_info']['func_info'] as $func) {
            $funcInfo[] = $func['funcscope_category']['id'];
        }

        // 绑定
        $data = array();
        $data['store_id']           = session('store_id');
        $data['appid']              = $auth_appid;
        $data['func_info']          = implode(',', $funcInfo);
        $data['mp_nickname']        = $accountInfo['authorizer_info']['nick_name'];
        $data['mp_headimg']         = $accountInfo['authorizer_info']['head_img'];
        $data['mp_wechatid']        = $accountInfo['authorizer_info']['alias'];
        $data['mp_username']        = $accountInfo['authorizer_info']['user_name'];
        $data['mp_type']            = $type;
        $data['mp_service_type']    = $accountInfo['authorizer_info']['service_type_info']['id'];
        $data['mp_verify_type']     = $accountInfo['authorizer_info']['verify_type_info']['id'];
        $data['mp_qrcode']          = $accountInfo['authorizer_info']['qrcode_url'];
        $data['auth_state']         = 1;
        $data['refresh_token']      = $auth_refresh_token;

        if ($appInfo) {
            $data['update_time'] = time();
            $update = $model->save($data);
        } else {
            $data['create_time'] = time();
            $data['update_time'] = $data['create_time'];
            $update = $model->add($data);
        }

        $storeModel = new \Common\Model\StoreModel();
        $storeModel->where(['store_id' => session('store_id')])->setField('if_bind_wechat', 1);
        exit('授权成功');
    }
    
    /**
     * 模拟店铺管理后台授权
     */
    public function bindOp()
    {
        if (isset($_GET['store_id'])) {
            session('store_id', $_GET['store_id']);
        }
        if (!session('?store_id')) {
            exit('请指定store_id');
        }
        $wechatPlatform = new \Org\Util\WechatPlatform();
        $authurl = $wechatPlatform->getAuthorizeRedirect(C('MEDIA_SITE_URL').'/component/auth');
        echo '<a style="margin-left:5px;" href="' . $authurl . '"><img src="https://open.weixin.qq.com/zh_CN/htmledition/res/assets/res-design-download/icon_button3_2.png"></a>';
    }
}

