<?php
namespace Common\Model;

/**
 * 课程模型类
 *
 * @uses Model
 * @package
 * @version $id$
 * @copyright 1997-2005 The PHP Group
 * @author Wang Jie <wangj@guanlizhihui.com> 2015-11-06
 * @license PHP Version 3.0 {@link http://www.php.net/license/3_0.txt}
 */
class ClassModel extends CommonModel
{
    /**
     * 课程表名称
     *
     * @var string
     * @access protected
     */
    protected $trueTableName = 'm_class';

    /**
     * 取得单个课程信息
     *
     * @param mixed $condition
     * @param string $field
     * @access public
     * @return void
     */
    public function getClassInfo($condition, $field = '*', $order = "")
    {
        return $this->field($field)->where($condition)->order($order)->find();
    }

    /**
     * 取得单个课程信息
     *
     * @param mixed $condition
     * @param string $field
     * @access public
     * @return void
     */
    public function getClassInfoArr($condition, $field = '*')
    {
        return $this->field($field)->where($condition)->select();
    }

    /**
     * 添加课程用户（报名用户）
     *
     * @param mixed $data
     * @access public
     * @return void
     */
    public function addClassUser($data)
    {
        return (new \Think\Model)->table('m_class_user')->add($data);
    }

    public function totalClassOrderList($condition = array(), $field = 'order_id')
    {
        return (new \Think\Model)->table('m_class_order')->where($condition)->count($field);
    }

    public function updateClassUser($condition, $data)
    {
        return (new \Think\Model)->table('m_class_user')->where($condition)->save($data);
    }

    public function getClassUserCount($condition, $field = '*')
    {
        return (new \Think\Model)->table('m_class_user')->where($condition)->count($field);
    }

    /**
     * 根据条件取得课程用户
     *
     * @param mixed $condition
     * @access public
     * @return void
     */
    public function getClassUser($condition)
    {
        return (new \Think\Model)->table('m_class_user')->where($condition)->find();
    }

    /**
     * 获取课程列表
     *
     * @param type $condition
     * @param type $field
     * @param type $order
     * @param type $page
     * @param type $limit
     */
    public function getClassList($condition = array(), $field = '*', $order = '', $page = 1, $limit = 1000)
    {
        return $this->field($field)->where($condition)->order($order)->page($page)->limit($limit)->select();
    }

    /**
     * 统计课程列表
     *
     * @param type $condition
     */
    public function totalClassList($condition = array())
    {
        return $this->where($condition)->count();
    }

    /**
     * 取得允许分销商转销的课程列表
     *
     * @param array $condition
     * @param type $field
     * @param type $order
     * @param type $page
     * @param type $limit
     * @return type
     */
    public function getAllowResellClassList($condition = array(), $field = '*', $order = '', $page = 1, $limit = 1000)
    {
        $condition['allow_resell'] = 1;
        return $this->getClassList($condition, $field, $order, $page, $limit);
    }

    /**
     * 取得课程用户列表
     *
     * @param mixed $condition
     * @access public
     * @return void
     */
    public function getClassUserList($condition = array(), $field = '*', $order = 'apply_time desc', $page = 1, $limit = 1000)
    {
        return (new \Think\Model)->table('m_class_user')->field($field)->where($condition)->order($order)->page($page)->limit($limit)->select();
    }
    
    public function editClass($data, $condition)
    {
        return $this->where($condition)->save($data);
    }
}
