@extends('layouts.app')

@section('title', 'Iscritti & Ore Zoom — Pannello Admin')

@section('content')
<div x-data="{
        newUserModal: false,
        csvImportModal: false,
        assignModalUser: null,
        assignModalName: '',
        editUserModal: false,
        editUser: {
            id: null,
            first_name: '',
            last_name: '',
            fiscal_code: '',
            birth_date: '',
            birth_city: '',
            birth_province: '',
            birth_country: '',
            email: '',
            phone: '',
            status: 'active',
            password: ''
        },
        openEditUser(u) {
            this.editUser = { ...u, password: '' };
            this.editUserModal = true;
        }
     }"
     class="max-w-6xl mx-auto py-8 px-4 sm:px-6 lg:px-8 space-y-6">

    <!-- Clean Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Anagrafica Corsisti & Iscrizioni</h1>
            <p class="text-xs text-slate-500 mt-0.5">
                Gestisci gli iscritti, modifica anagrafiche e password, sospendi o elimina utenti e assegna i corsi.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('register') }}" target="_blank"
               class="px-3.5 py-2 rounded-xl bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 text-xs font-bold transition shadow-2xs">
                Modulo Auto-Registrazione &nearr;
            </a>
            <button @click="csvImportModal = true"
                    class="px-3.5 py-2 rounded-xl bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 text-xs font-bold transition cursor-pointer">
                📥 Importa CSV
            </button>
            <button @click="newUserModal = true"
                    class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold transition cursor-pointer shadow-xs">
                + Nuovo Corsista
            </button>
        </div>
    </div>

    <!-- Compact Search & Filter Bar -->
    <form method="GET" action="{{ route('admin.users.index') }}" class="bg-white rounded-2xl border border-slate-200/90 p-3.5 shadow-2xs flex flex-wrap items-center gap-2.5 text-xs">
        <div class="flex-1 min-w-[220px]">
            <input type="text" name="q" value="{{ $search }}" placeholder="Cerca per nome, cognome, codice fiscale o email..."
                   class="w-full rounded-xl border border-slate-300 px-3.5 py-2">
        </div>
        <div>
            <select name="course_id" class="rounded-xl border border-slate-300 px-3.5 py-2 bg-white">
                <option value="">Tutti i corsi</option>
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

    <!-- Clean Students Table -->
    <div class="bg-white rounded-2xl border border-slate-200/90 shadow-2xs overflow-visible">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50/80 border-b border-slate-200 text-[11px] font-bold uppercase text-slate-500">
                        <th class="py-3.5 px-5">Corsista</th>
                        <th class="py-3.5 px-4">Dati Anagrafici</th>
                        <th class="py-3.5 px-4">Stato Account</th>
                        <th class="py-3.5 px-4">Corsi Assegnati & Frequenza</th>
                        <th class="py-3.5 px-5 text-right">Azioni</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($students as $stu)
                        <tr class="hover:bg-slate-50/70 transition {{ $stu->status !== 'active' ? 'bg-slate-50/50' : '' }}">
                            <td class="py-3.5 px-5">
                                <div class="font-extrabold text-sm text-slate-900">{{ $stu->full_name }}</div>
                                <div class="text-[11px] text-slate-400 font-mono">{{ $stu->fiscal_code ?: 'CF N/D' }} • {{ $stu->email }}</div>
                            </td>
                            <td class="py-3.5 px-4">
                                <div class="font-semibold text-slate-700">
                                    {{ $stu->birth_date ? $stu->birth_date->format('d/m/Y') : 'N/D' }}
                                </div>
                                <div class="text-[11px] text-slate-400">
                                    {{ $stu->birth_city }} ({{ $stu->birth_province }})
                                </div>
                            </td>
                            <td class="py-3.5 px-4">
                                @if($stu->status === 'active')
                                    <span class="px-2.5 py-0.5 rounded-full bg-emerald-100 text-emerald-800 font-bold text-[11px]">● Attivo</span>
                                @elseif($stu->status === 'suspended')
                                    <span class="px-2.5 py-0.5 rounded-full bg-rose-100 text-rose-800 font-bold text-[11px]">⏸ Sospeso</span>
                                @else
                                    <span class="px-2.5 py-0.5 rounded-full bg-amber-100 text-amber-800 font-bold text-[11px]">⏳ In attesa</span>
                                    <form method="POST" action="{{ route('admin.users.approve', $stu) }}" class="mt-1">
                                        @csrf
                                        <button type="submit" class="text-[11px] font-bold text-indigo-600 hover:underline cursor-pointer">Approva &check;</button>
                                    </form>
                                @endif
                            </td>
                            <td class="py-3.5 px-4">
                                <div class="flex flex-wrap gap-1.5">
                                    @forelse($stu->enrollments as $enr)
                                        <div class="inline-flex items-center gap-1.5 bg-slate-50 border border-slate-200 rounded-lg px-2 py-1">
                                            <a href="{{ route('admin.courses.show', $enr->course) }}" class="inline-flex items-center gap-1 hover:underline">
                                                <span class="font-mono font-bold text-indigo-600">{{ $enr->course->code }}</span>
                                                <span class="px-1.5 py-0.5 rounded font-bold text-[10px] {{ $enr->is_eligible_for_certificate ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                                                    {{ $enr->attendance_percent }}%
                                                </span>
                                            </a>
                                            <form method="POST" action="{{ route('admin.enrollments.destroy', $enr) }}"
                                                  onsubmit="return confirm('Rimuovere {{ addslashes($stu->full_name) }} dal corso {{ $enr->course->code }}?');"
                                                  class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" title="Disiscrivi da questo corso"
                                                        class="text-slate-400 hover:text-rose-600 font-bold px-0.5 cursor-pointer">&times;</button>
                                            </form>
                                        </div>
                                    @empty
                                        <span class="text-slate-400 italic">Nessun corso</span>
                                    @endforelse
                                </div>
                            </td>
                            <td class="py-3.5 px-5 text-right">
                                <div class="inline-flex items-center justify-end gap-1.5" x-data="{ actOpen: false }">
                                    <button type="button"
                                            @click="assignModalUser = {{ $stu->id }}; assignModalName = @js($stu->full_name)"
                                            class="px-2.5 py-1.5 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 font-bold transition cursor-pointer">
                                        + Corso
                                    </button>

                                    <button type="button"
                                            @click="openEditUser({
                                                id: {{ $stu->id }},
                                                first_name: @js($stu->first_name),
                                                last_name: @js($stu->last_name),
                                                fiscal_code: @js($stu->fiscal_code ?? ''),
                                                birth_date: @js($stu->birth_date ? $stu->birth_date->format('Y-m-d') : ''),
                                                birth_city: @js($stu->birth_city ?? ''),
                                                birth_province: @js($stu->birth_province ?? ''),
                                                birth_country: @js($stu->birth_country ?? 'Italia'),
                                                email: @js($stu->email),
                                                phone: @js($stu->phone ?? ''),
                                                status: @js($stu->status ?? 'active')
                                            })"
                                            class="px-2.5 py-1.5 rounded-lg bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 font-bold transition cursor-pointer">
                                        ✏️ Modifica
                                    </button>

                                    <div class="relative">
                                        <button type="button" @click="actOpen = !actOpen"
                                                class="px-2.5 py-1.5 rounded-lg bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 font-bold cursor-pointer">
                                            ⋯
                                        </button>
                                        <div x-cloak x-show="actOpen" @click.outside="actOpen = false" x-transition
                                             class="absolute right-0 mt-1.5 w-56 bg-white rounded-xl shadow-xl border border-slate-200 py-1.5 z-30 text-left text-xs">
                                            <form method="POST" action="{{ route('demo.switch', $stu) }}">
                                                @csrf
                                                <button type="submit" class="w-full text-left px-3.5 py-2 hover:bg-slate-50 text-slate-700 font-semibold cursor-pointer">
                                                    👁 Simula Accesso Corsista
                                                </button>
                                            </form>

                                            <form method="POST" action="{{ route('admin.users.toggle-status', $stu) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="w-full text-left px-3.5 py-2 hover:bg-amber-50 text-amber-700 font-semibold cursor-pointer">
                                                    {{ $stu->status === 'active' ? '⏸ Sospendi / Disattiva Utente' : '▶ Riattiva Utente' }}
                                                </button>
                                            </form>

                                            @foreach($stu->enrollments as $enr)
                                                <a href="{{ route('student.enrollments.certificate', $enr) }}"
                                                   class="block px-3.5 py-1.5 hover:bg-slate-50 text-emerald-700 font-semibold">
                                                    🏅 Attestato PDF ({{ $enr->course->code }})
                                                </a>
                                                <a href="{{ route('student.enrollments.audit-pdf', $enr) }}"
                                                   class="block px-3.5 py-1.5 hover:bg-slate-50 text-slate-600 font-medium">
                                                    🛡 Audit PDF ({{ $enr->course->code }})
                                                </a>
                                            @endforeach

                                            <div class="my-1 border-t border-slate-100"></div>
                                            <form method="POST" action="{{ route('admin.users.destroy', $stu) }}"
                                                  onsubmit="return confirm('Sei sicuro di voler eliminare definitivamente l\'utente {{ addslashes($stu->full_name) }} e tutte le sue iscrizioni?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="w-full text-left px-3.5 py-2 hover:bg-rose-50 text-rose-600 font-bold cursor-pointer">
                                                    🗑 Elimina Utente
                                                </button>
                                            </form>
                                        </div>
                                    </div>
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

    <!-- MODAL: Edit Student & Reset Password -->
    <div x-cloak x-show="editUserModal" class="fixed inset-0 z-50 bg-slate-950/70 backdrop-blur-xs flex items-center justify-center p-4 overflow-y-auto">
        <div @click.away="editUserModal = false" class="bg-white rounded-2xl max-w-2xl w-full p-6 sm:p-8 shadow-2xl border border-slate-200 my-8">
            <div class="flex items-center justify-between border-b border-slate-200 pb-3 mb-5">
                <div>
                    <h3 class="text-lg font-extrabold text-slate-900">Modifica Anagrafica & Password Corsista</h3>
                    <p class="text-xs text-slate-500">Aggiorna i dati del corsista, lo stato dell'account o imposta una nuova password.</p>
                </div>
                <button @click="editUserModal = false" class="text-slate-400 hover:text-slate-700 text-xl font-bold cursor-pointer">&times;</button>
            </div>

            <form method="POST" :action="'{{ url('/admin/users') }}/' + editUser.id" class="space-y-4 text-xs">
                @csrf
                @method('PUT')
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Nome *</label>
                        <input type="text" name="first_name" x-model="editUser.first_name" required class="w-full rounded-xl border border-slate-300 px-3 py-2">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Cognome *</label>
                        <input type="text" name="last_name" x-model="editUser.last_name" required class="w-full rounded-xl border border-slate-300 px-3 py-2">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Codice Fiscale *</label>
                        <input type="text" name="fiscal_code" x-model="editUser.fiscal_code" required maxlength="16" class="w-full rounded-xl border border-slate-300 px-3 py-2 uppercase font-mono">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Data Nascita</label>
                        <input type="date" name="birth_date" x-model="editUser.birth_date" class="w-full rounded-xl border border-slate-300 px-3 py-2">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Città Nascita</label>
                        <input type="text" name="birth_city" x-model="editUser.birth_city" class="w-full rounded-xl border border-slate-300 px-3 py-2">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Provincia</label>
                        <input type="text" name="birth_province" x-model="editUser.birth_province" class="w-full rounded-xl border border-slate-300 px-3 py-2 uppercase">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Stato</label>
                        <input type="text" name="birth_country" x-model="editUser.birth_country" class="w-full rounded-xl border border-slate-300 px-3 py-2">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Email *</label>
                        <input type="email" name="email" x-model="editUser.email" required class="w-full rounded-xl border border-slate-300 px-3 py-2">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Telefono</label>
                        <input type="text" name="phone" x-model="editUser.phone" class="w-full rounded-xl border border-slate-300 px-3 py-2">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Stato Account</label>
                        <select name="status" x-model="editUser.status" class="w-full rounded-xl border border-slate-300 px-3 py-2 bg-white font-semibold">
                            <option value="active">● Attivo</option>
                            <option value="suspended">⏸ Sospeso / Disattivato</option>
                            <option value="pending">⏳ In attesa di approvazione</option>
                        </select>
                    </div>
                </div>

                <div class="p-3.5 rounded-xl bg-amber-50/70 border border-amber-200">
                    <label class="block font-bold text-slate-800 mb-1">🔑 Reimposta Password (lascia vuoto per non modificarla)</label>
                    <input type="text" name="password" x-model="editUser.password" placeholder="Inserisci nuova password se vuoi cambiarla..."
                           class="w-full rounded-xl border border-amber-300 bg-white px-3 py-2 font-mono">
                </div>

                <div class="flex justify-end gap-2 pt-3 border-t border-slate-200">
                    <button type="button" @click="editUserModal = false" class="px-4 py-2 rounded-xl border border-slate-300 font-semibold cursor-pointer">Annulla</button>
                    <button type="submit" class="px-5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold cursor-pointer">Salva Modifiche</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL: Create New Student with Multi-Course Assignment -->
    <div x-cloak x-show="newUserModal" class="fixed inset-0 z-50 bg-slate-950/70 backdrop-blur-xs flex items-center justify-center p-4 overflow-y-auto">
        <div @click.away="newUserModal = false" class="bg-white rounded-2xl max-w-2xl w-full p-6 sm:p-8 shadow-2xl border border-slate-200 my-8">
            <div class="flex items-center justify-between border-b border-slate-200 pb-3 mb-5">
                <div>
                    <h3 class="text-lg font-extrabold text-slate-900">Nuovo Corsista & Assegnazione Corsi</h3>
                    <p class="text-xs text-slate-500">Compila i dati anagrafici e seleziona i corsi da abilitare.</p>
                </div>
                <button @click="newUserModal = false" class="text-slate-400 hover:text-slate-700 text-xl font-bold cursor-pointer">&times;</button>
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
                    <label class="block font-bold text-slate-800">Corsi da assegnare subito:</label>
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
                        <label class="block font-bold text-slate-700 mb-1">Ore Sincrone (Zoom) iniziali da accreditare (se previste dal corso)</label>
                        <input type="number" step="0.5" name="sync_hours_completed" value="20" class="w-32 rounded-xl border border-slate-300 bg-white px-3 py-1.5 font-bold">
                    </div>
                </div>

                <div class="flex justify-end gap-2 pt-3 border-t border-slate-200">
                    <button type="button" @click="newUserModal = false" class="px-4 py-2 rounded-xl border border-slate-300 font-semibold cursor-pointer">Annulla</button>
                    <button type="submit" class="px-5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold cursor-pointer">Crea e Iscrivi</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL: Bulk CSV Import -->
    <div x-cloak x-show="csvImportModal" class="fixed inset-0 z-50 bg-slate-950/70 backdrop-blur-xs flex items-center justify-center p-4">
        <div @click.away="csvImportModal = false" class="bg-white rounded-2xl max-w-xl w-full p-6 shadow-2xl border border-slate-200">
            <h3 class="text-base font-extrabold text-slate-900">Importazione Massiva Corsisti (CSV / Excel)</h3>
            <p class="text-xs text-slate-500 mt-1">
                Formato righe: <code class="text-indigo-600 font-bold">Nome;Cognome;CodiceFiscale;Email;DataNascita(YYYY-MM-DD);Citta;Prov;OreZoom</code>
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
                          placeholder="Nome;Cognome;CodiceFiscale;Email;DataNascita;Citta;Prov;OreZoom&#10;Paola;Gentili;GNTPLA81C45H501U;paola.gentili@guideroma.it;1981-03-05;Roma;RM;20"></textarea>

                <div class="flex justify-end gap-2">
                    <button type="button" @click="csvImportModal = false" class="px-4 py-2 rounded-xl border border-slate-300 font-semibold cursor-pointer">Annulla</button>
                    <button type="submit" class="px-5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold cursor-pointer">Importa e Iscrivi</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL: Assign Course to Specific User -->
    <div x-cloak x-show="assignModalUser !== null" class="fixed inset-0 z-50 bg-slate-950/70 backdrop-blur-xs flex items-center justify-center p-4">
        <div @click.away="assignModalUser = null" class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-200">
            <h3 class="text-base font-extrabold text-slate-900">Assegna Corso</h3>
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
                    <label class="block font-bold text-slate-700 mb-1">Ore Sincrone (Zoom) già effettuate (ignorate se il corso è 100% FAD)</label>
                    <input type="number" step="0.5" name="sync_hours_completed" value="20" class="w-full rounded-xl border border-slate-300 px-3 py-2">
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" @click="assignModalUser = null" class="px-4 py-2 rounded-xl border border-slate-300 font-semibold cursor-pointer">Annulla</button>
                    <button type="submit" class="px-5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold cursor-pointer">Assegna Corso</button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
