<?php
namespace Media\Action;

class EmptyAction extends \Think\Action
{
    public function _empty()
    {
        send_http_status(404);
    }
}