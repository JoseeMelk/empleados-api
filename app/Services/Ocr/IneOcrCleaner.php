<?php

namespace App\Services\Ocr;

class IneOcrCleaner
{
    public function normalize(string $text): string
    {
        $text = strtoupper($text);
        $text = preg_replace('/[^\PC\s]/u', '', $text); // elimina caracteres raros
        $text = preg_replace('/\s+/', ' ', $text);

        return trim($text);
    }

    public function extractCurp(string $text): ?string
    {
        preg_match('/[A-Z]{4}\d{6}[A-Z]{6}\d{2}/', $text, $m);
        return $m[0] ?? null;
    }

    public function extractFullName(string $text): array
    {
        preg_match('/NOMBRE\s+([A-Z\s]+?)\s{2,}/', $text, $m);

        $parts = isset($m[1])
            ? array_values(array_filter(explode(' ', trim($m[1]))))
            : [];

        return [
            'apellidos' => implode(' ', array_slice($parts, 0, 2)),
            'nombre'    => implode(' ', array_slice($parts, 2))
        ];
    }

    public function extractLocation(string $text): array
    {
        preg_match('/([A-Z\s]+),\s*(CHIS|CHIAPAS)\./', $text, $m);

        return [
            'estado'    => 'CHIAPAS',
            'municipio' => $m[1] ?? null,
            'localidad' => null
        ];
    }
}
