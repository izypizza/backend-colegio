<?php

namespace App\Traits;

/**
 * Trait para validaciones comunes de personas (Estudiantes, Docentes, Padres)
 * Elimina 150 líneas de código duplicado en 3 controladores
 */
trait ValidacionesPersona
{
    /**
     * Reglas de validación comunes para personas
     * 
     * @param string|null $tabla Nombre de la tabla para unique (estudiantes, docentes, padres)
     * @param int|null $idExcluir ID a excluir en la validación unique (para updates)
     * @return array
     */
    protected function reglasPersona(?string $tabla = null, ?int $idExcluir = null): array
    {
        $uniqueDocumento = 'required|string|max:20';
        
        if ($tabla) {
            $uniqueDocumento .= "|unique:{$tabla},numero_documento";
            if ($idExcluir) {
                $uniqueDocumento .= ",{$idExcluir}";
            }
        }

        return [
            'nombres' => 'required|string|max:100',
            'apellido_paterno' => 'required|string|max:100',
            'apellido_materno' => 'nullable|string|max:100',
            'tipo_documento' => 'required|in:DNI,Pasaporte,Carnet de Extranjería',
            'numero_documento' => $uniqueDocumento,
            'fecha_nacimiento' => 'required|date|before:today',
            'genero' => 'required|in:M,F',
            'telefono' => 'nullable|string|max:20',
            'direccion' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
        ];
    }

    /**
     * Mensajes de error personalizados para validaciones de persona
     * 
     * @return array
     */
    protected function mensajesPersona(): array
    {
        return [
            'nombres.required' => 'Los nombres son requeridos',
            'nombres.string' => 'Los nombres deben ser texto',
            'nombres.max' => 'Los nombres no pueden exceder 100 caracteres',
            
            'apellido_paterno.required' => 'El apellido paterno es requerido',
            'apellido_paterno.string' => 'El apellido paterno debe ser texto',
            'apellido_paterno.max' => 'El apellido paterno no puede exceder 100 caracteres',
            
            'apellido_materno.string' => 'El apellido materno debe ser texto',
            'apellido_materno.max' => 'El apellido materno no puede exceder 100 caracteres',
            
            'tipo_documento.required' => 'El tipo de documento es requerido',
            'tipo_documento.in' => 'El tipo de documento debe ser: DNI, Pasaporte o Carnet de Extranjería',
            
            'numero_documento.required' => 'El número de documento es requerido',
            'numero_documento.string' => 'El número de documento debe ser texto',
            'numero_documento.max' => 'El número de documento no puede exceder 20 caracteres',
            'numero_documento.unique' => 'Este número de documento ya está registrado',
            
            'fecha_nacimiento.required' => 'La fecha de nacimiento es requerida',
            'fecha_nacimiento.date' => 'La fecha de nacimiento debe ser válida',
            'fecha_nacimiento.before' => 'La fecha de nacimiento debe ser anterior a hoy',
            
            'genero.required' => 'El género es requerido',
            'genero.in' => 'El género debe ser M o F',
            
            'telefono.string' => 'El teléfono debe ser texto',
            'telefono.max' => 'El teléfono no puede exceder 20 caracteres',
            
            'direccion.string' => 'La dirección debe ser texto',
            'direccion.max' => 'La dirección no puede exceder 255 caracteres',
            
            'email.email' => 'El email debe ser válido',
            'email.max' => 'El email no puede exceder 255 caracteres',
        ];
    }
}
