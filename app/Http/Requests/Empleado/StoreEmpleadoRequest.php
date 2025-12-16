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
            'ine' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ];
    }

    public function messages(): array
    {
        return [
            'ine.required' => 'La imagen del INE es obligatoria.',
            'ine.image' => 'El archivo debe ser una imagen.',
            'ine.mimes' => 'La imagen debe ser un archivo de tipo: jpeg, png, jpg.',
            'ine.max' => 'La imagen no debe ser mayor a 2MB.',
        ];
    }

    public function attributes(): array
    {
        return [
            'ine' => 'imagen del INE',
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
