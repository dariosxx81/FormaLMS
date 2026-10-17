@extends('layouts.app')

@section('title', 'Accesso Piattaforma FAD — ' . ($settings['platform_name'] ?? 'FormaFAD LMS'))

@section('content')
@php
    $hasLogo = !empty($settings['logo_url']);
    $hideBrandText = $hasLogo && (($settings['hide_brand_text_with_logo'] ?? '0') === '1');
    $logoBg = $settings['logo_bg'] ?? 'white';
    $logoSize = $settings['logo_size'] ?? 'md';
    $loginLogoSizeClasses = match($logoSize) {
        'sm' => 'h-10 max-w-[180px]',
        'lg' => 'h-16 max-w-[280px]',
        default => 'h-12 max-w-[230px]',
    };
    $loginLogoBgClasses = match($logoBg) {
        'dark' => 'logo-box-dark border p-2 shadow-xs',
        'transparent' => 'bg-transparent p-0.5',
        default => 'bg-white border border-slate-200/80 p-2 shadow-2xs',
    };
@endphp

<div class="min-h-screen flex items-center justify-center py-10 px-4 sm:px-6 lg:px-8 bg-slate-50/70">
    <div class="max-w-5xl w-full grid grid-cols-1 lg:grid-cols-12 rounded-3xl overflow-hidden shadow-xl border border-slate-200/90 bg-white">

        <!-- Left Column: Brand Identity & 1-Click Demo Access -->
        <div class="lg:col-span-7 bg-slate-900 p-8 sm:p-10 flex flex-col justify-between relative overflow-hidden">
            <div class="space-y-6">
                <div class="flex flex-wrap items-center gap-4">
                    @if($hasLogo)
                        <img src="{{ $settings['logo_url'] }}"
                             alt="Logo {{ $settings['platform_name'] ?? 'Ente' }}"
                             class="{{ $loginLogoSizeClasses }} {{ $loginLogoBgClasses }} w-auto object-contain rounded-2xl shrink-0 transition">
                    @else
                        <div class="w-12 h-12 rounded-2xl bg-indigo-600 flex items-center justify-center font-extrabold text-lg shadow-xs shrink-0">
                            FAD
                        </div>
                    @endif

                    @if(!$hideBrandText)
                        <div>
                            <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-white leading-tight">
                                {{ $settings['platform_name'] ?? 'FormaFAD LMS' }}
                            </h1>
                            <div class="text-xs text-slate-300 font-medium mt-0.5">
                                {{ $settings['provider_name'] ?? 'Ente di Formazione Accreditato' }}
                            </div>
                        </div>
                    @else
                        <span class="sr-only">{{ $settings['platform_name'] ?? 'FormaFAD LMS' }}</span>
                    @endif
                </div>

                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-slate-800 border border-slate-700 text-xs font-bold text-indigo-300">
                    <span>Piattaforma Telematica Formazione Sincrona & Asincrona</span>
                </div>

                <p class="text-slate-300 text-sm leading-relaxed">
                    {{ $settings['platform_subtitle'] ?? 'Ambiente digitale certificato per la fruizione dei corsi e il rilascio degli attestati ufficiali.' }}
                </p>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs pt-1">
                    <div class="bg-slate-800 border border-slate-700 rounded-xl p-3.5">
                        <div class="font-extrabold text-white">🎬 Aula Video H24</div>
                        <p class="text-slate-300 text-[11px] mt-1">Ripresa automatica e salvataggio continuo.</p>
                    </div>
                    <div class="bg-slate-800 border border-slate-700 rounded-xl p-3.5">
                        <div class="font-extrabold text-white">🔑 Accesso Controllato</div>
                        <p class="text-slate-300 text-[11px] mt-1">Corsi abilitati da Segreteria o Codice Invito.</p>
                    </div>
                    <div class="bg-slate-800 border border-slate-700 rounded-xl p-3.5">
                        <div class="font-extrabold text-white">🏅 Attestati PDF</div>
                        <p class="text-slate-300 text-[11px] mt-1">Scarico immediato al raggiungimento soglia.</p>
                    </div>
                </div>
            </div>

            <!-- 1-Click Quick Demo Access Box -->
            <div class="mt-8 pt-6 border-t border-slate-800">
                <div class="text-[11px] font-extrabold uppercase tracking-wider text-slate-400 mb-3">
                    Accesso Rapido Demo (1-Click senza password)
                </div>

                <div class="space-y-2.5">
                    @foreach($demoAdmins as $admin)
                        <form method="POST" action="{{ route('demo.switch', $admin) }}">
                            @csrf
                            <button type="submit" class="w-full text-left px-4 py-3 rounded-xl bg-indigo-600 hover:bg-indigo-500 flex items-center justify-between transition shadow-sm cursor-pointer">
                                <div>
                                    <div class="font-extrabold text-xs sm:text-sm">Entra come Amministratore / Segreteria</div>
                                    <div class="text-[11px] opacity-85">{{ $admin->full_name }} ({{ $admin->email }})</div>
                                </div>
                                <span class="px-2.5 py-1 rounded-lg bg-black/15 text-xs font-bold shrink-0">Pannello Admin &rarr;</span>
                            </button>
                        </form>
                    @endforeach

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 pt-1">
                        @foreach($demoStudents as $student)
                            <form method="POST" action="{{ route('demo.switch', $student) }}">
                                @csrf
                                <button type="submit" class="w-full text-left px-3.5 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 border border-slate-700 flex items-center justify-between transition cursor-pointer">
                                    <div class="truncate pr-2">
                                        <div class="font-bold text-xs text-white truncate">👤 {{ $student->full_name }}</div>
                                        <div class="text-[10px] text-slate-300 truncate">Corsista • {{ $student->fiscal_code }}</div>
                                    </div>
                                    <span class="text-[11px] font-bold text-indigo-300 shrink-0">Aula &rarr;</span>
                                </button>
                            </form>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: Clean Login Form with Brand Logo, Password Recovery & Registration -->
        <div class="lg:col-span-5 p-8 sm:p-10 flex flex-col justify-center bg-white">
            <div class="mb-6 pb-5 border-b border-slate-100 flex items-center justify-between gap-3">
                <div>
                    <span class="text-[11px] font-extrabold uppercase tracking-wider text-indigo-600">Area Riservata</span>
                    <h2 class="text-xl font-extrabold text-slate-900 mt-0.5">Accedi alla Piattaforma</h2>
                    <p class="text-xs text-slate-500 mt-0.5">Inserisci le tue credenziali personali.</p>
                </div>
                @if($hasLogo)
                    <img src="{{ $settings['logo_url'] }}"
                         alt="Logo"
                         class="h-9 w-auto max-w-[120px] object-contain rounded-lg shrink-0 {{ $logoBg === 'dark' ? 'logo-box-dark p-1' : 'bg-white' }}">
                @endif
            </div>

            <form method="POST" action="{{ route('login.submit') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Indirizzo Email</label>
                    <input type="email" name="email" value="{{ old('email', 'admin@formalms.it') }}" required
                           class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-indigo-600 outline-none transition">
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="block text-xs font-bold text-slate-700">Password</label>
                        <a href="{{ route('password.request') }}" class="text-[11px] font-bold text-indigo-600 hover:underline">
                            Password dimenticata?
                        </a>
                    </div>
                    <input type="password" name="password" value="password" required
                           class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-indigo-600 outline-none transition">
                </div>

                <div class="flex items-center justify-between text-xs pt-1">
                    <label class="inline-flex items-center gap-2 text-slate-600 cursor-pointer">
                        <input type="checkbox" name="remember" class="rounded border-slate-300 text-indigo-600">
                        <span>Ricorda accesso</span>
                    </label>
                </div>

                <button type="submit" class="w-full py-3 px-4 rounded-xl bg-indigo-600 hover:bg-indigo-500 font-extrabold text-sm transition shadow-sm cursor-pointer">
                    Accedi alla Piattaforma &rarr;
                </button>
            </form>

            @if(($settings['allow_public_registration'] ?? '1') === '1')
                <div class="mt-7 pt-6 border-t border-slate-100 text-center space-y-2">
                    <p class="text-xs text-slate-500">Primo accesso? Crea il tuo profilo anagrafico:</p>
                    <a href="{{ route('register') }}" class="inline-flex items-center justify-center w-full py-2.5 px-4 rounded-xl bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-bold text-xs transition border border-indigo-200">
                        Registrazione Nuovo Corsista &rarr;
                    </a>
                </div>
            @endif

            <div class="mt-6 text-center text-[11px] text-slate-400">
                {{ $settings['provider_name'] ?? 'Ente di Formazione Accreditato' }} • Supporto: <a href="mailto:{{ $settings['support_email'] ?? 'segreteria@ente.it' }}" class="text-indigo-600 hover:underline">{{ $settings['support_email'] ?? 'segreteria@ente.it' }}</a>
            </div>
        </div>

    </div>
</div>
@endsection
