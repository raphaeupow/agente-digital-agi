<?php

namespace robot\tools\provider;

use Exception;
use robot\Tools\Debug;


class Google implements ProviderInterface
{
    private $apiKey;

    public function __construct()
    {
        $this->apiKey =  getenv('GOOGLE_KEY');
    }

    /**
     * Texto para voz (TTS)
     */
    public function textToSpeech(string $text, string $outputFile, string $voiceName, string $languageCode = 'pt-BR',string $style = "" ): void
    {
        if (file_exists($outputFile)) return;

        $url = "https://texttospeech.googleapis.com/v1/text:synthesize?key={$this->apiKey}";
        $body = [
            "input" => ["text" => $text],
            "voice" => [
                "languageCode" => $languageCode,
                "name" => $voiceName
            ],
            "audioConfig" => [
                "audioEncoding" => "LINEAR16"
            ]
        ];

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                "X-Goog-Api-Key: $this->apiKey"
            ],
            CURLOPT_POSTFIELDS => json_encode($body),
            CURLOPT_SSL_VERIFYPEER => false 
        ]);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new Exception("Erro no TTS do Google: $error");
        }

        $json = json_decode($response, true);

        if (!isset($json['audioContent'])) {
            throw new Exception("Erro: resposta inválida do Google TTS");
        }

        $tempFile = $outputFile . '.temp';
        
        try {
            // Save initial audio to temp file
            file_put_contents($tempFile, base64_decode($json['audioContent']));
            
            // Convert using sox to 8kHz mono for Asterisk
            $cmd = "sox {$tempFile} -r 8000 -c 1 {$outputFile}";
            exec($cmd, $output, $returnCode);
            
            if ($returnCode !== 0) {
                throw new Exception("Error converting audio with sox");
            }
            
            // Cleanup temp file
            unlink($tempFile);
            
        } catch(Exception $e) {
            if (file_exists($tempFile)) {
                unlink($tempFile); 
            }
            throw new Exception('Failed to process audio file: ' . $e->getMessage());
        }

    }

    /**
     * Fala para texto (ASR)
     * Entrada: arquivo WAV mono 16kHz PCM
     */
    public function speechToText(string $audioFile, string $languageCode = 'pt-BR'): string
    {

        if (!file_exists($audioFile)) throw new Exception('File ASR not found '.$audioFile);    
    
        $audioContent = base64_encode(file_get_contents($audioFile));
        $url = "https://speech.googleapis.com/v1/speech:recognize?key={$this->apiKey}";

        $body = [
            "config" => [
                "encoding" => "LINEAR16",
                "sampleRateHertz" => 8000,
                "languageCode" => $languageCode
            ],
            "audio" => [
                "content" => $audioContent
            ]
        ];

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                "Content-Type: application/json",
                "X-Goog-Api-Key: $this->apiKey"
            ],
            CURLOPT_POSTFIELDS => json_encode($body),
            CURLOPT_SSL_VERIFYPEER => false 
        ]);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new Exception("Erro no ASR do Google: $error");
        }

        $json = json_decode($response, true);

        return $json['results'][0]['alternatives'][0]['transcript'] ?? '';
    }
}

