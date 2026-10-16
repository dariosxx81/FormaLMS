@extends('layouts.app')

@section('title', 'Accesso Piattaforma FAD — ' . ($settings['platform_name'] ?? 'FormaFAD LMS'))

@section('content')
<div class="min-h-[calc(100vh-8rem)] flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-5xl w-full grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">

        <!-- Left Column: Institutional Info & 1-Click Demo Access -->
        <div class="lg:col-span-7 bg-slate-900 text-white rounded-2xl p-8 shadow-xl border border-slate-800">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 text-xs font-semibold mb-4">
                <span>Piattaforma Telematica Asincrona & Sincrona</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-white">
                {{ $settings['platform_name'] ?? 'FormaFAD LMS' }}
            </h1>
            <p class="mt-2 text-slate-300 text-sm leading-relaxed">
                {{ $settings['platform_subtitle'] ?? 'Sistema White-Label per l\'erogazione e il tracciamento certificato della formazione a distanza (Linee Guida B - Ministero del Turismo).' }}
            </p>

            <div class="mt-6 grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs">
                <div class="bg-slate-800/80 border border-slate-700/70 rounded-xl p-3.5">
                    <div class="font-bold text-indigo-400">Player Anti-Skip</div>
                    <p class="text-slate-300 mt-1">Blocco avanzamento rapido, ripresa automatica e heartbeat ogni 15s.</p>
                </div>
                <div class="bg-slate-800/80 border border-slate-700/70 rounded-xl p-3.5">
                    <div class="font-bold text-emerald-400">Registro Zoom + FAD</div>
                    <p class="text-slate-300 mt-1">Unificazione automatica ore sincrone (80%) e asincrone (20%).</p>
                </div>
                <div class="bg-slate-800/80 border border-slate-700/70 rounded-xl p-3.5">
                    <div class="font-bold text-amber-400">Attestati Allegato 4</div>
                    <p class="text-slate-300 mt-1">Generazione PDF immediata di Attestati e Fascicoli Probanti.</p>
                </div>
            </div>

            <!-- 1-Click Quick Demo Login Box -->
            <div class="mt-8 pt-6 border-t border-slate-800">
                <div class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-3">
                    Accesso Rapido Demo (1-Click senza digitare password)
                </div>

                <div class="space-y-2.5">
                    @foreach($demoAdmins as $admin)
                        <form method="POST" action="{{ route('demo.switch', $admin) }}">
                            @csrf
                            <button type="submit" class="w-full text-left px-4 py-3 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white flex items-center justify-between transition shadow-sm cursor-pointer">
                                <div>
                                    <div class="font-bold text-sm">Entra come Amministratore / Segreteria Ente</div>
                                    <div class="text-xs text-indigo-200">{{ $admin->full_name }} ({{ $admin->email }}) — Gestione Corsi, Utenti, Ore Zoom e PDF</div>
                                </div>
                                <span class="px-2.5 py-1 rounded-lg bg-white/15 text-xs font-bold">Pannello Admin &rarr;</span>
                            </button>
                        </form>
                    @endforeach

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 pt-1">
                        @foreach($demoStudents as $student)
                            <form method="POST" action="{{ route('demo.switch', $student) }}">
                                @csrf
                                <button type="submit" class="w-full text-left px-3.5 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 border border-slate-700 text-slate-100 flex items-center justify-between transition cursor-pointer">
                                    <div class="truncate pr-2">
                                        <div class="font-semibold text-xs text-white truncate">{{ $student->full_name }}</div>
                                        <div class="text-[11px] text-slate-400 truncate">Corsista • CF: {{ $student->fiscal_code }}</div>
                                    </div>
                                    <span class="text-xs font-bold text-emerald-400 shrink-0">Aula FAD &rarr;</span>
                                </button>
                            </form>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: Standard Login & Public Registration Link -->
        <div class="lg:col-span-5 bg-white rounded-2xl p-8 shadow-sm border border-slate-200">
            <h2 class="text-xl font-bold text-slate-900">Accedi con le tue credenziali</h2>
            <p class="text-xs text-slate-500 mt-1">Inserisci l'indirizzo email e la password ricevuti dalla segreteria o scelti in fase di registrazione.</p>

            <form method="POST" action="{{ route('login.submit') }}" class="mt-6 space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Indirizzo Email</label>
                    <input type="email" name="email" value="{{ old('email', 'admin@formalms.it') }}" required
                           class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-indigo-600 focus:ring-2 focus:ring-indigo-600/20 outline-none">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Password</label>
                    <input type="password" name="password" value="password" required
                           class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-indigo-600 focus:ring-2 focus:ring-indigo-600/20 outline-none">
                </div>

                <div class="flex items-center justify-between text-xs">
                    <label class="inline-flex items-center gap-2 text-slate-600">
                        <input type="checkbox" name="remember" class="rounded border-slate-300 text-indigo-600">
                        <span>Ricorda accesso su questo dispositivo</span>
                    </label>
                </div>

                <button type="submit" class="w-full py-3 px-4 rounded-xl bg-slate-900 hover:bg-indigo-600 text-white font-bold text-sm transition shadow-xs cursor-pointer">
                    Accedi alla Piattaforma
                </button>
            </form>

            @if(($settings['allow_public_registration'] ?? '1') === '1')
                <div class="mt-6 pt-6 border-t border-slate-200 text-center">
                    <p class="text-xs text-slate-600">Devi iscriverti a un corso di aggiornamento o specializzazione?</p>
                    <a href="{{ route('register') }}" class="mt-2.5 inline-flex items-center justify-center w-full py-2.5 px-4 rounded-xl bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-bold text-xs transition border border-indigo-200">
                        Registrati come nuovo Corsista e scegli il Corso &rarr;
                    </a>
                </div>
            @endif
        </div>

    </div>
</div>
@endsection
