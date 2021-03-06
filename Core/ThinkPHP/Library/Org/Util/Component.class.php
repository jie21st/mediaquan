<?php
namespace Org\Util;

class Component extends \Org\Util\Wechat
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

    public function __construct($account = array())
    {
        $this->appid = 'wx1445ced338f34e05';
        $this->appsecret = 'c8cc91a726b126145f3ef65acbf01de9';
        $this->encodingAesKey = 'NJqKhReOkr5JchPCCzVZVFWrlgPRrMT6VxL4Dbs0wbF';
        $this->token = '86a8c273c5a0110d49a0dd7c724ac3fc';
        
        if($account) {
            $this->account = $account;
            $this->account['store_appid'] = $account['appid'];
        }
//        parent::__construct([
//            'appid' => 'wx1445ced338f34e05',
//            'appsecret' => 'c8cc91a726b126145f3ef65acbf01de9',
//            'encodingaeskey' => 'NJqKhReOkr5JchPCCzVZVFWrlgPRrMT6VxL4Dbs0wbF',
//            'token' => '86a8c273c5a0110d49a0dd7c724ac3fc',
//        ]);
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

    public function checkComponentAuth()
    {
        $appid = $this->appid;
        $appsecret = $this->appsecret;
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
            'component_appid' => $appid,
            'component_appsecret' => $appsecret,
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
        if (!$this->component_access_token && !$this->checkComponentAuth()) return false;
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
        if (!$this->component_access_token && !$this->checkComponentAuth()) return false;
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

    public function getAuthorizerInfo($authorizer_appid)
    {
        if (!$this->component_access_token && !$this->checkComponentAuth()) return false;
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
        if (!$this->component_access_token && !$this->checkComponentAuth()) return false;
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
        if (!$this->component_access_token && !$this->checkComponentAuth()) return false;
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

    public function getUserInfo($token, $openid){
        //$userInfoCacheKey = 'wechat:userinfo:'.$openid;
        //$expire = 604800;  //60 * 60 * 24 * 7     7天
        //$userInfo = $this->getCache($userInfoCacheKey);
        //if($userInfo && $userInfo['subscribe'] != 0)
        //    return $userInfo;
        $result = $this->http_get(self::API_URL_PREFIX.self::USER_INFO_URL.'access_token='.$token.'&openid='.$openid);
        if ($result) {
            $json = json_decode($result,true);
            if (isset($json['errcode'])) {
                $this->errCode = $json['errcode'];
                $this->errMsg = $json['errmsg'];
                return false;
            }
            //$this->setCache($userInfoCacheKey, $json, $expire);
            return $json;
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

