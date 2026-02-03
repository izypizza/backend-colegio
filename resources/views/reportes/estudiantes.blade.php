<!doctype html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>Reporte de Estudiantes</title>
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
    <h1>Reporte de Estudiantes</h1>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Nombre Completo</th>
                <th>DNI</th>
                <th>Grado</th>
                <th>Seccion</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($estudiantes as $e)
                <tr>
                    <td>{{ $e->id }}</td>
                    <td>{{ $e->nombre_completo }}</td>
                    <td>{{ $e->dni }}</td>
                    <td>{{ optional($e->seccion?->grado)->nombre }}</td>
                    <td>{{ optional($e->seccion)->nombre }}</td>
                    <td>{{ $e->estado }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>

</html>
