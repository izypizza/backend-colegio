<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ciclo del Currículo Nacional al que pertenece cada grado.
     * Primaria: III (1.°-2.°), IV (3.°-4.°), V (5.°-6.°).
     * Secundaria: VI (1.°-2.°), VII (3.°-5.°).
     */
    public function up(): void
    {
        if (Schema::hasColumn('grados', 'ciclo')) {
            return;
        }

        Schema::table('grados', function (Blueprint $table) {
            $table->string('ciclo', 3)->nullable()->after('nivel');
        });

        foreach (DB::table('grados')->get(['id', 'nombre', 'nivel']) as $grado) {
            $ciclo = $this->cicloDelGrado($grado->nombre, $grado->nivel);

            if ($ciclo !== null) {
                DB::table('grados')->where('id', $grado->id)->update(['ciclo' => $ciclo]);
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('grados', 'ciclo')) {
            return;
        }

        Schema::table('grados', function (Blueprint $table) {
            $table->dropColumn('ciclo');
        });
    }

    private function cicloDelGrado(?string $nombre, ?string $nivel): ?string
    {
        $numero = null;
        if ($nombre !== null && preg_match('/^\s*(\d)/', $nombre, $coincidencia) === 1) {
            $numero = (int) $coincidencia[1];
        }

        if ($numero === null) {
            return null;
        }

        if ($nivel === 'primaria') {
            return match (true) {
                $numero <= 2 => 'III',
                $numero <= 4 => 'IV',
                default => 'V',
            };
        }

        if ($nivel === 'secundaria') {
            return $numero <= 2 ? 'VI' : 'VII';
        }

        return null;
    }
};
