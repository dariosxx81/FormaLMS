@extends('layouts.app')

@section('title', 'Recupero Password — ' . ($settings['platform_name'] ?? 'FormaFAD LMS'))

@section('content')
<div x-data="{ mode: 'email' }"
     class="min-h-[calc(100vh-4rem)] flex items-center justify-center p-4 sm:p-8"
     style="background: radial-gradient(circle at top right, var(--brand-primary-soft), transparent 45%), radial-gradient(circle at bottom left, var(--brand-accent-soft), transparent 45%);">

    <div class="max-w-lg w-full bg-white rounded-3xl shadow-xl border border-slate-200/90 overflow-hidden">

        <!-- Branded Top Banner -->
        <div class="p-6 sm:p-8 text-white"
             style="background: linear-gradient(135deg, var(--brand-secondary) 0%, var(--brand-secondary-dark) 100%);">
            <div class="flex items-center justify-between gap-3 mb-4">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white/10 border border-white/15 text-[11px] font-bold uppercase tracking-wider">
                    <span>🔑 Ripristino Credenziali</span>
                </span>
                <a href="{{ route('login') }}" class="text-xs font-bold text-white/80 hover:text-white underline">
                    &larr; Torna al Login
                </a>
            </div>
            <h1 class="text-xl sm:text-2xl font-extrabold tracking-tight">Hai dimenticato la password?</h1>
            <p class="text-xs opacity-80 mt-1 leading-relaxed">
                Puoi ricevere il <strong>Link di Ripristino via Email</strong> oppure reimpostarla subito verificando il tuo <strong>Codice Fiscale</strong>.
            </p>
        </div>

        <div class="p-6 sm:p-8 space-y-5">

            <!-- Mode Selector Tabs -->
            <div class="grid grid-cols-2 gap-2 p-1 rounded-2xl bg-slate-100 text-xs font-extrabold">
                <button type="button" @click="mode = 'email'"
                        :class="mode === 'email' ? 'bg-white text-slate-900 shadow-2xs' : 'text-slate-500 hover:text-slate-800'"
                        class="py-2.5 px-3 rounded-xl transition cursor-pointer">
                    📧 Ricevi Link via Email
                </button>
                <button type="button" @click="mode = 'identity'"
                        :class="mode === 'identity' ? 'bg-white text-slate-900 shadow-2xs' : 'text-slate-500 hover:text-slate-800'"
                        class="py-2.5 px-3 rounded-xl transition cursor-pointer">
                    🪪 Verifica Codice Fiscale
                </button>
            </div>

            @if(session('reset_link_preview'))
                <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-xs space-y-2">
                    <div class="font-extrabold text-emerald-900">✓ Email di recupero inviata con successo!</div>
                    <p class="text-emerald-800">
                        Abbiamo inviato il link di reimpostazione all'indirizzo indicato.
                    </p>
                    <div class="pt-1">
                        <a href="{{ session('reset_link_preview') }}"
                           class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold shadow-2xs transition">
                            <span>🔗 Apri Link di Reset Password (Anteprima Email) &rarr;</span>
                        </a>
                    </div>
                </div>
            @endif

            <!-- MODE 1: Send Reset Link via Email -->
            <form x-show="mode === 'email'" method="POST" action="{{ route('password.email') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Email Registrata *</label>
                    <input type="email" name="email" value="{{ old('email') }}" required
                           placeholder="nome.cognome@dominio.it"
                           class="w-full rounded-xl border border-slate-300 bg-slate-50/60 focus:bg-white px-4 py-3 text-sm text-slate-900 focus:outline-none focus:ring-2 transition"
                           style="--tw-ring-color: var(--brand-primary);">
                    <p class="text-[11px] text-slate-500 mt-1.5">
                        Ti invieremo un'email contenente il link sicuro per scegliere una nuova password.
                    </p>
                </div>

                <button type="submit"
                        class="w-full py-3.5 px-6 rounded-xl font-extrabold text-sm shadow-lg transition transform active:scale-[0.99] cursor-pointer"
                        style="background-color: var(--brand-primary); color: var(--brand-primary-contrast);">
                    Invia Email di Recupero Password &rarr;
                </button>
            </form>

            <!-- MODE 2: Immediate Reset with Email + Fiscal Code -->
            <form x-show="mode === 'identity'" method="POST" action="{{ route('password.reset.submit') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Email Registrata *</label>
                    <input type="email" name="email" value="{{ old('email') }}" required
                           placeholder="nome.cognome@dominio.it"
                           class="w-full rounded-xl border border-slate-300 bg-slate-50/60 focus:bg-white px-4 py-3 text-sm text-slate-900 focus:outline-none focus:ring-2 transition"
                           style="--tw-ring-color: var(--brand-primary);">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Codice Fiscale (Verifica Identità) *</label>
                    <input type="text" name="fiscal_code" value="{{ old('fiscal_code') }}" required maxlength="16"
                           placeholder="RSSMRA80A01H501U"
                           class="w-full rounded-xl border border-slate-300 bg-slate-50/60 focus:bg-white px-4 py-3 text-sm text-slate-900 font-mono uppercase focus:outline-none focus:ring-2 transition"
                           style="--tw-ring-color: var(--brand-primary);">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Nuova Password *</label>
                        <input type="password" name="password" required minlength="6"
                               placeholder="Minimo 6 caratteri"
                               class="w-full rounded-xl border border-slate-300 bg-slate-50/60 focus:bg-white px-4 py-3 text-sm text-slate-900 focus:outline-none focus:ring-2 transition"
                               style="--tw-ring-color: var(--brand-primary);">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Conferma Password *</label>
                        <input type="password" name="password_confirmation" required minlength="6"
                               placeholder="Ripeti nuova password"
                               class="w-full rounded-xl border border-slate-300 bg-slate-50/60 focus:bg-white px-4 py-3 text-sm text-slate-900 focus:outline-none focus:ring-2 transition"
                               style="--tw-ring-color: var(--brand-primary);">
                    </div>
                </div>

                <button type="submit"
                        class="w-full py-3.5 px-6 rounded-xl font-extrabold text-sm shadow-lg transition transform active:scale-[0.99] cursor-pointer mt-2"
                        style="background-color: var(--brand-primary); color: var(--brand-primary-contrast);">
                    Reimposta Password e Accedi &rarr;
                </button>
            </form>

            <div class="pt-4 border-t border-slate-100 text-center text-xs text-slate-500">
                Ricordi la password?
                <a href="{{ route('login') }}" class="font-extrabold hover:underline ml-1" style="color: var(--brand-primary);">
                    Torna alla schermata di Login
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
