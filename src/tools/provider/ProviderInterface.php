<?php
namespace robot\tools\provider;

interface ProviderInterface
{
    /**
     * Converte texto em áudio e salva em arquivo
     *
     * @param string $text Texto a ser sintetizado
     * @param string $outputFile Caminho do arquivo de saída .wav
     * @param string $languageCode Código do idioma (ex: pt-BR)
     * @param string|null $voiceName Nome da voz (opcional)
     */
    public function textToSpeech(string $text, string $outputFile, string $voiceName, string $languageCode = 'pt-BR',string $style  ): void;

    /**
     * Converte áudio de voz em texto
     *
     * @param string $audioFile Caminho do arquivo de áudio .wav
     * @param string $languageCode Código do idioma (ex: pt-BR)
     * @return string Texto reconhecido
     */
    public function speechToText(string $audioFile, string $languageCode = 'pt-BR'): string;
}
