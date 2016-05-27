<?php
namespace Media\Action;

use \Org\Util\Wechat;

class WechatAction extends CommonAction
{
    /**
     * wechat 
     * 
     * @var mixed
     * @access private
     */
    private $wechat;

    /**
     * __construct 
     * 
     * @access public
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->wechat = new Wechat;
    }
    
    /**
     * 接收微信推送消息
     */
    public function receiveOp()
    {
        (new \Media\Service\WechatResponseService)->responseHandle();
    }
    
    /**
     * 获取用户信息,生成毕业证时需要实时用户信息
     *
     */
    public function getUserInfoOp()
    {
        if (! isset($_GET['code'])) {
            $returnUrl = $_GET['returnUrl'];
            if (empty($returnUrl)) {
                exit('empty returnUrl'); 
            }
            session('returnUrl', $returnUrl);
            // 生成唯一随机串防CSRF攻击
            $state = md5(uniqid(rand(), TRUE));
            session('state', $state);
            // 构造请求url
            $baseUrl = C('APP_SITE_URL') . $_SERVER['REQUEST_URI'];
            $url = $this->wechat->getOauthRedirect($baseUrl, $state); 
            redirect($url);
        } else {
            // 验证state防止CSRF攻击
            if(session('state') != I('get.state', '')) {
                exit('The state does not match. You may be a victim of CSRF.');
            }
            $result = $this->wechat->getOauthAccessToken();
            if (false === $result) {
                exit('AuthToken error');
            }
            $authUserInfo = $this->wechat->getOauthUserinfo($result['access_token'], $result['openid']);
            if (false === $authUserInfo) {
                exit('获取用户信息失败');
            }
            $headimgurl = urlencode($authUserInfo['headimgurl']);
            $returnUrl = session('returnUrl');
            if (false !== strpos($returnUrl, '?')) {
                $url = $returnUrl . '&headimgurl=' . $headimgurl;
            } else {
                $url = $returnUrl . '?headimgurl=' . $headimgurl;
            }
            redirect($url);
        }
    }

    /**
     * 公众平台权限
     * 获取公众平台TOKEN
     *
     * @access public
     * @return json
     */
    public function getAccessTokenOp()
    {
        $result = $this->wechat->checkAuth();
        if ($result) {
            $result['expire'] *= 1000;
            $this->returnJson(1, 'SUCCESS', $result);
        } else {
            $this->returnJson(0, '获取失败');
        }
    }

    /**
     * 生成带参数的二维码
     *
     */
    public function getQRUrlOp()
    {
        $scene_id = I('post.scene_id', 0, 'intval');
        $type = I('post.type', 0, 'intval');
        $expire = I('post.expire', 3600, 'intval');

        if ($scene_id <= 0) {
            exit('scene_id invalid'); 
        }
        $result= $this->wechat->getQRCode($scene_id, $type, $expire);
        if ($result) {
            $ticket = $result['ticket']; 
            $url = $this->wechat->getQRUrl($ticket);
            $this->returnJson(1, 'SUCCESS', [
                'url'   => $url
            ]);
        } else {
            $this->returnJson(0, '获取失败'); 
        }
    }

    public function sendTemplateMessageOp()
    {
        
        $data = [
            'touser'    => 'oeE-Tt07niiF4LgVpyHkLwLyT8lg',
            'template_id'   => '-xHiYGseJaw8ZcwuLf-qrtCjUVsMGWn8-Yqql-tTHr8',    
            'url'       => 'http://m.guanlizhihui.com:86/i/feed',
            'topcolor'  => '#FF0000',
            'data'  => [
                'first' => [
                    'value' => '我们已收到您的货款，开始为您打包商品，请耐心等待',
                    'color' => '#173177', 
                ],
                'orderMoneySum' => [
                    'value' => '39.10元',
                    'color' => '#173177'
                ],
                'orderProductName' => [
                    'value' => '微信公开课',
                    'color' => '#173177'
                ],
                'Remark'    => [
                    'value' => '如有问题欢迎致电13521923981',
                    'color' => '#173177'
                ]
            ],
        ];
        $result = $this->wechat->sendTemplateMessage($data);
    }
     
    /**
     * 公众平台JSAPI签名
     * 
     * @access public
     * @return void
     */
    public function getJsSignOp()
    {
        $url = I('request.url', '', 'urldecode');
        if (! $url) {
            $this->ajaxReturn(['code' => 10010, 'msg' => '参数错误'], 'jsonp');
        }
        $result = $this->wechat->getJsSign($url);
        if ($result) {
            $this->ajaxReturn(['code' => 1, 'data' => $result, 'msg' => 'SUCCESS'], 'jsonp');
        } else {
            $this->ajaxReturn(['code' => 10010, 'errMsg' => '参数错误'], 'jsonp');
        }
    }
    
    
}
