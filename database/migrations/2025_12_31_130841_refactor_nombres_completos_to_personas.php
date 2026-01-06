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
        // ESTUDIANTES: Cambiar 'nombre' por 'nombres', 'apellido_paterno', 'apellido_materno'
        Schema::table('estudiantes', function (Blueprint $table) {
            $table->string('nombres')->after('user_id');
            $table->string('apellido_paterno')->after('nombres');
            $table->string('apellido_materno')->after('apellido_paterno');
            // dni ya existe, no agregarlo
        });

        // Migrar datos existentes
        \DB::statement("
            UPDATE estudiantes 
            SET nombres = SUBSTRING_INDEX(nombre, ' ', 1),
                apellido_paterno = COALESCE(SUBSTRING_INDEX(SUBSTRING_INDEX(nombre, ' ', 2), ' ', -1), 'Apellido'),
                apellido_materno = COALESCE(SUBSTRING_INDEX(nombre, ' ', -1), 'Apellido')
        ");

        Schema::table('estudiantes', function (Blueprint $table) {
            $table->dropColumn('nombre');
        });

        // DOCENTES: Cambiar 'nombre' por 'nombres', 'apellido_paterno', 'apellido_materno'
        Schema::table('docentes', function (Blueprint $table) {
            $table->string('nombres')->after('user_id');
            $table->string('apellido_paterno')->after('nombres');
            $table->string('apellido_materno')->after('apellido_paterno');
            // Agregar campos si no existen
            if (!Schema::hasColumn('docentes', 'dni')) {
                $table->string('dni', 8)->unique()->nullable()->after('apellido_materno');
            }
            if (!Schema::hasColumn('docentes', 'email')) {
                $table->string('email')->unique()->nullable()->after('dni');
            }
            if (!Schema::hasColumn('docentes', 'telefono')) {
                $table->string('telefono')->nullable()->after('email');
            }
            if (!Schema::hasColumn('docentes', 'direccion')) {
                $table->string('direccion')->nullable()->after('telefono');
            }
        });

        // Migrar datos existentes
        \DB::statement("
            UPDATE docentes 
            SET nombres = SUBSTRING_INDEX(nombre, ' ', 1),
                apellido_paterno = COALESCE(SUBSTRING_INDEX(SUBSTRING_INDEX(nombre, ' ', 2), ' ', -1), 'Apellido'),
                apellido_materno = COALESCE(SUBSTRING_INDEX(nombre, ' ', -1), 'Apellido')
        ");

        Schema::table('docentes', function (Blueprint $table) {
            $table->dropColumn('nombre');
        });

        // PADRES: Cambiar 'nombre' por 'nombres', 'apellido_paterno', 'apellido_materno'
        Schema::table('padres', function (Blueprint $table) {
            $table->string('nombres')->after('user_id');
            $table->string('apellido_paterno')->after('nombres');
            $table->string('apellido_materno')->after('apellido_paterno');
            // Agregar campos si no existen
            if (!Schema::hasColumn('padres', 'dni')) {
                $table->string('dni', 8)->unique()->nullable()->after('apellido_materno');
            }
            if (!Schema::hasColumn('padres', 'email')) {
                $table->string('email')->unique()->nullable()->after('dni');
            }
            if (!Schema::hasColumn('padres', 'direccion')) {
                $table->string('direccion')->nullable()->after('telefono');
            }
            if (!Schema::hasColumn('padres', 'ocupacion')) {
                $table->string('ocupacion')->nullable()->after('direccion');
            }
        });

        // Migrar datos existentes
        \DB::statement("
            UPDATE padres 
            SET nombres = SUBSTRING_INDEX(nombre, ' ', 1),
                apellido_paterno = COALESCE(SUBSTRING_INDEX(SUBSTRING_INDEX(nombre, ' ', 2), ' ', -1), 'Apellido'),
                apellido_materno = COALESCE(SUBSTRING_INDEX(nombre, ' ', -1), 'Apellido')
        ");

        Schema::table('padres', function (Blueprint $table) {
            $table->dropColumn('nombre');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // ESTUDIANTES: Revertir cambios
        Schema::table('estudiantes', function (Blueprint $table) {
            $table->string('nombre')->after('id');
        });

        \DB::statement("
            UPDATE estudiantes 
            SET nombre = CONCAT(nombres, ' ', apellido_paterno, ' ', apellido_materno)
        ");

        Schema::table('estudiantes', function (Blueprint $table) {
            $table->dropColumn(['nombres', 'apellido_paterno', 'apellido_materno', 'dni']);
        });

        // DOCENTES: Revertir cambios
        Schema::table('docentes', function (Blueprint $table) {
            $table->string('nombre')->after('id');
        });

        \DB::statement("
            UPDATE docentes 
            SET nombre = CONCAT(nombres, ' ', apellido_paterno, ' ', apellido_materno)
        ");

        Schema::table('docentes', function (Blueprint $table) {
            $table->dropColumn(['nombres', 'apellido_paterno', 'apellido_materno', 'dni', 'email', 'telefono', 'direccion']);
        });

        // PADRES: Revertir cambios
        Schema::table('padres', function (Blueprint $table) {
            $table->string('nombre')->after('id');
        });

        \DB::statement("
            UPDATE padres 
            SET nombre = CONCAT(nombres, ' ', apellido_paterno, ' ', apellido_materno)
        ");

        Schema::table('padres', function (Blueprint $table) {
            $table->dropColumn(['nombres', 'apellido_paterno', 'apellido_materno', 'dni', 'email', 'direccion', 'ocupacion']);
        });
    }
};
