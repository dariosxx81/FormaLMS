@extends('layouts.app')

@section('title', 'Anagrafica Corsisti & Iscrizioni — Pannello Admin')

@section('content')
<div x-data="{ newUserModal: false, csvImportModal: false, assignModalUser: null, assignModalName: '' }"
     class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8 space-y-6">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <span class="text-xs font-bold uppercase tracking-wider text-indigo-600">Gestione Flusso Ibrido Iscrizioni</span>
            <h1 class="text-2xl font-extrabold text-slate-900">Anagrafica Corsisti & Assegnazione Corsi</h1>
            <p class="text-xs text-slate-500 mt-0.5">
                Registra manualmente i corsisti, importa elenchi massivi da Excel/CSV o abilita gli utenti che si sono auto-registrati dal portale pubblico.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
            <a href="{{ route('register') }}" target="_blank"
               class="px-3.5 py-2.5 rounded-xl bg-white hover:bg-slate-50 text-slate-700 border border-slate-300 text-xs font-bold transition">
                Apri Link Auto-Registrazione Pubblica &nearr;
            </a>
            <button @click="csvImportModal = true"
                    class="px-4 py-2.5 rounded-xl bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 text-xs font-bold transition cursor-pointer">
                Importazione Massiva CSV / Excel
            </button>
            <button @click="newUserModal = true"
                    class="px-4 py-2.5 rounded-xl bg-slate-900 hover:bg-indigo-600 text-white text-xs font-bold transition cursor-pointer">
                + Nuovo Corsista & Assegna Corsi
            </button>
        </div>
    </div>

    <!-- Search & Course Filter Bar -->
    <form method="GET" action="{{ route('admin.users.index') }}" class="bg-white rounded-2xl border border-slate-200 p-4 shadow-2xs flex flex-wrap items-center gap-3 text-xs">
        <div class="flex-1 min-w-[220px]">
            <input type="text" name="q" value="{{ $search }}" placeholder="Cerca per Nome, Cognome, Codice Fiscale o Email..."
                   class="w-full rounded-xl border border-slate-300 px-3.5 py-2">
        </div>
        <div>
            <select name="course_id" class="rounded-xl border border-slate-300 px-3.5 py-2">
                <option value="">-- Tutti i Corsi --</option>
                @foreach($courses as $c)
                    <option value="{{ $c->id }}" {{ (string) $courseFilter === (string) $c->id ? 'selected' : '' }}>
                        [{{ $c->code }}] {{ $c->title }}
                    </option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="px-4 py-2 rounded-xl bg-slate-900 text-white font-bold cursor-pointer">Filtra</button>
        @if($search || $courseFilter)
            <a href="{{ route('admin.users.index') }}" class="px-3 py-2 text-slate-500 hover:text-slate-800 font-semibold">Reset</a>
        @endif
    </form>

    <!-- Students Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-2xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-[11px] font-bold uppercase text-slate-500">
                        <th class="py-3.5 px-5">Corsista & Codice Fiscale</th>
                        <th class="py-3.5 px-4">Dati di Nascita (Allegato 3/4)</th>
                        <th class="py-3.5 px-4">Stato Account</th>
                        <th class="py-3.5 px-4">Corsi Assegnati & Avanzamento</th>
                        <th class="py-3.5 px-5 text-right">Azioni Rapide</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse($students as $stu)
                        <tr class="hover:bg-slate-50/80 transition">
                            <td class="py-4 px-5">
                                <div class="font-extrabold text-sm text-slate-900">{{ $stu->full_name }}</div>
                                <div class="text-[11px] text-slate-500 font-mono">CF: {{ $stu->fiscal_code ?: 'N/D' }}</div>
                                <div class="text-[11px] text-slate-400">{{ $stu->email }} @if($stu->phone) • {{ $stu->phone }} @endif</div>
                            </td>
                            <td class="py-4 px-4">
                                <div class="font-semibold text-slate-800">
                                    {{ $stu->birth_date ? $stu->birth_date->format('d/m/Y') : 'N/D' }}
                                </div>
                                <div class="text-[11px] text-slate-500">
                                    {{ $stu->birth_city }} ({{ $stu->birth_province }}) — {{ $stu->birth_country }}
                                </div>
                                @if($stu->national_list_number)
                                    <div class="text-[10px] text-indigo-600 font-semibold mt-0.5">Elenco Naz.: {{ $stu->national_list_number }}</div>
                                @endif
                            </td>
                            <td class="py-4 px-4">
                                @if($stu->status === 'active')
                                    <span class="px-2.5 py-0.5 rounded-full bg-emerald-100 text-emerald-800 font-bold text-[11px]">Attivo</span>
                                @else
                                    <span class="px-2.5 py-0.5 rounded-full bg-amber-100 text-amber-800 font-bold text-[11px]">In attesa</span>
                                    <form method="POST" action="{{ route('admin.users.approve', $stu) }}" class="mt-1">
                                        @csrf
                                        <button type="submit" class="text-[11px] font-bold text-indigo-600 hover:underline cursor-pointer">Approva ora &check;</button>
                                    </form>
                                @endif
                                @if($stu->last_login_at)
                                    <div class="text-[10px] text-slate-400 mt-1">Ultimo accesso: {{ $stu->last_login_at->format('d/m H:i') }}</div>
                                @endif
                            </td>
                            <td class="py-4 px-4">
                                <div class="space-y-1.5">
                                    @forelse($stu->enrollments as $enr)
                                        <div class="flex items-center justify-between gap-2 bg-slate-50 border border-slate-200 rounded-lg px-2.5 py-1.5">
                                            <div>
                                                <a href="{{ route('admin.courses.show', $enr->course) }}" class="font-mono font-bold text-indigo-600 hover:underline">
                                                    {{ $enr->course->code }}
                                                </a>
                                                <span class="text-slate-600 ml-1">({{ $enr->total_hours_completed }}h / {{ $enr->course->total_hours }}h)</span>
                                            </div>
                                            <div class="flex items-center gap-1.5">
                                                <span class="px-1.5 py-0.5 rounded font-bold text-[10px] {{ $enr->is_eligible_for_certificate ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                                                    {{ $enr->attendance_percent }}%
                                                </span>
                                                <a href="{{ route('student.enrollments.certificate', $enr) }}" class="text-[10px] font-bold text-emerald-600 hover:underline" title="Scarica Attestato PDF">
                                                    PDF
                                                </a>
                                            </div>
                                        </div>
                                    @empty
                                        <span class="text-slate-400 italic">Nessun corso assegnato</span>
                                    @endforelse
                                </div>
                            </td>
                            <td class="py-4 px-5 text-right">
                                <div class="inline-flex flex-wrap items-center justify-end gap-1.5">
                                    <button type="button"
                                            @click="assignModalUser = {{ $stu->id }}; assignModalName = @js($stu->full_name)"
                                            class="px-3 py-1.5 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 font-bold transition cursor-pointer">
                                        + Assegna Corso
                                    </button>
                                    <form method="POST" action="{{ route('demo.switch', $stu) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="px-2.5 py-1.5 rounded-lg bg-slate-900 hover:bg-slate-700 text-white font-bold transition cursor-pointer" title="Entra nei panni di questo corsista">
                                            Simula Accesso
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-8 text-center text-slate-400">Nessun corsista trovato.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- MODAL: Create New Student with Multi-Course Assignment -->
    <div x-cloak x-show="newUserModal" class="fixed inset-0 z-50 bg-slate-950/70 backdrop-blur-xs flex items-center justify-center p-4 overflow-y-auto">
        <div @click.away="newUserModal = false" class="bg-white rounded-2xl max-w-2xl w-full p-6 sm:p-8 shadow-2xl border border-slate-200 my-8">
            <div class="flex items-center justify-between border-b border-slate-200 pb-3 mb-5">
                <div>
                    <h3 class="text-lg font-extrabold text-slate-900">Inserisci Nuovo Corsista & Assegna ai Corsi</h3>
                    <p class="text-xs text-slate-500">Compila i dati anagrafici ministeriali e seleziona uno o più corsi a cui iscriverlo subito.</p>
                </div>
                <button @click="newUserModal = false" class="text-slate-400 hover:text-slate-700 text-xl font-bold">&times;</button>
            </div>

            <form method="POST" action="{{ route('admin.users.store') }}" class="space-y-4 text-xs">
                @csrf
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Nome *</label>
                        <input type="text" name="first_name" required class="w-full rounded-xl border border-slate-300 px-3 py-2">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Cognome *</label>
                        <input type="text" name="last_name" required class="w-full rounded-xl border border-slate-300 px-3 py-2">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Codice Fiscale *</label>
                        <input type="text" name="fiscal_code" required maxlength="16" class="w-full rounded-xl border border-slate-300 px-3 py-2 uppercase font-mono">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Data Nascita</label>
                        <input type="date" name="birth_date" value="1984-06-20" class="w-full rounded-xl border border-slate-300 px-3 py-2">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Città Nascita</label>
                        <input type="text" name="birth_city" value="Roma" class="w-full rounded-xl border border-slate-300 px-3 py-2">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Provincia</label>
                        <input type="text" name="birth_province" value="RM" class="w-full rounded-xl border border-slate-300 px-3 py-2 uppercase">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Stato</label>
                        <input type="text" name="birth_country" value="Italia" class="w-full rounded-xl border border-slate-300 px-3 py-2">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Email *</label>
                        <input type="email" name="email" required class="w-full rounded-xl border border-slate-300 px-3 py-2">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Telefono</label>
                        <input type="text" name="phone" class="w-full rounded-xl border border-slate-300 px-3 py-2">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Password iniziale</label>
                        <input type="text" name="password" value="Corsista2026!" class="w-full rounded-xl border border-slate-300 px-3 py-2 font-mono">
                    </div>
                </div>

                <div class="bg-slate-50 p-4 rounded-xl border border-slate-200 space-y-2">
                    <label class="block font-bold text-slate-800">Seleziona i Corsi a cui iscrivere subito l'utente:</label>
                    <div class="grid grid-cols-1 gap-2 max-h-36 overflow-y-auto">
                        @foreach($courses as $c)
                            <label class="flex items-center gap-2.5 p-2 rounded-lg bg-white border border-slate-200">
                                <input type="checkbox" name="course_ids[]" value="{{ $c->id }}" checked class="rounded text-indigo-600">
                                <span class="font-mono font-bold text-indigo-600">{{ $c->code }}</span>
                                <span class="font-semibold text-slate-800">{{ $c->title }}</span>
                            </label>
                        @endforeach
                    </div>
                    <div class="pt-2">
                        <label class="block font-bold text-slate-700 mb-1">Ore Sincrone (Zoom) iniziali da accreditare</label>
                        <input type="number" step="0.5" name="sync_hours_completed" value="20" class="w-32 rounded-xl border border-slate-300 bg-white px-3 py-1.5 font-bold">
                    </div>
                </div>

                <div class="flex justify-end gap-2 pt-3 border-t border-slate-200">
                    <button type="button" @click="newUserModal = false" class="px-4 py-2 rounded-xl border border-slate-300 font-semibold">Annulla</button>
                    <button type="submit" class="px-5 py-2 rounded-xl bg-slate-900 hover:bg-indigo-600 text-white font-bold cursor-pointer">Crea Corsista e Iscrivi</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL: Bulk CSV Import -->
    <div x-cloak x-show="csvImportModal" class="fixed inset-0 z-50 bg-slate-950/70 backdrop-blur-xs flex items-center justify-center p-4">
        <div @click.away="csvImportModal = false" class="bg-white rounded-2xl max-w-xl w-full p-6 shadow-2xl border border-slate-200">
            <h3 class="text-base font-extrabold text-slate-900">Importazione Massiva Corsisti (CSV / Excel)</h3>
            <p class="text-xs text-slate-500 mt-1">
                Incolla l'elenco degli iscritti (anche 80+ righe insieme) nel formato:<br>
                <code class="text-indigo-600 font-bold">Nome;Cognome;CodiceFiscale;Email;DataNascita(YYYY-MM-DD);Citta;Prov;OreZoom</code>
            </p>

            <form method="POST" action="{{ route('admin.users.import-csv') }}" class="mt-4 space-y-4 text-xs">
                @csrf
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Corso di Destinazione *</label>
                        <select name="course_id" required class="w-full rounded-xl border border-slate-300 px-3 py-2">
                            @foreach($courses as $c)
                                <option value="{{ $c->id }}">[{{ $c->code }}] {{ $c->title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Ore Sincrone (Zoom) predefinite</label>
                        <input type="number" step="0.5" name="default_sync_hours" value="20" class="w-full rounded-xl border border-slate-300 px-3 py-2">
                    </div>
                </div>

                <textarea name="csv_content" rows="6" class="w-full rounded-xl border border-slate-300 p-3 font-mono text-xs"
                          placeholder="Nome;Cognome;CodiceFiscale;Email;DataNascita;Citta;Prov;OreZoom&#10;Paola;Gentili;GNTPLA81C45H501U;paola.gentili@guideroma.it;1981-03-05;Roma;RM;20&#10;Lorenzo;Fabbri;FBBLRN79A12F205K;lorenzo.fabbri@guideroma.it;1979-01-12;Milano;MI;20"></textarea>

                <div class="flex justify-end gap-2">
                    <button type="button" @click="csvImportModal = false" class="px-4 py-2 rounded-xl border border-slate-300 font-semibold">Annulla</button>
                    <button type="submit" class="px-5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold cursor-pointer">Importa e Iscrivi Tutti</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL: Assign Course to Specific User -->
    <div x-cloak x-show="assignModalUser !== null" class="fixed inset-0 z-50 bg-slate-950/70 backdrop-blur-xs flex items-center justify-center p-4">
        <div @click.away="assignModalUser = null" class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-200">
            <h3 class="text-base font-extrabold text-slate-900">Assegna Nuovo Corso</h3>
            <p class="text-xs text-slate-500 mt-0.5" x-text="'Corsista: ' + assignModalName"></p>

            <form method="POST" action="{{ route('admin.enrollments.assign') }}" class="mt-4 space-y-4 text-xs">
                @csrf
                <input type="hidden" name="user_id" :value="assignModalUser">
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Seleziona Corso *</label>
                    <select name="course_id" required class="w-full rounded-xl border border-slate-300 px-3 py-2">
                        @foreach($courses as $c)
                            <option value="{{ $c->id }}">[{{ $c->code }}] {{ $c->title }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Ore Sincrone (Zoom) già effettuate</label>
                    <input type="number" step="0.5" name="sync_hours_completed" value="20" class="w-full rounded-xl border border-slate-300 px-3 py-2">
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" @click="assignModalUser = null" class="px-4 py-2 rounded-xl border border-slate-300 font-semibold">Annulla</button>
                    <button type="submit" class="px-5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold cursor-pointer">Assegna Corso</button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
