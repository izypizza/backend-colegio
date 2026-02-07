<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class CalificacionUpdateRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            'estudiante_id' => 'sometimes|exists:estudiantes,id',
            'materia_id' => 'sometimes|exists:materias,id',
            'periodo_academico_id' => 'sometimes|exists:periodos_academicos,id',
            'tipo_evaluacion' => 'sometimes|in:parcial,final,recuperacion,tarea,examen',
            'nota' => 'sometimes|numeric|min:0|max:20',
            'fecha' => 'sometimes|date',
            'observaciones' => 'nullable|string|max:500',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'estudiante_id.exists' => 'El estudiante no existe',
            'materia_id.exists' => 'La materia no existe',
            'periodo_academico_id.exists' => 'El período académico no existe',
            'tipo_evaluacion.in' => 'El tipo de evaluación debe ser: parcial, final, recuperacion, tarea o examen',
            'nota.numeric' => 'La nota debe ser un número',
            'nota.min' => 'La nota mínima es 0',
            'nota.max' => 'La nota máxima es 20',
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
