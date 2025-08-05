<?php
namespace robot;


use robot\components\checkpoint\Checkpoint;
use robot\components\ComponentInterface;
use robot\components\conditional\Conditional;
use robot\components\conditional\Option;
use robot\components\conditional\Operation;
use robot\components\decision\Alternative;
use robot\components\decision\Decision;
use robot\components\end\End;
use robot\components\integration\Integration;
use robot\components\play\Play;
use robot\components\repeater\Repeater;
use robot\components\setVar\SetVar;
use robot\components\start\Start;
use robot\components\transfer\Transfer;
use robot\tools\Database;

class Script
{
    private $id;
    private $name;
    private $language;
    private $voice;
    private $provider;
    public $components;
    private $test;

    public function __construct($id, $test=false) {
        $this->test = $test;
        
        $stmt = Database::get()->prepare("SELECT id, name, language, voice, provider, production, test FROM scripts WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $data = $stmt->fetch();
    
        if ($data) {
            $this->id       = $data['id'];
            $this->name     = $data['name'];
            $this->language = $data['language'];
            $this->voice    = $data['voice'];
            $this->provider = $data['provider'];
            $this->components = $this->load(json_decode($this->test?$data['test']:$data['production']));
            
        } else {
            throw new \Exception("Script com ID $id não encontrado.");
        }
    }

    protected function load($flow): array {
        $return = [];
        if ($flow) {

            foreach ($flow as  $component) {
        
                switch ($component->component) {
                    case "conditional":
                        $return[$component->id] = new Conditional($component->id, $component->faultNextId);
                        if (is_array($component->options)) {
                            foreach ($component->options as $option) {
                                if (isset($option->equation)) {
                                    $return[$component->id]->addEquationOption($option->nextId, $option->equation);
                                } else {
                                    $optionObj = new Option($option->nextId);
                                    if (is_array($option->operations)) {
                                        foreach ($option->operations as $operation) {
                                            $optionObj->addOperation(new Operation(
                                                $operation->variableIdA,
                                                $operation->operationBettween,
                                                $operation->variableIdB,
                                                $operation->operationConnection
                                            ));
                                        }
                                    }
                                    $return[$component->id]->addOption($optionObj);
                                }
                            }
                        }
                        break;
                    case "repeater":
                        $return[$component->id] = new Repeater($component->id, $component->nextId, $component->totalRepeat, $component->faultNextId);
                    break;
                    case "play":
                        $return[$component->id] = new Play($component->id, $component->nextId, $component->text ?? '', $component->style ?? null, $component->dtmfStop ?? null);
                    break;
                    case "checkpoint":
                        $return[$component->id] = new Checkpoint($component->id, $component->nextId, $component->statusId);
                    break;
                    case "start":
                        $return[$component->id] = new Start($component->id, $component->nextId, $component->statusId);
                    break;
                    case "setVar":
                        $return[$component->id] = new SetVar($component->id, $component->nextId, $component->variable, $component->value);
                    break;
                    case "end":
                        $return[$component->id] = new End($component->id);
                    break;
                    case "decision":
                        $return[$component->id] = new Decision($component->id, muteNextId: $component->muteNextId,  nextId: $component->nextId, timeout: $component->timeout, timeMute: $component->timeMute, timeSilenceBetweenSpeech: $component->timeSilenceBetweenSpeech, timeDTMF: $component->timeDTMF);
                        if (is_array($component->alternatives))
                            foreach($component->alternatives as $alternative) 
                                $return[$component->id]->addAlternative(new Alternative($alternative->nextId, $alternative->words));
                    break;
                    case "integration":
                        $return[$component->id] = new Integration($component->id, $component->nextId, $component->faultNextId, $component->loadingNextId, $component->method, $component->timeout, $component->parameters, $component->returns, $component->isAsync);
                    break;
                    case "transfer":
                        $return[$component->id] = new Transfer($component->id, $component->nextId, $component->ramal, $component->type ?? 'blind');
                    break;
    
                }
            }   
        }
        return $return;
    }

    public function getProvider()   {
        return $this->provider;
    }

    public function getId() {
        return $this->id;
    }

    public function getName() {
        return $this->name;
    }

    public function getLanguage() {
        return $this->language;
    }

    public function getVoice() {
        return $this->voice;
    }

    public function getTest() {
        return $this->test;
    }

    public function getComponent($id): ComponentInterface{
        if (isset($this->components[$id])) {
            return $this->components[$id];
        }
        throw new \Exception("Component with ID $id not found.");
    }

}
