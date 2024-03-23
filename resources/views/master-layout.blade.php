<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title')</title>
    @vite('resources/js/app.js')
    <style>
        body {
            cursor: url('/storage/icons/custom-cursor.cur'), auto;
        }

    </style>
</head>
<body id="app" class="antialiased bg-gray-800">
    <div id="app-wrapper">
        @yield('content')
    </div>
</body>
</html>
