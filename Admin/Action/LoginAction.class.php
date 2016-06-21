<?php
namespace Admin\Action;

class LoginAction extends \Think\Action 
{
    
    // 登陆页
    public function loginInOp()
    {
        $this->display();
    }

    // 验证用户 登陆
    public function verifyOp()
    {
        if(IS_POST) {

            $userName = I('post.user', '');
            $passwd = I('post.passwd', '');

            if(! empty($userName) and ! empty($passwd)) {
                $condition = ['account' => $userName];
                $userInfo = D('UserAdmin')->getUserInfo($condition); 

                if(empty($userInfo)) {
                    $this->ajaxReturn(array('code'=>'0'));
                    exit();
                } 
            
                if($userInfo['state'] == 0) {
                    $this->ajaxReturn(array('code'=>'0', 'flag'=>'2'));
                    exit();
                }

                $userPasswd = md5(C('ADMIN_LOGIN_KEY') . $passwd);
                if($userPasswd === $userInfo['password']) {
                    $sessionData = array( 
                                    'user_id'   => $userInfo['user_id'],
                                    'account'   => $userInfo['account'],
                                    'user_name' => $userInfo['user_name'],
                                    'mobile'    => $userInfo['mobile'],
                                    'state'     => $userInfo['state'],
                               );
                    session('admin_user', $sessionData);
                    $this->ajaxReturn(array('code'=>'1'));
                } else {
                    $this->ajaxReturn(array('code'=>''));
                    exit();
                }

            } else {
                $this->ajaxReturn(array('code'=>'0'));
                exit();
            }
        
            $this->ajaxReturn(array('code'=>'0'));
            exit();
        }
    }

    // 退出
    public function loginOutOp()
    {
        session(null);
        $this->redirect('login/loginIn');
    }
}
