<?php
namespace Media\Action;

class LoginAction extends CommonAction
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
        echo $login_url;exit();
        redirect($login_url);
    }
    
    public function callback()
    {
        
    }
}
