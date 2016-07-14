<?php
/**
 * 店铺首页
 */
namespace Media\Action;

class HomepageAction extends BaseStoreAction
{
    public function indexOp()
    {
        $condition = array();
        $condition['store_id'] = $this->storeInfo['store_id'];
        $classModel = new \Common\Model\ClassModel();
        $chapterModel = new \Common\Model\ChapterModel;
        $classService = new \Common\Service\ClassService();
        
        $field = 'class_id,class_title,class_image,teacher_id,teacher_name,class_price,study_num';
        $classList = $classModel->getClassOnlineList($condition, $field, 'class_sort desc');
        foreach ($classList as &$classInfo) {
            // 课程是否已购买
            $classInfo['is_buy'] = $classService->checkClassUser($classInfo['class_id'], session('user_id'));
            
            // 课程章节数
            $classInfo['chapter_num'] = $chapterModel->getCourseCount([
                'class_id' => $classInfo['class_id'],
                'status' => 1,
            ]);
        }
        
        $storeNav = (new \Media\Service\StoreService)->getStoreNav($this->storeInfo['store_id']);
        $this->assign('class_list', $classList);
        $this->assign('store_nav', $storeNav);
        $this->display();
    }
}
