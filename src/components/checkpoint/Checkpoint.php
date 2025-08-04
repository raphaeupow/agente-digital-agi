<?php

namespace robot\components\checkpoint;

use robot\components\ComponentAbstract;
use robot\Bot;
use robot\tools\Debug;

class Checkpoint extends ComponentAbstract
{
    private $statusId;

    public function __construct($id, $nextId, $statusId)
    {
        parent::__construct($id, $nextId);
        $this->statusId = $statusId;
    }

    public function do(Bot &$bot):int
    {
        Debug::info("Setting checkpoint statusId: ".$this->statusId);
        $bot->setLastStatus($this->statusId);
        return $this->getNextId();
    }
}