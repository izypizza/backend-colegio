<?php

namespace App\Services;

use App\Models\Notificacion;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Servicio centralizado para gestionar notificaciones del sistema
 * 
 * Tipos de notificaciones:
 * - mensaje: Nuevo mensaje en chat
 * - eleccion: Evento de elección estudiantil
 * - calificacion: Nueva calificación registrada
 * - asistencia: Registro de asistencia
 * - comunicado: Anuncio general
 * - evento: Evento académico o extracurricular
 * - alerta: Alertas importantes del sistema
 */
class NotificationService
{
    /**
     * Tipos válidos de notificaciones
     */
    const TIPOS = [
        'mensaje' => ['icono' => '💬', 'prioridad' => 'normal'],
        'eleccion' => ['icono' => '🗳️', 'prioridad' => 'alta'],
        'calificacion' => ['icono' => '📝', 'prioridad' => 'normal'],
        'asistencia' => ['icono' => '✓', 'prioridad' => 'baja'],
        'comunicado' => ['icono' => '📢', 'prioridad' => 'alta'],
        'evento' => ['icono' => '📅', 'prioridad' => 'normal'],
        'alerta' => ['icono' => '⚠️', 'prioridad' => 'alta'],
        'info' => ['icono' => 'ℹ️', 'prioridad' => 'baja'],
    ];

    /**
     * Crear notificación individual
     */
    public function crear(
        int $userId,
        string $titulo,
        string $mensaje,
        string $tipo = 'info',
        ?array $data = null,
        ?string $accionUrl = null
    ): Notificacion {
        $tipoConfig = self::TIPOS[$tipo] ?? self::TIPOS['info'];

        return Notificacion::create([
            'user_id' => $userId,
            'titulo' => $titulo,
            'mensaje' => $mensaje,
            'tipo' => $tipo,
            'prioridad' => $tipoConfig['prioridad'],
            'icono' => $tipoConfig['icono'],
            'data' => $data,
            'accion_url' => $accionUrl,
        ]);
    }

    /**
     * Crear notificaciones masivas por rol
     */
    public function crearPorRol(
        string $rol,
        string $titulo,
        string $mensaje,
        string $tipo = 'info',
        ?array $data = null,
        ?string $accionUrl = null
    ): int {
        $users = User::where('role', $rol)->get();
        $count = 0;

        foreach ($users as $user) {
            $this->crear($user->id, $titulo, $mensaje, $tipo, $data, $accionUrl);
            $count++;
        }

        return $count;
    }

    /**
     * Crear notificaciones masivas para múltiples usuarios
     */
    public function crearMasivo(
        array $userIds,
        string $titulo,
        string $mensaje,
        string $tipo = 'info',
        ?array $data = null,
        ?string $accionUrl = null
    ): int {
        $count = 0;

        foreach ($userIds as $userId) {
            $this->crear($userId, $titulo, $mensaje, $tipo, $data, $accionUrl);
            $count++;
        }

        return $count;
    }

    /**
     * Notificar nuevo mensaje de chat
     */
    public function notificarNuevoMensaje(int $remitenteId, int $destinatarioId, string $conversacionId): Notificacion
    {
        $remitente = User::find($remitenteId);
        $nombre = $remitente->name ?? 'Usuario';

        return $this->crear(
            $destinatarioId,
            'Nuevo mensaje',
            "Tienes un nuevo mensaje de {$nombre}",
            'mensaje',
            ['conversacion_id' => $conversacionId],
            "/dashboard/chat/{$conversacionId}"
        );
    }

    /**
     * Notificar nueva calificación a estudiante y padres
     */
    public function notificarNuevaCalificacion(int $estudianteId, string $materia, float $nota, string $periodo): Collection
    {
        $notificaciones = collect();

        // Obtener estudiante y sus padres
        $estudiante = \App\Models\Estudiante::with('user', 'padres.user')->find($estudianteId);
        
        if (!$estudiante || !$estudiante->user) {
            return $notificaciones;
        }

        // Notificar al estudiante
        $notificaciones->push(
            $this->crear(
                $estudiante->user->id,
                'Nueva calificación',
                "Se ha registrado una calificación en {$materia}: {$nota}",
                'calificacion',
                ['materia' => $materia, 'nota' => $nota, 'periodo' => $periodo],
                '/dashboard/estudiante/mis-calificaciones'
            )
        );

        // Notificar a los padres
        foreach ($estudiante->padres as $padre) {
            if ($padre->user) {
                $notificaciones->push(
                    $this->crear(
                        $padre->user->id,
                        'Nueva calificación de su hijo/a',
                        "Se registró una calificación para {$estudiante->nombre_completo} en {$materia}: {$nota}",
                        'calificacion',
                        ['estudiante' => $estudiante->nombre_completo, 'materia' => $materia, 'nota' => $nota],
                        '/dashboard/padre/calificaciones'
                    )
                );
            }
        }

        return $notificaciones;
    }

    /**
     * Notificar elección habilitada
     */
    public function notificarEleccionHabilitada(int $eleccionId, string $titulo, string $descripcion): int
    {
        $count = 0;

        // Notificar a estudiantes
        $count += $this->crearPorRol(
            'estudiante',
            'Elección Estudiantil Habilitada',
            "Se ha habilitado la elección: {$titulo}. {$descripcion}",
            'eleccion',
            ['eleccion_id' => $eleccionId],
            "/dashboard/estudiante/elecciones/{$eleccionId}"
        );

        // Notificar a padres
        $count += $this->crearPorRol(
            'padre',
            'Elección Estudiantil Disponible',
            "Sus hijos pueden participar en: {$titulo}. {$descripcion}",
            'eleccion',
            ['eleccion_id' => $eleccionId],
            "/dashboard/padre/elecciones"
        );

        return $count;
    }

    /**
     * Notificar evento próximo
     */
    public function notificarEvento(string $nombreEvento, string $fecha, array $roles = ['all']): int
    {
        $count = 0;

        if (in_array('all', $roles)) {
            $roles = ['admin', 'auxiliar', 'docente', 'estudiante', 'padre'];
        }

        foreach ($roles as $rol) {
            $count += $this->crearPorRol(
                $rol,
                'Evento próximo',
                "Recordatorio: {$nombreEvento} - {$fecha}",
                'evento',
                ['evento' => $nombreEvento, 'fecha' => $fecha],
                '/dashboard/eventos'
            );
        }

        return $count;
    }

    /**
     * Enviar comunicado general
     */
    public function enviarComunicado(string $titulo, string $mensaje, array $roles, ?string $url = null): int
    {
        $count = 0;

        foreach ($roles as $rol) {
            $count += $this->crearPorRol($rol, $titulo, $mensaje, 'comunicado', null, $url);
        }

        return $count;
    }

    /**
     * Obtener estadísticas de notificaciones de un usuario
     */
    public function estadisticas(int $userId): array
    {
        $total = Notificacion::where('user_id', $userId)->count();
        $noLeidas = Notificacion::where('user_id', $userId)->whereNull('leido_at')->count();
        $porTipo = Notificacion::where('user_id', $userId)
            ->selectRaw('tipo, count(*) as total')
            ->groupBy('tipo')
            ->pluck('total', 'tipo')
            ->toArray();

        return [
            'total' => $total,
            'no_leidas' => $noLeidas,
            'por_tipo' => $porTipo,
        ];
    }
}
