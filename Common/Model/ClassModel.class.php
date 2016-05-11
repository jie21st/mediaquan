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
        return (new \Think\Model)->table('glzh_class_user')->add($data);
    }

    /**
     * addOrder
     *
     * @param mixed $data
     * @access public
     * @return void
     */
    public function addOrder($data)
    {
//        $data['order_state'] = C('ORDER_STATE_NEW');
        $data['create_time'] = time();
        return (new \Think\Model)->table('glzh_class_order')->add($data);
    }

    /**
     * getOrderInfo
     *
     * @param mixed $condition
     * @param string $field
     * @access public
     * @return void
     */
    public function getOrderInfo($condition, $field = '*')
    {
        return (new \Think\Model)->table('glzh_class_order')->field($field)->where($condition)->find();
    }

    /**
     * 取得订单列表
     *
     * @param type $condition
     * @param type $field
     * @param type $order
     * @return type
     */
    public function getOrderList($condition = array(), $field = '*', $order = 'order_id desc', $page = 1, $limit = 1000)
    {
        return (new \Think\Model)->table('glzh_class_order')->field($field)->where($condition)->order($order)->page($page)->limit($limit)->select();
    }

    public function totalClassOrderList($condition = array(), $field = 'order_id')
    {
        return (new \Think\Model)->table('glzh_class_order')->where($condition)->count($field);
    }

    /**
     * editOrder
     *
     * @param mixed $data
     * @param mixed $condition
     * @access public
     * @return void
     */
    public function editOrder($data, $condition)
    {
        return (new \Think\Model)->table('glzh_class_order')->where($condition)->save($data);
    }

    public function updateClassUser($condition, $data)
    {
        return (new \Think\Model)->table('glzh_class_user')->where($condition)->save($data);
    }

    public function getClassUserCount($condition, $field = '*')
    {
        return (new \Think\Model)->table('glzh_class_user')->where($condition)->count($field);
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
        return (new \Think\Model)->table('glzh_class_user')->where($condition)->find();
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
        return (new \Think\Model)->table('glzh_class_user')->field($field)->where($condition)->order($order)->page($page)->limit($limit)->select();
    }

    /**
     * 取得课程班级信息
     *
     * @param mixed $condition
     * @access public
     * @return void
     */
    public function getClassGroupInfo($condition, $field = '*')
    {
        return (new \Think\Model)->table('glzh_class_group')->field($field)->where($condition)->find();
    }

    //public function getClassGroupList($condition = array(), $field = '*', $order = '')
    public function getClassGroupList($condition = array(), $field = '*', $order = '', $page = 1, $limit = 1000)
    {
        //return (new \Think\Model)->table('glzh_class_group')->field($field)->where($condition)->order($order)->select();
        return (new \Think\Model)->table('glzh_class_group')->field($field)->where($condition)->page($page)->limit($limit)->order($order)->select();
    }

    public function getClassGroupTotal($condition = array(), $field = 'class_id')
    {
        return (new \Think\Model)->table('glzh_class_group')->field($field)->where($condition)->count();
    }

    public function addGroup($data)
    {
        return (new \Think\Model)->table('glzh_class_group')->filter(C('DEFAULT_FILTER'))->data($data)->add();
    }

    /**
     * 更新课程班级信息
     *
     * @param type $data
     * @param type $condition
     * @return type
     */
    public function updateClassGroupInfo($data, $condition)
    {
        return (new \Think\Model)->table('glzh_class_group')->where($condition)->save($data);
    }

    public function addClassUserPrice($data)
    {
        return (new \Think\Model)->table('glzh_class_price')->filter(C('DEFAULT_FILTER'))->data($data)->add();
    }

    public function getClassUserPrice($condition, $field = '*')
    {
        return (new \Think\Model)->table('glzh_class_price')->field($field)->where($condition)->select();
    }

    public function upClassUserPrice($condition, $data)
    {
        return (new \Think\Model)->table('glzh_class_price')->where($condition)->filter(C('DEFAULT_FILTER'))->save($data);
    }

    /**
     * 取得未报满班级信息
     *
     * @param type $classId
     * @return int
     */
//    public function getAvailableGroupInfo($condition = array())
    //    {
    //        // 取得课程最新班级标识
    //        $maxGroupCode = (new Model())->table('glzh_class_group')->where($condition)->max('group_code');
    //        if (! $maxGroupCode) {
    //            return null;
    //        }
    //
    //        $groupInfo = $this->getClassGroupInfo([
    //            'class_id' => $classId,
    //            'group_code' => $maxGroupCode
    //        ]);
    //        if ($groupInfo['group_num'] < $groupInfo['group_mcount']) {
    //            return $groupInfo;
    //        } else {
    //            return null;
    //        }
    //    }
}
