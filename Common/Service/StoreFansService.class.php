<?php
namespace Common\Service;

class StoreFansService
{
    public function getFansByUserId($storeId, $userId)
    {
        $fansModel = new \Common\Model\FansModel();
        return $fansModel->getFansInfo(['store_id' => $storeId, 'user_id' => $userId]);
    }
    
    public function getParents($cid, $level = 3)
    {
        static $parents = [];
        if ($level-- > 0) {
            $fansModel = new \Common\Model\FansModel();
            $parentId = $fansModel->where(['fans_id' => $cid])->getField('parent_id');
            if ($parentId) {
                $parents[] = $parentId;
                $this->getParents($parentId, $level);
            }
        }
        return $parents;
    }
    
    public function getFans($pid, $level_limit = 3, $extfields = [], $func = null)
    {
        $fansModel = new \Common\Model\FansModel();
        $list = [];
        static $level = 1;
        $fields = ['fans_id'];
        $children = $fansModel->field(array_merge($fields, $extfields))->where(['parent_id' => $pid])->select();
        foreach ($children as $child) {
            $child['level'] = $level;
            if ($level_limit && $level > $level_limit) {
                break;
            }
            $level++;
            $child['child'] = $this->getFans($child['fans_id'], $level_limit, $extfields, $func);
            $level--;
            $list[] = $child;
            call_user_func($func, $child);
        }
        
        return $list;
    }
}
