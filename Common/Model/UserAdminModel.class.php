<?php

namespace Common\Model;

class UserAdminModel extends CommonModel 
{
    protected $trueTableName = 'm_admin';

    protected $_validate = array(
        array('admin_name',         'require',  '帐号名称已经存在',     1, 'unique',    3),
        array('admin_password',     '6,18',     '密码长度6到18位',      1, 'length',    3),
        array('admin_password1',    'admin_password', '确认密码不正确', 1, 'confirm',   3),
        array('admin_truename',     '1,10',     '用户名长度1到10位',    1, 'length',    3),
        array('admin_mobile',       '11',       '手机号码必须11位',     2, 'length',    3),
        array('admin_email',        'email',    'email格式错误', 2),
        array('admin_description',  '1,60',     '备注超过60字', 2, 'length', 3),
    );

    protected $_auto = array(
        //array('admin_password', 'setPassword', 3, 'callback'),
        array('admin_password', 'setPassword', 3, 'callback'),
        array('admin_create_time', 'time', 3, 'function'),
        array('admin_state', 1),
    );

    public function setPassword($value)
    {
        return md5(C('ADMIN_LOGIN_KEY') . $value);
    }

    public function getUserInfo($condition, $field = '*')
    {
        return $this->field($field)->where($condition)->find();
    }

    public function getUserList($condition, $page, $limit = 1000, $order = 'admin_id desc', $field = '*')
    {
        return $this->field($field)->where($condition)->page($page, $limit)->select();
    }
    
    
    public function totalUserList($condition, $field = "admin_id") 
    {
        return $this->where($condition)->count($field);
    }

    public function setUserMessage($condition, $data, $field = "admin_login_time") 
    {
        return $this->field($field)->where($condition)->save($data);
    }

    public function addUser($data)
    {
        return $this->data($data)->add();
    }
}



