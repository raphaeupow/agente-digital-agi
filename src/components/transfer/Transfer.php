<?php

namespace robot\components\transfer;

use robot\components\ComponentAbstract;
use robot\Bot;
use robot\tools\AgiServices;
use robot\tools\Debug;

class Transfer extends ComponentAbstract
{
    private $ramal;
    private $type;

    public function __construct($id, $nextId, $ramal, $type)
    {
        parent::__construct($id, $nextId);
        $this->ramal = $ramal;
        $this->type = $type;
    }

    public function do(Bot &$bot):int
    {
        Debug::info("Setting transfer ramal: ".$this->ramal);
        Debug::info("Setting transfer type: ".$this->type);
        
        
        $result = AgiServices::getInstance()->transfer($this->ramal, $this->type);
        
        // Verifica o resultado da transferência
        if ($result['result'] == 1) {
            Debug::info("Transferência realizada com sucesso para o ramal: " . $this->ramal);
        } else {
            Debug::error("Falha na transferência para o ramal: " . $this->ramal . ". Código: " . $result['code'] . ", Resultado: " . $result['result']);
        }
        
        // Retorna o próximo ID para continuar o fluxo
        return $this->getNextId();
    }
}