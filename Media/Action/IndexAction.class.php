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
        $chapterModel = new \Common\Model\ChapterModel;
        $classService = new \Common\Service\ClassService();
        
        $classList = $classModel->select();
        foreach ($classList as &$classInfo) {
            // 课程是否已购买
            $classInfo['is_buy'] = $classService->checkClassUser($classInfo['class_id'], session('user_id'));
            
            // 课程章节数
            $classInfo['chapter_num'] = $chapterModel->getCourseCount(['class_id' => $classInfo['class_id']]);
        }
        $this->assign('class_list', $classList);
        $this->display();
    }
    
    /**
     * 联系我们页面
     */
    public function contactOp()
    {
        $this->assign('user_id', session('user_id'));
        $this->display();
    }
}
