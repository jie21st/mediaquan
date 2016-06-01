<?php
namespace Common\Model;

class TeacherModel extends CommonModel
{
    public function getTeacherInfo($condition)
    {
        return $this->where($condition)->find();
    }
}

