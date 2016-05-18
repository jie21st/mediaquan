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
    
    /**
     * 首页
     */   
    public function indexOp()
    {
        $classModel = new \Common\Model\ClassModel();
        $classService = new \Common\Service\ClassService();
        $classList = $classModel->select();
        foreach ($classList as &$class) {
            // 课程是否已购买
            $class['is_buy'] = $classService->checkClassUser($class['class_id'], session('user_id'));
            
            // 课程章节数
            $class['chapter_num'] = rand(1, 99);
            
            // 课程播放链接
        }
        $this->assign('class_list', $classList);
        $this->display();
    }
}
