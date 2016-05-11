<?php
namespace Media\Action;

class IndexAction extends CommonAction
{
    protected $needAuth = true;
    
    /**
     * 构造方法
     */
    public function __construct() {
        parent::__construct();
    }
    
    public function indexOp()
    {
        echo '测试';
    }
}
