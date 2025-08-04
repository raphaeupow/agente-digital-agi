<?php
namespace robot\components\start;

use robot\components\ComponentAbstract;
use robot\Bot;
use robot\tools\AgiServices;
use robot\tools\Debug;

class Start extends ComponentAbstract
{
    private $statusId;

    public function __construct($id, $nextIdComponent, $statusId)
    {
        parent::__construct($id, $nextIdComponent);
        $this->statusId = $statusId;
    }

    public function do(Bot &$bot):int
    {
        AgiServices::getInstance()->answer();
        
        if ($this->statusId > 0)
        {
            Debug::info("Setado novo valor para statusId: ".$this->statusId);
            $bot->setLastStatus($this->statusId);
        }else{
            Debug::info("Nenhum valor setado para statusId ");

        }
        return $this->getNextId();

    }
}
