<?php
namespace Common\Model;

class StoreModel extends CommonModel
{
    public function getStoreInfo($condition) {
        return $this->where($condition)->find();
    }
}
