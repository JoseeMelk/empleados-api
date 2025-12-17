<?php

namespace App\Http\Requests\Empleado;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;

class StoreEmpleadoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nombre' => 'required|string|max:100',
            'apellidos' => 'required|string|max:150',
            'curp' => 'required|string|size:18|unique:empleados,curp',
            'estado' => 'nullable|string|max:100',
            'municipio' => 'nullable|string|max:100',
            'localidad' => 'nullable|string|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre es obligatorio.',
            'nombre.string' => 'El nombre debe ser una cadena de texto.',
            'nombre.max' => 'El nombre no debe exceder los 100 caracteres.',
            'apellidos.required' => 'Los apellidos son obligatorios.',
            'apellidos.string' => 'Los apellidos deben ser una cadena de texto.',
            'apellidos.max' => 'Los apellidos no deben exceder los 150 caracteres.',
            'curp.required' => 'La CURP es obligatoria.',
            'curp.string' => 'La CURP debe ser una cadena de texto.',
            'curp.size' => 'La CURP debe tener exactamente 18 caracteres.',
            'curp.unique' => 'La CURP ya está registrada para otro empleado.',
            'estado.string' => 'El estado debe ser una cadena de texto.',
            'estado.max' => 'El estado no debe exceder los 100 caracteres.',
            'municipio.string' => 'El municipio debe ser una cadena de texto.',
            'municipio.max' => 'El municipio no debe exceder los 100 caracteres.',
            'localidad.string' => 'La localidad debe ser una cadena de texto.',
            'localidad.max' => 'La localidad no debe exceder los 100 caracteres.',
        ];
    }

    public function attributes(): array
    {
        return [
            'nombre' => 'nombre',
            'apellidos' => 'apellidos',
            'curp' => 'CURP',
            'estado' => 'estado',
            'municipio' => 'municipio',
            'localidad' => 'localidad',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'ok' => false,
            'message' => 'Error de validación.',
            'errors' => $validator->errors()
        ], 422));
    } 
}
