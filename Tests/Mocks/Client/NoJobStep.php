<?php
namespace Vpg\Disturb\Test;

use \Vpg\Disturb;
use \Vpg\Disturb\Message\MessageDto as Message;

class NoJobStep extends TestStepAbstract
{
    public function execute(array $paramHash) : array
    {
        $resultHash = [];
        $resultHash = [
            'status' => Message::MSG_RETURN_SUCCESS,
            'data' => [ 'noJob' => get_class($this)]
        ];
        return $resultHash;
    }
}


