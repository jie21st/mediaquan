<?php
namespace Media\Action;

class MyAction extends \Media\Action\CommonAction
{
    protected $needAuth = true;
    
    public function indexOp()
    {
        $userService = new \Common\Service\UserService;
        $userInfo = $userService->getUserFullInfo(session('user_id'));
        
        $this->assign('user_info', $userInfo);
        $this->display();
    }
}