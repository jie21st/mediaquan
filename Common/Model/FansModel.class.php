<?php
namespace Common\Model;

/**
 * 店铺粉丝模型
 */
class FansModel extends CommonModel
{
    protected $trueTableName = 'm_store_fans';
    
    public function getFansInfo($condition)
    {
        return $this->where($condition)->find();
    }
}
