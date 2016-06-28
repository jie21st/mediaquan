<?php
namespace Common\Model;

class FansModel extends CommonModel
{
    protected $trueTableName = 'm_wechat_fans';
    
    public function getFansInfo($condition)
    {
        return $this->where($condition)->find();
    }
}
