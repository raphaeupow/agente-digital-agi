<?php

namespace robot\components\repeater;

use robot\components\ComponentAbstract;
use robot\Bot;
use robot\tools\Debug;
class Repeater extends ComponentAbstract
{
    private $totalRepeat;
    private $faultNextId;
    private $repeatedTimes=0;

    public function __construct($id, $nextId, $totalRepeat, $faultNextId)
    {
        $this->totalRepeat = $totalRepeat;
        $this->faultNextId = $faultNextId;
        parent::__construct($id, $nextId);
    }

    public function do(Bot &$bot): int
    {
        Debug::info("Time : ".$this->repeatedTimes. " of ".$this->totalRepeat);
        
        if ($this->repeatedTimes >= $this->totalRepeat) {
            return $this->faultNextId;
        }

        $this->repeatedTimes += 1;
        return $this->getNextId();
    }
}
