<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EmpleadoController;
use App\Http\Controllers\OcrController;

Route::apiResource('ocr', OcrController::class)->only(['store']);
Route::apiResource('empleados', EmpleadoController::class)->only(['index', 'store', 'show']);

