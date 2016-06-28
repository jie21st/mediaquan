<?php
namespace Common\Model;

class MessageTemplatesModel extends CommonModel
{
    public function getOneTemplates($tplCode)
    {
        return $this->where(['tpl_code' => $tplCode])->find();
    }
}