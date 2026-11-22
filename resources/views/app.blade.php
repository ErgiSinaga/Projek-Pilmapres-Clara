<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Dento - Petualangan Sahabat Gigi Sehat</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-sky-light text-navy antialiased">
    <div id="app" class="min-h-screen flex flex-col"></div>
</body>
</html>
