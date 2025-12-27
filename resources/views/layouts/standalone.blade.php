<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>@yield('title', 'Twins')</title>

    {{-- Always-works styling for standalone pages (no Vite dependency) --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-slate-950 text-slate-100">
    <div class="min-h-screen grid place-items-center px-4 py-10">
        @yield('content')
    </div>
</body>
</html>