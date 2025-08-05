<?php

namespace robot\tools\provider;

use Exception;

class HybridProvider implements ProviderInterface
{
    private $groqProvider;
    private $elevenLabsProvider;

    public function __construct()
    {
        $this->groqProvider = new GroqCloud();
        $this->elevenLabsProvider = new ElevenLabs();
    }

    /**
     * Texto para voz (TTS) usando ElevenLabs
     */
    public function textToSpeech(string $text, string $outputFile, string $voiceName, string $languageCode = 'pt-BR', string $style = ""): void
    {
        $this->elevenLabsProvider->textToSpeech($text, $outputFile, $voiceName, $languageCode, $style);
    }

    /**
     * Fala para texto (ASR) usando GroqCloud Whisper
     */
    public function speechToText(string $audioFile, string $languageCode = 'pt-BR'): string
    {
        return $this->groqProvider->speechToText($audioFile, $languageCode);
    }
} 