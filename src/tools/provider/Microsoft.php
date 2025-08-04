<?php

namespace robot\tools\provider;

use robot\tools\Debug;

class Microsoft implements ProviderInterface
{
    private $subscriptionKey;
    private $region;
    private $ttsEndpoint;
    private $asrEndpoint;

    public function __construct(string $region = 'eastus')
    {
        $this->subscriptionKey = getenv('MICROSOFT_KEY');
        $this->region = $region;
        $this->ttsEndpoint = "https://$region.tts.speech.microsoft.com/cognitiveservices/v1";
        $this->asrEndpoint = "https://$region.stt.speech.microsoft.com/speech/recognition/conversation/cognitiveservices/v1";
    }

    /**
     * Converte texto em fala e salva como arquivo .wav
     */
    public function textToSpeech(string $text, string $outputFile, string $voiceName, string $languageCode = 'pt-BR',string $style = "general" ): void
    {
        if (file_exists($outputFile)) return;

        $ssml = <<<XML
<speak version='1.0' xml:lang='$languageCode'>
<voice name='$voiceName'>$text</voice>
</speak>
XML;

        // Inicializa o cURL
        $ch = curl_init($this->ttsEndpoint);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $ssml,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER => [
                "Ocp-Apim-Subscription-Key: {$this->subscriptionKey}",
                "Content-Type: application/ssml+xml",
                "X-Microsoft-OutputFormat: riff-8khz-16bit-mono-pcm",
                "User-Agent: SmartCTI-TTS"
            ]
        ]);

        // Executa e salva o resultado
        $response = curl_exec($ch);
        $err = curl_error($ch); 
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($httpCode == 200) {
            file_put_contents($outputFile, $response);
        } else {
            throw new \Exception("Erro no TTS: $httpCode | $err");
        }

        curl_close($ch);
    }

    /**
     * Converte fala para texto a partir de um arquivo de áudio (.wav 16bit mono 16kHz)
     */
    public function speechToText(string $audioFile, string $language = "pt-BR"): string
    {
        if (!file_exists($audioFile)) throw new \Exception('File ASR not found '.$audioFile);    
    
        $headers = [
            "Ocp-Apim-Subscription-Key: {$this->subscriptionKey}",
            "Content-Type: audio/wav; codecs=audio/pcm; samplerate=16000",
            "Accept: application/json"
        ];

        $url = $this->asrEndpoint . "?language=$language";

        $audioData = file_get_contents($audioFile);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $audioData,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER => $headers
        ]);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new \Exception("Erro no ASR: $error");
        }

        $json = json_decode($response, true);
        return $json['DisplayText'] ?? '';
    }
}