<?php

namespace robot\components\setVar;

use robot\components\ComponentAbstract;
use robot\Bot;
use robot\tools\Debug;
use robot\Variable;

class SetVar extends ComponentAbstract
{

    private $variable;
    private $value;
    

    public function __construct($id, $nextId, $variable, $value )
    {
        parent::__construct($id, $nextId);
        $this->variable = $variable;
        $this->value = $value;
    }

    public function do(Bot &$bot):int
    {

        Debug::info("Setando variavel {$this->variable} com valor {$this->value}");

        Variable::set($this->variable, $this->value);


        return $this->getNextId();
    }

}

