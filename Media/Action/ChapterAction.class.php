<?php

namespace Media\Action;

class ChapterAction extends CommonAction
{

    protected $needAuth = true;

    public function classListOp()
    {

        $classId = I('get.class_id', 0, 'intval');

        $userId  = session('user_id');

        // 取得课程信
        $classModel = new \Common\Model\ClassModel();
        $classInfo = $classModel->getClassInfo(['class_id' => $classId]);
        if (empty($classInfo)) {
            showMessage('课程不存在');
        }

        $classService = new \Common\Service\ClassService;
        // 验证用户是否已报名
        $applyed = $classService->checkClassUser($classId, $userId);

        if (! $applyed) {
            redirect(C('APP_SITE_URL') . "/class/{$classId}.html");
        }

        // 课程列表
        $chapterList = $this->getChapterList(['class_id' => $classId, 'status'=>['eq', 1]]);

        $this->assign('classInfo', $classInfo);
        $this->assign('user_id', $userId);
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
                redirect(C('APP_SITE_URL') . "/class/{$classId}.html");
            }

            //章节列表
            $chapterList = $this->getChapterList(['class_id'=>$classId, 'status'=>1]);

            //$condition = ['class_id' => $classId, 'chapter_id' => $chapterId, 'user_id' => $userId];

            // 章节详情
            $chapterInfo = D('Chapter')->getCourseInfo(['class_id' => $classId, 'chapter_id' => $chapterId, 'status'=>1]);

            // 上次播放时间
            $redis = \Think\Cache::getInstance('Redis');
            //echo $userId;
            $timeData = $redis->hGetAll('courses:histoty:'.$userId);

            $time = 0;
            if(! empty($timeData) 
                    and $timeData['time'] > 0 
                    and $classId == $timeData['class_id'] 
                    and $chapterId == $timeData['chapter_id']
            ) {
                $time = $timeData['time'];
            }
            //dump($time);
            //$time = D('ChapterUser')->getCoursesClientTime(['class_id' => $classId, 'chapter_id' => $chapterId, 'user_id'=>$userId]);

            $this->assign('ext', 'jpg');
            $this->assign('title', $classInfo['class_title']);
            $this->assign('chapterList', $chapterList);
            $this->assign('chapterId', $chapterId);
            $this->assign('user_id', $userId);
            $this->assign('info', $chapterInfo);
            $this->assign('time', $time);
            $this->display();

        } else {
            showMessage('404');
        }
        
        
    }

    /**
    * 更新听课时间
    * @access public
    * @return boolean
    **/
    public function updateOp()
    {
        ignore_user_abort(true); // 后台运行
        set_time_limit(0); // 取消脚本运行时间的超时上限

        $userId = session('user_id');
        $classId = I('class_id');
        $chapterId = I('chapter_id');
        //$userId = I('user_id');
        $time = I('time');

        $updata = [
            'class_id' => $classId,
            'chapter_id' => $chapterId,
            'user_id'   => $userId,
            'create_time' => date('Y-m-d H:i:s'),
            'time' => $time,
        ];
        
        try{
            $redis = \Think\Cache::getInstance('Redis');
            $result= $redis->hSet('courses:histoty:'.$userId, $updata);
            if (false === $result) {
                throw new \Exception("insert data error");
            } else {
                echo json_encode(['code'=>1, 'msg'=>'success']);
            }
        }catch(\Exception $e){
            echo json_encode(['code'=>-1, 'msg'=>$e->getMessage()]);
        }

    }

    private function getChapterList($condition)
    {
        return D('Chapter')->getCourseList($condition);
    }

}
