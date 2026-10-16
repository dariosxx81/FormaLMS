<!DOCTYPE html>
<html lang="it" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', $settings['platform_name'] ?? 'FormaFAD LMS')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="min-h-full flex flex-col text-slate-800 antialiased">

    @auth
    <!-- Top Demo / Quick Role Switcher Bar -->
    <div class="bg-slate-900 text-slate-200 text-xs border-b border-slate-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-2 flex flex-wrap items-center justify-between gap-2">
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                    Ambiente White-Label Attivo
                </span>
                <span class="text-slate-400 hidden sm:inline">|</span>
                <span class="text-slate-300 hidden sm:inline">
                    Ente configurato: <strong class="text-white">{{ $settings['provider_name'] ?? 'Ente Accreditato' }}</strong>
                    (ATECO {{ $settings['ateco_code'] ?? '79.90' }} • CP ISTAT {{ $settings['cp_istat_code'] ?? '3.4.1.5.2' }})
                </span>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-slate-400">Passa rapidamente a:</span>
                @php
                    $quickAdmin = \App\Models\User::where('role', 'admin')->first();
                    $quickStudents = \App\Models\User::where('role', 'student')->orderBy('id')->limit(2)->get();
                @endphp
                @if($quickAdmin && auth()->id() !== $quickAdmin->id)
                    <form method="POST" action="{{ route('demo.switch', $quickAdmin) }}" class="inline">
                        @csrf
                        <button type="submit" class="px-2.5 py-1 rounded bg-indigo-600 hover:bg-indigo-500 text-white font-semibold transition cursor-pointer">
                            Vista Admin ({{ $quickAdmin->first_name }})
                        </button>
                    </form>
                @endif
                @foreach($quickStudents as $qs)
                    @if(auth()->id() !== $qs->id)
                        <form method="POST" action="{{ route('demo.switch', $qs) }}" class="inline">
                            @csrf
                            <button type="submit" class="px-2.5 py-1 rounded bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 font-medium transition cursor-pointer">
                                Corsista: {{ $qs->full_name }}
                            </button>
                        </form>
                    @endif
                @endforeach
            </div>
        </div>
    </div>

    <!-- Main Navigation Header -->
    <header class="bg-white border-b border-slate-200 sticky top-0 z-30 shadow-xs">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between gap-4">
            <div class="flex items-center gap-6">
                <a href="{{ auth()->user()->isAdmin() ? route('admin.dashboard') : route('student.dashboard') }}" class="flex items-center gap-3 group">
                    <div class="w-10 h-10 rounded-xl bg-slate-900 text-white flex items-center justify-center font-extrabold text-lg shadow-xs group-hover:bg-indigo-600 transition">
                        FAD
                    </div>
                    <div>
                        <div class="font-bold text-slate-900 text-base leading-tight">{{ $settings['platform_name'] ?? 'FormaFAD LMS' }}</div>
                        <div class="text-[11px] text-slate-500 font-medium">{{ $settings['provider_name'] ?? 'Piattaforma E-Learning Certificata' }}</div>
                    </div>
                </a>

                @if(auth()->user()->isAdmin())
                    <nav class="hidden md:flex items-center gap-1 ml-4">
                        <a href="{{ route('admin.dashboard') }}"
                           class="px-3.5 py-2 rounded-lg text-sm font-semibold transition {{ request()->routeIs('admin.dashboard') || request()->routeIs('admin.courses.*') ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-100' }}">
                            Catalogo Corsi & Moduli
                        </a>
                        <a href="{{ route('admin.users.index') }}"
                           class="px-3.5 py-2 rounded-lg text-sm font-semibold transition {{ request()->routeIs('admin.users.*') ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-100' }}">
                            Anagrafica Utenti & Iscrizioni
                        </a>
                        <a href="{{ route('admin.logs.index') }}"
                           class="px-3.5 py-2 rounded-lg text-sm font-semibold transition {{ request()->routeIs('admin.logs.*') ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-100' }}">
                            Registro Tracciamento & Log
                        </a>
                        <a href="{{ route('admin.settings.index') }}"
                           class="px-3.5 py-2 rounded-lg text-sm font-semibold transition {{ request()->routeIs('admin.settings.*') ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-100' }}">
                            White-Label Ente
                        </a>
                    </nav>
                @else
                    <nav class="hidden md:flex items-center gap-1 ml-4">
                        <a href="{{ route('student.dashboard') }}"
                           class="px-3.5 py-2 rounded-lg text-sm font-semibold transition {{ request()->routeIs('student.dashboard') ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-100' }}">
                            I Miei Corsi & Attestati
                        </a>
                    </nav>
                @endif
            </div>

            <div class="flex items-center gap-3">
                <div class="hidden sm:block text-right">
                    <div class="text-sm font-bold text-slate-900">{{ auth()->user()->full_name }}</div>
                    <div class="text-xs text-slate-500">
                        @if(auth()->user()->isAdmin())
                            <span class="text-indigo-600 font-semibold">Amministratore Piattaforma</span>
                        @else
                            CF: <span class="font-mono">{{ auth()->user()->fiscal_code ?: 'N/D' }}</span>
                        @endif
                    </div>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="px-3 py-2 rounded-lg text-xs font-semibold text-slate-600 hover:text-red-600 hover:bg-red-50 border border-slate-200 transition cursor-pointer">
                        Esci
                    </button>
                </form>
            </div>
        </div>
    </header>
    @endauth

    <!-- Flash Notifications -->
    @if(session('success') || (isset($errors) && $errors->any()))
        <div class="max-w-7xl mx-auto w-full px-4 sm:px-6 lg:px-8 mt-4">
            @if(session('success'))
                <div x-data="{ show: true }" x-show="show" class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-xl flex items-center justify-between shadow-2xs">
                    <div class="flex items-center gap-2.5 text-sm font-medium">
                        <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span>{{ session('success') }}</span>
                    </div>
                    <button @click="show = false" class="text-emerald-600 hover:text-emerald-900 text-sm font-bold">&times;</button>
                </div>
            @endif

            @if(isset($errors) && $errors->any())
                <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-xl shadow-2xs mt-2">
                    <div class="font-bold text-sm mb-1">Attenzione: verifica i dati inseriti</div>
                    <ul class="list-disc list-inside text-xs space-y-0.5">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    @endif

    <!-- Main Content -->
    <main class="flex-1">
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="bg-white border-t border-slate-200 mt-12 py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-slate-500">
            <div>
                <strong>{{ $settings['platform_name'] ?? 'FormaFAD LMS' }}</strong> — {{ $settings['provider_name'] ?? 'Ente di Formazione Accreditato' }}
                • Sistema conforme alle Linee Guida B del Ministero del Turismo (D.M. 26 giugno 2024, n. 88)
            </div>
            <div>
                Tracciamento Telemetrico Certificato • Supporto: <a href="mailto:{{ $settings['support_email'] ?? 'segreteria@ente.it' }}" class="text-indigo-600 hover:underline">{{ $settings['support_email'] ?? 'segreteria@ente.it' }}</a>
            </div>
        </div>
    </footer>

</body>
</html>
