<?php
namespace Org\Util;

/**
 * 微信第三方平台
 */
class WechatPlatform extends \Org\Util\Wechat
{
    const INFOTYPE_AUTHORIZED = 'authorized';
    const INFOTYPE_UNAUTHORIZED = 'unauthorized';
    const INFOTYPE_VERIFY_TICKET = 'component_verify_ticket';
    const INFOTYPE_UPDATEAUTHORIZED = 'updateauthorized';
    const COMPONENT_AUTH_URL = '/component/api_component_token';
    const COMPONENT_PREAUTHCODE_URL = '/component/api_create_preauthcode';
    const COMPONENT_AUTHORIZE_URL = 'https://mp.weixin.qq.com/cgi-bin/componentloginpage';
    const COMPONENT_QUERY_AUTH_URL = '/component/api_query_auth';
    const COMPONENT_AUTHORIZER_TOKEN_URL = '/component/api_authorizer_token';
    const COMPONENT_AUTHORIZER_INFO = '/component/api_get_authorizer_info';
    const COMPONENT_OAUTH_TOKEN_URL = '/sns/oauth2/component/access_token';

    private $component_access_token;
    protected $account = array();

    public function __construct($storeIdOrAccount)
    {
        $setting = (new \Common\Model\SettingModel)->get('platform');
        $this->appid = $setting['appid'];
        $this->appsecret = $setting['appsecret'];
        $this->encodingAesKey = $setting['encodingaeskey'];
        $this->token = $setting['token'];
        
        if (!empty($storeIdOrAccount)) {
            if(is_array($storeIdOrAccount)) {
                $account = $storeIdOrAccount;
            } else {
                $account = M('store_wechat')->where(['store_id' => $storeIdOrAccount])->find();
            }

            $this->account = $account;
        }
    }

    public function getRevInfoType() {
        if (isset($this->_receive['InfoType'])) {
            return $this->_receive['InfoType'];
        } else {
            return false;
        }
    }

    public function getRevVerifyTicket()
    {
        $this->log($this->_receive);
        if (isset($this->_receive['ComponentVerifyTicket'])) {
            return $this->_receive['ComponentVerifyTicket'];
        } else {
            return false;
        }
    }

    public function getRevAuthorizerAppid()
    {
        if (isset($this->_receive['AuthorizerAppid'])) {
            return $this->_receive['AuthorizerAppid'];
        } else {
            return false; 
        }
    }

    public function getComponentAccesstoken()
    {
        $authname = 'component:access_token';
        if ($rs = $this->getCache($authname))  {
            $this->component_access_token = $rs;
            return $rs;
        }

        $ticket = $this->getCache('component:verify_ticket');
        if (empty($ticket)) {
            $this->errMsg = '缺少接入平台关键数据，等待微信开放平台推送数据，请十分钟后再试或是检查“授权事件接收URL”是否写错';
            return false;
        }
        
        $data = [
            'component_appid' => $this->appid,
            'component_appsecret' => $this->appsecret,
            'component_verify_ticket' => $ticket,
        ];
        $result = $this->http_post(self::API_URL_PREFIX.self::COMPONENT_AUTH_URL, self::json_encode($data));
        if ($result) {
            $json = json_decode($result,true);
            if (!$json || isset($json['errcode'])) {
                $this->errCode = $json['errcode'];
                $this->errMsg = $json['errmsg'];
                return false;
            }

            $this->component_access_token = $json['component_access_token'];
            $expire = $json['expires_in'] ? intval($json['expires_in'])-100 : 3600;
            $this->setCache($authname, $this->component_access_token, $expire);

            return $this->component_access_token;
        }
        return false;
    }


    public function getPreAuthCode()
    {
        if (!$this->component_access_token && !$this->getComponentAccesstoken()) {
            return false;
        }
        
        $data = array(
            'component_appid' => $this->appid
        );
        $result = $this->http_post(self::API_URL_PREFIX.self::COMPONENT_PREAUTHCODE_URL.'?component_access_token='.$this->component_access_token, self::json_encode($data));
        if ($result) {
            $json = json_decode($result,true); 
            if (!$json || !empty($json['errcode'])) {
                $this->errCode = $json['errcode'];
                $this->errMsg = $json['errmsg'];
                return false;
            }

            return $json['pre_auth_code'];
        }
        return false;
    }

    public function getAuthorizeRedirect($redirect = ''){
        $preauthcode = $this->getPreAuthCode();
        if (empty($preauthcode)) {
            $authurl = "javascript:alert('{$this->errMsg}');";
        } else {
            $authurl = self::COMPONENT_AUTHORIZE_URL.'?component_appid='.$this->appid.'&pre_auth_code='.$preauthcode.'&redirect_uri='.urlencode($redirect);
        }
        return $authurl;
    }

    public function queryAuth($authcode)
    {
        if (!$this->component_access_token && !$this->getComponentAccesstoken()) return false;
        $result = $this->http_post(self::API_URL_PREFIX.self::COMPONENT_QUERY_AUTH_URL.'?component_access_token='.$this->component_access_token, self::json_encode(['component_appid' => $this->appid, 'authorization_code' => $authcode])); 
        if($result) {
            $json = json_decode($result, true); 
            if (!$json || !empty($json['errcode'])) {
                $this->errCode = $json['errcode'];
                $this->errMsg = $json['errmsg'];
                return false;
            }
            return $json;
        }
        return false;
    }
    public function getAuthInfo($code)
    {
        return $this->queryAuth($code);
    }

