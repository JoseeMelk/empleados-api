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
        'HIS' => 'CHIAPAS',
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

    /**
     * Normaliza el texto OCR para facilitar la extracción de datos.
     */
    public function normalize(string $text): string
    {
        $text = strtoupper($text);
        $converted = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
        $text = $converted !== false ? $converted : $text;
        $text = preg_replace('/[^\w\s\.,]/u', '', $text);
        $text = preg_replace('/\s+/', ' ', $text);

        return trim($text);
    }

    /**
     * Extrae el CURP del texto normalizado, en este caso se hacen multiples intentos por error de captura con el OCR.
     */
    public function extractCurp(string $text): ?string
    {
        // Lista de palabras a excluir (ruido común del OCR)
        $excludeWords = [
            'ESTIMATING',
            'RESOLUTION',
            'DETECTED',
            'DIACRITICS',
            'INSTITUTO',
            'NACIONAL',
            'ELECTORAL',
            'CREDENCIAL',
            'MEXICO',
            'DOMICILIO',
            'NOMBRE',
            'SECCION',
            'VIGENCIA',
            'ANO',
            'REGISTRO',
            'FECHA',
            'NACIMIENTO',
            'CLAVE',
            'ELECTOR'
        ];

        // Intento 1: CURP completo sin espacios, el formato estandar del curp
        if (preg_match('/\b[A-Z]{4}\d{6}[HM][A-Z]{5}[A-Z0-9]\d\b/', $text, $m)) {
            return $m[0];
        }

        // Intento 2: Buscar después de la palabra CURP, saltando palabras intermedias, permitiendo espacios, por ejemplo:
        // CURP AÑO DE REGISTRO XXXXX# ###XXXXXXX# 2022 00
        if (preg_match('/CURP\s+(?:ANO|AÑO|DE|REGISTRO|\s)*([A-Z]{4}[A-Z0-9\s]{14,25})/i', $text, $m)) {
            $captured = preg_replace('/[^A-Z0-9\s]/', '', strtoupper(trim($m[1])));
            $parts = preg_split('/\s+/', $captured);

            foreach ($parts as $part) {
                if (strlen($part) < 15) continue; // CURP tiene mínimo 18 caracteres
                $curpLimpio = preg_replace('/\s+/', '', $part);

                if (strlen($curpLimpio) >= 18) {
                    $isExcluded = false;
                    foreach ($excludeWords as $word) {
                        if (strpos($curpLimpio, $word) === 0) {
                            $isExcluded = true;
                            break;
                        }
                    }

                    if ($isExcluded) continue;
                    $curpCandidato = substr($curpLimpio, 0, 18);
                    if (preg_match('/^[A-Z]{4}[A-Z0-9]{2}/', $curpCandidato)) {
                        return $curpCandidato;
                    }
                }
            }
        }

        // Intento 3: Buscar patrón específico en todo el texto
        // Permitir O/0 intercambiables (OCR confunde O con 0)
        if (preg_match_all('/\b[A-Z]{4}[A-Z0-9]{2}[A-Z0-9\s]{12,20}\b/', $text, $matches)) {
            foreach ($matches[0] as $match) {
                $isExcluded = false;
                foreach ($excludeWords as $word) {
                    if (strpos($match, $word) === 0) {
                        $isExcluded = true;
                        break;
                    }
                }

                if ($isExcluded) continue;
                $curpLimpio = preg_replace('/\s+/', '', $match);

                if (strlen($curpLimpio) >= 18) {
                    $curpCandidato = substr($curpLimpio, 0, 18);
                    if (preg_match('/^[A-Z]{4}[A-Z0-9]{14}$/', $curpCandidato)) {
                        return $curpCandidato;
                    }
                }
            }
        }

        return null;
    }

    /**
     * Extrae el nombre completo del texto normalizado, en este caso se hacen varios intentos por error de captura con el OCR.
     */
    public function extractFullName(string $text): array
    {
        $nombreCompleto = null;

        // Intento 1: Buscar con la palabra NOMBRE explícita
        if (preg_match('/NOMBRE\s+([A-Z\s]+?)\s+DOMIC/s', $text, $m)) {
            $nombreCompleto = trim($m[1]);
        }
        // Intento 2: Buscar entre VOTAR y DOMICILIO (más flexible para OCR malo o errores de lectura)
        elseif (preg_match('/(?:VOTAR|OTAR)\s+([A-Z\s]+?)\s+DOMIC/s', $text, $m)) {
            $nombreCompleto = trim($m[1]);
        }
        // Intento 3: Buscar patrón común después de CREDENCIAL/ENCIAL
        elseif (preg_match('/(?:CREDENCIAL|ENCIAL)\s+PARA\s+VOTAR\s+([A-Z\s]+?)\s+DOMIC/s', $text, $m)) {
            $nombreCompleto = trim($m[1]);
        }
        if (!$nombreCompleto) {
            return [
                'apellidos' => null,
                'nombre' => null,
            ];
        }

        $nombreCompleto = preg_replace('/\s+/', ' ', $nombreCompleto);
        $parts = array_values(array_filter(explode(' ', $nombreCompleto)));

        if (count($parts) < 3) {
            return [
                'apellidos' => null,
                'nombre' => null,
            ];
        }

        return [
            'apellidos' => implode(' ', array_slice($parts, 0, 2)),
            'nombre'    => implode(' ', array_slice($parts, 2)),
        ];
    }

    /**
     * Extrae la ubicación (estado, municipio, localidad) del texto normalizado, siguiendo la estructura del INE.
     * Este método ha sido mejorado para manejar mejor los errores comunes del OCR, siendo generica, aun que si vi que tiene errores por como se captura datos con el OCR.
     */
    public function extractLocation(string $text): array
    {
        if (!preg_match('/DOMIC\w*\s+(.*?)\s+(\d{4,5})\s+(.+?),\s*([A-Z]{2,5})\.?/s', $text, $m)) {
            return [
                'estado' => null,
                'municipio' => null,
                'localidad' => null,
            ];
        }

        $localidadCompleta = trim($m[1]);
        $municipioCompleto = trim($m[3]);
        $estadoAbr = trim($m[4]);

        $localidad = preg_replace('/\s+/', ' ', $localidadCompleta);
        $localidad = preg_replace('/^[^A-Z0-9]+\s*/', '', $localidad);
        $localidad = preg_replace('/\s*[^A-Z0-9]+$/', '', $localidad);
        $localidad = preg_replace('/\s+[\.\,\;\:\$\#\@\&\%]+\s+/', ' ', $localidad);
        $localidad = trim($localidad);

        $municipio = preg_replace('/\s+/', ' ', $municipioCompleto);
        $municipio = preg_replace('/^[^A-Z]*([A-Z]{2,}.*)$/', '$1', $municipio);

        $municipio = preg_replace('/^(?![EL|LA|LOS|LAS]\s)[A-Z]\s+/', '', $municipio);

        $municipio = trim($municipio);

        return [
            'estado'    => self::ESTADOS_ABR[$estadoAbr] ?? $estadoAbr,
            'municipio' => $municipio,
            'localidad' => $localidad,
        ];
    }
}
