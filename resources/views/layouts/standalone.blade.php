<!doctype html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Twins - @yield('title', 'Twins ERP')</title>

    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="min-h-screen bg-slate-950 text-slate-100">
    <main class="w-full">
        <div class="mx-auto w-full max-w-[980px] px-4 sm:px-6 pt-8 sm:pt-10 pb-12">
            @yield('content')
        </div>
    </main>
</body>
</html>