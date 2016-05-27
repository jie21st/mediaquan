<?php
namespace Media\Action;

class ArticleAction extends CommonAction
{
    public function detailOp()
    {
        $arcticleId = I('get.id', 0, 'intval');
        if ($arcticleId <= 0) {
            showMessage('参数错误');
        }
        $articleModel = new \Common\Model\ArticleModel();
        $articleInfo = $articleModel->getOneArticle($arcticleId);
        if (empty($articleInfo)) {
            showMessage('该文章不存在');
        }
        $this->assign('article', $articleInfo);
        $this->display();
    }
}
