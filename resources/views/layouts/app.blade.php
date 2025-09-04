<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Heritage Explorer')</title>
    @yield('meta')
    @yield('styles')
    @vite('resources/css/app.css')
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 text-gray-800">

    <header class="bg-white shadow p-4">
        <div class="container mx-auto flex justify-between items-center">
            <h1 class="text-xl font-bold">
                <a href="{{ url('01_module_c') }}">Heritage Explorer</a>
            </h1>
            <form action="{{ url('01_module_c/search') }}" method="GET" class="flex">
                <input type="text" name="q" placeholder="Search..." class="border rounded-l px-2 py-1"
                    value="{{ request('q') }}">
                <button type="submit" class="bg-black text-white px-3 rounded-r">Go</button>
            </form>
        </div>
    </header>

    <main class="container mx-auto py-8">
        @yield('content')
    </main>

    <footer class="bg-white shadow mt-8 p-4 text-center text-sm">
        &copy; {{ date('Y') }} Heritage Project
    </footer>

    @yield('scripts')
</body>

</html>
