<!doctype html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>Nómina de la sección</title>
    <style>
        @page {
            margin: 28px 24px;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #111;
        }

        .encabezado {
            border-bottom: 2px solid #04ADBF;
            padding-bottom: 8px;
            margin-bottom: 12px;
        }

        h1 {
            font-size: 15px;
            margin: 0 0 2px 0;
        }

        .subtitulo {
            font-size: 10px;
            color: #555;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 5px;
        }

        th {
            background: #f2f4f5;
            font-size: 9px;
            text-transform: uppercase;
        }

        td.numero {
            text-align: center;
            width: 26px;
        }

        .pie {
            margin-top: 14px;
            font-size: 8px;
            color: #777;
            text-align: right;
        }
    </style>
</head>

<body>
    <div class="encabezado">
        <h1>Nómina de estudiantes</h1>
        <p class="subtitulo">Institución Educativa N° 51006 Túpac Amaru</p>
        <p class="subtitulo">
            {{ $seccion->grado?->nombre }} "{{ $seccion->nombre }}"
            @if ($seccion->turno)
                · Turno {{ $seccion->turno }}
            @endif
            · {{ $estudiantes->count() }} estudiantes
        </p>
    </div>

    <table>
        <thead>
            <tr>
                <th>N</th>
                <th>Apellidos y nombres</th>
                <th>DNI</th>
                <th>F. nacimiento</th>
                <th>Estado</th>
                <th>Teléfono</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($estudiantes as $indice => $estudiante)
                <tr>
                    <td class="numero">{{ $indice + 1 }}</td>
                    <td>{{ $estudiante->nombre_completo }}</td>
                    <td>{{ $estudiante->dni }}</td>
                    <td>{{ optional($estudiante->fecha_nacimiento)->format('d/m/Y') }}</td>
                    <td>{{ ucfirst($estudiante->estado) }}</td>
                    <td>{{ $estudiante->telefono }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6">La sección no tiene estudiantes registrados.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <p class="pie">Generado el {{ $generado_en }}</p>
</body>

</html>
