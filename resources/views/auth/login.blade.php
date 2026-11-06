@extends('layouts.app')

@section('title', 'Accesso Piattaforma FAD — ' . ($settings['platform_name'] ?? 'FormaFAD LMS'))

@section('content')
@php
    $hasLogo = !empty($settings['logo_url']);
    $hideBrandText = $hasLogo && (($settings['hide_brand_text_with_logo'] ?? '0') === '1');
    $logoBg = $settings['logo_bg'] ?? 'white';
    $logoSize = $settings['logo_size'] ?? 'md';
    $loginLogoSizeClasses = match($logoSize) {
        'sm' => 'h-11 max-w-[190px]',
        'lg' => 'h-16 max-w-[280px]',
        default => 'h-13 max-w-[240px]',
    };
    $loginLogoBgClasses = match($logoBg) {
        'dark' => 'logo-box-dark border p-2 shadow-xs',
        'transparent' => 'bg-transparent p-0.5',
        default => 'bg-white border border-slate-200/80 p-2 shadow-2xs',
    };
@endphp

<div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8 bg-slate-100/70">
    <div class="max-w-5xl w-full grid grid-cols-1 lg:grid-cols-12 rounded-3xl overflow-hidden shadow-2xl border border-slate-200/90 bg-white">

        <!-- Left Column: Modern Hero / Brand Identity -->
        <div class="lg:col-span-6 bg-slate-900 p-8 sm:p-12 flex flex-col justify-between relative overflow-hidden text-white">
            <!-- Decorative Subtle Ambient Glow -->
            <div class="absolute -top-24 -left-24 w-72 h-72 bg-indigo-600/20 rounded-full blur-3xl pointer-events-none"></div>
            <div class="absolute -bottom-24 -right-24 w-72 h-72 bg-emerald-500/10 rounded-full blur-3xl pointer-events-none"></div>

            <div class="relative z-10 space-y-8">
                <!-- Platform Brand & Identity Header -->
                <div class="flex items-center gap-4">
                    @if($hasLogo)
                        <img src="{{ $settings['logo_url'] }}"
                             alt="Logo {{ $settings['platform_name'] ?? 'Ente' }}"
                             class="{{ $loginLogoSizeClasses }} {{ $loginLogoBgClasses }} w-auto object-contain rounded-2xl shrink-0 shadow-sm transition">
                    @else
                        <div class="w-13 h-13 rounded-2xl bg-indigo-600 flex items-center justify-center font-black text-xl shadow-lg shadow-indigo-600/30 shrink-0 text-white tracking-wider">
                            FAD
                        </div>
                    @endif

                    @if(!$hideBrandText)
                        <div>
                            <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-white leading-tight">
                                {{ $settings['platform_name'] ?? 'FormaFAD LMS' }}
                            </h1>
                            <div class="text-xs text-indigo-200 font-semibold mt-0.5">
                                {{ $settings['provider_name'] ?? 'Ente di Formazione Accreditato' }}
                            </div>
                        </div>
                    @else
                        <span class="sr-only">{{ $settings['platform_name'] ?? 'FormaFAD LMS' }}</span>
                    @endif
                </div>

                <!-- Description & Tagline -->
                <div class="space-y-3">
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-slate-800/90 border border-slate-700/80 text-xs font-bold text-indigo-300">
                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                        <span>Ambiente Telematico Certificato</span>
                    </div>

                    <p class="text-slate-300 text-sm leading-relaxed">
                        {{ $settings['platform_subtitle'] ?? 'Piattaforma digitale per la fruizione dei corsi di formazione continua e l\'emissione degli attestati ufficiali.' }}
                    </p>
                </div>

                <!-- Key Platform Features List (Card in stile chiaro) -->
                <div class="space-y-3 pt-2">
                    <div class="flex items-start gap-3.5 p-4 rounded-2xl bg-white/95 text-slate-800 shadow-md border border-white/40 backdrop-blur-sm transition hover:bg-white">
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center text-lg shrink-0 font-bold shadow-2xs border"
                             style="background-color: var(--brand-accent-soft); border-color: var(--brand-accent-border); color: var(--brand-accent-readable);">
                            🎬
                        </div>
                        <div>
                            <div class="font-extrabold text-xs" style="color: var(--brand-accent-readable);">
                                Aula Multimediale & Telemetria
                            </div>
                            <p class="text-[11px] text-slate-600 mt-0.5 leading-snug font-medium">
                                Tracciamento automatico e continuo della presenza con salvataggio dello stato di avanzamento.
                            </p>
                        </div>
                    </div>

                    <div class="flex items-start gap-3.5 p-4 rounded-2xl bg-white/95 text-slate-800 shadow-md border border-white/40 backdrop-blur-sm transition hover:bg-white">
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center text-lg shrink-0 font-bold shadow-2xs border"
                             style="background-color: var(--brand-accent-soft); border-color: var(--brand-accent-border); color: var(--brand-accent-readable);">
                            🏅
                        </div>
                        <div>
                            <div class="font-extrabold text-xs" style="color: var(--brand-accent-readable);">
                                Attestati & Registri Conformi
                            </div>
                            <p class="text-[11px] text-slate-600 mt-0.5 leading-snug font-medium">
                                Generazione istantanea del certificato PDF e del dossier di audit al raggiungimento della soglia minima.
                            </p>
                        </div>
                    </div>

                    <div class="flex items-start gap-3.5 p-4 rounded-2xl bg-white/95 text-slate-800 shadow-md border border-white/40 backdrop-blur-sm transition hover:bg-white">
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center text-lg shrink-0 font-bold shadow-2xs border"
                             style="background-color: var(--brand-accent-soft); border-color: var(--brand-accent-border); color: var(--brand-accent-readable);">
                            🛡️
                        </div>
                        <div>
                            <div class="font-extrabold text-xs" style="color: var(--brand-accent-readable);">
                                Accesso Riservato & Crittografato
                            </div>
                            <p class="text-[11px] text-slate-600 mt-0.5 leading-snug font-medium">
                                Protocolli conformi alle Linee Guida per la formazione continua asincrona e sincrona.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Left Footer / Legal Notice -->
            <div class="mt-8 pt-6 border-t border-slate-800/80 text-[11px] text-slate-400 relative z-10 flex items-center justify-between">
                <span>© {{ date('Y') }} {{ $settings['provider_name'] ?? 'Piattaforma FAD' }}</span>
                <span class="inline-flex items-center gap-1.5 text-emerald-400 font-semibold">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span> Servizio Attivo
                </span>
            </div>
        </div>

        <!-- Right Column: Institutional Login Form -->
        <div class="lg:col-span-6 p-8 sm:p-12 flex flex-col justify-center bg-white">
            <div class="mb-8">
                <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-indigo-50 text-indigo-700 text-[11px] font-extrabold uppercase tracking-wider mb-2">
                    <span>Portale Utenti & Segreteria</span>
                </div>
                <h2 class="text-2xl font-black text-slate-900 tracking-tight">Accedi all'Area Riservata</h2>
                <p class="text-xs text-slate-500 mt-1">
                    Inserisci l'indirizzo email e la password associati al tuo profilo corsista o docente.
                </p>
            </div>

            @if(session('success'))
                <div class="mb-6 p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-semibold flex items-center gap-2">
                    <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            @if($errors->any())
                <div class="mb-6 p-4 rounded-2xl bg-red-50 border border-red-200 text-red-800 text-xs font-semibold space-y-1">
                    @foreach($errors->all() as $err)
                        <div class="flex items-center gap-2">
                            <span class="text-red-500">•</span>
                            <span>{{ $err }}</span>
                        </div>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('login.submit') }}" class="space-y-5">
                @csrf
                <div>
                    <label class="block text-xs font-extrabold text-slate-700 mb-1.5">
                        Indirizzo Email
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"/></svg>
                        </div>
                        <input type="email"
                               name="email"
                               value="{{ old('email') }}"
                               placeholder="nome.cognome@dominio.it"
                               required
                               autofocus
                               class="w-full rounded-2xl border border-slate-300 pl-10 pr-4 py-3 text-sm font-medium focus:border-indigo-600 focus:ring-2 focus:ring-indigo-100 outline-none transition bg-slate-50/50 hover:bg-white focus:bg-white">
                    </div>
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="block text-xs font-extrabold text-slate-700">
                            Password
                        </label>
                        <a href="{{ route('password.request') }}" class="text-[11px] font-bold text-indigo-600 hover:text-indigo-700 hover:underline">
                            Password dimenticata?
                        </a>
                    </div>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                        </div>
                        <input type="password"
                               name="password"
                               placeholder="••••••••••••"
                               required
                               class="w-full rounded-2xl border border-slate-300 pl-10 pr-4 py-3 text-sm font-medium focus:border-indigo-600 focus:ring-2 focus:ring-indigo-100 outline-none transition bg-slate-50/50 hover:bg-white focus:bg-white">
                    </div>
                </div>

                <div class="flex items-center justify-between text-xs pt-0.5">
                    <label class="inline-flex items-center gap-2.5 text-slate-600 cursor-pointer select-none">
                        <input type="checkbox" name="remember" class="w-4 h-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                        <span class="font-medium text-slate-700">Rimani connesso su questo dispositivo</span>
                    </label>
                </div>

                <button type="submit"
                        class="w-full py-3.5 px-4 rounded-2xl bg-indigo-600 hover:bg-indigo-500 text-white font-black text-sm tracking-wide transition shadow-lg shadow-indigo-600/25 hover:shadow-indigo-600/35 cursor-pointer flex items-center justify-center gap-2">
                    <span>Accedi alla Piattaforma</span>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                </button>
            </form>

            @if(($settings['allow_public_registration'] ?? '1') === '1')
                <div class="mt-8 pt-6 border-t border-slate-100 text-center space-y-2.5">
                    <p class="text-xs text-slate-500">Sei un nuovo corsista e non possiedi ancora un account?</p>
                    <a href="{{ route('register') }}"
                       class="inline-flex items-center justify-center w-full py-3 px-4 rounded-2xl bg-slate-100 hover:bg-slate-200 text-slate-800 font-bold text-xs transition border border-slate-200">
                        <span>Registrazione Nuovo Corsista</span>
                        <span class="ml-1 text-slate-500">&rarr;</span>
                    </a>
                </div>
            @endif

            <div class="mt-6 text-center text-[11px] text-slate-400">
                Assistenza tecnica o didattica: <a href="mailto:{{ $settings['support_email'] ?? 'segreteria@ente.it' }}" class="text-indigo-600 hover:underline font-semibold">{{ $settings['support_email'] ?? 'segreteria@ente.it' }}</a>
            </div>
        </div>

    </div>
</div>
@endsection
