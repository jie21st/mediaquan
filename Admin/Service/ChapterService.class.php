<?php
namespace Admin\Service;
use Common\Service\UploadService as uploads;
/**
 * Created by PhpStorm.
 * User: LiYi
 * Date: 16/6/23
 * Time: 下午2:49
 */
class ChapterService {
	/**
	 * 获取章节列表
	 * @param  [type]  $condition [description]
	 * @param  integer $page      [description]
	 * @param  integer $limit     [description]
	 * @return [type]             [description]
	 */
	public function getChapterList($condition, $page = 1, $limit = 10)
	{
		$chapterModel = new \Common\Model\ChapterModel();
		$field      = "chapter_id, class_id, chapter_title, teacher_name, start_datetime, status, create_datetime, creator_id";
		$total      = $chapterModel->getCourseCount($condition);
		$list       = $chapterModel->getChapterList($condition, $field, 'chapter_id desc', $page, $limit);

//		$condition = '';
		foreach ($list as $key => $value) {
			$adminModel = new \Common\Model\UserAdminModel();
			$list[$key]['creater_user'] = $adminModel->getUserInfo(array('admin_id' => $value['creator_id']))['admin_truename'];
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