<?php
namespace Media\Action;

class MyAction extends \Media\Action\CommonAction
{
    protected $needAuth = true;
    
    /**
     * 个人中心主页
     */
    public function indexOp()
    {
        $userService = new \Common\Service\UserService;
        $userInfo = $userService->getUserFullInfo(session('user_id'));
        
        $this->assign('user_info', $userInfo);
        $this->display();
    }
    
    /**
     * 我的东家
     * 
     */
    public function parentOp()
    {
        $userModel = new \Common\Model\UserModel();
        $userInfo = $userModel->getUserInfo(['user_id' => session('user_id')]);
        $parentId = $userInfo['parent_id'];
        
        $parentInfo = $userModel->getUserInfo(['user_id' => $parentId]);
        $this->assign('parent_info', $parentInfo);
        $this->display();
    }
    
    /**
     * 我的粉丝
     */
    public function fansOp()
    {
        $userModel = new \Common\Model\UserModel();
        $condition = array();
        $condition['parent_id'] = session('user_id');
        
        $count = $userModel->where($condition)->count();
        $userList = $userModel->where($condition)->select();
        
        $this->assign('count', $count);
        $this->assign('user_list', $userList);
        $this->display();
    }
    
    /**
     * 我的课程
     */
    public function courseOp()
    {
        $classModel = new \Common\Model\ClassModel();
        $applyList = $classModel->getClassUserList(['user_id' => session('user_id')]);
        $classIds = array();
        foreach ($applyList as &$apply) {
            $apply['class_info'] = $classModel->getClassInfo(['class_id' => $apply['class_id']]);
        }
        $this->assign('apply_list', $applyList);
        $this->display();
    }
}