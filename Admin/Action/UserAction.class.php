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

    }

    public function delOp()
    {
        if(IS_POST) {
            $userId = I('post.user_id', 0, 'intval');

            if(!$userId) {
                $this->ajaxReturn(['code'=>0, 'msg'=>'error:Parameter error']);
            }

            $userAdminModel = D('UserAdmin');
            $userInfo = $userAdminModel->getUserInfo(['user_id'=>$userId]);
            if(empty($userInfo)) {
                $this->ajaxReturn(['code'=>0, 'msg'=>'error:Without this person']);
            }

            $bool = $userAdminModel->upUser($condition, $data);
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
                $condition['mobile']    = $mobile;

            if(isset($userName) and $userName != '') 
                $condition['account'] = $userName;

            $userAdminModel = D('UserAdmin');

            $total      = $userAdminModel->totalUserList($condition);
            $userList   = $userAdminModel->getUserList($condition, $page, $limit);
        
            $this->returnData($userList, $total);
        }
    }
}
