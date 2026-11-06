<!DOCTYPE html>
<html lang="it" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', $settings['platform_name'] ?? 'FormaFAD LMS')</title>
    @if(!empty($settings['favicon_url']))
        <link rel="icon" href="{{ $settings['favicon_url'] }}">
        <link rel="alternate icon" href="{{ $settings['favicon_url'] }}">
    @else
        <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
        <link rel="alternate icon" href="{{ asset('favicon.ico') }}">
    @endif
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
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

            --brand-secondary: {{ $settings['secondary_color'] ?? '#1e1b4b' }};
            --brand-secondary-contrast: {{ $settings['secondary_contrast'] ?? '#ffffff' }};
            --brand-secondary-muted: {{ $settings['secondary_muted'] ?? 'rgba(255, 255, 255, 0.78)' }};
            --brand-secondary-surface: {{ $settings['secondary_surface'] ?? 'rgba(255, 255, 255, 0.11)' }};
            --brand-secondary-border: {{ $settings['secondary_border'] ?? 'rgba(255, 255, 255, 0.20)' }};
            --brand-secondary-highlight: {{ $settings['secondary_highlight'] ?? '#a5b4fc' }};

            --brand-accent: {{ $settings['accent_color'] ?? '#10b981' }};
            --brand-accent-contrast: {{ $settings['accent_contrast'] ?? '#ffffff' }};
            --brand-accent-readable: {{ $settings['accent_readable_on_light'] ?? '#059669' }};
            --brand-accent-soft: {{ $settings['accent_soft_bg'] ?? 'rgba(16, 185, 129, 0.12)' }};
            --brand-accent-border: {{ $settings['accent_soft_border'] ?? 'rgba(16, 185, 129, 0.28)' }};

            --brand-heading: {{ $settings['brand_heading'] ?? '#11102e' }};
            --brand-body: {{ $settings['brand_body'] ?? '#1e1b4b' }};
        }
        body { font-family: 'Plus Jakarta Sans', sans-serif; color: var(--brand-body); }
        [x-cloak] { display: none !important; }

        /* 1. Brand Primary Buttons & Progress Bars (with automatic tonal text contrast) */
        .bg-indigo-600, .bg-indigo-500 {
            background-color: var(--brand-primary) !important;
            color: var(--brand-primary-contrast) !important;
        }
        .hover\:bg-indigo-500:hover, .hover\:bg-indigo-600:hover {
            background-color: var(--brand-primary) !important;
            color: var(--brand-primary-contrast) !important;
            filter: brightness(1.07);
        }

        /* 2. Readable Brand Text on Light Surfaces */
        .text-indigo-600, .text-indigo-700, .text-indigo-800, .text-indigo-950 {
            color: var(--brand-primary-readable) !important;
        }
        .border-indigo-500, .border-indigo-600, .border-indigo-400 {
            border-color: var(--brand-primary) !important;
        }

        /* 3. Soft 10% Opacity Tints for Badges & Info Boxes */
        .bg-indigo-50, .bg-indigo-50\/40, .bg-indigo-50\/70, .bg-indigo-50\/80, .bg-indigo-100 {
            background-color: var(--brand-primary-soft) !important;
            color: var(--brand-primary-readable) !important;
        }
        .border-indigo-200, .border-indigo-300 {
            border-color: var(--brand-primary-border) !important;
        }

        /* 4. Brand Accent (Certificates & Eligibility Badges) */
        .bg-brand-accent {
            background-color: var(--brand-accent) !important;
            color: var(--brand-accent-contrast) !important;
        }
        .text-brand-accent {
            color: var(--brand-accent-readable) !important;
        }
        .progress-success-bar {
            background-color: #10b981 !important; /* Emerald green AgID standard for completed / passed requirements */
        }
        .progress-active-bar {
            background-color: var(--brand-primary) !important;
        }

        /* 5. Harmonize ALL .bg-slate-900 Banners, Panels, Buttons & Active Tabs with --brand-secondary (no fixed #0f172a!) */
        .bg-slate-900, .brand-topbar {
            background-color: var(--brand-secondary) !important;
            color: var(--brand-secondary-contrast) !important;
        }
        .hover\:bg-slate-800:hover, .hover\:bg-slate-900:hover {
            background-color: var(--brand-secondary) !important;
            color: var(--brand-secondary-contrast) !important;
            filter: brightness(1.12);
        }

        /* Harmonize all child fonts, sub-cards & borders inside .bg-slate-900 panels */
        .bg-slate-900 .text-white,
        .bg-slate-900 .text-slate-100,
        .bg-slate-900 .text-slate-200 {
            color: var(--brand-secondary-contrast) !important;
        }
        .bg-slate-900 .text-slate-300,
        .bg-slate-900 .text-slate-400 {
            color: var(--brand-secondary-muted) !important;
        }
        .bg-slate-900 .text-indigo-300,
        .bg-slate-900 .text-indigo-400 {
            color: var(--brand-secondary-highlight) !important;
        }
        .bg-slate-900 .bg-slate-800,
        .bg-slate-900 .bg-slate-800\/80 {
            background-color: var(--brand-secondary-surface) !important;
            color: var(--brand-secondary-contrast) !important;
        }
        .bg-slate-900 .border-slate-700,
        .bg-slate-900 .border-slate-800 {
            border-color: var(--brand-secondary-border) !important;
        }

        /* 6. Harmonize Headings (.text-slate-900) on light cards with deep brand tone instead of #0f172a */
        .text-slate-900 {
            color: var(--brand-heading) !important;
        }
        .bg-slate-900.text-white, .bg-slate-900 .text-slate-900 {
            color: var(--brand-secondary-contrast) !important;
        }

        .brand-nav-active {
            background-color: var(--brand-primary) !important;
            color: var(--brand-primary-contrast) !important;
        }
        .logo-box-dark {
            background-color: var(--brand-secondary) !important;
            border-color: var(--brand-secondary-border) !important;
        }
    </style>
