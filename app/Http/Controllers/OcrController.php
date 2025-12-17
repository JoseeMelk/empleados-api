<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\Ocr\StoreOcrRequest;
use App\Services\Ocr\IneOcrCleanerService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class OcrController extends Controller
{
    protected IneOcrCleanerService $ineOcrCleanerService;

    public function __construct(IneOcrCleanerService $ineOcrCleanerService)
    {
        $this->ineOcrCleanerService = $ineOcrCleanerService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreOcrRequest $request)
    {
        $response = [
            'ok' => false,
            'message' => 'Error al procesar el INE del empleado.',
        ];
        $statusCode = 500;

        try {
            $file = $request->file('ine');
            $path = $file->store('ines');
            $imagePath = Storage::path($path);

            $tesseractPath = config('ocr.tesseract_path');
            
            // En Windows, envolver la ruta completa en comillas
            $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
            if ($isWindows && str_contains($tesseractPath, ' ')) {
                $command = sprintf(
                    '"%s" %s stdout -l spa 2>&1',
                    $tesseractPath,
                    escapeshellarg($imagePath)
                );
            } else {
                // En Linux sin espacios en la ruta
                $command = sprintf(
                    '%s %s stdout -l spa 2>&1',
                    escapeshellcmd($tesseractPath),
                    escapeshellarg($imagePath)
                );
            }

            $output = shell_exec($command);

            if (!$output || str_contains($output, 'NOT FOUND')) {
                throw new \RuntimeException('OCR no produjo resultados');
            }

            $normalizedText = $this->ineOcrCleanerService->normalize($output);
            $curp = $this->ineOcrCleanerService->extractCurp($normalizedText);
            $fullName = $this->ineOcrCleanerService->extractFullName($normalizedText);
            $location = $this->ineOcrCleanerService->extractLocation($normalizedText);

            $responseData = [
                'nombre' => $fullName['nombre'] ?? null,
                'apellidos' => $fullName['apellidos'] ?? null,
                'curp' => $curp,
                'estado' => $location['estado'] ?? null,
                'municipio' => $location['municipio'] ?? null,
                'localidad' => $location['localidad'] ?? null,
            ];

            Storage::delete($path);

            $statusCode = 201;
            $response['ok'] = true;
            $response['message'] = 'INE del empleado procesado exitosamente.';
            $response['data'] = $responseData;
        } catch (\Throwable $e) {
            Log::error('OCR INE error: ' . $e->getMessage());
        }

        return response()->json($response, $statusCode);
    }


    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
