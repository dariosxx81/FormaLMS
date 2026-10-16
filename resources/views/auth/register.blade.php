@extends('layouts.app')

@section('title', 'Registrazione Corsista — ' . ($settings['platform_name'] ?? 'FormaFAD LMS'))

@section('content')
<div class="max-w-3xl mx-auto py-10 px-4 sm:px-6 lg:px-8">
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 sm:p-8">
        <div class="flex items-center justify-between border-b border-slate-200 pb-5 mb-6">
            <div>
                <span class="text-xs font-bold uppercase tracking-wider text-indigo-600">Modulo Iscrizione Telematica</span>
                <h1 class="text-2xl font-extrabold text-slate-900 mt-0.5">Registrazione Anagrafica Corsista</h1>
                <p class="text-xs text-slate-500 mt-1">
                    I dati anagrafici richiesti sono obbligatori ai fini del rilascio dell'Attestato Ufficiale valido su tutto il territorio nazionale (Allegati 3 e 4 - Linee Guida B Ministero del Turismo).
                </p>
            </div>
            <a href="{{ route('login') }}" class="text-xs font-semibold text-slate-600 hover:text-indigo-600 shrink-0">
                &larr; Torna al Login
            </a>
        </div>

        <form method="POST" action="{{ route('register.submit') }}" class="space-y-6">
            @csrf

            <!-- Selezione Corso -->
            <div class="bg-indigo-50/70 border border-indigo-200 rounded-xl p-4">
                <label class="block text-xs font-bold text-indigo-950 mb-1.5">
                    Corso a cui desideri iscriverti (opzionale se verrai assegnato dalla Segreteria)
                </label>
                <select name="course_id" class="w-full rounded-xl border border-indigo-300 bg-white px-3.5 py-2.5 text-sm font-medium text-slate-800">
                    <option value="">-- Seleziona un corso attivo dal catalogo --</option>
                    @foreach($courses as $c)
                        <option value="{{ $c->id }}" {{ (old('course_id') == $c->id || $selectedCourseCode === $c->code) ? 'selected' : '' }}>
                            [{{ $c->code }}] {{ $c->title }} ({{ $c->total_hours }}h totali: {{ $c->sync_required_hours }}h Sincrone + {{ $c->async_required_hours }}h FAD)
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Dati Personali e Codice Fiscale -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Nome *</label>
                    <input type="text" name="first_name" value="{{ old('first_name') }}" required placeholder="Es. Giulia"
                           class="w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Cognome *</label>
                    <input type="text" name="last_name" value="{{ old('last_name') }}" required placeholder="Es. De Santis"
                           class="w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Codice Fiscale *</label>
                    <input type="text" name="fiscal_code" value="{{ old('fiscal_code') }}" required maxlength="16" placeholder="DSNGLI85E55H501Z"
                           class="w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm uppercase font-mono">
                </div>
            </div>

            <!-- Dati di Nascita per Attestato Allegato 3/4 -->
            <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Data di Nascita *</label>
                    <input type="date" name="birth_date" value="{{ old('birth_date', '1986-04-18') }}" required
                           class="w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm">
                </div>
                <div class="sm:col-span-1">
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Comune di Nascita *</label>
                    <input type="text" name="birth_city" value="{{ old('birth_city', 'Roma') }}" required
                           class="w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Provincia (Sigla) *</label>
                    <input type="text" name="birth_province" value="{{ old('birth_province', 'RM') }}" required maxlength="4"
                           class="w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm uppercase">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Stato di Nascita *</label>
                    <input type="text" name="birth_country" value="{{ old('birth_country', 'Italia') }}" required
                           class="w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm">
                </div>
            </div>

            <!-- Contatti & N. Elenco Nazionale -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Indirizzo Email *</label>
                    <input type="email" name="email" value="{{ old('email') }}" required placeholder="nome.cognome@email.it"
                           class="w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Telefono Cellulare</label>
                    <input type="text" name="phone" value="{{ old('phone') }}" placeholder="+39 333 1234567"
                           class="w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">N. Iscrizione Elenco Nazionale</label>
                    <input type="text" name="national_list_number" value="{{ old('national_list_number') }}" placeholder="Es. GT-RM-00482"
                           class="w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm">
                </div>
            </div>

            <!-- Credenziali di Accesso -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2 border-t border-slate-200">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Scegli una Password (min. 6 caratteri) *</label>
                    <input type="password" name="password" required
                           class="w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Conferma Password *</label>
                    <input type="password" name="password_confirmation" required
                           class="w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm">
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 pt-4">
                <a href="{{ route('login') }}" class="px-4 py-2.5 rounded-xl border border-slate-300 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                    Annulla
                </a>
                <button type="submit" class="px-6 py-2.5 rounded-xl bg-slate-900 hover:bg-indigo-600 text-white text-sm font-bold transition shadow-xs cursor-pointer">
                    Completa Registrazione e Accedi all'Aula FAD
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
