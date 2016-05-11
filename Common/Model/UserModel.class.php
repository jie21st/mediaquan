<?php
namespace Common\Model;

/**
 * 用户信息模型
 *
 * @uses Model
 * @package
 * @version 1.0
 * @copyright 1997-2005 The PHP Group
 * @author Wang Jie <wangj@guanlizhihui.com> 2016-05-11
 * @license PHP Version 3.0 {@link http://www.php.net/license/3_0.txt}
 */
class UserModel extends CommonModel
{
    /**
     * 真实数据表名称
     *
     * @var string
     * @access protected
     */
    protected $trueTableName = 'm_users';

    /**
     * 通过用户ID获取用户详细信息
     *
     * @param mixed $uid
     * @param mixed $field
     * @access public
     * @return void
     */
    public function getUserByUid($uid, $field = '*')
    {
        static $users = array();
        if (empty($users[$uid])) {
            $users[$uid] = $this->field($field)->find($uid);
        }
        return $users[$uid];
    }

    /**
     * 根据条件取得用户信息
     *
     * @param mixed $condition
     * @access public
     * @return void
     */
    public function getUserInfo($condition, $field = '*')
    {
        return $this->field($field)->where($condition)->find();
    }

    /**
     * 根据条件取得用户列表
     *
     * @param type $condition
     * @param type $field
     * @param type $order
     * @return type
     */
    public function getUserList($condition, $field = '*', $order = '', $page = 1, $limit = 1000)
    {
        return $this->field($field)->where($condition)->order($order)->page($page)->limit($limit)->select();
    }

    /**
     * addUser
     *
     * @param mixed $data
     * @access public
     * @return void
     */
    public function addUser($data)
    {
        return $this->add($data);
    }

    /**
     * 编辑用户信息
     *
     * @param mixed $data
     * @param mixed $condition
     * @access public
     * @return void
     */
    public function editUser($data, $condition)
    {
        return $this->where($condition)->save($data);
    }
}
