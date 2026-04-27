<!DOCTYPE html>
<html lang="es" class="dark">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión — DJ TRUCKS & CARS</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'sans-serif'] },
                    colors: {
                        primary: { DEFAULT: '#6c63ff', hover: '#5a52d5' },
                        accent: { DEFAULT: '#00d4aa' },
                    }
                }
            }
        }
    </script>
</head>

<body class="font-sans min-h-screen flex items-center justify-center relative overflow-hidden
             bg-[#f4f6f9] text-slate-800
             dark:bg-[#0f1117] dark:text-slate-200">

    {{-- Background glows --}}
    <div class="absolute w-[600px] h-[600px] rounded-full -top-24 -right-36 pointer-events-none
                bg-[radial-gradient(circle,rgba(108,99,255,.08)_0%,transparent_70%)]
                dark:bg-[radial-gradient(circle,rgba(108,99,255,.15)_0%,transparent_70%)]"></div>
    <div class="absolute w-[400px] h-[400px] rounded-full -bottom-12 -left-24 pointer-events-none
                bg-[radial-gradient(circle,rgba(0,212,170,.06)_0%,transparent_70%)]
                dark:bg-[radial-gradient(circle,rgba(0,212,170,.10)_0%,transparent_70%)]"></div>

    <div class="relative z-10 w-full max-w-[400px] mx-4 rounded-3xl p-8 shadow-2xl border
                bg-white border-slate-200
                dark:bg-[#1a1d27] dark:border-[#2d3148]">

        {{-- Logo --}}
        <div class="text-center mb-8">
            <div
                class="w-16 h-16 mx-auto mb-4 bg-gradient-to-br from-primary to-accent rounded-2xl flex items-center justify-center">
                <svg class="w-8 h-8 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 0 0-10.026 0 1.106 1.106 0 0 0-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12" />
                </svg>
            </div>
            <h1 class="text-xl font-bold">DJ TRUCKS & CARS</h1>
            <p class="text-sm mt-1 text-slate-500 dark:text-slate-400">Sistema de gestión empresarial</p>
        </div>

        {{-- Errors --}}
        @if($errors->any())
            <div class="mb-4 px-4 py-3 rounded-xl text-sm bg-red-500/10 border border-red-500/30 text-red-500">
                {{ $errors->first() }}</div>
        @endif
        @if(session('error'))
            <div class="mb-4 px-4 py-3 rounded-xl text-sm bg-red-500/10 border border-red-500/30 text-red-500">
                {{ session('error') }}</div>
        @endif

        {{-- Form --}}
        <form method="POST" action="{{ route('login.post') }}">
            @csrf
            <div class="mb-5">
                <label for="email" class="block text-xs font-semibold mb-2 text-slate-500 dark:text-slate-400">Correo
                    electrónico</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" placeholder="admin@erp.com"
                    required autofocus class="w-full px-4 py-3 rounded-xl text-sm outline-none border transition-colors duration-200
                              bg-slate-50 border-slate-200 text-slate-800 focus:border-primary focus:ring-1 focus:ring-primary/30
                              dark:bg-[#242736] dark:border-[#2d3148] dark:text-slate-200">
            </div>
            <div class="mb-5">
                <label for="password"
                    class="block text-xs font-semibold mb-2 text-slate-500 dark:text-slate-400">Contraseña</label>
                <input type="password" id="password" name="password" placeholder="••••••••" required class="w-full px-4 py-3 rounded-xl text-sm outline-none border transition-colors duration-200
                              bg-slate-50 border-slate-200 text-slate-800 focus:border-primary focus:ring-1 focus:ring-primary/30
                              dark:bg-[#242736] dark:border-[#2d3148] dark:text-slate-200">
            </div>
            <button type="submit"
                class="w-full py-3 rounded-xl text-white font-semibold cursor-pointer border-none transition-opacity duration-200 hover:opacity-90 bg-gradient-to-r from-primary to-primary-hover">
                Iniciar sesión →
            </button>
        </form>

    </div>

    {{-- Footer --}}
    <div class="absolute bottom-6 w-full text-center text-sm text-slate-500 dark:text-slate-400 z-10 font-medium">
        Desarrollado por <span class="text-primary font-semibold">DeveaD</span>
    </div>

    {{-- Theme: Apply saved preference --}}
    <script>
            (function () {
                const saved = localStorage.getItem('erp_theme');
                if (saved === 'light') {
                    document.documentElement.classList.remove('dark');
                }
            })();
    </script>
</body>

</html>