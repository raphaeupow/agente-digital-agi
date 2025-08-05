<?php

namespace robot\components\conditional;

use robot\tools\Debug;
use robot\Variable;

class Option
{
    private $nextId;
    private $operations;
    private $equation;

    public function __construct($nextId, $equation = null)
    {
        $this->nextId = $nextId;
        $this->operations = [];
        $this->equation = $equation;
        
        if ($equation) {
            $this->parseEquation($equation);
        }
    }

    public function do()
    {
        if ($this->equation) {
            return $this->evaluateEquation() ? $this->nextId : false;
        }
        
        if ($this->recussiveOperations($this->operations))
        {
            return $this->nextId;
        }
        return false;
    }

    private function evaluateEquation()
    {
        // Substituir variáveis pelos seus valores
        $equation = $this->equation;
        preg_match_all('/\b([a-zA-Z_][a-zA-Z0-9_]*)\b/', $equation, $matches);
        
        foreach ($matches[1] as $variable) {
            $value = Variable::get($variable);
            // Se a variável não existir, substituir por null
            if ($value === null) {
                $value = 'null';
            } elseif (is_string($value)) {
                $value = "'" . $value . "'";
            }
            $equation = preg_replace('/\b' . $variable . '\b/', $value, $equation);
        }
        
        // Avaliar a equação
        try {
            $result = eval('return ' . $equation . ';');
            Debug::success("Equação: " . $this->equation . " = " . ($result ? 'true' : 'false'));
            return $result;
        } catch (\Exception $e) {
            Debug::error("Erro ao avaliar equação: " . $e->getMessage());
            return false;
        }
    }

    private function parseEquation($equation)
    {
        // Padrões para identificar operadores e variáveis
        $operators = ['>', '>=', '<', '<=', '==', '!=', '&&', '\|\|'];
        $operatorPattern = '(' . implode('|', $operators) . ')';
        
        // Dividir a equação em tokens
        preg_match_all('/\b([a-zA-Z_][a-zA-Z0-9_]*)\b|\b(\d+)\b|(' . $operatorPattern . ')/', $equation, $matches, PREG_SET_ORDER);
        
        $tokens = [];
        foreach ($matches as $match) {
            if (!empty($match[0])) {
                $tokens[] = $match[0];
            }
        }
        
        // Processar tokens para criar operações
        $currentOperation = null;
        $connection = '';
        
        for ($i = 0; $i < count($tokens); $i++) {
            $token = $tokens[$i];
            
            // Se for uma variável ou número
            if (preg_match('/\b([a-zA-Z_][a-zA-Z0-9_]*)\b|\b(\d+)\b/', $token)) {
                if ($currentOperation === null) {
                    $currentOperation = [
                        'variableA' => $token,
                        'operator' => '',
                        'variableB' => '',
                        'connection' => ''
                    ];
                } elseif (empty($currentOperation['operator'])) {
                    // Ignorar, já temos a variável A
                } elseif (empty($currentOperation['variableB'])) {
                    $currentOperation['variableB'] = $token;
                    // Adicionar a operação
                    $this->addOperation(new Operation(
                        $currentOperation['variableA'],
                        $currentOperation['operator'],
                        $currentOperation['variableB'],
                        $currentOperation['connection']
                    ));
                    $currentOperation = null;
                }
            }
            // Se for um operador
            elseif (preg_match('/' . $operatorPattern . '/', $token)) {
                if ($token == '&&' || $token == '||') {
                    $connection = $token;
                    if ($currentOperation !== null) {
                        $currentOperation['connection'] = $connection;
                    }
                } else {
                    if ($currentOperation !== null) {
                        $currentOperation['operator'] = $token;
                    }
                }
            }
        }
    }

    private function recussiveOperations($operations, $expression="")
    {
        $operation = array_shift($operations);
        
        $varA = $operation->getVariableA();
        $varB = $operation->getVariableB();
        $connection = $operation->getOperationConnection();
        $operator = $operation->getOperationBettwwen();

        $expression .=  $varA . " " . $operator . " " . $varB ;
        $expression .= " ". $connection. " ";

        if (in_array($operation->getOperationConnection(), ["||", "&&"]) && !empty($operations)) // verifica se tem operador de conexao e se há mais operações
        {
 
            $result1 = $this->recussiveOperations($operations, $expression);
            $result2 = $this->execOperation($varA, $operator, $varB);
            return $this->execConnection($result1, $connection, $result2);
        }
        $return  = $this->execOperation($varA, $operator, $varB);
        if ($return) 
            Debug::success($expression);
        else
            Debug::info($expression);

        return $return;
    }

    public function addOperation(Operation $operation)
    {
        $this->operations[] = $operation;
    }

    private function execOperation($variableA, $operation, $variableB)
    {
        switch ($operation)
        {
            case ">":
                $result = $variableA > $variableB;
            break;
            case ">=":
                $result = $variableA >= $variableB;
            break;
            case "<":
                $result = $variableA < $variableB;
            break;
            case "<=":
                $result = $variableA <= $variableB;
            break;
            case "==":
                $result = $variableA == $variableB;
            break;
            case "!=":
                $result = $variableA != $variableB;
            break;
        }
        return $result;
    }

    private function execConnection($variableA, $operation, $variableB)
    {
        switch ($operation)
        {
            case "&&":
                $result = $variableA && $variableB;
            break;
            case "||":
                $result = $variableA || $variableB;
            break;
        }
        return $result;
    }
}