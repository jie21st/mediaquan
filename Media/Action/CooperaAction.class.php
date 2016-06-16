<?php
/**
* 我要合作
**/

namespace Media\Action;

class CooperaAction extends CommonAction
{
    /**
    * 是否需要登录
    * @var boolean
    **/
    protected $needAuth = true;

    //首页
    public function indexOp()
    {
        $this->display('list');
    }

    // 讲师页面
    public function teacharOp()
    {
        $this->display();
    }

    // 系统页面
    public function systemOp()
    {

        $this->display();
    }

    // 公众号页面
    public function wechatOp()
    {

        $this->display();
    }
}
