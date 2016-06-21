<?php
namespace Media\Action;

class Test2Action extends \Think\Action
{

    protected $postxml;
    private $_receive;

    public function infoOp()
    {
        phpinfo();
    }

    public function indexOp()
    {
        $cp = new Component();
        $cp->valid();
        $type = $cp->getRev()->getRevInfoType();
        switch($type) {
            case Component::INFOTYPE_VERIFY_TICKET:
                $ticket = $cp->getRev()->getRevVerifyTicket();
                \Think\Log::write($ticket); 
                $redis = \Think\Cache::getInstance('redis');
                $redis->set('component:verify_ticket', $ticket);
                exit('success');
                break;
            case Component::INFOTYPE_AUTHORIZED:
                // TODO 授权处理
                break;
            case Component::INFOTYPE_UPDATEAUTHORIZED:
                $data = $cp->getRevData();
                $authorizerAppid = $data['AuthorizerAppid'];
                $authorizationCode = $data['AuthorizationCode'];
                $authorizationCodeExpiredTime = $data['AuthorizationCodeExpiredTime'];

                $key = 'component:authorization:'.$authorizerAppid;
                $redis = \Think\Cache::getInstance('redis');
                $redis->set($key, $authorizationCode, $authorizationCodeExpiredTime-time());
                break;
            case Component::INFOTYPE_UNAUTHORIZED:
                // TODO 取消授权处理
                $authorizerAppid = $cp->getRevAuthorizerAppid();
                \Think\Log::write('微信第三方推送取消授权 appid='.$authorizerAppid);
                break;
            default:
                \Think\Log::write('微信第三方推送类型未定义'.$type);
        }
    }    

