<?php

namespace App\Services\Ocr;

class IneOcrCleanerService
{
    private const ESTADOS_ABR = [
        'AGS' => 'AGUASCALIENTES',
        'BC'  => 'BAJA CALIFORNIA',
        'BCS' => 'BAJA CALIFORNIA SUR',
        'CAMP' => 'CAMPECHE',
        'CHIS' => 'CHIAPAS',
        'CHIH' => 'CHIHUAHUA',
        'CDMX' => 'CIUDAD DE MEXICO',
        'COAH' => 'COAHUILA',
        'COL' => 'COLIMA',
        'DGO' => 'DURANGO',
        'MEX' => 'ESTADO DE MEXICO',
        'GTO' => 'GUANAJUATO',
        'GRO' => 'GUERRERO',
        'HGO' => 'HIDALGO',
        'JAL' => 'JALISCO',
        'MICH' => 'MICHOACAN',
        'MOR' => 'MORELOS',
        'NAY' => 'NAYARIT',
        'NL'  => 'NUEVO LEON',
        'OAX' => 'OAXACA',
        'PUE' => 'PUEBLA',
        'QRO' => 'QUERETARO',
        'QROO' => 'QUINTANA ROO',
        'SLP' => 'SAN LUIS POTOSI',
        'SIN' => 'SINALOA',
        'SON' => 'SONORA',
        'TAB' => 'TABASCO',
        'TAM' => 'TAMAULIPAS',
        'TLAX' => 'TLAXCALA',
        'VER' => 'VERACRUZ',
        'YUC' => 'YUCATAN',
        'ZAC' => 'ZACATECAS',
    ];


    public function normalize(string $text): string
    {
        $text = strtoupper($text);
        $converted = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
        $text = $converted !== false ? $converted : $text;
        $text = preg_replace('/[^\w\s\.,]/u', '', $text);
        $text = preg_replace('/\s+/', ' ', $text);

        return trim($text);
    }

    public function extractCurp(string $text): ?string
    {
        preg_match('/\b[A-Z]{4}\d{6}[HM][A-Z]{5}[A-Z0-9]\d\b/', $text, $m);
        return $m[0] ?? null;
    }

    public function extractFullName(string $text): array
    {
        if (!preg_match('/NOMBRE\s+([A-Z\s]+?)\s+DOMIC/', $text, $m)) {
            return [
                'apellidos' => null,
                'nombre' => null,
            ];
        }

        $parts = array_values(array_filter(explode(' ', trim($m[1]))));

        return [
            'apellidos' => implode(' ', array_slice($parts, 0, 2)),
            'nombre'    => implode(' ', array_slice($parts, 2)),
        ];
    }



    public function extractLocation(string $text): array
    {
        if (!preg_match('/DOMIC\w*\s+(.*?)\s+(\d{5})\s+([A-Z\s]+),\s*([A-Z]{2,5})\./', $text, $m)) {
            return [
                'estado' => null,
                'municipio' => null,
                'localidad' => null,
            ];
        }

        $localidad = trim($m[1]);
        $municipio = trim($m[3]);
        $estadoAbr = $m[4];

        return [
            'estado'    => self::ESTADOS_ABR[$estadoAbr] ?? $estadoAbr,
            'municipio' => $municipio,
            'localidad' => $localidad,
        ];
    }
}
