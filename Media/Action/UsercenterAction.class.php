<?php
/**
 * 用户中心
 */
namespace Media\Action;

class UsercenterAction extends \Media\Action\StoreAction
{
    public function indexOp()
    {
        $ucpage = M('store_page')->where(['store_id' => $this->storeInfo['store_id'], 'type' => 3])->find();
        if (!empty($ucpage['params'])) {
                $ucpage['params'] = json_decode($ucpage['params'], true);
        }
        $this->assign('page_title', $ucpage['params'][0]['title']);
        $this->assign('ucpage', $ucpage);
        $this->display();
    }
}