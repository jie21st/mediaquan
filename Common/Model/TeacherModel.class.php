<?php
namespace Common\Model;

class TeacherModel extends CommonModel
{
    protected $tableName = 'store_teacher';
    
    public function getTeacherInfo($condition)
    {
        return $this->where($condition)->find();
    }
}

