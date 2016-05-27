<?php
namespace Common\Model;

class ArticleModel extends CommonModel
{
    public function getOneArticle($id)
    {
        return $this->find($id);
    }
}

