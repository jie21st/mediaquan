<?php
/**
 * 用户听课时间表
 */
namespace Common\Model;

class ChapterUserModel extends \Think\Model
{

    protected $trueTableName = 'm_chapter_user';


    public function  getCoursesClientTime($condition, $field="*",$order='create_time desc')
    {
        return $this->field($field)->where($condition)->order($order)->find();

    }

}
