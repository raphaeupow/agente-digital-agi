<?php

namespace robot\components\conditional;

use robot\components\ComponentAbstract;
use robot\Bot;

class Conditional extends ComponentAbstract
{
    private $options;

    public function __construct($id, $faultNextId)
    {
        parent::__construct($id, $faultNextId);
        $this->options = [];
    }

    public function addOption(Option $option)
    {
        $this->options[] = $option;
    }
    
    public function addEquationOption($nextId, $equation)
    {
        $option = new Option($nextId, $equation);
        $this->options[] = $option;
    }

    public function do(Bot &$bot):int
    {
        foreach($this->options as $option)
        {
            $return = $option->do();
            if ($return > 0) 
            {
                return $return;
            }
        }
        return $this->getNextId();
    }
}
