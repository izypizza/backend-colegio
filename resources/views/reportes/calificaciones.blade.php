<!doctype html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>Reporte de Calificaciones</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #111;
        }

        h1 {
            font-size: 18px;
            margin-bottom: 12px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 6px;
        }

        th {
            background: #f5f5f5;
        }
    </style>
</head>

<body>
    <h1>Reporte de Calificaciones</h1>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Estudiante</th>
                <th>DNI</th>
                <th>Grado</th>
                <th>Seccion</th>
                <th>Materia</th>
                <th>Periodo</th>
                <th>Nota</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($calificaciones as $c)
                <tr>
                    <td>{{ $c->id }}</td>
                    <td>{{ optional($c->estudiante)->nombre_completo }}</td>
                    <td>{{ optional($c->estudiante)->dni }}</td>
                    <td>{{ optional($c->estudiante?->seccion?->grado)->nombre }}</td>
                    <td>{{ optional($c->estudiante?->seccion)->nombre }}</td>
                    <td>{{ optional($c->materia)->nombre }}</td>
                    <td>{{ optional($c->periodoAcademico)->nombre }}</td>
                    <td>{{ $c->nota }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>

</html>
