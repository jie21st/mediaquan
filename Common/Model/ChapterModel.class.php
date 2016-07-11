<?php
namespace Common\Model;

/**
 * CourseModel 
 * 
 * @uses Model
 * @package 
 * @version $id$
 * @copyright 1997-2005 The PHP Group
 * @author Wang Jie <wangj@guanlizhihui.com> 2015-09-23 
 * @license PHP Version 3.0 {@link http://www.php.net/license/3_0.txt}
 */
class ChapterModel extends CommonModel
{
    /**
     * trueTableName 
     * 
     * @var string
     * @access protected
     */
    protected $trueTableName = 'm_class_chapter';

    public function getCourseInfo($condition, $field = '*')
    {
        return $this->field($field)->where($condition)->find();
    }

    /**
     * getCourseList 
     * 
     * @param mixed $condition 
     * @param string $field 
     * @access public
     * @return void
     */
    public function getCourseList($condition, $field = '*')
    {
        return $this->field($field)->where($condition)->order('chapter_id asc')->select();
    }
    
    /**
     * 取得章节数量
     * 
     * @param type $condition
     * @return type
     */
    public function getCourseCount($condition)
    {
        return $this->where($condition)->count();
    }

    /**
     * 判断课程是否可用 
     * 
     * @param mixed $condition 
     * @access public
     * @return void
     */
    public function isValidCourse($condition)
    {
        $condition['is_delete'] = 0; // 未删除
        $result = $this->getCourseInfo($condition);
        return $result ? true : false;
    }

    /**
     * @param array $condition  查询条件
     * @param string $field   查询字段
     * @param string $order   排序
     * @param int $page 页码
     * @param int $limit  条数
     * @return mixed
     */
    public function getChapterList($condition = array(), $field = '*', $order = '', $page = 1, $limit = 1000)
    {
         return $this->field($field)->where($condition)->order($order)->page($page)->limit($limit)->select();
    }
}
