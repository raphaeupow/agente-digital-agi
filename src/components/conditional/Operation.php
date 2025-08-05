<?php

namespace robot\components\conditional;

use robot\Variable;

class Operation
{
    private $variableIdA;
    private $operationBettween;
    private $variableIdB;
    private $operationConnection;

    public function __construct($variableIdA, $operationBettween, $variableIdB, $operationConnection)
    {
        $this->variableIdA = $variableIdA;
        $this->operationBettween = $operationBettween;
        $this->variableIdB = $variableIdB;
        $this->operationConnection = $operationConnection;
    }

    public function getVariableA()
    {
        return Variable::get($this->variableIdA);
    }

    public function getVariableB()
    {
        return Variable::get($this->variableIdB);
    }

    public function getOperationBettwwen()
    {
        return $this->operationBettween;
    }

    public function getOperationConnection()
    {
        return $this->operationConnection;
    }


}
