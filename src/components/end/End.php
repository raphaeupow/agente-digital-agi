<?php
namespace robot\components\end;

use robot\components\ComponentAbstract;
use robot\components\Result;
use robot\Bot;
use robot\tools\Debug;
use robot\tools\AgiServices;

class End extends ComponentAbstract
{
    public function __construct($id)
    {
        parent::__construct($id, -1);
    }

    public function do(Bot &$bot):int
    {
        AgiServices::getInstance()->hangup();
        Debug::info("End attendance");
        return $this->getNextId();
    }
}

