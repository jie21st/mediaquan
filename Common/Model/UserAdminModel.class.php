<?php

namespace Common\Model;

class UserAdminModel extends CommonModel 
{
    protected $trueTableName = 'm_admin';

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

    public function trueDel($condition)
    {
        return $this->where($condition)->delete();
    }
}



