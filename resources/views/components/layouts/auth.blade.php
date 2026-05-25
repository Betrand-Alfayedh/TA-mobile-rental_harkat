@props(['title' => 'Login', 'bg' => 'bg-gray-100'])

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="{{ $bg }} flex items-center justify-center h-screen">
    {{ $slot }}
</body>
</html>
