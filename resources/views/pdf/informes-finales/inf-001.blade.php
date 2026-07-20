<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>INF-001 — {{ $informe->numero_registro ?: 'Pendiente de asignación' }}</title>
    <style>@page { margin: 0; } html,body{margin:0}</style>
</head>
<body>@include('proyectos.informe-final.partials.inf-001-document', ['isPdf' => true])</body>
</html>
