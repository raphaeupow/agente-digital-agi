<?php
namespace robot\tools;

use DateTimeImmutable;
use InvalidArgumentException;


class Utils
{

    public static function getArraybetweenAspas($text)
    {
        $regex = '/\[(.*?)\]/';
        
        preg_match_all($regex, $text, $matches);
        
        return $matches[1];
    }

    public static function compactText(string $text, int $maxLength): string {
        // Converte para minúsculas
        $text = mb_strtolower($text, 'UTF-8');
    
        // Substitui espaços por underlines
        $text = str_replace(' ', '_', $text);
    
        // Remove acentos e caracteres especiais opcionais (slug mais limpo)
        $text = iconv('UTF-8', 'ASCII//TRANSLIT', $text);
        $text = preg_replace('/[^a-z0-9_]/', '', $text);
    
        // Limita o tamanho
        return substr($text, 0, $maxLength);
    }
    
    public static function getMillisecondsBetween(string $start, string $end): int {
        $startDate = DateTimeImmutable::createFromFormat('Y-m-d H:i:s.u', $start);
        $endDate = DateTimeImmutable::createFromFormat('Y-m-d H:i:s.u', $end);
    
        if (!$startDate || !$endDate) {
            throw new InvalidArgumentException("Datas inválidas. Use o formato 'Y-m-d H:i:s.u'");
        }
    
        $diffInSeconds = (float)$endDate->format('U.u') - (float)$startDate->format('U.u');
        return (int) round($diffInSeconds * 1000);
    }

}