<?php

namespace Common\Model;

class UserAdminModel extends CommonModel 
{
    protected $trueTableName = 'm_admin_user';


    public function getUserInfo($condition, $field = '*')
    {
        return $this->field($field)->where($condition)->find();
    }

    public function getUserList($condition, $page, $limit = 1000, $order = 'user_id desc', $field = '*')
    {
        return $this->field($field)->where($condition)->page($page, $limit)->select();
    }
    
    
    public function totalUserList($condition, $field = "user_id") 
    {
        return $this->where($condition)->count($field);
    }
}



