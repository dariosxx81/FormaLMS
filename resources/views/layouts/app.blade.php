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
        :root {
            --brand-primary: {{ $settings['primary_color'] ?? '#4f46e5' }};
            --brand-primary-contrast: {{ $settings['primary_contrast'] ?? '#ffffff' }};
            --brand-primary-readable: {{ $settings['primary_readable_on_light'] ?? '#4f46e5' }};
            --brand-primary-soft: {{ $settings['primary_soft_bg'] ?? 'rgba(79, 70, 229, 0.10)' }};
            --brand-primary-border: {{ $settings['primary_soft_border'] ?? 'rgba(79, 70, 229, 0.26)' }};

            --brand-secondary: {{ $settings['secondary_color'] ?? '#0f172a' }};
            --brand-secondary-contrast: {{ $settings['secondary_contrast'] ?? '#ffffff' }};

            --brand-accent: {{ $settings['accent_color'] ?? '#10b981' }};
            --brand-accent-contrast: {{ $settings['accent_contrast'] ?? '#ffffff' }};
            --brand-accent-readable: {{ $settings['accent_readable_on_light'] ?? '#059669' }};
            --brand-accent-soft: {{ $settings['accent_soft_bg'] ?? 'rgba(16, 185, 129, 0.12)' }};
            --brand-accent-border: {{ $settings['accent_soft_border'] ?? 'rgba(16, 185, 129, 0.28)' }};
        }
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        [x-cloak] { display: none !important; }

        /* 1. Brand Primary Buttons & Progress Bars (with automatic WCAG text contrast!) */
        .bg-indigo-600, .bg-indigo-500 {
            background-color: var(--brand-primary) !important;
            color: var(--brand-primary-contrast) !important;
        }
        .hover\:bg-indigo-500:hover, .hover\:bg-indigo-600:hover {
            background-color: var(--brand-primary) !important;
            color: var(--brand-primary-contrast) !important;
            filter: brightness(1.07);
        }

        /* 2. Readable Brand Text on Light Surfaces (auto-darkened if brand color is too bright) */
        .text-indigo-600, .text-indigo-700, .text-indigo-800, .text-indigo-950 {
            color: var(--brand-primary-readable) !important;
        }
        .border-indigo-500, .border-indigo-600, .border-indigo-400 {
            border-color: var(--brand-primary) !important;
        }

        /* 3. Soft 10% Opacity Tints for Badges & Info Boxes (prevents tone-on-tone unreadability) */
        .bg-indigo-50, .bg-indigo-50\/40, .bg-indigo-50\/70, .bg-indigo-50\/80, .bg-indigo-100 {
            background-color: var(--brand-primary-soft) !important;
            color: var(--brand-primary-readable) !important;
        }
        .border-indigo-200, .border-indigo-300 {
            border-color: var(--brand-primary-border) !important;
        }

        /* 4. Brand Accent (Certificates & Eligibility Badges) */
        .bg-emerald-600, .bg-emerald-500 {
            background-color: var(--brand-accent) !important;
            color: var(--brand-accent-contrast) !important;
        }
        .hover\:bg-emerald-500:hover {
            background-color: var(--brand-accent) !important;
            color: var(--brand-accent-contrast) !important;
            filter: brightness(1.07);
        }

        /* 5. Top Institutional Bar & Active Nav Pills (keeps .bg-slate-900 dark for Video Player!) */
        .brand-topbar {
            background-color: var(--brand-secondary) !important;
            color: var(--brand-secondary-contrast) !important;
        }
        .brand-nav-active {
            background-color: var(--brand-primary) !important;
            color: var(--brand-primary-contrast) !important;
        }
    </style>
