<?php
namespace Media\Action;

use \Think\Action;

class CommonAction extends Action
{
    /**
     * 是否需要登录验证 
     * 
     * @var mixed
     * @access protected
     */
    protected $needAuth = false;

    /**
     * 初始化控制器 
     * 
     * @access public
     * @return void
     */
    protected function _initialize()
    {
        // 判断是否登录
        if ($this->needAuth) {
            $this->checkLogin();
        }
    }
    
    /**
     * 验证会员是否登录
     * @access protected
     * @return void
     */
    protected function checkLogin()
    {
        if (session('is_login') !== '1') {
            $returnUrl = C('MEDIA_SITE_URL') . $_SERVER['REQUEST_URI'];
            redirect('/login/?returnUrl='.urlencode($returnUrl));
        }
    }

    /**
     * 自定义AJAX 返回格式
     * 
     * @param int $code 
     * @param string $msg 
     * @param array $data 
     * @access protected
     * @return JSON
     */
    protected function returnJson($code = 1, $msg = '', $data = '')
    {
        header('Access-Control-Allow-Origin: *');
        header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
        header('Access-Control-Allow-Methods: GET, POST');
        $arr = array();
        $arr['code'] = $code;
        if ($msg !== '') {
            $arr['msg'] = $msg;
        }
        if ($data !== '') {
            $arr['data'] = $data;
        }
        return $this->ajaxReturn($arr, 'json');
    }

    /**
     * 空操作 
     * 
     * @access public
     * @return void
     */
    protected function _empty()
    {
        send_http_status(404);
    }
}
