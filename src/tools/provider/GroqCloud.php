<?php

namespace robot\tools\provider;

use Exception;

class GroqCloud implements ProviderInterface
{
    private $apiKey;

    public function __construct()
    {
        $this->apiKey = getenv('GROQ_API_KEY');
    }

    /**
     * Texto para voz (TTS) usando GroqCloud Mixtral
     */
    public function textToSpeech(string $text, string $outputFile, string $voiceName, string $languageCode = 'pt-BR', string $style = ""): void
    {
        if (file_exists($outputFile)) return;

        $url = "https://api.groq.com/openai/v1/audio/speech";
        
        $body = [
            "model" => "tts-1",
            "input" => $text,
            "voice" => $voiceName,
            "response_format" => "mp3",
            "speed" => 1.0
        ];

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer {$this->apiKey}",
                "Content-Type: application/json"
            ],
            CURLOPT_POSTFIELDS => json_encode($body),
            CURLOPT_SSL_VERIFYPEER => false
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new Exception("Erro no TTS da GroqCloud: $error");
        }

        if ($httpCode !== 200) {
            throw new Exception("Erro HTTP {$httpCode}: {$response}");
        }

        $tempFile = $outputFile . '.temp';
        
        try {
            // Salvar áudio inicial em arquivo temporário
            file_put_contents($tempFile, $response);
            
            // Converter usando sox para 8kHz mono para Asterisk
            $cmd = "sox {$tempFile} -r 8000 -c 1 {$outputFile}";
            exec($cmd, $output, $returnCode);
            
            if ($returnCode !== 0) {
                throw new Exception("Erro ao converter áudio com sox");
            }
            
            // Limpar arquivo temporário
            unlink($tempFile);
            
        } catch(Exception $e) {
            if (file_exists($tempFile)) {
                unlink($tempFile); 
            }
            throw new Exception('Falha ao processar arquivo de áudio: ' . $e->getMessage());
        }
    }

    /**
     * Fala para texto (ASR) usando Whisper via GroqCloud
     * Entrada: arquivo WAV mono 8kHz PCM
     */
    public function speechToText(string $audioFile, string $languageCode = 'pt-BR'): string
    {
        if (!file_exists($audioFile)) {
            throw new Exception('Arquivo ASR não encontrado: ' . $audioFile);
        }

        // Converter para base64
        $audioContent = base64_encode(file_get_contents($audioFile));
        
        $url = "https://api.groq.com/openai/v1/audio/transcriptions";
        
        // Preparar dados para multipart/form-data
        $boundary = uniqid();
        $data = "--{$boundary}\r\n";
        $data .= "Content-Disposition: form-data; name=\"model\"\r\n\r\n";
        $data .= "whisper-large-v3\r\n";
        $data .= "--{$boundary}\r\n";
        $data .= "Content-Disposition: form-data; name=\"file\"; filename=\"audio.wav\"\r\n";
        $data .= "Content-Type: audio/wav\r\n\r\n";
        $data .= file_get_contents($audioFile);
        $data .= "\r\n";
        $data .= "--{$boundary}--\r\n";

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer {$this->apiKey}",
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
            throw new Exception("Erro no ASR da GroqCloud: $error");
        }

        if ($httpCode !== 200) {
            throw new Exception("Erro HTTP {$httpCode}: {$response}");
        }

        $json = json_decode($response, true);

        if (!isset($json['text'])) {
            throw new Exception("Resposta inválida da GroqCloud: " . $response);
        }

        return $json['text'];
    }
} 