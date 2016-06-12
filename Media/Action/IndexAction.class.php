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
        
        $field = 'class_id,class_title,class_image,teacher_id,teacher_name,class_price,study_num';
        $classList = $classModel->getClassOnlineList([], $field, 'class_sort desc');
        foreach ($classList as &$classInfo) {
            // 课程是否已购买
            $classInfo['is_buy'] = $classService->checkClassUser($classInfo['class_id'], session('user_id'));
            
            // 课程章节数
            $classInfo['chapter_num'] = $chapterModel->getCourseCount([
                'class_id' => $classInfo['class_id'],
                'status' => 1,
            ]);
        }
        $this->assign('class_list', $classList);
        $this->display();
    }
    
    /**
     * 联系我们页面
     */
    public function contactOp()
    {
        $this->display();
    }
    
    /**
     * 新手指南页面
     */
    public function manualOp()
    {
        $this->display();
    }
    
    public function sales_modelOp()
    {
        $this->display();
    }

    /**
     * 分享海报
     **/ 
    public function posterOp()
    {
        $data = I('get.data');
        $imagePath = json_decode(base64_decode($data), true); 

        $this->assign('data', $data);
        $this->assign('imageSrc', $imagePath['pathName']);
        $this->display('My/poster');
    }

    public function posterForeverOp()
    {
        $forever = 1;
        $posterService = new \Media\Service\CreatePosterService();
        $imagePath = $posterService->getPoster(session('user_id'), true, $forever);
    } 

}
