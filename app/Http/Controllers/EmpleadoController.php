<?php

namespace App\Http\Controllers;

use App\Models\Empleado;
use Illuminate\Http\Request;
use App\Http\Requests\Empleado\StoreEmpleadoRequest;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;


class EmpleadoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $response = [
            'ok' => false,
            'message' => 'Error al obtener los empleados.',
        ];
        $statusCode = 500;

        try {
            $empleados = Empleado::all();

            if ($empleados->isEmpty()) {
                $response['message'] = 'No se encontraron empleados.';
                $statusCode = 404;
                return response()->json($response, $statusCode);
            }

            $statusCode = 200;
            $response['ok'] = true;
            $response['message'] = 'Empleados obtenidos exitosamente.';
            $response['data'] = $empleados;
        } catch (Exception $e) {
            Log::error('Error al obtener los empleados: ' . $e->getMessage());
        }
        return response()->json($response, $statusCode);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreEmpleadoRequest $request)
    {
        $response = [
            'ok' => false,
            'message' => 'Error al crear el empleado.',
        ];
        $statusCode = 500;

        try {
            $file = $request->file('ine');
            $path = $file->store('ines');
            $imagePath = Storage::path($path);

            $tesseractPath = env('TESSERACT_PATH', 'tesseract');
            $command = "\"{$tesseractPath}\" \"{$imagePath}\" stdout -l spa";
            $output = shell_exec($command . " 2>&1");



            $statusCode = 201;
            $response['ok'] = true;
            $response['message'] = 'Empleado creado exitosamente.';
            $response['data'] = [
                'ocr_text' => $output,
            ];
        } catch (Exception $e) {
            Log::error('Error al crear el empleado: ' . $e->getMessage());
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Display the specified resource.
     */
    public function show(Empleado $empleado)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Empleado $empleado)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Empleado $empleado)
    {
        //
    }
}
