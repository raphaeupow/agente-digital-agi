<?php

namespace robot\components\integration;

use async\Async;
use async\Task;
use robot\components\ComponentAbstract;
use robot\Bot;
use robot\Tools\Debug;
use robot\Variable;

class Integration extends ComponentAbstract
{
	private $method;
	private $isAsync;
	private $timeout;
	private $faultNextId;
	private $loadingNextId;
	private $parameters;
	private $returns;
	private $task=null;
	private $async;
	public function __construct($id, $nextId, $faultNextId, $loadingNextId, $method, $timeout, $parameters, $returns, $isAsync=1)
	{
		$this->method = $method;
		$this->isAsync = $isAsync;
		$this->timeout = $timeout;
		$this->faultNextId = $faultNextId;
		$this->loadingNextId = $loadingNextId;
		$this->parameters = json_decode(json_encode($parameters), true);
		$this->returns = json_decode(json_encode($returns), true);
		parent::__construct($id, $nextId);
	}

	public function getParametersConvert():array
	{
		$parameters=[];
		if (is_array($this->parameters)) {
			foreach ($this->parameters as $key => $value) {
				if (strpos($value, '[') !== false && strpos($value, ']') !== false) {
					$parameters[$key] = Variable::get( str_replace(['[', ']'], '', $value));
				}else{
					$parameters[$key] = $value;
				}
			}
		}
		return $parameters;
	}

	public function do(Bot &$bot): int
	{
		if ($this->isAsync) 
		{
			// Rodando tarefas assíncronas
			if (is_null($this->task)) {
				// Criando tarefa assíncrona
				Debug::info("Criando tarefa assíncrona.");
				$this->async = new Async();
				$this->task = new Task("php ".dirname(__DIR__)."/integration/assync.php ".$bot->getScript()->getId()." ".$this->method." ".base64_encode(serialize($parameters=$this->getParametersConvert())));
				$this->async->addTask($this->task);
				$this->async->hasDo();
				Debug::notice("Method: ".$this->method);
				Debug::notice("Parameters original: ".json_encode($this->parameters));
				Debug::notice("Parameters: ".json_encode($parameters));
				return $this->loadingNextId;	
			}else{
				// Verificando se a tarefa está pronta
				$this->async->hasDo();
				if ($this->task->isDone()) {
					Debug::success("Integração executada com sucesso.");
					if (isset(json_decode($this->task->getResult(), 1)["returns"])) {
						if (json_decode($this->task->getResult(), 1)["returns"]=="") {
							Debug::error("Nenhum retorno da integração metodo ".$this->method.".");
							return $this->faultNextId;
						}else{
							$this->setVariablesByReturn(json_decode($this->task->getResult(), 1)["returns"]);
						}
					}
					if (isset($this->task->getResult()["error"])) {
						Debug::error("Erro na integração: " . json_decode($this->task->getResult())["error"]);
						return $this->faultNextId;
					}
					return $this->getNextId();
				}else{
					Debug::info("Integração ainda em execução.");
					return $this->loadingNextId;
				}
			}
		}else{
			try {
				$scriptId = $bot->getScript()->getId();
				$path = __DIR__ . "/../../scripts/" . $scriptId . "/Main.php";
			
				if (!file_exists($path)) {
					Debug::error("Arquivo não encontrado: " . $path);
					return $this->faultNextId;
				}
			
				include_once($path);
			
				$className = "Main";
				if (!class_exists($className)) {
					Debug::error("Classe '$className' não encontrada no arquivo.");
					return $this->faultNextId;
				}
			
				$instance = new $className();
			
				$method = $this->method;
				if (!method_exists($instance, $method)) {
					Debug::error("Método '$method' não encontrado na classe '$className'.");
					return $this->faultNextId;
				}
				$returns = $instance->$method($this->getParametersConvert());
				$this->setVariablesByReturn($returns);
				Debug::success("Integração executada com sucesso.");
				return $this->getNextId();
			} catch (\Exception $e) {
				Debug::error("Falha ao executar a integração: " . $e->getMessage());
				return $this->faultNextId;
			}
		}
	
		// 🔴 Se não for async, ainda precisa retornar algo!
		return $this->getNextId(); // ou $this->faultNextId;
	}
	
	private function setVariablesByReturn($returns)	
	{
		if (is_array($this->returns)) {
			foreach ($this->returns as $fieldName => $variableName ) {
				if (!isset($returns[$fieldName])) {
					Debug::error("Campo '$fieldName' não encontrado na resposta da integração.");
					continue;
				}else{
					Variable::set($variableName, $returns[$fieldName]);
				}
			}
		} 
	}
}

