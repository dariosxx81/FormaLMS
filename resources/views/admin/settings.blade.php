@extends('layouts.app')

@section('title', 'Impostazioni White-Label Ente — Pannello Admin')

@section('content')
<div class="max-w-4xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
    <div class="bg-white rounded-2xl border border-slate-200 p-6 sm:p-8 shadow-2xs">
        <span class="text-xs font-bold uppercase tracking-wider text-indigo-600">Personalizzazione SaaS / White-Label</span>
        <h1 class="text-2xl font-extrabold text-slate-900 mt-0.5">Identità Piattaforma & Dati Ente Erogatore</h1>
        <p class="text-xs text-slate-500 mt-1">
            Modifica in qualsiasi momento la denominazione della piattaforma e dell'Ente di Formazione: i dati vengono applicati istantaneamente sia all'interfaccia web sia alle intestazioni degli Attestati PDF (Allegato 3 e Allegato 4).
        </p>

        <form method="POST" action="{{ route('admin.settings.update') }}" class="mt-6 space-y-5 text-xs">
            @csrf

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Nome Piattaforma (Header & Titoli) *</label>
                    <input type="text" name="platform_name" value="{{ $settings['platform_name'] }}" required class="w-full rounded-xl border border-slate-300 px-3.5 py-2">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Email Segreteria / Supporto Didattico *</label>
                    <input type="email" name="support_email" value="{{ $settings['support_email'] }}" required class="w-full rounded-xl border border-slate-300 px-3.5 py-2">
                </div>
            </div>

            <div>
                <label class="block font-bold text-slate-700 mb-1">Sottotitolo Descrittivo Piattaforma</label>
                <input type="text" name="platform_subtitle" value="{{ $settings['platform_subtitle'] }}" class="w-full rounded-xl border border-slate-300 px-3.5 py-2">
            </div>

            <div class="pt-4 border-t border-slate-200 grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Denominazione Completa Ente di Formazione (per Attestati) *</label>
                    <input type="text" name="provider_name" value="{{ $settings['provider_name'] }}" required class="w-full rounded-xl border border-slate-300 px-3.5 py-2">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Rappresentante Legale / Firmatario Attestati *</label>
                    <input type="text" name="provider_legal_rep" value="{{ $settings['provider_legal_rep'] }}" required class="w-full rounded-xl border border-slate-300 px-3.5 py-2">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="sm:col-span-1">
                    <label class="block font-bold text-slate-700 mb-1">Sede Legale / Operativa Ente</label>
                    <input type="text" name="provider_address" value="{{ $settings['provider_address'] }}" class="w-full rounded-xl border border-slate-300 px-3.5 py-2">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Codice ATECO (Linee Guida B) *</label>
                    <input type="text" name="ateco_code" value="{{ $settings['ateco_code'] }}" required class="w-full rounded-xl border border-slate-300 px-3.5 py-2 font-mono">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Codice CP ISTAT (Linee Guida B) *</label>
                    <input type="text" name="cp_istat_code" value="{{ $settings['cp_istat_code'] }}" required class="w-full rounded-xl border border-slate-300 px-3.5 py-2 font-mono">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                <label class="flex items-center gap-2.5 p-3.5 rounded-xl border border-slate-200 bg-slate-50">
                    <input type="checkbox" name="allow_public_registration" value="1" {{ ($settings['allow_public_registration'] ?? '1') === '1' ? 'checked' : '' }} class="rounded text-indigo-600">
                    <span><strong>Consenti Auto-Registrazione Pubblica</strong> tramite pagina <code>/register</code></span>
                </label>
                <label class="flex items-center gap-2.5 p-3.5 rounded-xl border border-slate-200 bg-slate-50">
                    <input type="checkbox" name="auto_approve_registration" value="1" {{ ($settings['auto_approve_registration'] ?? '1') === '1' ? 'checked' : '' }} class="rounded text-indigo-600">
                    <span><strong>Abilita automaticamente i nuovi iscritti</strong> senza attesa approvazione manuale</span>
                </label>
            </div>

            <div class="flex justify-end pt-4 border-t border-slate-200">
                <button type="submit" class="px-6 py-2.5 rounded-xl bg-slate-900 hover:bg-indigo-600 text-white font-bold cursor-pointer">
                    Salva Configurazione White-Label
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
