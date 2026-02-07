<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class CalificacionStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // La autorización se maneja en el controlador/middleware
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'estudiante_id' => 'required|exists:estudiantes,id',
            'materia_id' => 'required|exists:materias,id',
            'periodo_academico_id' => 'required|exists:periodos_academicos,id',
            'tipo_evaluacion' => 'required|in:parcial,final,recuperacion,tarea,examen',
            'nota' => 'required|numeric|min:0|max:20',
            'fecha' => 'required|date',
            'observaciones' => 'nullable|string|max:500',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'estudiante_id.required' => 'El estudiante es requerido',
            'estudiante_id.exists' => 'El estudiante no existe',
            'materia_id.required' => 'La materia es requerida',
            'materia_id.exists' => 'La materia no existe',
            'periodo_academico_id.required' => 'El período académico es requerido',
            'periodo_academico_id.exists' => 'El período académico no existe',
            'tipo_evaluacion.required' => 'El tipo de evaluación es requerido',
            'tipo_evaluacion.in' => 'El tipo de evaluación debe ser: parcial, final, recuperacion, tarea o examen',
            'nota.required' => 'La nota es requerida',
            'nota.numeric' => 'La nota debe ser un número',
            'nota.min' => 'La nota mínima es 0',
            'nota.max' => 'La nota máxima es 20',
            'fecha.required' => 'La fecha es requerida',
            'fecha.date' => 'La fecha debe ser válida',
            'observaciones.max' => 'Las observaciones no pueden exceder 500 caracteres',
        ];
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422)
        );
    }
}