</head>
<body class="min-h-full flex flex-col antialiased">

    @php
        $logoBg = $settings['logo_bg'] ?? 'white';
        $logoSize = $settings['logo_size'] ?? 'md';
        $hideBrandText = !empty($settings['logo_url']) && ($settings['hide_brand_text_with_logo'] ?? '0') === '1';
        $logoBgClasses = match($logoBg) {
            'dark' => 'logo-box-dark border p-1.5 shadow-xs',
            'transparent' => 'bg-transparent p-0.5',
            default => 'bg-white border border-slate-200 p-1 shadow-2xs',
        };
        $logoSizeClasses = match($logoSize) {
            'sm' => 'h-8 max-w-[150px]',
            'lg' => 'h-13 max-w-[230px]',
            default => 'h-10 max-w-[190px]',
        };
        $quickAdmin = auth()->check() ? \App\Models\User::where('role', 'admin')->first() : null;
        $quickStudents = auth()->check() ? \App\Models\User::where('role', 'student')->orderBy('id')->limit(2)->get() : collect();
    @endphp

    @auth
        @if(auth()->user()->isAdmin())
            <!-- ===================================================================== -->
            <!-- LAYOUT ADMIN: SIDEBAR LATERALE FISSA (STILE GESTIONALE SAAS)          -->
            <!-- ===================================================================== -->
            <div x-data="{ mobileMenuOpen: false, profileDropdown: false }" class="min-h-screen flex bg-slate-50/70">

                <!-- Mobile Backdrop -->
                <div x-cloak x-show="mobileMenuOpen" @click="mobileMenuOpen = false"
                     class="fixed inset-0 z-40 bg-slate-950/50 backdrop-blur-xs lg:hidden"></div>

                <!-- Sidebar Navigation (Fixed on Desktop, Drawer on Mobile) -->
                <aside :class="mobileMenuOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
                       class="fixed lg:sticky top-0 left-0 z-50 h-screen w-64 shrink-0 bg-white border-r border-slate-200 flex flex-col justify-between transition-transform duration-200 ease-in-out">

                    <!-- Top: Brand Logo -->
                    <div>
                        <div class="min-h-18 px-5 py-4 border-b border-slate-100 flex items-center justify-between gap-2">
                            <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 min-w-0">
                                @if(!empty($settings['logo_url']))
                                    <img src="{{ $settings['logo_url'] }}"
                                         alt="Logo {{ $settings['platform_name'] ?? 'Ente' }}"
                                         class="{{ $logoSizeClasses }} {{ $logoBgClasses }} w-auto object-contain rounded-xl shrink-0 transition">
                                @else
                                    <div class="w-10 h-10 rounded-xl bg-indigo-600 flex items-center justify-center font-extrabold text-base shadow-xs shrink-0">
                                        FAD
                                    </div>
                                @endif
                                @if(!$hideBrandText)
                                    <div class="truncate">
                                        <div class="font-extrabold text-slate-900 text-sm leading-tight truncate">{{ $settings['platform_name'] ?? 'FormaFAD LMS' }}</div>
                                        <div class="text-[11px] text-slate-400 font-medium truncate">Pannello Gestione Ente</div>
                                    </div>
                                @endif
                            </a>
                            <button @click="mobileMenuOpen = false" class="lg:hidden text-slate-400 hover:text-slate-700 p-1">
                                &times;
                            </button>
                        </div>

                        <!-- Menu Links -->
                        <nav class="p-3.5 space-y-5">
                            <div>
                                <div class="px-3 mb-2 text-[10px] font-extrabold uppercase tracking-wider text-slate-400">
                                    Didattica & Corsi
                                </div>
                                <div class="space-y-1">
                                    <a href="{{ route('admin.dashboard') }}"
                                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-bold transition {{ request()->routeIs('admin.dashboard') ? 'brand-nav-active shadow-xs' : 'text-slate-600 hover:bg-slate-100' }}">
                                        <svg class="w-4 h-4 shrink-0 opacity-85" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                                        <span>Dashboard</span>
                                    </a>

                                    <a href="{{ route('admin.courses.index') }}"
                                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-bold transition {{ request()->routeIs('admin.courses.*') ? 'brand-nav-active shadow-xs' : 'text-slate-600 hover:bg-slate-100' }}">
                                        <svg class="w-4 h-4 shrink-0 opacity-85" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                                        <span>Corsi</span>
                                    </a>

                                    <a href="{{ route('admin.users.index') }}"
                                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-bold transition {{ request()->routeIs('admin.users.*') ? 'brand-nav-active shadow-xs' : 'text-slate-600 hover:bg-slate-100' }}">
                                        <svg class="w-4 h-4 shrink-0 opacity-85" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                        <span>Anagrafica Corsisti</span>
                                    </a>
                                </div>
                            </div>

                            <div>
                                <div class="px-3 mb-2 text-[10px] font-extrabold uppercase tracking-wider text-slate-400">
                                    Controllo & Configurazione
                                </div>
                                <div class="space-y-1">
                                    <a href="{{ route('admin.logs.index') }}"
                                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-bold transition {{ request()->routeIs('admin.logs.*') ? 'brand-nav-active shadow-xs' : 'text-slate-600 hover:bg-slate-100' }}">
                                        <svg class="w-4 h-4 shrink-0 opacity-85" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                        <span>Registro Audit & Log</span>
                                    </a>

                                    <a href="{{ route('admin.emails.index') }}"
                                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-bold transition {{ request()->routeIs('admin.emails.*') ? 'brand-nav-active shadow-xs' : 'text-slate-600 hover:bg-slate-100' }}">
                                        <svg class="w-4 h-4 shrink-0 opacity-85" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                        <span>Log Email Inviate</span>
                                    </a>

                                    <a href="{{ route('admin.media.index') }}"
                                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-bold transition {{ request()->routeIs('admin.media.*') ? 'brand-nav-active shadow-xs' : 'text-slate-600 hover:bg-slate-100' }}">
                                        <svg class="w-4 h-4 shrink-0 opacity-85" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                        <span>Libreria Media & File</span>
                                    </a>

                                    <a href="{{ route('admin.settings.index') }}"
                                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-bold transition {{ request()->routeIs('admin.settings.*') ? 'brand-nav-active shadow-xs' : 'text-slate-600 hover:bg-slate-100' }}">
                                        <svg class="w-4 h-4 shrink-0 opacity-85" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                        <span>Impostazioni</span>
                                    </a>
                                </div>
                            </div>
                        </nav>
                    </div>

                    <!-- Bottom: Admin Profile -->
                    <div class="p-3.5 border-t border-slate-100 bg-slate-50/60 relative" @click.outside="profileDropdown = false">
                        <div x-cloak x-show="profileDropdown" x-transition
                             class="absolute bottom-full mb-2 left-3.5 right-3.5 bg-white rounded-xl border border-slate-200 shadow-2xl p-2 z-50 text-xs space-y-1">
                            <div class="px-2.5 py-1.5 border-b border-slate-100 mb-1">
                                <div class="font-extrabold text-slate-900">{{ auth()->user()->full_name }}</div>
                                <div class="text-[10px] text-slate-500">{{ auth()->user()->email }}</div>
                            </div>
                            <a href="{{ route('admin.dashboard') }}" class="block px-2.5 py-2 rounded-lg hover:bg-slate-100 text-slate-700 font-semibold">
                                📊 Dashboard
                            </a>
                            <a href="{{ route('profile.show') }}" class="block px-2.5 py-2 rounded-lg hover:bg-slate-100 text-slate-700 font-semibold">
                                👤 Il Mio Profilo & Sicurezza
                            </a>
                            <a href="{{ route('admin.settings.index') }}" class="block px-2.5 py-2 rounded-lg hover:bg-slate-100 text-slate-700 font-semibold">
                                ⚙️ Impostazioni
                            </a>
                            <div class="border-t border-slate-100 my-1"></div>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="w-full text-left px-2.5 py-2 rounded-lg hover:bg-red-50 text-red-600 font-bold flex items-center gap-2 cursor-pointer">
                                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                                    <span>Esci dalla sessione (Logout)</span>
                                </button>
                            </form>
                        </div>

                        <div class="flex items-center gap-1.5">
                            <button type="button" @click="profileDropdown = !profileDropdown"
                                    class="flex-1 min-w-0 flex items-center justify-between gap-2 p-2.5 rounded-xl bg-white border border-slate-200 hover:border-slate-300 text-left transition cursor-pointer shadow-2xs">
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <div class="w-8 h-8 rounded-lg bg-indigo-600 flex items-center justify-center font-extrabold text-xs shrink-0">
                                        {{ strtoupper(substr(auth()->user()->first_name, 0, 1) . substr(auth()->user()->last_name, 0, 1)) }}
                                    </div>
                                    <div class="truncate">
                                        <div class="text-xs font-extrabold text-slate-900 truncate">{{ auth()->user()->full_name }}</div>
                                        <div class="text-[10px] text-slate-400 font-semibold">Amministratore &#9650;</div>
                                    </div>
                                </div>
                            </button>

                            <form method="POST" action="{{ route('logout') }}" class="shrink-0">
                                @csrf
                                <button type="submit" title="Esci dalla sessione (Logout)"
                                        class="p-2.5 rounded-xl bg-white hover:bg-red-50 text-slate-500 hover:text-red-600 border border-slate-200 hover:border-red-200 transition cursor-pointer shadow-2xs">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                                </button>
                            </form>
                        </div>
                    </div>
                </aside>

                <!-- Right Main Workspace -->
                <div class="flex-1 min-w-0 flex flex-col">
                    <!-- Mobile Header Bar (only on small screens) -->
                    <div class="lg:hidden bg-white border-b border-slate-200 px-4 py-3 flex items-center justify-between sticky top-0 z-30">
                        <button @click="mobileMenuOpen = true" class="p-2 rounded-lg border border-slate-200 text-slate-700">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                        </button>
                        <span class="font-extrabold text-sm text-slate-900">{{ $settings['platform_name'] ?? 'FormaFAD LMS' }}</span>
                        <a href="{{ route('admin.settings.index') }}" class="text-xs font-bold text-indigo-600">White-Label</a>
                    </div>

                    @if(session('success') || (isset($errors) && $errors->any()))
                        <div class="max-w-7xl mx-auto w-full px-4 sm:px-6 lg:px-8 pt-5">
                            @if(session('success'))
                                <div x-data="{ show: true }" x-show="show" class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-xl flex items-center justify-between shadow-2xs">
                                    <div class="flex items-center gap-2.5 text-xs font-bold">
                                        <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        <span>{{ session('success') }}</span>
                                    </div>
                                    <button @click="show = false" class="text-emerald-600 hover:text-emerald-900 text-sm font-bold cursor-pointer">&times;</button>
                                </div>
                            @endif

                            @if(isset($errors) && $errors->any())
                                <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-xl shadow-2xs mt-2">
                                    <div class="font-bold text-xs mb-1">Attenzione: verifica i dati inseriti</div>
                                    <ul class="list-disc list-inside text-xs space-y-0.5">
                                        @foreach($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                        </div>
                    @endif

                    <main class="flex-1">
                        @yield('content')
                    </main>

                    <footer class="bg-white border-t border-slate-200/80 mt-12 py-4">
                        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-2 text-[11px] text-slate-400">
                            <div>
                                <strong>{{ $settings['platform_name'] ?? 'FormaFAD LMS' }}</strong> — {{ $settings['provider_name'] ?? 'Ente Accreditato' }} (ATECO {{ $settings['ateco_code'] ?? '79.90' }} • CP ISTAT {{ $settings['cp_istat_code'] ?? '3.4.1.5.2' }})
                            </div>
                            <div>
                                Supporto: <a href="mailto:{{ $settings['support_email'] ?? 'segreteria@ente.it' }}" class="text-indigo-600 hover:underline">{{ $settings['support_email'] ?? 'segreteria@ente.it' }}</a>
                            </div>
                        </div>
                    </footer>
                </div>
            </div>
        @else
            <!-- ===================================================================== -->
            <!-- LAYOUT CORSISTA: HEADER MINIMALE STILE STREAMING + MENU PROFILO        -->
            <!-- ===================================================================== -->
            <header x-data="{ profileDropdown: false }" class="bg-white border-b border-slate-200 sticky top-0 z-30 shadow-2xs">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 min-h-16 py-2 flex items-center justify-between gap-4">
                    <div class="flex items-center gap-6">
                        <a href="{{ route('student.dashboard') }}" class="flex items-center gap-3 group">
                            @if(!empty($settings['logo_url']))
                                <img src="{{ $settings['logo_url'] }}"
                                     alt="Logo {{ $settings['platform_name'] ?? 'Ente' }}"
                                     class="{{ $logoSizeClasses }} {{ $logoBgClasses }} w-auto object-contain rounded-xl shrink-0 transition">
                            @else
                                <div class="w-10 h-10 rounded-xl bg-indigo-600 flex items-center justify-center font-extrabold text-base shadow-xs shrink-0">
                                    FAD
                                </div>
                            @endif
                            @if(!$hideBrandText)
                                <div>
                                    <div class="font-extrabold text-slate-900 text-base leading-tight">{{ $settings['platform_name'] ?? 'FormaFAD LMS' }}</div>
                                    <div class="text-[11px] text-slate-500 font-medium">{{ $settings['provider_name'] ?? 'Aula Didattica Telematica' }}</div>
                                </div>
                            @endif
                        </a>

                        <nav class="hidden md:flex items-center gap-1.5 ml-2">
                            <a href="{{ route('student.dashboard') }}"
                               class="px-3.5 py-2 rounded-xl text-xs font-bold transition {{ request()->routeIs('student.dashboard') && request('tab') !== 'certificates' ? 'brand-nav-active shadow-2xs' : 'text-slate-600 hover:bg-slate-100' }}">
                                📚 I Miei Corsi
                            </a>
                            <a href="{{ route('student.dashboard', ['tab' => 'certificates']) }}"
                               class="px-3.5 py-2 rounded-xl text-xs font-bold transition {{ request('tab') === 'certificates' ? 'brand-nav-active shadow-2xs' : 'text-slate-600 hover:bg-slate-100' }}">
                                🏅 Attestati & Storico Presenze
                            </a>
                        </nav>
                    </div>

                    <!-- Right: Student Profile Pill & Menu Dropdown -->
                    <div class="relative" @click.outside="profileDropdown = false">
                        <button type="button" @click="profileDropdown = !profileDropdown"
                                class="flex items-center gap-2.5 px-3 py-1.5 rounded-xl bg-slate-50 hover:bg-slate-100 border border-slate-200 transition cursor-pointer">
                            <div class="w-8 h-8 rounded-lg bg-indigo-600 flex items-center justify-center font-extrabold text-xs">
                                {{ strtoupper(substr(auth()->user()->first_name, 0, 1) . substr(auth()->user()->last_name, 0, 1)) }}
                            </div>
                            <div class="hidden sm:block text-left">
                                <div class="text-xs font-extrabold text-slate-900 leading-tight">{{ auth()->user()->full_name }}</div>
                                <div class="text-[10px] text-slate-500 font-mono">{{ auth()->user()->fiscal_code ?: 'Corsista' }}</div>
                            </div>
                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>

                        <div x-cloak x-show="profileDropdown" x-transition
                             class="absolute right-0 mt-2 w-64 bg-white rounded-xl border border-slate-200 shadow-xl p-2 z-50 text-xs space-y-1">
                            <div class="px-2.5 py-1.5 border-b border-slate-100 mb-1">
                                <div class="font-extrabold text-slate-900">{{ auth()->user()->full_name }}</div>
                                <div class="text-[10px] text-slate-500">{{ auth()->user()->email }}</div>
                            </div>

                            <a href="{{ route('student.dashboard') }}" class="block px-2.5 py-2 rounded-lg hover:bg-slate-100 text-slate-700 font-semibold">
                                📚 I Miei Corsi
                            </a>
                            <a href="{{ route('student.dashboard', ['tab' => 'certificates']) }}" class="block px-2.5 py-2 rounded-lg hover:bg-slate-100 text-slate-700 font-semibold">
                                🏅 Attestati & Registro Personale
                            </a>
                            <a href="{{ route('profile.show') }}" class="block px-2.5 py-2 rounded-lg hover:bg-slate-100 text-slate-700 font-semibold">
                                👤 Il Mio Profilo & Sicurezza
                            </a>

                            <div class="border-t border-slate-100 pt-1 mt-1">
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="w-full text-left px-2.5 py-2 rounded-lg hover:bg-red-50 text-red-600 font-bold cursor-pointer">
                                        Esci dalla piattaforma
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            @if(session('success') || (isset($errors) && $errors->any()))
                <div class="max-w-7xl mx-auto w-full px-4 sm:px-6 lg:px-8 mt-4">
                    @if(session('success'))
                        <div x-data="{ show: true }" x-show="show" class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-xl flex items-center justify-between shadow-2xs">
                            <div class="flex items-center gap-2.5 text-xs font-bold">
                                <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <span>{{ session('success') }}</span>
                            </div>
                            <button @click="show = false" class="text-emerald-600 hover:text-emerald-900 text-sm font-bold cursor-pointer">&times;</button>
                        </div>
                    @endif

                    @if(isset($errors) && $errors->any())
                        <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-xl shadow-2xs mt-2">
                            <div class="font-bold text-xs mb-1">Attenzione:</div>
                            <ul class="list-disc list-inside text-xs space-y-0.5">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
            @endif

            <main class="flex-1">
                @yield('content')
            </main>

            <footer class="bg-white border-t border-slate-200/80 mt-12 py-5">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-2 text-xs text-slate-400">
                    <div>
                        <strong>{{ $settings['platform_name'] ?? 'FormaFAD LMS' }}</strong> — {{ $settings['provider_name'] ?? 'Ente di Formazione Accreditato' }}
                    </div>
                    <div>
                        Assistenza Didattica: <a href="mailto:{{ $settings['support_email'] ?? 'segreteria@ente.it' }}" class="text-indigo-600 hover:underline">{{ $settings['support_email'] ?? 'segreteria@ente.it' }}</a>
                    </div>
                </div>
            </footer>
        @endif
    @else
        <!-- Guest Layout (Login / Register) -->
        @if(session('success') || (isset($errors) && $errors->any()))
            <div class="max-w-5xl mx-auto w-full px-4 sm:px-6 lg:px-8 mt-4">
                @if(session('success'))
                    <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-xl text-xs font-bold">
                        {{ session('success') }}
                    </div>
                @endif
                @if(isset($errors) && $errors->any())
                    <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-xl text-xs mt-2">
                        <ul class="list-disc list-inside space-y-0.5">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        @endif

        <main class="flex-1">
            @yield('content')
        </main>
    @endauth

</body>
</html>
