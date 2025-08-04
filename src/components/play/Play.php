<?php

namespace robot\components\play;

use robot\components\ComponentAbstract;
use robot\Bot;
use robot\tools\AgiServices;
use robot\tools\Debug;
use robot\tools\Provider;
use robot\tools\Timer;
use robot\tools\Utils;
use robot\Variable;

class Play extends ComponentAbstract
{
    protected $text;
    protected $style;
    protected $dtmfStop;

    public function __construct($id, $nextId, $text, $style = null, $dtmfStop=null )
    {
        parent::__construct($id, $nextId);
        $this->text = $text;    
        $this->style = $style;
        $this->dtmfStop = $dtmfStop;
    }

    public function do(Bot &$bot):int
    {

        $variables = Utils::getArraybetweenAspas($this->text);

        if (is_array($variables))
        {
            foreach ($variables as $variable) {
                Debug::info("Variable: " . Variable::get($variable));
                $this->text = str_replace("[{$variable}]", Variable::get($variable), $this->text);
            }
        }
        
        Debug::info($this->text);
        

        if (!is_dir(getenv("FOLDER_TTS"))) {
            mkdir(getenv("FOLDER_TTS"));
        }
        
        $fileName = (getenv("FOLDER_TTS") . "/" . Utils::compactText($this->text, 255)).".wav";
        
        if (!file_exists($fileName)) {
            Timer::start();
            Provider::get()->textToSpeech($this->text,  $fileName, $bot->getScript()->getVoice(), $bot->getScript()->getLanguage(), $this->style??"");
            Timer::stop();
            Debug::info("Generate TTS timer: " . Timer::getElapsedTimeMs());
        }   
        



        $dtmf = AgiServices::getInstance()->stream_file(str_replace(".wav", "", $fileName), $this->dtmfStop?"1234567890*#":null);

        if ($this->dtmfStop && $dtmf['result'] === 'dtmf') {
            Debug::info("Play stoped by dtmf:" . chr($dtmf['result']));
            Variable::systemSet("dtmf_tmp", $dtmf['result']);
        }

        return $this->getNextId();
    }

}

