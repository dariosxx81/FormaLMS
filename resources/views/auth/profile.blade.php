@extends('layouts.app')

@section('title', 'Il Mio Profilo e Sicurezza')

@section('content')
<div class="max-w-4xl mx-auto py-8 px-4 sm:px-6 lg:px-8 space-y-8">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-200 pb-5">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold {{ $user->isAdmin() ? 'bg-amber-100 text-amber-800' : 'bg-indigo-100 text-indigo-800' }}">
                    {{ $user->isAdmin() ? 'Amministratore' : 'Corsista' }}
                </span>
                <span class="text-xs text-slate-400">ID Utente #{{ $user->id }}</span>
            </div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Gestione Profilo & Sicurezza</h1>
            <p class="text-sm text-slate-500 mt-1">Aggiorna le tue credenziali di accesso (Email e Password) e i dati anagrafici.</p>
        </div>

        <div>
            <a href="{{ $user->isAdmin() ? route('admin.dashboard') : route('student.dashboard') }}"
               class="inline-flex items-center gap-2 px-4 py-2 rounded-xl border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 text-xs font-bold transition shadow-2xs">
                &larr; Torna alla Dashboard
            </a>
        </div>
    </div>

    @if ($errors->any())
        <div class="p-4 rounded-2xl bg-red-50 border border-red-200 text-red-700 text-xs space-y-1">
            <div class="font-bold flex items-center gap-2">
                <svg class="w-4 h-4 text-red-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                Si sono verificati dei problemi durante il salvataggio:
            </div>
            <ul class="list-disc pl-5 space-y-0.5">
                @foreach ($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start">
        <!-- Colonna Sinistra / Dati Anagrafici & Email -->
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 sm:p-7">
                <div class="flex items-center gap-3 border-b border-slate-100 pb-4 mb-6">
                    <div class="w-10 h-10 rounded-xl bg-indigo-50 border border-indigo-100 flex items-center justify-center text-indigo-600 font-bold">
                        👤
                    </div>
                    <div>
                        <h2 class="text-base font-extrabold text-slate-900">Anagrafica & Recapiti</h2>
                        <p class="text-xs text-slate-500">I dati identificativi registrati sulla piattaforma e l'indirizzo email di accesso.</p>
                    </div>
                </div>

                <form method="POST" action="{{ route('profile.update') }}" class="space-y-5">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">Nome <span class="text-red-500">*</span></label>
                            <input type="text" name="first_name" value="{{ old('first_name', $user->first_name) }}" required
                                   class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 shadow-2xs">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">Cognome <span class="text-red-500">*</span></label>
                            <input type="text" name="last_name" value="{{ old('last_name', $user->last_name) }}" required
                                   class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 shadow-2xs">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">Codice Fiscale <span class="text-red-500">*</span></label>
                            <input type="text" name="fiscal_code" value="{{ old('fiscal_code', $user->fiscal_code) }}" required maxlength="20"
                                   class="w-full uppercase font-mono rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 shadow-2xs">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">Email di Accesso (Login) <span class="text-red-500">*</span></label>
                            <input type="email" name="email" value="{{ old('email', $user->email) }}" required
                                   class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 shadow-2xs">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">Data di Nascita</label>
                            <input type="date" name="birth_date" value="{{ old('birth_date', $user->birth_date?->format('Y-m-d')) }}"
                                   class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 shadow-2xs">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">Comune di Nascita</label>
                            <input type="text" name="birth_city" value="{{ old('birth_city', $user->birth_city) }}"
                                   class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 shadow-2xs">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">Provincia (Sigla)</label>
                            <input type="text" name="birth_province" value="{{ old('birth_province', $user->birth_province) }}" maxlength="10"
                                   class="w-full uppercase rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 shadow-2xs">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">Stato / Nazione di Nascita</label>
                            <input type="text" name="birth_country" value="{{ old('birth_country', $user->birth_country ?: 'Italia') }}"
                                   class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 shadow-2xs">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">Telefono / Cellulare</label>
                            <input type="text" name="phone" value="{{ old('phone', $user->phone) }}"
                                   class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 shadow-2xs">
                        </div>
                    </div>

                    @if($user->national_list_number)
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">N° Elenco / Albo Nazionale</label>
                            <input type="text" name="national_list_number" value="{{ old('national_list_number', $user->national_list_number) }}"
                                   class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 shadow-2xs">
                        </div>
                    @endif

                    <div class="flex justify-end pt-3 border-t border-slate-100">
                        <button type="submit"
                                class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs shadow-sm hover:shadow transition cursor-pointer">
                            💾 Salva Modifiche Anagrafica ed Email
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Colonna Destra / Cambio Password & Info Sicurezza -->
        <div class="space-y-6">
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                <div class="flex items-center gap-3 border-b border-slate-100 pb-4 mb-5">
                    <div class="w-10 h-10 rounded-xl bg-amber-50 border border-amber-200 flex items-center justify-center text-amber-600 font-bold">
                        🔑
                    </div>
                    <div>
                        <h2 class="text-base font-extrabold text-slate-900">Cambia Password</h2>
                        <p class="text-xs text-slate-500">Aggiorna la tua chiave di accesso.</p>
                    </div>
                </div>

                <form method="POST" action="{{ route('profile.password') }}" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Password Attuale <span class="text-red-500">*</span></label>
                        <input type="password" name="current_password" required
                               placeholder="Inserisci la password corrente"
                               class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 shadow-2xs">
                    </div>

                    <div class="border-t border-slate-100 pt-3">
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Nuova Password <span class="text-red-500">*</span></label>
                        <input type="password" name="password" required minlength="6"
                               placeholder="Almeno 6 caratteri"
                               class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 shadow-2xs">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Conferma Nuova Password <span class="text-red-500">*</span></label>
                        <input type="password" name="password_confirmation" required minlength="6"
                               placeholder="Ripeti la nuova password"
                               class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 shadow-2xs">
                    </div>

                    <div class="pt-3 border-t border-slate-100">
                        <button type="submit"
                                class="w-full py-2.5 rounded-xl bg-amber-600 hover:bg-amber-700 text-white font-bold text-xs shadow-sm hover:shadow transition cursor-pointer">
                            🔒 Aggiorna Password
                        </button>
                    </div>
                </form>
            </div>

            <div class="bg-slate-50 rounded-2xl border border-slate-200 p-5 text-xs space-y-2 text-slate-600">
                <div class="font-bold text-slate-800 flex items-center gap-1.5">
                    🛡️ Sicurezza & Audit Log
                </div>
                <p class="text-[11px] leading-relaxed">
                    Ogni variazione di email o password viene tracciata nel registro di audit della piattaforma secondo le direttive AgID / Linee Guida FAD, con data, ora e indirizzo IP di provenienza.
                </p>
                <div class="pt-2 text-[10px] text-slate-400 font-mono">
                    Ultimo accesso: {{ $user->last_login_at ? $user->last_login_at->format('d/m/Y H:i:s') : 'N/D' }} (IP: {{ $user->last_login_ip ?: 'Locale' }})
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