    public function authOp()
    {
        session('store_id', 2);
        $cp = new Component();
        if (isset($_GET['auth_code'])) {
            //dump($_GET);
            $auth = $cp->queryAuth($_GET['auth_code']);
            if ($auth) {
                //dump($auth);
                $result = $cp->getAuthorizerInfo($auth['authorization_info']['authorizer_appid']);
                if ($result) {
                    dump($result);
                    $authorizerInfo = $result['authorizer_info'];
                    $authorizationInfo = $result['authorization_info'];


                    $cp->checkAuth('', '', $auth['authorization_info']['authorizer_access_token']);
                    $userList = $cp->getUserList();
                    echo '用户列表';
                    echo $cp->errMsg;
                    dump($userList);
                    echo $openid = $userList['data']['openid'][1];
                    $userInfo = $cp->getUserInfo('oHyZAwN_gfvL8JCMhFtKB24bQPCQ');
                    //echo $cp->errMsg;
                    dump($userInfo);

                    // 保存令牌
                    $redis = \Think\Cache::getInstance('redis');
                    $cachekey = 'authorizer:'.$auth['authorization_info']['authorizer_appid'];
                    echo $cachekey;
                    $redis->hSet($cachekey, [
                        'access_token' => $auth['authorization_info']['authorizer_access_token'],
                        'refresh_token' => $auth['authorization_info']['authorizer_refresh_token']
                    ]);

                    // 绑定
                    $data = array();
                    $data['store_id'] = session('store_id');
                    $data['appid'] = $authorizationInfo['authorizer_appid'];
                    $data['mp_nickname'] = $authorizerInfo['nick_name'];
                    $data['mp_headimg'] = $authorizerInfo['head_img'];
                    $data['mp_wechatid'] = $authorizerInfo['alias'];
                    $data['mp_username'] = $authorizerInfo['user_name'];
                    $data['mp_service_type'] = $authorizerInfo['service_type_info']['id'];
                    $data['mp_verify_type'] = $authorizerInfo['verify_type_info']['id'];
                    $data['mp_qrcode'] = $authorizerInfo['qrcode_url'];

                    dump($data);
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
            $url = $cp->getAuthorizeRedirect('http://mediaquan.guanlizhihui.com/test2/auth', $authcode);
            redirect($url);
        }
    }

    public function loginOp(){
        session('store_id', 1);
        $wechat = new \Org\Util\Wechat;
        if (isset($_GET['state'])) {
            $result = $wechat->getOauthAccessToken();
            if (false === $result) {
                exit('AuthToken error');
            }
            echo '授权信息'; 
            dump($result);
            $userInfo = $wechat->getOauthUserinfo($result['access_token'], $result['openid']); 
            session('oauth_user_info', $userInfo);
            session('user_id', 10047);
            echo '授权用户信息';
            dump($userInfo);
            redirect('/test2/getOpenid');
        } else {
            $state = md5(uniqid(rand(), TRUE));
            session('state', $state);
            // 构造请求参数列表
            $login_url = $wechat->getOauthRedirect(C('MEDIA_SITE_URL') . '/test2/login', $state);
            redirect($login_url);
        }
    }

    public function getOpenidOp(){
        $wechatModel = M('wechat');
        $appInfo = $wechatModel->where(['store_id' => session('store_id')])->find();
        if (empty($appInfo)) {
            exit('app not exists');
        }
        $component = new Component;
        if (isset($_GET['state'])) {
            $result = $component->getOauthAccessToken($appInfo['appid']);
            //dump($result);
            if ($result) {
                dump(session('oauth_user_info'));
                session('seller_fans_openid', $result['openid']);
                $fansModel = M('wechatFans');
                $fansInfo = $fansModel->where(['openid' => $result['openid']])->find();
                if ($fansInfo && !$fansInfo['user_id']) {
                    $fansModel->where(['openid' => $result['openid']])->setField('user_id', session('user_id'));

                }
                //echo $result['access_token'].'<br/>';
                echo $result['openid'];
                //$userInfo = $component->getOauthUserinfo($result['access_token'], $result['openid']); 
                //echo $component->errMsg;
                //dump($userInfo);
            }
            return null;
        } else {
            $state = md5(uniqid(rand(), TRUE));
            //session('state', $state);
            $loginUrl = $component->getOauthRedirect($appInfo['appid'], C('MEDIA_SITE_URL').'/test2/getOpenid', $state, 'snsapi_base');
            //echo $loginUrl;
            redirect($loginUrl);
        }
    }


    public function receiveOp()
    {
        $appid = $_GET['appid'];
        if (empty($appid)){
            exit('invalid appid');
        }
        $model = M('wechat');
        $appInfo = $model->where(['appid' => $appid])->find();
        if (empty($appInfo)){
            exit('app not exists');
        }
        
        $cachekey = 'authorizer:'.$appid;
        $cache = \Think\Cache::getInstance('redis');
        $token = $cache->hGet($cachekey, 'access_token');
        \Think\Log::write('公众号token: '.$token);

        $wechat = new Component;
        $wechat->valid();
        $type = $wechat->getRev()->getRevType();
        switch($type) {
            case Component::MSGTYPE_EVENT:
                $data = $wechat->getRevData();
                $event = $wechat->getRev()->getRevEvent();
                \Think\Log::write('事件接收'.json_encode($event).' : '.json_encode($data));
                switch ($event['event']) {
                    case 'subscribe':
                        $openid = $wechat->getRevFrom(); 
                        \Think\Log::write('关注人openid: '.$openid);
                        $userInfo = $wechat->getUserInfo($token, $openid);
                        \Think\Log::write('关注人信息: '.json_encode($userInfo) . $wechat->errMsg);
                        $insert = array();
                        $insert['store_id'] = $appInfo['store_id'];
                        $insert['openid'] = $userInfo['openid'];
                        $insert['fans_nickname'] = $userInfo['nickname'];
                        $insert['fans_sex'] = $userInfo['sex'];
                        $insert['fans_avatar'] = $userInfo['headimgurl'];
                        $insert['fans_country'] = $userInfo['country'];
                        $insert['fans_province'] = $userInfo['province'];
                        $insert['fans_city'] = $userInfo['city'];
                        $insert['subscribe_state'] = 1;
                        $insert['subscribe_time'] = $userInfo['subscribe_time'];
                        $insert['fans_remark'] = $userInfo['remark'];
                        $fansModel = M('wechatFans');
                        $fansModel->add($insert);
                        break;
                }
                break;
                //case Wechat::MSGTYPE_TEXT:
                //$this->wechat->text("hello")->reply();
                //    break;
                //case Wechat::MSGTYPE_IMAGE:
                //    break;
            default:
                $data = $wechat->getRevData();
                \Think\Log::write('其他接收'.print_r($data, true));
                //$this->wechat->text("help info")->reply();
                //$this->wechat->transfer_customer_service()->reply();
        }
        
    }
}

class Component extends \Org\Util\Wechat
{
    const INFOTYPE_AUTHORIZED = 'unauthorized';
    const INFOTYPE_UNAUTHORIZED = 'unauthorized';
    const INFOTYPE_VERIFY_TICKET = 'component_verify_ticket';
    const INFOTYPE_UPDATEAUTHORIZED = 'updateauthorized';
    const COMPONENT_AUTH_URL = '/component/api_component_token';
    const COMPONENT_PREAUTHCODE_URL = '/component/api_create_preauthcode';
    const COMPONENT_AUTHORIZE_URL = 'https://mp.weixin.qq.com/cgi-bin/componentloginpage';
    const COMPONENT_QUERY_AUTH_URL = '/component/api_query_auth';
    const COMPONENT_AUTHORIZER_INFO = '/component/api_get_authorizer_info';
    const COMPONENT_OAUTH_TOKEN_URL = '/sns/oauth2/component/access_token';

    private $component_access_token;

    public function __construct()
    {
        parent::__construct([
            'appid' => 'wx1445ced338f34e05',
            'appsecret' => 'c8cc91a726b126145f3ef65acbf01de9',
            'encodingaeskey' => 'NJqKhReOkr5JchPCCzVZVFWrlgPRrMT6VxL4Dbs0wbF',
            'token' => '86a8c273c5a0110d49a0dd7c724ac3fc',
        ]); 
    }

    public function getRevInfoType() {
        if (isset($this->_receive['InfoType']))
            return $this->_receive['InfoType'];
        else
            return false;
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
        $requestData = [
            'component_appid' => $appid,
            'component_appsecret' => $appsecret,
            'component_verify_ticket' => $ticket,
        ];
        $result = $this->http_post(self::API_URL_PREFIX.self::COMPONENT_AUTH_URL, self::json_encode($requestData));
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
        $appid = $this->appid;
        //echo $this->component_access_token;
        //$authname = 'component:pre_auth_code';
        //if ($rs = $this->getCache($authname)){
        //    return $rs;
        //}

        $result = $this->http_post(self::API_URL_PREFIX.self::COMPONENT_PREAUTHCODE_URL.'?component_access_token='.$this->component_access_token, self::json_encode(['component_appid' => $appid]));
        if ($result) {
            $json = json_decode($result,true); 
            if (!$json || !empty($json['errcode'])) {
                $this->errCode = $json['errcode'];
                $this->errMsg = $json['errmsg'];
                return false;
            }

            return $json['pre_auth_code'];
            //$this->pre_auth_code = $json['pre_auth_code'];
            //$expire = $json['expires_in'] ? intval($json['expires_in'])-100 : 600;
            //$this->setCache($authname, $this->pre_auth_code, $expire); 
            //return $this->pre_auth_code;
        }
        return false;
    }

    public function getAuthorizeRedirect($redirect, $code){
        return self::COMPONENT_AUTHORIZE_URL.'?component_appid='.$this->appid.'&pre_auth_code='.$code.'&redirect_uri='.urlencode($redirect);
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

/**
 * PKCS7Encoder class
 *
 * 提供基于PKCS7算法的加解密接口.
 */
class PKCS7Encoder
{
    public static $block_size = 32;

    /**
     * 对需要加密的明文进行填充补位
     * @param $text 需要进行填充补位操作的明文
     * @return 补齐明文字符串
     */
    function encode($text)
    {
        $block_size = PKCS7Encoder::$block_size;
        $text_length = strlen($text);
        //计算需要填充的位数
        $amount_to_pad = PKCS7Encoder::$block_size - ($text_length % PKCS7Encoder::$block_size);
        if ($amount_to_pad == 0) {
            $amount_to_pad = PKCS7Encoder::block_size;
        }
        //获得补位所用的字符
        $pad_chr = chr($amount_to_pad);
        $tmp = "";
        for ($index = 0; $index < $amount_to_pad; $index++) {
            $tmp .= $pad_chr;
        }
        return $text . $tmp;
    }

    /**
     * 对解密后的明文进行补位删除
     * @param decrypted 解密后的明文
     * @return 删除填充补位后的明文
     */
    function decode($text)
    {

        $pad = ord(substr($text, -1));
        if ($pad < 1 || $pad > PKCS7Encoder::$block_size) {
            $pad = 0;
        }
        return substr($text, 0, (strlen($text) - $pad));
    }

}

/**
 * Prpcrypt class
 *
 * 提供接收和推送给公众平台消息的加解密接口.
 */
class Prpcrypt
{
    public $key;

    function __construct($k) {
        $this->key = base64_decode($k . "=");
    }

    /**
     * 兼容老版本php构造函数，不能在 __construct() 方法前边，否则报错
     */
    function Prpcrypt($k)
    {
        $this->key = base64_decode($k . "=");
    }

    /**
     * 对明文进行加密
     * @param string $text 需要加密的明文
     * @return string 加密后的密文
     */
    public function encrypt($text, $appid)
    {

        try {
            //获得16位随机字符串，填充到明文之前
            $random = $this->getRandomStr();//"aaaabbbbccccdddd";
            $text = $random . pack("N", strlen($text)) . $text . $appid;
            // 网络字节序
            $size = mcrypt_get_block_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);
            $module = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_CBC, '');
            $iv = substr($this->key, 0, 16);
            //使用自定义的填充方式对明文进行补位填充
            $pkc_encoder = new PKCS7Encoder;
            $text = $pkc_encoder->encode($text);
            mcrypt_generic_init($module, $this->key, $iv);
            //加密
            $encrypted = mcrypt_generic($module, $text);
            mcrypt_generic_deinit($module);
            mcrypt_module_close($module);

            //          print(base64_encode($encrypted));
            //使用BASE64对加密后的字符串进行编码
            return array(ErrorCode::$OK, base64_encode($encrypted));
        } catch (Exception $e) {
            //print $e;
            return array(ErrorCode::$EncryptAESError, null);
        }
    }

    /**
     * 对密文进行解密
     * @param string $encrypted 需要解密的密文
     * @return string 解密得到的明文
     */
    public function decrypt($encrypted, $appid)
    {

        try {
            //使用BASE64对需要解密的字符串进行解码
            $ciphertext_dec = base64_decode($encrypted);
            $module = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_CBC, '');
            $iv = substr($this->key, 0, 16);
            mcrypt_generic_init($module, $this->key, $iv);
            //解密
            $decrypted = mdecrypt_generic($module, $ciphertext_dec);
            mcrypt_generic_deinit($module);
            mcrypt_module_close($module);
        } catch (Exception $e) {
            return array(ErrorCode::$DecryptAESError, null);
        }


        try {
            //去除补位字符
            $pkc_encoder = new PKCS7Encoder;
            $result = $pkc_encoder->decode($decrypted);
            //去除16位随机字符串,网络字节序和AppId
            if (strlen($result) < 16)
                return "";
            $content = substr($result, 16, strlen($result));
            $len_list = unpack("N", substr($content, 0, 4));
            $xml_len = $len_list[1];
            $xml_content = substr($content, 4, $xml_len);
            $from_appid = substr($content, $xml_len + 4);
            if (!$appid)
                $appid = $from_appid;
            //如果传入的appid是空的，则认为是订阅号，使用数据中提取出来的appid
        } catch (Exception $e) {
            //print $e;
            return array(ErrorCode::$IllegalBuffer, null);
        }
        if ($from_appid != $appid)
            return array(ErrorCode::$ValidateAppidError, null);
        //不注释上边两行，避免传入appid是错误的情况
        return array(0, $xml_content, $from_appid); //增加appid，为了解决后面加密回复消息的时候没有appid的订阅号会无法回复

    }


    /**
     * 随机生成16位字符串
     * @return string 生成的字符串
     */
    function getRandomStr()
    {

        $str = "";
        $str_pol = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz";
        $max = strlen($str_pol) - 1;
        for ($i = 0; $i < 16; $i++) {
            $str .= $str_pol[mt_rand(0, $max)];
        }
        return $str;
    }

}

/**
 * error code
 * 仅用作类内部使用，不用于官方API接口的errCode码
 */
class ErrorCode
{
    public static $OK = 0;
    public static $ValidateSignatureError = 40001;
    public static $ParseXmlError = 40002;
    public static $ComputeSignatureError = 40003;
    public static $IllegalAesKey = 40004;
    public static $ValidateAppidError = 40005;
    public static $EncryptAESError = 40006;
    public static $DecryptAESError = 40007;
    public static $IllegalBuffer = 40008;
    public static $EncodeBase64Error = 40009;
    public static $DecodeBase64Error = 40010;
    public static $GenReturnXmlError = 40011;
    public static $errCode=array(
            '0' => '处理成功',
            '40001' => '校验签名失败',
            '40002' => '解析xml失败',
            '40003' => '计算签名失败',
            '40004' => '不合法的AESKey',
            '40005' => '校验AppID失败',
            '40006' => 'AES加密失败',
            '40007' => 'AES解密失败',
            '40008' => '公众平台发送的xml不合法',
            '40009' => 'Base64编码失败',
            '40010' => 'Base64解码失败',
            '40011' => '公众帐号生成回包xml失败'
    );
    public static function getErrText($err) {
        if (isset(self::$errCode[$err])) {
            return self::$errCode[$err];
        }else {
            return false;
        };
    }
}
