<?php
/**
 * @Author: zenghp
 * @Date:   2016-02-29 14:35:02
 * @Last Modified by:   zenghp
 * @Last Modified time: 2016-04-08 16:35:47
 */

namespace Admin\Action;

use Think\Action;

class CommonAction extends Action
{

    public function _initialize()
    {
        if (empty(session('admin_user')) or session('admin_user.user_id') == '') {
            $this->redirect('Login/loginIn');
        }
    }

    public function returnData($data, $total, $code = 1, $msg = 'success', $type = 'json')
    {
        $this->ajaxReturn(['code'=>$code, 'msg' => $msg, 'rows'=>$data, 'total'=>$total], $type);
    }
}
