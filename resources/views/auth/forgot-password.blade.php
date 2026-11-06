@extends('layouts.app')

@section('title', 'Recupero Password — ' . ($settings['platform_name'] ?? 'FormaFAD LMS'))

@section('content')
<div x-data="{ mode: 'email' }"
     class="min-h-screen flex items-center justify-center p-4 sm:p-8 bg-slate-50/70">

    <div class="max-w-lg w-full bg-white rounded-3xl shadow-xl border border-slate-200/90 overflow-hidden">

        <!-- Top Header Banner -->
        <div class="p-6 sm:p-8 bg-slate-900 text-white relative">
            <div class="flex items-center justify-between gap-3 mb-4">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white/10 border border-white/15 text-[11px] font-bold uppercase tracking-wider text-indigo-200">
                    <span>🔑 Ripristino Credenziali</span>
                </span>
                <a href="{{ route('login') }}" class="text-xs font-bold text-slate-300 hover:text-white transition">
                    &larr; Torna al Login
                </a>
            </div>
            <h1 class="text-xl sm:text-2xl font-black text-white tracking-tight">Hai dimenticato la password?</h1>
            <p class="text-xs text-slate-300 mt-1.5 leading-relaxed">
                Puoi ricevere il <strong>Link di Ripristino via Email</strong> oppure reimpostarla subito verificando il tuo <strong>Codice Fiscale</strong>.
            </p>
        </div>

        <div class="p-6 sm:p-8 space-y-6">

            <!-- Mode Selector Tabs -->
            <div class="grid grid-cols-2 gap-2 p-1.5 rounded-2xl bg-slate-100 text-xs font-extrabold">
                <button type="button" @click="mode = 'email'"
                        :class="mode === 'email' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-800'"
                        class="py-2.5 px-3 rounded-xl transition cursor-pointer">
                    📧 Ricevi Link via Email
                </button>
                <button type="button" @click="mode = 'identity'"
                        :class="mode === 'identity' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-800'"
                        class="py-2.5 px-3 rounded-xl transition cursor-pointer">
                    🪪 Verifica Codice Fiscale
                </button>
            </div>

            @if(session('success'))
                <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-xs text-emerald-800 space-y-1">
                    <div class="font-extrabold text-emerald-900">✓ {{ session('success') }}</div>
                    <p class="text-emerald-700">Apri l'email ricevuta e clicca sul link sicuro per reimpostare la tua password. Se non la trovi, controlla anche nella cartella Posta indesiderata / Spam.</p>
                </div>
            @endif

            @if($errors->any())
                <div class="p-4 rounded-2xl bg-red-50 border border-red-200 text-red-800 text-xs font-semibold space-y-1">
                    @foreach($errors->all() as $err)
                        <div class="flex items-center gap-2">
                            <span class="text-red-500">•</span>
                            <span>{{ $err }}</span>
                        </div>
                    @endforeach
                </div>
            @endif

            <!-- MODE 1: Send Reset Link via Email -->
            <form x-show="mode === 'email'" method="POST" action="{{ route('password.email') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1.5">Email Registrata *</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"/></svg>
                        </div>
                        <input type="email" name="email" value="{{ old('email') }}" required
                               placeholder="nome.cognome@dominio.it"
                               class="w-full rounded-2xl border border-slate-300 bg-slate-50/50 hover:bg-white focus:bg-white pl-10 pr-4 py-3 text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-100 focus:border-indigo-600 transition">
                    </div>
                    <p class="text-[11px] text-slate-500 mt-1.5">
                        Ti invieremo un'email contenente il link sicuro per scegliere una nuova password.
                    </p>
                </div>

                <button type="submit"
                        class="w-full py-3.5 px-6 rounded-2xl bg-indigo-600 hover:bg-indigo-500 text-white font-black text-sm shadow-lg shadow-indigo-600/25 transition cursor-pointer flex items-center justify-center gap-2">
                    <span>Invia Email di Recupero Password</span>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                </button>
            </form>

            <!-- MODE 2: Instant Reset via Fiscal Code Identity Check -->
            <form x-show="mode === 'identity'" method="POST" action="{{ route('password.reset.submit') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1.5">Indirizzo Email *</label>
                    <input type="email" name="email" value="{{ old('email') }}" required
                           placeholder="nome.cognome@dominio.it"
                           class="w-full rounded-2xl border border-slate-300 bg-slate-50/50 hover:bg-white focus:bg-white px-4 py-3 text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-100 focus:border-indigo-600 transition">
                </div>

                <div>
                    <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1.5">Codice Fiscale *</label>
                    <input type="text" name="fiscal_code" value="{{ old('fiscal_code') }}" required maxlength="16"
                           placeholder="RSSMRA85M01H501Z"
                           class="w-full rounded-2xl border border-slate-300 bg-slate-50/50 hover:bg-white focus:bg-white px-4 py-3 text-sm font-mono uppercase text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-100 focus:border-indigo-600 transition">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
                    <div>
                        <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1.5">Nuova Password *</label>
                        <input type="password" name="password" required minlength="6"
                               placeholder="Min. 6 caratteri"
                               class="w-full rounded-2xl border border-slate-300 bg-slate-50/50 hover:bg-white focus:bg-white px-4 py-3 text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-100 focus:border-indigo-600 transition">
                    </div>
                    <div>
                        <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1.5">Conferma Password *</label>
                        <input type="password" name="password_confirmation" required minlength="6"
                               placeholder="Ripeti password"
                               class="w-full rounded-2xl border border-slate-300 bg-slate-50/50 hover:bg-white focus:bg-white px-4 py-3 text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-100 focus:border-indigo-600 transition">
                    </div>
                </div>

                <button type="submit"
                        class="w-full py-3.5 px-6 rounded-2xl bg-indigo-600 hover:bg-indigo-500 text-white font-black text-sm shadow-lg shadow-indigo-600/25 transition cursor-pointer flex items-center justify-center gap-2">
                    <span>Reimposta Password Ora</span>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                </button>
            </form>

            <div class="pt-4 border-t border-slate-100 text-center text-xs text-slate-500">
                Ricordi la password? <a href="{{ route('login') }}" class="font-extrabold text-indigo-600 hover:underline">Torna alla schermata di Login</a>
            </div>
        </div>

    </div>
</div>
@endsection
