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
        if (IS_POST) {
            $userAdminModel = D('UserAdmin');
            if (!$userAdminModel->create()) {
                $error = $userAdminModel->getError();
                $this->ajaxReturn(['code'=>0, 'msg' => $error]);
            } else {
                $bool = $userAdminModel->add();
                if($bool) {
                    $this->ajaxReturn(['code'=>0, 'msg' => 'success']);
                }
            }
        }
        $this->display();
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
}
