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
                $condition = ['admin_name' => $userName];
                $userInfo = D('UserAdmin')->getUserInfo($condition); 

                if(empty($userInfo)) {
                    $this->ajaxReturn(array('code'=>'0'));
                    exit();
                } 
            
                if($userInfo['admin_state'] == 0) {
                    $this->ajaxReturn(array('code'=>'0', 'flag'=>'2'));
                    exit();
                }

                $userPasswd = md5(C('ADMIN_LOGIN_KEY') . $passwd);
                if($userPasswd === $userInfo['admin_password']) {
                    $sessionData = array( 
                                    'admin_id'      => $userInfo['admin_id'],
                                    'admin_name'    => $userInfo['admin_name'],
                                    'admin_truename'    => $userInfo['admin_truename'],
                                    'admin_mobile'      => $userInfo['admin_mobile'],
                                    'admin_state'       => $userInfo['admin_state'],
                               );
                    session('admin_user', $sessionData);

                    // 更新登陆状态
                    $data = array();
                    $condition['admin_id'] = $userInfo['admin_id'];
                    $data['admin_login_time'] = time();
                    $data['admin_login_num'] = ['exp', 'admin_login_num+1'];
                    $field = ['admin_login_time', 'admin_login_num'];

                    D('UserAdmin')->setUserMessage($condition, $data, $field);
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
