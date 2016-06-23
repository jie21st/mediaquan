<?php
namespace Admin\Action;

class UserAction extends CommonAction
{

    public function listOp()
    {
        $this->display();
    }

    public function addOp()
    {
        if (IS_GET) {
            $this->display();
        }

        if (IS_POST) {
            $userAdminModel = D('UserAdmin');

            $reult = array(
                array('admin_name',         'require',  '帐号名称已经存在',     1, 'unique',    3),
                array('admin_password',     '6,18',     '密码长度6到18位',      1, 'length',    3),
                array('admin_password1',    'admin_password', '确认密码不正确', 1, 'confirm',   3),
                array('admin_truename',     '1,10',     '用户名长度1到10位',    1, 'length',    3),
                array('admin_mobile',       '11',       '手机号码必须11位',     2, 'length',    3),
                array('admin_email',        'email',    'email格式错误', 2),
                array('admin_description',  '1,60',     '备注超过60字', 2, 'length', 3),
            );

            if (!$userAdminModel->validate($reult)->create()) {
                $error = $userAdminModel->getError();
                $this->ajaxReturn(['code'=>0, 'msg' => $error]);
            } else {
                $data = array();
                $data['admin_name']         = I('post.admin_name');
                $data['admin_password']     = md5(C('ADMIN_LOGIN_KEY') . I('post.admin_password'));
                $data['admin_truename']     = I('post.admin_truename');
                $data['admin_mobile']       = I('post.admin_mobile');
                $data['admin_email']        = I('post.admin_email');
                $data['admin_description']  = I('post.description');
                $data['admin_create_time']  = time();
                $data['admin_state']        = 1;

                $bool = $userAdminModel->addUser($data);
                if($bool) {
                    $this->ajaxReturn(['code'=>1, 'msg' => 'success']);
                } else {
                    $this->ajaxReturn(['code'=>0, 'msg' => '更新数据失败']);
                }
            }
        }
    }
    
    public function editOp()
    {
        $userAdminModel = D('UserAdmin');
        if (IS_GET) {
            $condition = array('admin_id' => I('get.admin_id', 0, 'intval'));
            $field = "admin_id,admin_name,admin_truename,admin_mobile,admin_email,admin_description";
            $userInfo = $userAdminModel->getUserInfo($condition, $field);
            $this->assign('user', $userInfo);
            $this->display();
        } 

        if (IS_POST) {
            $adminId    = I('post.admin_id');

            $rules = array(
                array('admin_truename',     '1,10',     '用户名长度1到10位',    1, 'length',    3),
                array('admin_mobile',       '11',       '手机号码必须11位',     2, 'length',    3),
                array('admin_email',        'email',    'email格式错误', 2),
                array('admin_description',  '1,60',     '备注超过60字', 2, 'length', 3),
            );

            if(! $userAdminModel->validate($rules)->create()) {
                $this->ajaxReturn(array('code' => 0, 'msg' => $userAdminModel->getError()));
            } else {
                $condition  = array('admin_id' => $adminId);
                $field      = array("admin_truename", "admin_mobile", "admin_email", "admin_description");

                $data       = array();
                $data['admin_truename']     = I('admin_truename');
                $data['admin_mobile']       = I('admin_mobile');
                $data['admin_email']        = I('admin_email');
                $data['admin_description']  = I('admin_description');

                $userAdminModel->setUserMessage($condition, $data, $field);
                if(false === $bool) {
                    $this->ajaxReturn(array('code'=>0, 'msg'=>'数据更新失败'));
                } else {
                    $this->ajaxReturn(array('code'=>1, 'msg'=>'success'));
                }
            }
        }
    } 

    public function delOp()
    {
        if(IS_POST) {
            $state = I('post.admin_state', 0, 'intval');
            $userId = I('post.admin_id', 0, 'intval');

            if(!$userId and ($state >= 0 and $state <= 1)) {
                $this->ajaxReturn(['code'=>0, 'msg'=>'error:Parameter error']);
            }

            $userAdminModel = D('UserAdmin');
            $userInfo = $userAdminModel->getUserInfo(['admin_id'=>$userId]);
            if(empty($userInfo)) {
                $this->ajaxReturn(['code'=>0, 'msg'=>'error:Without this person']);
            }

            $bool = $userAdminModel->setUserMessage(['admin_id'=>$userId], ['admin_state'=>$state], 'admin_state');
            if(false === $bool) {
                $this->ajaxReturn(['code'=>0, 'msg'=>'error:operation failed']);
            } else {
                $this->ajaxReturn(['code'=>0, 'msg'=>'success']);
            }
        } else {
            $this->ajaxReturn(['code'=>0, 'msg'=>'error:Parameter error']);
        }
    }

    public function getUserListOp()
    {
        if (IS_POST) {

            // 条件 
            $condition  = array();
            $mobile     = I('post.mobile', '');
            $userName   = I('post.user_name', '');

            $page       = I('post.page', 1, 'intval');
            $limit      = I('post.rows', 20, 'intval');
            
            if(isset($mobile) and $mobile != '')
                $condition['admin_mobile']    = $mobile;

            if(isset($userName) and $userName != '') 
                $condition['admin_name'] = $userName;

            $userAdminModel = D('UserAdmin');

            $total      = $userAdminModel->totalUserList($condition);
            $userList   = $userAdminModel->getUserList($condition, $page, $limit);
        
            $this->returnData($userList, $total);
        }
    }

    public function trueDelOp()
    {
        $adminId = I('post.admin_id', 0, 'intval');
        $bool = D('UserAdmin')->trueDel(array('admin_id'=>$adminId)); 
        if ($bool) {
            $this->ajaxReturn(['code'=>1, 'msg'=>'success']);
        } else {
            $this->ajaxReturn(['code'=>0, 'msg'=>'操作失败']);
        }
    }

    public function resetPasswdOp()
    {
        $userAdminModel = D('UserAdmin');

        if (IS_GET) {
            $condition  = array('admin_id'=> I('get.id', 0, 'intval'));
            $userInfo   = $userAdminModel->getUserInfo($condition, 'admin_name');
            $this->assign('user', $userInfo);
            $this->display();
        }

        if(IS_POST) {

            $condition  = array('admin_id'=> I('post.id', 0, 'intval'));
            $passwd = md5(C('ADMIN_LOGIN_KEY') . I('post.passwd'));
            $userInfo   = $userAdminModel->getUserInfo($condition, 'admin_password');

            if($passwd !== $userInfo['admin_password']) {
                $this->ajaxReturn(['code'=>0, 'msg'=>'旧密码错误']);
            }

            $result = array(
                array('admin_password',     '6,18',     '新密码长度6到18位',      1, 'length',    3),
                array('admin_password1',    'admin_password', '确认密码不正确', 1, 'confirm',   3),
            ); 

            if(! $userAdminModel->validate($result)->create()) {
                $this->ajaxReturn(['code'=>0, 'msg'=> $userAdminModel->getError()]);
            } else {

                $data = array('admin_password'=> md5(C('ADMIN_LOGIN_KEY') . I('admin_password')));
                $bool = $userAdminModel->setUserMessage($condition, $data, 'admin_password');

                if($bool) {
                    $this->ajaxReturn(['code'=>1, 'msg'=>'success']);
                } else {
                    $this->ajaxReturn(['code'=>0, 'msg'=>'数据更新失败']);
                }
            }
        }
    }
}
