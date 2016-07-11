<?php
namespace Think;

class Process
{
    public static function getInstance($name) {
        $class  =   'Think\\Process\\'.ucwords(strtolower($name));
        if(class_exists($class))
            $cache = new $class();
        else
            E('module processor不存在:'.$name);
        return $cache;
    }
}
