<?php
namespace Common\Model;

class ArticleModel extends CommonModel
{
    /**
     * 取单个内容
     * 
     * @param type $id 文章id
     * @return type
     */
    public function getOneArticle($id)
    {
        return $this->find($id);
    }
    
    /**
     * 列表
     * 
     * @param type $condition
     * @param type $field
     * @param type $order
     * @return type
     */
    public function getArticleList($condition, $field = '', $order ='article_sort asc,article_time desc'){      
           return $this->field($field)->where($condition)->order($order)->select();
    }
}

