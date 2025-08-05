<?php

namespace robot\tools\provider;

use Exception;

class ElevenLabs implements ProviderInterface
{
    private $apiKey;

    public function __construct()
    {
        $this->apiKey = getenv('ELEVENLABS_API_KEY');
    }

    /**
     * Texto para voz (TTS) usando ElevenLabs
     */
    public function textToSpeech(string $text, string $outputFile, string $voiceName, string $languageCode = 'pt-BR', string $style = ""): void
    {
        if (file_exists($outputFile)) return;

        // Mapear códigos de idioma para ElevenLabs
        $languageMap = [
            'pt-BR' => 'pt',
            'en-US' => 'en',
            'es-ES' => 'es',
            'fr-FR' => 'fr',
            'de-DE' => 'de',
            'it-IT' => 'it'
        ];

        $language = $languageMap[$languageCode] ?? 'pt';

        // URL com parâmetros para 16kHz PCM
        $url = "https://api.elevenlabs.io/v1/text-to-speech/{$voiceName}?output_format=pcm_8000&sample_rate=8000";

        $body = [
            "text" => $text,
            "model_id" => "eleven_multilingual_v2",
            "voice_settings" => [
                "stability" => 0.5,
                "similarity_boost" => 0.5,
                "style" => 0.0,
                "use_speaker_boost" => true
            ]
        ];

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                "xi-api-key: {$this->apiKey}",
                "Accept: audio/L16" // Solicita áudio PCM 16-bit
            ],
            CURLOPT_POSTFIELDS => json_encode($body),
            CURLOPT_SSL_VERIFYPEER => false
        ]);

        $audioData = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) throw new Exception("Erro no TTS da ElevenLabs: $error");
        if ($httpCode !== 200) throw new Exception("Erro HTTP {$httpCode}");
        if (!$audioData) throw new Exception("Falha ao obter resposta da ElevenLabs");

        // Converter dados PCM para WAV 16kHz
        $this->createWavFromPcm($audioData, $outputFile, 8000);
        //file_put_contents($outputFile, $audioData);

        error_log("Arquivo de áudio criado: $outputFile, tamanho: " . filesize($outputFile));
    }

    /**
     * Criar arquivo WAV a partir de dados PCM
     */
    private function createWavFromPcm(string $pcmData, string $outputFile, int $sampleRate = 8000): void
    {
        $bitsPerSample = 16;
        $channels = 1;
    
        $byteRate = $sampleRate * $channels * ($bitsPerSample / 8);
        $blockAlign = $channels * ($bitsPerSample / 8);
        $dataSize = strlen($pcmData);
        $chunkSize = 36 + $dataSize;
    
        // Criar cabeçalho WAV manualmente
        $header = '';
        
        // RIFF header
        $header .= 'RIFF';
        $header .= pack('V', $chunkSize - 8);
        $header .= 'WAVE';
        
        // fmt chunk
        $header .= 'fmt ';
        $header .= pack('V', 16);
        $header .= pack('v', 1); // PCM
        $header .= pack('v', $channels);
        $header .= pack('V', $sampleRate);
        $header .= pack('V', $byteRate);
        $header .= pack('v', $blockAlign);
        $header .= pack('v', $bitsPerSample);
        
        // data chunk
        $header .= 'data';
        $header .= pack('V', $dataSize);
    
        $wavData = $header . $pcmData;
        file_put_contents($outputFile, $wavData);
    }

    /**
     * Fala para texto (ASR) usando ElevenLabs
     */
    public function speechToText(string $audioFile, string $languageCode = 'pt-BR'): string
    {
        if (!file_exists($audioFile)) {
            throw new Exception('Arquivo ASR não encontrado: ' . $audioFile);
        }

        $url = "https://api.elevenlabs.io/v1/speech-recognition";

        $boundary = uniqid();
        $data = "--{$boundary}\r\n";
        $data .= "Content-Disposition: form-data; name=\"audio\"; filename=\"audio.wav\"\r\n";
        $data .= "Content-Type: audio/wav\r\n\r\n";
        $data .= file_get_contents($audioFile);
        $data .= "\r\n";
        $data .= "--{$boundary}\r\n";
        $data .= "Content-Disposition: form-data; name=\"model_id\"\r\n\r\n";
        $data .= "eleven_english_sts_v2\r\n";
        $data .= "--{$boundary}--\r\n";

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                "xi-api-key: {$this->apiKey}",
                "Content-Type: multipart/form-data; boundary={$boundary}"
            ],
            CURLOPT_POSTFIELDS => $data,
            CURLOPT_SSL_VERIFYPEER => false
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new Exception("Erro no ASR da ElevenLabs: $error");
        }

        if ($httpCode !== 200) {
            throw new Exception("Erro HTTP {$httpCode}: {$response}");
        }

        $json = json_decode($response, true);

        if (!isset($json['text'])) {
            throw new Exception("Resposta inválida da ElevenLabs: " . $response);
        }

        return $json['text'];
    }
}
