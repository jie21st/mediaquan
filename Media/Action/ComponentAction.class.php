<?php
namespace Media\Action;

use \Org\Util\Component;

class ComponentAction extends CommonAction
{
    protected $needAuth = false;
    
    /**
     * 授权推送事件接收
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
                $update['access_token']     = $authorizationInfo['authorizer_access_token'];
                $update['refresh_token']    = $authorizationInfo['authorizer_refresh_token'];
                $update['token_expiretime'] = time() + intval($authorizationInfo['expires_in']) - 100;
                $update['update_time']      = time();
                $result = M('wechat')->where(['appid' => $appid])->save($update);
                if ($result === false) {
                    \Think\Log::write('微信第三方推送更新授权失败 appid='.$appid);
                }
                break;
            // 取消授权
            case Component::INFOTYPE_UNAUTHORIZED:
                // TODO 取消授权处理
                $authorizerAppid = $cp->getRevAuthorizerAppid();
                \Think\Log::write('微信第三方推送取消授权 appid='.$authorizerAppid);
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
        if (isset($_GET['store_id'])) {
            session('store_id', $_GET['store_id']);
        }
        if (! session('?store_id')) {
            exit('请指定store_id');
        }
        $cp = new \Org\Util\Component();
        if (isset($_GET['auth_code'])) {
            // auth callback
            $auth = $cp->queryAuth($_GET['auth_code']);
            if (!$auth) {
                exit('授权失败'.$cp->errMsg);
            }
                //dump($auth);
            $result = $cp->getAuthorizerInfo($auth['authorization_info']['authorizer_appid']);
            if (!$result) {
                exit('授权失败'.$cp->errMsg);
            }
                
            $authorizerInfo = $result['authorizer_info'];
            $authorizationInfo = $auth['authorization_info'];
            $appid = $authorizationInfo['authorizer_appid'];

            $model = M('wechat');
            $appInfo = $model->where(['appid' => $appid])->find();
            if ($appInfo && ($appInfo['store_id'] != session('store_id'))) {
                exit('该公众号已绑定其他店铺');
            }

            $funcInfo = array();
            foreach ($authorizationInfo['func_info'] as $func) {
                $funcInfo[] = $func['funcscope_category']['id'];
            }

            // 绑定
            $data = array();
            $data['store_id']           = session('store_id');
            $data['appid']              = $appid;
            $data['func_info']          = implode(',', $funcInfo);
            $data['mp_nickname']        = $authorizerInfo['nick_name'];
            $data['mp_headimg']         = $authorizerInfo['head_img'];
            $data['mp_wechatid']        = $authorizerInfo['alias'];
            $data['mp_username']        = $authorizerInfo['user_name'];
            $data['mp_service_type']    = $authorizerInfo['service_type_info']['id'];
            $data['mp_verify_type']     = $authorizerInfo['verify_type_info']['id'];
            $data['mp_qrcode']          = $authorizerInfo['qrcode_url'];
            $data['access_token']       = $authorizationInfo['authorizer_access_token'];
            $data['refresh_token']      = $authorizationInfo['authorizer_refresh_token'];
            $data['token_expiretime']   = time() + intval($authorizationInfo['expires_in']) - 100;

            if ($appInfo) {
                $data['update_time'] = time();
                $update = $model->where(['rec_id' => $appInfo['rec_id']])->save($data);
            } else {
                $data['create_time'] = time();
                $data['update_time'] = $data['create_time'];
                $update = $model->add($data);
            }
            
            exit('授权成功');
        } else {
            $authcode = $cp->getPreAuthCode();
            $url = $cp->getAuthorizeRedirect(C('MEDIA_SITE_URL').'/component/auth', $authcode);
            redirect($url);
        }
    }
}

