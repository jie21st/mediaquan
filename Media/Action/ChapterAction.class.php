<?php

namespace Media\Action;

class ChapterAction extends CommonAction
{

    protected $needAuth = true;

    public function classListOp()
    {

        $classId = I('get.class_id', 0, 'intval');

        $userId  = session('user_id');

        // 取得课程信息
        $classModel = new \Common\Model\ClassModel();
        $classInfo = $classModel->getClassInfo(['class_id' => $classId]);
        if (empty($classInfo)) {
            showMessage('课程不存在');
        }



        $classService = new \Common\Service\ClassService;
        // 验证用户是否已报名
        $applyed = $classService->checkClassUser($classId, $userId);

        if (! $applyed) {
            redirect(C('APP_SITE_URL') . "/class/ticket?class_id={$classId}");
        }

        // 课程列表
        $chapterList = $this->getChapterList($classId);

        $this->assign('list', $chapterList);
        $this->display('Chapter:list');
    }


    public function attendOp()
    {
        $classId = I('get.class_id', 0, 'intval');
        $chapterId = I('get.chapter_id', 0, 'intval');
        $userId  = session('user_id');

        if ($classId != 0 and $chapterId != 0) {

            // 取得课程信息
            $classModel = new \Common\Model\ClassModel();
            $classInfo = $classModel->getClassInfo(['class_id' => $classId]);
            if (empty($classInfo)) {
                showMessage('课程不存在');
            }

            $classService = new \Common\Service\ClassService;
            
            // 验证用户是否已报名
            $applyed = $classService->checkClassUser($classId, $userId);

            if (!$applyed) {
                redirect(C('APP_SITE_URL') . "/class/ticket?class_id={$classId}");
            }

            // 获取课程章节
            $chapterInfo = D('Chapter')->getCourseInfo(['class_id' => $classId, 'chapter_id' => $chapterId]);

            $chapterList = $this->getChapterList($classId);

            $this->assign('ext', 'jpg');
            $this->assign('chapterList', $chapterList);
            $this->assign('chapterId', $chapterId);
            $this->assign('info', $chapterInfo);
            $this->display();

        } else {
            showMessage('404');
        }
        
        
    }


    private function getChapterList($classId)
    {
        return D('Chapter')->getCourseList(['class_id' => $classId]);
    }

}