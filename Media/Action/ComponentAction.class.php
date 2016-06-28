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
                $authorizerAppid = $data['AuthorizerAppid'];
                $authorizationCode = $data['AuthorizationCode'];
                $authorizationCodeExpiredTime = $data['AuthorizationCodeExpiredTime'];

                $key = 'component:authorization:'.$authorizerAppid;
                $redis = \Think\Cache::getInstance('redis');
                $redis->set($key, $authorizationCode, $authorizationCodeExpiredTime-time());
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
            if ($auth) {
                //dump($auth);
                $result = $cp->getAuthorizerInfo($auth['authorization_info']['authorizer_appid']);
                if ($result) {
                    $authorizerInfo = $result['authorizer_info'];
                    $authorizationInfo = $auth['authorization_info'];
                    $appid = $authorizationInfo['authorizer_appid'];
                    $model = M('wechat');
//                    try {
//                        $model->startTrans();
//                        // 保存token
//                        $tokenModel = M('wechat_token');
//                        $result = $tokenModel->add([
//                            'app_id' => $appid,
//                            'access_token' => $authorizationInfo['authorizer_access_token'],
//                            'refresh_token' => $authorizationInfo['authorizer_refresh_token'],
//                            'expire_time' => time() + intval($authorizationInfo['expires_in']) - 100
//                        ]);
//                        if (! $result) {
//                            throw new \Exception('token保存失败');
//                        }
                        // 绑定
                        $data = array();
                        $data['store_id']           = session('store_id');
                        $data['appid']              = $appid;
                        $data['mp_nickname']        = $authorizerInfo['nick_name'];
                        $data['mp_headimg']         = $authorizerInfo['head_img'];
                        $data['mp_wechatid']        = $authorizerInfo['alias'];
                        $data['mp_username']        = $authorizerInfo['user_name'];
                        $data['mp_service_type']    = $authorizerInfo['service_type_info']['id'];
                        $data['mp_verify_type']     = $authorizerInfo['verify_type_info']['id'];
                        $data['mp_qrcode']          = $authorizerInfo['qrcode_url'];
                        $data['access_token']       = $authorizationInfo['authorizer_access_token'];
                        $data['refresh_token']      = $authorizationInfo['authorizer_refresh_token'];
                        $data['expire_time']        = time() + intval($authorizationInfo['expires_in']) - 100;
                        $update = $model->add($data);
                        
//                        $model->commit();
//                    } catch (\Exception $e) {
//                        $model->rollback();
//                        exit('授权失败:'.$e->getMessage());
//                    }
                    
                    exit('授权成功');
                } else {
                    exit('授权失败'.$cp->errMsg);
                }
            } else {
                exit('授权失败'.$cp->errMsg);
            }
        } else {
            $authcode = $cp->getPreAuthCode();
            $url = $cp->getAuthorizeRedirect(C('MEDIA_SITE_URL').'/component/auth', $authcode);
            redirect($url);
        }
    }
}

