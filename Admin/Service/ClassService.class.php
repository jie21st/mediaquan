<?php
namespace Admin\Service;
use Common\Service\UploadService as uploads;
/**
 * Created by PhpStorm.
 * User: LiYi
 * Date: 16/6/23
 * Time: 下午2:49
 */
class ClassService {
	/**
	 * 获取课程列表
	 * @param  [type]  $condition [description]
	 * @param  integer $page      [description]
	 * @param  integer $limit     [description]
	 * @return [type]             [description]
	 */
	public function getClassList($condition, $page = 1, $limit = 10)
	{
		$classModel = new \Common\Model\ClassModel();
		$field      = "class_id,class_title,teacher_name,commis_rate,study_num,class_addtime,class_price,class_sort";
		$total      = $classModel->totalClassList($condition);
		$list       = $classModel->getClassList($condition, $field, 'class_id desc', $page, $limit);

//		$condition = '';
		foreach ($list as $key => $value) {
			$list[$key]['class_addtime'] = date('Y-m-d H:i:s' , $value['class_addtime']);
		}

		$data = [];

		if ($list and !empty($list)) {
			$data = $list;
		}

		return array('code' => 1, 'total' => $total, 'msg' => '', 'rows' => $data);
	}

	/**
	 * 上传操作
	 * @param  array  $config [description]
	 * @return [type]         [description]
	 */
	public function uploadImages($config = array())
	{
		if(empty($config))
			return false;

		$result = (new uploads)->imgUpload($config);

		if (isset($result['error'])) {
			return array('code'=>'0', 'msg' => $result['error']);
		} else {
			foreach ($result as $key => $value) {
				$data['imagePath'] = $value['savename'];
				$data['siteUrl'] = C('UPLOADS_SITE_URL') .  $config['savePath'];
			}
			return array('code'=>'1', 'msg' => $message, 'data'=>$data);
		}
	}
}