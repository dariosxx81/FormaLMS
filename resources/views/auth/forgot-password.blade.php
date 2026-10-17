@extends('layouts.app')

@section('title', 'Recupero Password — ' . ($settings['platform_name'] ?? 'FormaFAD LMS'))

@section('content')
@php
    $hasLogo = !empty($settings['logo_url']);
    $hideBrandText = $hasLogo && (($settings['hide_brand_text_with_logo'] ?? '0') === '1');
    $logoBg = $settings['logo_bg'] ?? 'white';
    $logoSize = $settings['logo_size'] ?? 'md';
    $fpLogoSizeClasses = match($logoSize) {
        'sm' => 'h-9 max-w-[160px]',
        'lg' => 'h-14 max-w-[260px]',
        default => 'h-11 max-w-[210px]',
    };
    $fpLogoBgClasses = match($logoBg) {
        'dark' => 'logo-box-dark border p-1.5 shadow-xs',
        'transparent' => 'bg-transparent p-0.5',
        default => 'bg-white border border-slate-200 p-1.5 shadow-2xs',
    };
@endphp

<div class="min-h-[calc(100vh-4rem)] flex items-center justify-center py-10 px-4 sm:px-6 lg:px-8">
    <div class="max-w-lg w-full bg-white rounded-3xl shadow-md border border-slate-200/90 overflow-hidden">
        <!-- Branded Header -->
        <div class="bg-slate-900 p-6 sm:p-7 flex items-center justify-between gap-4">
            <div class="flex items-center gap-3.5">
                @if($hasLogo)
                    <img src="{{ $settings['logo_url'] }}"
                         alt="Logo"
                         class="{{ $fpLogoSizeClasses }} {{ $fpLogoBgClasses }} w-auto object-contain rounded-xl shrink-0">
                @else
                    <div class="w-10 h-10 rounded-xl bg-indigo-600 flex items-center justify-center font-extrabold text-sm shadow-xs shrink-0">
                        FAD
                    </div>
                @endif
                <div>
                    @if(!$hideBrandText)
                        <div class="text-[11px] font-bold text-indigo-300 uppercase tracking-wider">{{ $settings['platform_name'] ?? 'FormaFAD LMS' }}</div>
                    @endif
                    <h1 class="text-lg sm:text-xl font-extrabold text-white">Recupero & Reimpostazione Password</h1>
                </div>
            </div>
            <a href="{{ route('login') }}" class="px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 border border-slate-700 text-xs font-bold text-white shrink-0 transition">
                &larr; Login
            </a>
        </div>

        <form method="POST" action="{{ route('password.reset.submit') }}" class="p-6 sm:p-8 space-y-4">
            @csrf
            <p class="text-xs text-slate-600 leading-relaxed">
                Sei già registrato ma non ricordi la password? Verifica la tua identità inserendo l'<strong>Email</strong> e il <strong>Codice Fiscale</strong> associati al tuo profilo e imposta subito una nuova password.
            </p>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Indirizzo Email registrato *</label>
                <input type="email" name="email" value="{{ old('email') }}" required placeholder="nome.cognome@email.it"
                       class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-indigo-600 outline-none">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Codice Fiscale *</label>
                <input type="text" name="fiscal_code" value="{{ old('fiscal_code') }}" required maxlength="16" placeholder="BNCLNE84D52H501Q"
                       class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm uppercase font-mono focus:border-indigo-600 outline-none">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-2 border-t border-slate-100">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Nuova Password (min. 6 car.) *</label>
                    <input type="password" name="password" required
                           class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-indigo-600 outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Conferma Nuova Password *</label>
                    <input type="password" name="password_confirmation" required
                           class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-indigo-600 outline-none">
                </div>
            </div>

            <div class="pt-3">
                <button type="submit" class="w-full py-3 px-4 rounded-xl bg-indigo-600 hover:bg-indigo-500 font-extrabold text-sm transition shadow-xs cursor-pointer">
                    Verifica Identità e Aggiorna Password &rarr;
                </button>
            </div>

            <div class="text-center pt-2 text-[11px] text-slate-400">
                Hai bisogno di assistenza? Scrivi a <a href="mailto:{{ $settings['support_email'] ?? 'segreteria@ente.it' }}" class="text-indigo-600 hover:underline font-semibold">{{ $settings['support_email'] ?? 'segreteria@ente.it' }}</a>
            </div>
        </form>
    </div>
</div>
@endsection
