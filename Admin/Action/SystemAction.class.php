<?php

namespace Admin\Action;

class SystemAction extends CommonAction
{
    public function getSystemNavOp()
    {
        $navList = D('Func')->getFuncList();
        
        foreach($navList as $key => $value) {
            //dump($value);

        }

    }
}
