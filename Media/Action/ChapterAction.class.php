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

            //章节列表
            $chapterList = $this->getChapterList($classId);

            $condition = ['class_id' => $classId, 'chapter_id' => $chapterId, 'user_id' => $userId];
            // 章节详情
            $chapterInfo = D('Chapter')->getCourseInfo($condition);

            // 上次播放时间
            $time = D('ChapterUser')->getCoursesClientTime($condition);

            $this->assign('ext', 'jpg');
            $this->assign('chapterList', $chapterList);
            $this->assign('chapterId', $chapterId);
            $this->assign('user_id', $userId);
            $this->assign('info', $chapterInfo);
            $this->assign('time', (! empty($time) and $time['time'] > 0) ? $time['time'] : 0);
            $this->display();

        } else {
            showMessage('404');
        }
        
        
    }


    public function updateOp()
    {
        ignore_user_abort(true); // 后台运行
        set_time_limit(0); // 取消脚本运行时间的超时上限

        $classId = I('class_id');
        $chapterId = I('chapter_id');
        $userId = I('user_id');
        $time = I('time');

        $updata = [
            'class_id' => $classId,
            'chapter_id' => $chapterId,
            'user_id'   => $userId,
            'create_time' => date('Y-m-d H:i:s'),
            'time' => $time,
        ];

//        print_r($updata);exit();


        try{
            $id = D('ChapterUser')->data($updata)->add();
            if (! $id) {
                throw new \Exception("db not find");
            } else {
                echo json_encode(['code'=>1, 'msg'=>'success']);
            }
        }catch(\Exception $e){
            echo json_encode(['code'=>-1, 'msg'=>$e->getMessage()]);
        }

    }

    private function getChapterList($classId)
    {
        return D('Chapter')->getCourseList(['class_id' => $classId]);
    }

}
