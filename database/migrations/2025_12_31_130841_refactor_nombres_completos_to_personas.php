<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $this->normalizeNombreToPersonaColumns('estudiantes');
        $this->normalizeNombreToPersonaColumns('docentes');
        $this->normalizeNombreToPersonaColumns('padres');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->restoreNombreColumn('estudiantes');
        $this->restoreNombreColumn('docentes');
        $this->restoreNombreColumn('padres');
    }

    private function normalizeNombreToPersonaColumns(string $table): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        if (Schema::hasColumn($table, 'nombres')) {
            return;
        }

        if (! Schema::hasColumn($table, 'nombre')) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) {
            $blueprint->string('nombres')->nullable()->after('id');
            $blueprint->string('apellido_paterno')->nullable()->after('nombres');
            $blueprint->string('apellido_materno')->nullable()->after('apellido_paterno');
        });

        \DB::table($table)
            ->select('id', 'nombre')
            ->orderBy('id')
            ->chunkById(200, function ($rows) use ($table) {
                foreach ($rows as $row) {
                    [$nombres, $apellidoPaterno, $apellidoMaterno] = $this->splitNombre((string) $row->nombre);

                    \DB::table($table)
                        ->where('id', $row->id)
                        ->update([
                            'nombres' => $nombres,
                            'apellido_paterno' => $apellidoPaterno,
                            'apellido_materno' => $apellidoMaterno,
                        ]);
                }
            });

        Schema::table($table, function (Blueprint $blueprint) {
            $blueprint->dropColumn('nombre');
        });
    }

    private function restoreNombreColumn(string $table): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        if (! Schema::hasColumn($table, 'nombres')) {
            return;
        }

        if (Schema::hasColumn($table, 'nombre')) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) {
            $blueprint->string('nombre')->nullable()->after('id');
        });

        \DB::table($table)
            ->select('id', 'nombres', 'apellido_paterno', 'apellido_materno')
            ->orderBy('id')
            ->chunkById(200, function ($rows) use ($table) {
                foreach ($rows as $row) {
                    $nombreCompleto = trim(implode(' ', array_filter([
                        $row->nombres ?? '',
                        $row->apellido_paterno ?? '',
                        $row->apellido_materno ?? '',
                    ])));

                    \DB::table($table)
                        ->where('id', $row->id)
                        ->update(['nombre' => $nombreCompleto]);
                }
            });

        $tableName = $table;
        Schema::table($table, function (Blueprint $blueprint) use ($tableName) {
            $columns = array_filter([
                'nombres',
                'apellido_paterno',
                'apellido_materno',
            ], static function ($column) use ($tableName) {
                return Schema::hasColumn($tableName, $column);
            });

            if (! empty($columns)) {
                $blueprint->dropColumn($columns);
            }
        });
    }

    private function splitNombre(string $full): array
    {
        $parts = array_values(array_filter(explode(' ', trim($full))));

        $nombres = $parts[0] ?? 'N/A';
        $apellidoPaterno = $parts[1] ?? 'N/A';
        $apellidoMaterno = count($parts) > 2
            ? implode(' ', array_slice($parts, 2))
            : ($parts[2] ?? 'N/A');

        return [$nombres, $apellidoPaterno, $apellidoMaterno];
    }
};