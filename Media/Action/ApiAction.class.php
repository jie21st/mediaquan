<?php
namespace Media\Action;

class ApiAction extends CommonAction
{
    protected $needAuth = false;

    /**
     * 检查用户是否报名 
     * 
     * @access public
     * @return void
     */
    public function check_user_applyOp()
    {
        $classId = I('get.class_id', 0, 'intval');
        $userId  = I('get.user_id', 0, 'intval');
        if ($classId <= 0 || $userId <= 0) {
            $this->returnJson(10101, '参数错误'); 
        }
        
        $classService = D('Class', 'Service');
        $result = $classService->checkClassUser($classId, $userId);
        $applyed = $result ? 1 : 0;
        if ($result) {
            $this->returnJson(1, '成功', $applyed);
        } else {
            $this->returnJson(1, '成功', $applyed);
        }
    }

    /**
     * 地区 
     * 
     * @access public
     * @return void
     */
    public function areaOp()
    {
        $areaModel = M('area');
        $areaList = $areaModel->select();
        $list = [];
        foreach ($areaList as $area) {
            if ($area['area_parent_id'] == 0){
                $list[] = [
                    'id'    => $area['area_id'],
                    'name'  => $area['area_name']
                ]; 
            }
        }
        foreach($areaList as $area) {
            if ($area['area_deep'] == 2) {
                foreach($list as $key => $value) {
                    if($area['area_parent_id'] == $value['id']) {
                        $list[$key]['child'][] = [
                            'id'    => $area['area_id'],
                            'name'  => $area['area_name']
                        ]; 
                    }
                }
            }
        }
        echo json_encode(['data' => $list]);
    }

    /**
     * 生成二维码 
     * 
     * @access public
     * @return void
     */
    public function qrcodeOp()
    {
        $data = I('get.data', '');//$_GET['data'];//filter_input(INPUT_GET, 'data');
        if (! empty($data)) {
            Vendor('phpqrcode.phpqrcode');
            $size = abs(I('get.size', 4, 'intval'));
            $margin = I('get.margin', 2, 'intval');
            \QRcode::png($data, false, 'L', $size, $margin);
        }
    }
}
