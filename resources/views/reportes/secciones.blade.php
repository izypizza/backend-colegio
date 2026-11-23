<!doctype html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>Reporte de Secciones</title>
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

        .resumen {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
        }

        .resumen td {
            width: 20%;
            border: 1px solid #ddd;
            padding: 6px;
            text-align: center;
        }

        .resumen .etiqueta {
            font-size: 8px;
            text-transform: uppercase;
            color: #666;
            display: block;
        }

        .resumen .valor {
            font-size: 14px;
            font-weight: bold;
            color: #04ADBF;
        }

        table.detalle {
            width: 100%;
            border-collapse: collapse;
        }

        table.detalle th,
        table.detalle td {
            border: 1px solid #ddd;
            padding: 5px;
        }

        table.detalle th {
            background: #f2f4f5;
            font-size: 9px;
            text-transform: uppercase;
        }

        table.detalle td.numero {
            text-align: right;
        }

        tfoot td {
            background: #f7f9fa;
            font-weight: bold;
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
        <h1>Reporte de Secciones</h1>
        <p class="subtitulo">Institución Educativa N° 51006 Túpac Amaru</p>
        @if ($filtros)
            <p class="subtitulo">Filtros: {{ $filtros }}</p>
        @endif
    </div>

    <table class="resumen">
        <tr>
            <td><span class="etiqueta">Secciones</span><span class="valor">{{ $totales['secciones'] }}</span></td>
            <td><span class="etiqueta">Capacidad</span><span class="valor">{{ $totales['capacidad'] }}</span></td>
            <td><span class="etiqueta">Matriculados</span><span class="valor">{{ $totales['matriculados'] }}</span>
            </td>
            <td><span class="etiqueta">Vacantes</span><span class="valor">{{ $totales['vacantes'] }}</span></td>
            <td><span class="etiqueta">Ocupación</span><span class="valor">{{ $totales['ocupacion'] }}%</span></td>
        </tr>
    </table>

    <table class="detalle">
        <thead>
            <tr>
                <th>Nivel</th>
                <th>Grado</th>
                <th>Sección</th>
                <th>Turno</th>
                <th>Capacidad</th>
                <th>Matriculados</th>
                <th>Vacantes</th>
                <th>Ocupación</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($secciones as $seccion)
                <tr>
                    <td>{{ ucfirst($seccion['nivel']) }}</td>
                    <td>{{ $seccion['grado'] }}</td>
                    <td>{{ $seccion['nombre'] }}</td>
                    <td>{{ $seccion['turno'] ?? '—' }}</td>
                    <td class="numero">{{ $seccion['capacidad'] }}</td>
                    <td class="numero">{{ $seccion['matriculados'] }}</td>
                    <td class="numero">{{ $seccion['vacantes'] }}</td>
                    <td class="numero">{{ $seccion['ocupacion'] }}%</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4">Total</td>
                <td class="numero">{{ $totales['capacidad'] }}</td>
                <td class="numero">{{ $totales['matriculados'] }}</td>
                <td class="numero">{{ $totales['vacantes'] }}</td>
                <td class="numero">{{ $totales['ocupacion'] }}%</td>
            </tr>
        </tfoot>
    </table>

    <p class="pie">Generado el {{ $generado_en }}</p>
</body>

</html>
