@extends('layouts.app')

@section('title', 'Registrazione Corsista — ' . ($settings['platform_name'] ?? 'FormaFAD LMS'))

@section('content')
@php
    $hasLogo = !empty($settings['logo_url']);
    $hideBrandText = $hasLogo && (($settings['hide_brand_text_with_logo'] ?? '0') === '1');
    $logoBg = $settings['logo_bg'] ?? 'white';
    $logoSize = $settings['logo_size'] ?? 'md';
    $regLogoSizeClasses = match($logoSize) {
        'sm' => 'h-9 max-w-[160px]',
        'lg' => 'h-14 max-w-[260px]',
        default => 'h-11 max-w-[210px]',
    };
    $regLogoBgClasses = match($logoBg) {
        'dark' => 'logo-box-dark border p-1.5 shadow-xs',
        'transparent' => 'bg-transparent p-0.5',
        default => 'bg-white border border-slate-200 p-1.5 shadow-2xs',
    };
@endphp

<div class="max-w-3xl mx-auto py-10 px-4 sm:px-6 lg:px-8 space-y-5">

    <!-- Notice for Already Registered Students -->
    <div class="bg-white rounded-2xl border border-slate-200/90 p-4 sm:px-6 flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-2xs text-xs">
        <div>
            <strong class="text-slate-900">Sei già registrato in piattaforma e devi seguire un nuovo corso?</strong>
            <p class="text-slate-500 mt-0.5">Non creare un nuovo account: accedi al tuo profilo e inserisci il <strong>Codice Corso</strong> fornito dalla Segreteria.</p>
        </div>
        <div class="flex items-center gap-2 shrink-0">
            <a href="{{ route('login') }}" class="px-3.5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-xs font-bold transition">
                Accedi ora
            </a>
            <a href="{{ route('password.request') }}" class="px-3 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs transition">
                Recupera Password
            </a>
        </div>
    </div>

    <div class="bg-white rounded-3xl shadow-md border border-slate-200/90 overflow-hidden">
        <!-- Branded Top Banner -->
        <div class="bg-slate-900 p-6 sm:p-8 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                @if($hasLogo)
                    <img src="{{ $settings['logo_url'] }}"
                         alt="Logo {{ $settings['platform_name'] ?? 'Ente' }}"
                         class="{{ $regLogoSizeClasses }} {{ $regLogoBgClasses }} w-auto object-contain rounded-xl shrink-0">
                @else
                    <div class="w-11 h-11 rounded-xl bg-indigo-600 flex items-center justify-center font-extrabold text-base shadow-xs shrink-0">
                        FAD
                    </div>
                @endif
                <div>
                    @if(!$hideBrandText)
                        <div class="text-xs font-bold text-indigo-300 uppercase tracking-wider">{{ $settings['platform_name'] ?? 'FormaFAD LMS' }}</div>
                    @endif
                    <h1 class="text-xl sm:text-2xl font-extrabold text-white">Registrazione Anagrafica Corsista</h1>
                    <p class="text-xs text-slate-300 mt-0.5">
                        Compila i dati anagrafici richiesti per il rilascio dell'Attestato Ufficiale.
                    </p>
                </div>
            </div>
            <a href="{{ route('login') }}" class="px-3.5 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 border border-slate-700 text-xs font-bold text-white shrink-0 transition self-start sm:self-center">
                &larr; Torna al Login
            </a>
        </div>

        <form method="POST" action="{{ route('register.submit') }}" class="p-6 sm:p-8 space-y-6">
            @csrf

            <!-- Codice Invito / Codice Corso fornito dalla Segreteria (Niente scelta libera da catalogo!) -->
            <div class="bg-indigo-50 border border-indigo-200 rounded-2xl p-4 sm:p-5">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div class="space-y-0.5">
                        <label class="block text-xs font-extrabold text-slate-900">
                            🔑 Codice Invito Corso (fornito dalla Segreteria)
                        </label>
                        <p class="text-[11px] text-slate-600">
                            Se hai ricevuto un Codice Corso inseriscilo qui per abilitare subito il corso. In assenza di codice, il corso ti verrà assegnato dalla Segreteria dopo la registrazione.
                        </p>
                    </div>
                    <div class="sm:w-56 shrink-0">
                        <input type="text" name="invite_code" value="{{ old('invite_code', $prefilledCode ?? '') }}"
                               placeholder="Es. AGG-GT-2026-01"
                               class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2 text-xs font-mono uppercase font-bold text-slate-900">
                    </div>
                </div>
            </div>

            <!-- Dati Personali e Codice Fiscale -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Nome *</label>
                    <input type="text" name="first_name" value="{{ old('first_name') }}" required placeholder="Es. Giulia"
                           class="w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Cognome *</label>
                    <input type="text" name="last_name" value="{{ old('last_name') }}" required placeholder="Es. De Santis"
                           class="w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Codice Fiscale *</label>
                    <input type="text" name="fiscal_code" value="{{ old('fiscal_code') }}" required maxlength="16" placeholder="DSNGLI85E55H501Z"
                           class="w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm uppercase font-mono">
                </div>
            </div>

            <!-- Dati di Nascita per Attestato Allegato 3/4 -->
            <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Data di Nascita *</label>
                    <input type="date" name="birth_date" value="{{ old('birth_date', '1986-04-18') }}" required
                           class="w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Comune di Nascita *</label>
                    <input type="text" name="birth_city" value="{{ old('birth_city', 'Roma') }}" required
                           class="w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Provincia (Sigla) *</label>
                    <input type="text" name="birth_province" value="{{ old('birth_province', 'RM') }}" required maxlength="4"
                           class="w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm uppercase">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Stato di Nascita *</label>
                    <input type="text" name="birth_country" value="{{ old('birth_country', 'Italia') }}" required
                           class="w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm">
                </div>
            </div>

            <!-- Contatti & N. Elenco Nazionale -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Indirizzo Email *</label>
                    <input type="email" name="email" value="{{ old('email') }}" required placeholder="nome.cognome@email.it"
                           class="w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Telefono Cellulare</label>
                    <input type="text" name="phone" value="{{ old('phone') }}" placeholder="+39 333 1234567"
                           class="w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">N. Elenco Nazionale</label>
                    <input type="text" name="national_list_number" value="{{ old('national_list_number') }}" placeholder="Es. GT-RM-00482"
                           class="w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm">
                </div>
            </div>

            <!-- Credenziali di Accesso -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-4 border-t border-slate-100">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Scegli una Password (min. 6 caratteri) *</label>
                    <input type="password" name="password" required
                           class="w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Conferma Password *</label>
                    <input type="password" name="password_confirmation" required
                           class="w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm">
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                <a href="{{ route('login') }}" class="px-4 py-2.5 rounded-xl border border-slate-300 text-xs font-bold text-slate-700 hover:bg-slate-50">
                    Annulla
                </a>
                <button type="submit" class="px-6 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-sm font-extrabold transition shadow-xs cursor-pointer">
                    Completa Registrazione &rarr;
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