    public function getAuthorizerInfo($authorizer_appid)
    {
        if (!$this->component_access_token && !$this->getComponentAccesstoken()) return false;
        $result = $this->http_post(self::API_URL_PREFIX.self::COMPONENT_AUTHORIZER_INFO.'?component_access_token='.$this->component_access_token, self::json_encode(['component_appid' => $this->appid, 'authorizer_appid' => $authorizer_appid]));

        if ($result) {
            $json = json_decode($result, true); 
            if (!$json || !empty($json['errcode'])) {
                $this->errCode = $json['errcode'];
                $this->errMsg = $json['errmsg'];
                return false;
            }
            return $json;
        }
        return false;
    }
    
    public function getAuthorizeRefreshToken($appid, $refresh_token)
    {
        if (!$this->component_access_token && !$this->getComponentAccesstoken()) return false;
        $result = $this->http_post(self::API_URL_PREFIX.self::COMPONENT_AUTHORIZER_TOKEN_URL.'?component_access_token='.$this->component_access_token, self::json_encode(['component_appid' => $this->appid, 'authorizer_appid' => $appid, 'authorizer_refresh_token' => $refresh_token]));
        if ($result) {
            $json = json_decode($result, true);
            if (!$json || !empty($json['errcode'])) {
                $this->errCode = $json['errcode'];
                $this->errMsg = $json['errmsg'];
                return false;
            }
            return $json;
        }
        return false;
    }


    public function getOauthRedirect($appid, $callback,$state='',$scope='snsapi_userinfo'){
        return self::OAUTH_PREFIX.self::OAUTH_AUTHORIZE_URL.'appid='.$appid.'&redirect_uri='.urlencode($callback).'&response_type=code&scope='.$scope.'&state='.$state.'&component_appid='.$this->appid.'#wechat_redirect';
    }


    public function getOauthAccessToken($appid){
        if (!$this->component_access_token && !$this->getComponentAccesstoken()) return false;
        $code = isset($_GET['code'])?$_GET['code']:'';
        if (!$code) return false;
        $result = $this->http_get(self::API_BASE_URL_PREFIX.self::COMPONENT_OAUTH_TOKEN_URL.'?appid='.$appid.'&code='.$code.'&grant_type=authorization_code&component_appid='.$this->appid.'&component_access_token='.$this->component_access_token);
        if ($result)
        {
            $json = json_decode($result,true);
            if (!$json || !empty($json['errcode'])) {
                $this->errCode = $json['errcode'];
                $this->errMsg = $json['errmsg'];
                return false;
            }
            $this->user_token = $json['access_token'];
            return $json;
        }
        return false;
    }
    
    private function getAuthRefreshToken() {
        
        $cachename = 'wechat:refresh_token:' . $this->account['appid'];
        if ($rs = $this->getCache($cachename)) {
            echo "cache refresh_token ".$rs;
            return $rs;
        }
        
        $auth_refresh_token = $this->account['refresh_token'];
        $this->setCache($cachename, $auth_refresh_token);
        echo "get refresh_token ".$auth_refresh_token;
        return $auth_refresh_token;
    }
    
    private function setAuthRefreshToken($token)
    {
        M('store_wechat')->where(['appid' => $this->account['appid']])->setField('refresh_token', $token);
        $this->setCache('wechat:refresh_token:' . $this->account['appid'], $token);
    }

    public function getAccessToken() {
        
        $cachename = 'wechat:access_token:'.$this->account['appid'];
        if ($rs = $this->getCache($cachename)) {
            $this->access_token = $rs;
            return $rs;
        }
        
        if (!$this->component_access_token && !$this->getComponentAccesstoken()) 
            return false;
        $refreshtoken = $this->getAuthRefreshToken();
        $data = array(
            'component_appid' => $this->appid,
            'authorizer_appid' => $this->account['appid'],
            'authorizer_refresh_token' => $refreshtoken,
        );
        $result = $this->http_post(self::API_URL_PREFIX.self::COMPONENT_AUTHORIZER_TOKEN_URL.'?component_access_token='.$this->component_access_token, self::json_encode($data));
        if ($result) {
            $json = json_decode($result, true);
            if (!$json || !empty($json['errcode'])) {
                $this->errCode = $json['errcode'];
                $this->errMsg = $json['errmsg'];
                return false;
            }
            
            if ($json['authorizer_refresh_token'] != $refreshtoken) {
                $this->setAuthRefreshToken($response['authorizer_refresh_token']);
            } else {
                echo 'refreshtoken 没变';
            }
            print_r($json);
            $this->access_token = $json['authorizer_access_token'];
            $expire = $json['expires_in'] ? intval($json['expires_in']) - 200 : 3600;
            $this->setCache($cachename, $json['authorizer_access_token'], $expire);
            
            return $json['authorizer_access_token'];
        }
        return false;
    }

    public function log($log) {
        if (is_array($log)) {
            \Think\Log::write(json_encode($log));
        } else {
            \Think\Log::write($log);
        }
    }
}

