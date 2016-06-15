<?php
namespace Test\Action;

class OrderAction extends \Think\Action
{
    public function applyOp()
    {
        $cs = new \Common\Service\ClassService();
        $om = new \Common\Model\OrderModel();
        
        $order = $om->getOrderInfo(['order_id' => 26]);
        $cs->addClassUser($order);
    }
}

