<?php

namespace robot;

use robot\tools\AgiServices;
use robot\tools\Database;
use robot\tools\Debug;
use robot\tools\Provider;
use robot\tracer\Attendance;
use robot\tracer\Step;

class Bot
{
    private $id;
    private $lastStatusId;
    private $script;
    private $maxLoop = 20;

    public function __construct($scriptId)
    {
        try {
            //iniciando debug
            Debug::init(AgiServices::getInstance()->getPhone());

            //setando variaveis de ambiente
            Variable::set("sys_script_id", $scriptId);
            Variable::set("sys_phone", AgiServices::getInstance()->getPhone());
            Variable::set("sys_unique_id", AgiServices::getInstance()->getUniqueId());
            Variable::set("sys_key_id", AgiServices::getInstance()->getKeyId());
            Variable::set("sys_is_test", AgiServices::getInstance()->isTest());

            //conectando ao banco de dados  
            Database::init(getenv('DB_HOST'), getenv('DB_USER'), getenv('DB_PASS'),  getenv('DB_NAME'));

            //criando registro de atendimento
            $this->id = Attendance::create(
                $scriptId,
                AgiServices::getInstance()->getPhone(),
                AgiServices::getInstance()->getUniqueId(),
                AgiServices::getInstance()->getKeyId(),
                AgiServices::getInstance()->isTest()
            );

            //carregando fila de atendimento
            $this->script = new Script($scriptId, Variable::get("sys_is_test"));

            Debug::title("Bot", "");
            if (Variable::get("sys_is_test")==true){
                Debug::subTitle("(TEST)(TEST)(TEST)(TEST)(TEST)(TEST)(TEST)(TEST)(TEST)");
            }
            Debug::subTitle("attendance_id: ".$this->id);
            Debug::subTitle("Script: ".$this->getScript()->getId());
            Debug::subTitle("phone: ".Variable::get("sys_phone"));
            Debug::subTitle("unique_id: ".Variable::get("sys_unique_id"));
            Debug::subTitle("key_id: ".Variable::get("sys_key_id"));


            Provider::init($this->getScript()->getProvider());
        } catch (\Exception $e) {
            $this->cleanup('bot');
            throw $e;
        }
    }



    public function do()
    {

        $cont = 0;
        $nextId  = 1;
        //Debug::debug($this->queue->getScript()->components);
        do {
            Step::create(
                $this->id, 
                $this->getScript()->getComponent($nextId)->getComponentName(), 
                $this->getScript()->getComponent($nextId)->getId(),
                $this->getScript()->getId()
            );
            
            Debug::title($this->getScript()->getComponent($nextId)->getId().":".$this->getScript()->getComponent($nextId)->getComponentName(), $this->getLastStatus());

            $nextId = $this->getScript()->getComponent($nextId)->do($this);
            
            Step::finish();

            if ($cont++ > $this->maxLoop){
                Debug::error("Max loop reached ".$cont);
                break;
            } 
           
            if (AgiServices::getInstance()->channelIsUp()){
                Debug::error("Channel is not alive");
                break;
            }
        } while ($nextId != -1 );
    }


    public function cleanup($hangout_direction) {

        Attendance::finish(
            $this->id,  
            $hangout_direction,
            $this->lastStatusId,
            Variable::dump()
        );
    }

    public function __destruct() {
        $this->cleanup(AgiServices::getInstance()->channelIsUp() ? 'bot' : 'client');
    }

    public function setLastStatus($statusId) {
        $this->lastStatusId = $statusId;
    }

    public function getLastStatus() {
        return $this->lastStatusId;
    }

    public function getScript()
    {
        return $this->script;
    }

    public function getId() {
        return $this->id;
    }
}

