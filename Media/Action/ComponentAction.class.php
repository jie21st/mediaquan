<?php
namespace Media\Action;

class ComponentAction extends CommonAction
{
    protected $needAuth = false;

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
                    // 保存令牌
                    $redis = \Think\Cache::getInstance('redis');
                    $cachekey = 'authorizer:'.$appid;
                    $expire = $authorizationInfo['expires_in'] ? intval($authorizationInfo['expires_in']-100) : 6000;
                    $redis->set($cachekey, [
                        'access_token' => $authorizationInfo['authorizer_access_token'],
                        'refresh_token' => $authorizationInfo['authorizer_refresh_token']
                    ], $expire);

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

                    $model = M('wechat');
                    $update = $model->add($data);
                    if (false === $update) {
                        exit('授权失败'.$model->_sql());
                    } else {
                        exit('授权成功'); 
                    }
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

