<?php

namespace robot\components\decision;

use Fieg\Bayes\Classifier;
use Fieg\Bayes\Tokenizer\WhitespaceAndPunctuationTokenizer;
use robot\components\ComponentAbstract;
use robot\Bot;
use robot\tools\AgiServices;
use robot\Tools\Debug;
use robot\tools\Provider;
use robot\Variable;
use robot\tools\Timer;


class Decision extends ComponentAbstract
{
    private $timeout;
    private $timeMute;
    private $muteNextId;
    private $timeSilenceBetweenSpeech;
    private $timeDTMF;
    private $providerId;
    private $alternatives;
    private $similarity = 0.7;

    public function __construct($id, $muteNextId, $nextId, $timeout, $timeMute, $timeSilenceBetweenSpeech, $timeDTMF)
    {
        parent::__construct($id, $nextId);
        $this->muteNextId = $muteNextId;
        $this->timeMute = $timeMute;
        $this->timeout = $timeout;
        $this->timeSilenceBetweenSpeech = $timeSilenceBetweenSpeech;
        $this->timeDTMF = $timeDTMF;
    }

    public function addAlternative(Alternative $alternative)
    {
        return $this->alternatives[] = $alternative;
    }

    public function do(Bot &$bot): int
    {
        if (!Variable::systemGet("dtmf_tmp")) {
            $fileName = getenv('FOLDER_ASR')."/".$bot->getId()."_".$this->getId()."_".time();

            $dtmf_temp = AgiServices::getInstance()->record_file(
                $fileName, 
                $this->timeDTMF?"1234567890#*":null, 
                $this->timeout,
                $this->timeMute,
                $this->timeSilenceBetweenSpeech 
            );

            // Define o nome do arquivo base (sem extensão)
          
        }else{
            $dtmf_temp['data'] = "dtmf";
            $dtmf_temp['result'] = Variable::systemGet("dtmf_tmp");
            Variable::systemSet("dtmf_tmp", null);
        }

        $text = '';
        if (isset($dtmf_temp['data']) && $dtmf_temp['data'] === 'dtmf') { // validar timeDtmf aqui
            do {
                if ($dtmf_temp['result'] != 0) {
                    $text .= chr($dtmf_temp['result']);
                }
                Debug::notice("DTMF: " . chr($dtmf_temp['result']) . " Digitos: " . $text);
                $dtmf_temp = AgiServices::getInstance()->wait_for_digit($this->timeDTMF * 1000);
            } while ($dtmf_temp['result'] != 0);

            Debug::success("Value DTMF : " . $text);
        } else {
            //transcrever audio para texto
            try{
                Timer::start();
                $text = Provider::get()->speechToText($fileName.'.wav',$bot->getScript()->getLanguage());
                Timer::stop();
                Debug::info("ASR timer: " . Timer::getElapsedTimeMs());
                Debug::success("Value ASR: " . $text);
            }catch (\Exception $e){ 
                Debug::error("Error ASR !".$e->getMessage());
                return $this->getNextId();
            }
        }

        if (file_exists($fileName . '.wav')) {
            unlink($fileName . '.wav');
        }

        //se nao houver texto, retorna o id do proximo componente FICOU MUDO
        if ($text == "")  {
            Debug::info("Não teve interação com o usuário, FICOU MUDO");
            return $this->getMuteNextId();
        }

        $tokenizer = new WhitespaceAndPunctuationTokenizer();
        $classifier = new Classifier($tokenizer);

        //verificar se o texto digitado corresponde a algum alternativa
        foreach ($this->alternatives as $alternative) {
            // Treina o classificador com cada palavra individualmente
            foreach ($alternative->getWords() as $word) {
                $classifier->train($alternative->getNextId(), $word);
                if ($word == $text) {
                    Debug::success("Sucesso encontrado ocorrencia nivel 1: " . $text);
                    return $alternative->getNextId();
                }
            }
        }
        
        $result = $classifier->classify($text);
        foreach ($result as $nextId => $percent) {
            if ($percent > $this->getSimilarity())
            {
                Debug::success("Sucesso encontrado ocorrencia nivel 2" . $text);
                return $nextId;
            }
         }
         //se nao houver alternativa, retorna o id do proximo componente FALHOU PARA TODAS AS ALTERNATIVAS
         Debug::info("Falhou para todas as alternativas");
         return $this->getNextId();
    }

    public function getMuteNextId()
    {
        return $this->muteNextId;
    }
    public function getSimilarity()
    {
        return $this->similarity;
    }
}