</head>
<body class="min-h-full flex flex-col text-slate-800 antialiased">

    @php
        $logoBg = $settings['logo_bg'] ?? 'white';
        $logoSize = $settings['logo_size'] ?? 'md';
        $logoBgClasses = match($logoBg) {
            'dark' => 'bg-slate-900 border border-slate-700 p-1.5 shadow-xs',
            'transparent' => 'bg-transparent p-0.5',
            default => 'bg-white border border-slate-200 p-1 shadow-2xs',
        };
        $logoSizeClasses = match($logoSize) {
            'sm' => 'h-8 max-w-[140px]',
            'lg' => 'h-14 max-w-[220px]',
            default => 'h-10 max-w-[175px]',
        };
    @endphp

    @auth
    <!-- Top Demo / Quick Role Switcher Bar (Colored with Secondary Brand Color + Auto Contrast) -->
    <div class="brand-topbar text-xs border-b border-black/10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-2 flex flex-wrap items-center justify-between gap-2">
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-black/20 border border-white/20">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                    Ambiente White-Label Attivo
                </span>
                <span class="opacity-60 hidden sm:inline">|</span>
                <span class="hidden sm:inline opacity-95">
                    Ente configurato: <strong>{{ $settings['provider_name'] ?? 'Ente Accreditato' }}</strong>
                    (ATECO {{ $settings['ateco_code'] ?? '79.90' }} • CP ISTAT {{ $settings['cp_istat_code'] ?? '3.4.1.5.2' }})
                </span>
            </div>
            <div class="flex items-center gap-2">
                <span class="opacity-80">Passa rapidamente a:</span>
                @php
                    $quickAdmin = \App\Models\User::where('role', 'admin')->first();
                    $quickStudents = \App\Models\User::where('role', 'student')->orderBy('id')->limit(2)->get();
                @endphp
                @if($quickAdmin && auth()->id() !== $quickAdmin->id)
                    <form method="POST" action="{{ route('demo.switch', $quickAdmin) }}" class="inline">
                        @csrf
                        <button type="submit" class="px-2.5 py-1 rounded bg-indigo-600 hover:bg-indigo-500 font-bold transition cursor-pointer shadow-2xs">
                            Vista Admin ({{ $quickAdmin->first_name }})
                        </button>
                    </form>
                @endif
                @foreach($quickStudents as $qs)
                    @if(auth()->id() !== $qs->id)
                        <form method="POST" action="{{ route('demo.switch', $qs) }}" class="inline">
                            @csrf
                            <button type="submit" class="px-2.5 py-1 rounded bg-white/15 hover:bg-white/25 border border-white/25 font-semibold transition cursor-pointer">
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
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 min-h-16 py-2 flex items-center justify-between gap-4">
            <div class="flex items-center gap-6">
                <a href="{{ auth()->user()->isAdmin() ? route('admin.dashboard') : route('student.dashboard') }}" class="flex items-center gap-3 group">
                    @if(!empty($settings['logo_url']))
                        <img src="{{ $settings['logo_url'] }}"
                             alt="Logo {{ $settings['platform_name'] ?? 'Ente' }}"
                             class="{{ $logoSizeClasses }} {{ $logoBgClasses }} w-auto object-contain rounded-xl shrink-0 transition">
                    @else
                        <div class="w-10 h-10 rounded-xl bg-indigo-600 flex items-center justify-center font-extrabold text-lg shadow-xs transition shrink-0">
                            FAD
                        </div>
                    @endif
                    <div>
                        <div class="font-bold text-slate-900 text-base leading-tight">{{ $settings['platform_name'] ?? 'FormaFAD LMS' }}</div>
                        <div class="text-[11px] text-slate-500 font-medium">{{ $settings['provider_name'] ?? 'Piattaforma E-Learning Certificata' }}</div>
                    </div>
                </a>

                @if(auth()->user()->isAdmin())
                    <nav class="hidden md:flex items-center gap-1 ml-4">
                        <a href="{{ route('admin.dashboard') }}"
                           class="px-3.5 py-2 rounded-lg text-sm font-semibold transition {{ request()->routeIs('admin.dashboard') || request()->routeIs('admin.courses.*') ? 'brand-nav-active shadow-2xs' : 'text-slate-600 hover:bg-slate-100' }}">
                            Catalogo Corsi & Moduli
                        </a>
                        <a href="{{ route('admin.users.index') }}"
                           class="px-3.5 py-2 rounded-lg text-sm font-semibold transition {{ request()->routeIs('admin.users.*') ? 'brand-nav-active shadow-2xs' : 'text-slate-600 hover:bg-slate-100' }}">
                            Anagrafica Utenti & Iscrizioni
                        </a>
                        <a href="{{ route('admin.logs.index') }}"
                           class="px-3.5 py-2 rounded-lg text-sm font-semibold transition {{ request()->routeIs('admin.logs.*') ? 'brand-nav-active shadow-2xs' : 'text-slate-600 hover:bg-slate-100' }}">
                            Registro Tracciamento & Log
                        </a>
                        <a href="{{ route('admin.settings.index') }}"
                           class="px-3.5 py-2 rounded-lg text-sm font-semibold transition {{ request()->routeIs('admin.settings.*') ? 'brand-nav-active shadow-2xs' : 'text-slate-600 hover:bg-slate-100' }}">
                            White-Label Ente
                        </a>
                    </nav>
                @else
                    <nav class="hidden md:flex items-center gap-1 ml-4">
                        <a href="{{ route('student.dashboard') }}"
                           class="px-3.5 py-2 rounded-lg text-sm font-semibold transition {{ request()->routeIs('student.dashboard') ? 'brand-nav-active shadow-2xs' : 'text-slate-600 hover:bg-slate-100' }}">
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

        <!-- Mobile Navigation Bar (Visible on Smartphone/Tablet) -->
        @if(auth()->user()->isAdmin())
            <div class="md:hidden flex items-center gap-1.5 overflow-x-auto px-4 py-2 border-t border-slate-100 bg-slate-50">
                <a href="{{ route('admin.dashboard') }}"
                   class="px-3 py-1.5 rounded-lg text-xs font-bold whitespace-nowrap {{ request()->routeIs('admin.dashboard') || request()->routeIs('admin.courses.*') ? 'brand-nav-active' : 'bg-white text-slate-700 border border-slate-200' }}">
                    Corsi & Moduli
                </a>
                <a href="{{ route('admin.users.index') }}"
                   class="px-3 py-1.5 rounded-lg text-xs font-bold whitespace-nowrap {{ request()->routeIs('admin.users.*') ? 'brand-nav-active' : 'bg-white text-slate-700 border border-slate-200' }}">
                    Utenti & Iscrizioni
                </a>
                <a href="{{ route('admin.logs.index') }}"
                   class="px-3 py-1.5 rounded-lg text-xs font-bold whitespace-nowrap {{ request()->routeIs('admin.logs.*') ? 'brand-nav-active' : 'bg-white text-slate-700 border border-slate-200' }}">
                    Audit Log
                </a>
                <a href="{{ route('admin.settings.index') }}"
                   class="px-3 py-1.5 rounded-lg text-xs font-bold whitespace-nowrap {{ request()->routeIs('admin.settings.*') ? 'brand-nav-active' : 'bg-white text-slate-700 border border-slate-200' }}">
                    White-Label
                </a>
            </div>
        @endif
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
