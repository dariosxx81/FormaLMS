@extends('layouts.app')

@section('title', 'Anagrafica Corsisti — Pannello Admin')

@section('content')
<div x-data="{
        newUserModal: false,
        csvImportModal: false,
        assignModalUser: null,
        assignModalName: '',
        editUserModal: false,
        viewCoursesModal: false,
        selectedStudent: null,
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
            role: 'student',
            status: 'active',
            password: ''
        },
        openEditUser(u) {
            this.editUser = { ...u, role: u.role || 'student', password: '' };
            this.editUserModal = true;
        },
        openCoursesModal(student) {
            this.selectedStudent = student;
            this.viewCoursesModal = true;
        }
     }"
     class="max-w-6xl mx-auto py-8 px-4 sm:px-6 lg:px-8 space-y-6">

    <!-- Clean Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Anagrafica Corsisti</h1>
            <p class="text-xs text-slate-500 mt-0.5">
                Gestione completa dei profili utenti, anagrafiche, contatti, stato abilitazione e iscrizione ai corsi.
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
        <div class="flex-1 min-w-[200px]">
            <input type="text" name="q" value="{{ $search }}" placeholder="Cerca per nome, cognome, codice fiscale o email..."
                   class="w-full rounded-xl border border-slate-300 px-3.5 py-2">
        </div>
        <div>
            <select name="role" class="rounded-xl border border-slate-300 px-3 py-2 bg-white">
                <option value="">Tutti i ruoli</option>
                <option value="student" {{ $roleFilter === 'student' ? 'selected' : '' }}>Corsisti</option>
                <option value="admin" {{ $roleFilter === 'admin' ? 'selected' : '' }}>Amministratori</option>
                <option value="tutor" {{ $roleFilter === 'tutor' ? 'selected' : '' }}>Docenti / Tutor</option>
            </select>
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
        @if($search || $courseFilter || $roleFilter)
            <a href="{{ route('admin.users.index') }}" class="px-3 py-2 text-slate-500 hover:text-slate-800 font-semibold">Reset</a>
        @endif
    </form>

    <!-- Clean Students Table -->
    <div class="bg-white rounded-2xl border border-slate-200/90 shadow-2xs">
        <div>
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
                                <div class="flex items-center gap-2">
                                    <span class="font-extrabold text-sm text-slate-900">{{ $stu->full_name }}</span>
                                    @if($stu->role === 'admin')
                                        <span class="px-2 py-0.5 rounded-md bg-amber-100 text-amber-800 font-bold text-[10px]">ADMIN</span>
                                    @elseif($stu->role === 'tutor')
                                        <span class="px-2 py-0.5 rounded-md bg-purple-100 text-purple-800 font-bold text-[10px]">TUTOR</span>
                                    @endif
                                </div>
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
                                @if($stu->enrollments->isNotEmpty())
                                    @php
                                        $enrCount = $stu->enrollments->count();
                                        $completedCount = $stu->enrollments->where('is_eligible_for_certificate', true)->count();
                                        $avgPercent = round($stu->enrollments->avg('attendance_percent'));
                                    @endphp
                                    <button type="button"
                                            @click="openCoursesModal({
                                                id: {{ $stu->id }},
                                                name: @js($stu->full_name),
                                                fiscal_code: @js($stu->fiscal_code ?? ''),
                                                email: @js($stu->email),
                                                enrollments: [
                                                    @foreach($stu->enrollments as $enr)
                                                        {
                                                            id: {{ $enr->id }},
                                                            code: @js($enr->course->code),
                                                            title: @js($enr->course->title),
                                                            course_url: @js(route('admin.courses.show', $enr->course)),
                                                            certificate_url: @js(route('student.enrollments.certificate', $enr)),
                                                            audit_url: @js(route('student.enrollments.audit-pdf', $enr)),
                                                            attendance_percent: {{ $enr->attendance_percent }},
                                                            completed_hours: {{ $enr->total_hours_completed }},
                                                            total_hours: {{ $enr->course->total_hours }},
                                                            is_eligible: {{ $enr->is_eligible_for_certificate ? 'true' : 'false' }},
                                                            enrolled_at: @js($enr->created_at ? $enr->created_at->format('d/m/Y') : 'N/D'),
                                                            reset_url: @js(route('admin.enrollments.reset', $enr)),
                                                            destroy_url: @js(route('admin.enrollments.destroy', $enr))
                                                        },
                                                    @endforeach
                                                ]
                                            })"
                                            class="group inline-flex items-center gap-2 px-3 py-1.5 rounded-xl border border-slate-200 bg-slate-50 hover:bg-indigo-50 hover:border-indigo-300 transition text-left cursor-pointer shadow-2xs">
                                        <div class="flex items-center gap-1.5">
                                            <span class="w-6 h-6 rounded-lg bg-indigo-600/10 text-indigo-700 flex items-center justify-center font-bold text-xs">
                                                {{ $enrCount }}
                                            </span>
                                            <span class="font-bold text-slate-700 group-hover:text-indigo-700 text-xs">
                                                {{ $enrCount === 1 ? 'Corso' : 'Corsi' }}
                                            </span>
                                        </div>
                                        <span class="text-slate-300">•</span>
                                        <div class="flex items-center gap-1">
                                            <span class="px-1.5 py-0.5 rounded-md font-bold text-[10px] {{ $avgPercent >= 80 ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                                                {{ $avgPercent }}% media
                                            </span>
                                        </div>
                                        <span class="text-[10px] text-indigo-500 font-semibold ml-0.5 group-hover:translate-x-0.5 transition-transform">&rarr;</span>
                                    </button>
                                @else
                                    <span class="text-slate-400 italic text-xs">Nessun corso</span>
                                @endif
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
                                            <a href="{{ route('admin.dashboard') }}"
                                               class="block px-3.5 py-2 hover:bg-slate-50 text-indigo-700 font-semibold">
                                                📚 Corsi & Dashboard
                                            </a>

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

    <!-- MODAL: View Student Courses & Progress Details -->
    <div x-cloak x-show="viewCoursesModal" class="fixed inset-0 z-50 bg-slate-950/70 backdrop-blur-xs flex items-center justify-center p-4 overflow-y-auto">
        <div @click.away="viewCoursesModal = false" class="bg-white rounded-2xl max-w-2xl w-full p-6 sm:p-8 shadow-2xl border border-slate-200 my-8">
            <div class="flex items-center justify-between border-b border-slate-200 pb-4 mb-5">
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <span class="px-2 py-0.5 rounded-full bg-indigo-100 text-indigo-800 text-[10px] font-extrabold uppercase tracking-wider">
                            Iscrizioni & Frequenza
                        </span>
                        <span class="text-xs text-slate-400 font-mono" x-text="'CF: ' + (selectedStudent ? selectedStudent.fiscal_code : '')"></span>
                    </div>
                    <h3 class="text-lg font-black text-slate-900" x-text="selectedStudent ? selectedStudent.name : ''"></h3>
                    <p class="text-xs text-slate-500" x-text="selectedStudent ? selectedStudent.email : ''"></p>
                </div>
                <button @click="viewCoursesModal = false" class="text-slate-400 hover:text-slate-700 text-2xl font-bold cursor-pointer leading-none">&times;</button>
            </div>

            <template x-if="selectedStudent && selectedStudent.enrollments.length > 0">
                <div class="space-y-3 max-h-[60vh] overflow-y-auto pr-1">
                    <template x-for="enr in selectedStudent.enrollments" :key="enr.id">
                        <div class="bg-slate-50/80 hover:bg-slate-50 border border-slate-200 rounded-2xl p-4 transition space-y-3">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="font-mono text-xs font-black text-indigo-700 bg-indigo-100/70 px-2 py-0.5 rounded-md" x-text="enr.code"></span>
                                        <span class="font-extrabold text-sm text-slate-900" x-text="enr.title"></span>
                                    </div>
                                    <div class="text-[11px] text-slate-400 mt-1">
                                        Data iscrizione: <span class="text-slate-600 font-semibold" x-text="enr.enrolled_at"></span>
                                    </div>
                                </div>

                                <div class="text-right shrink-0">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-black shadow-2xs"
                                          :class="enr.is_eligible ? 'bg-emerald-100 text-emerald-800 border border-emerald-200' : 'bg-amber-100 text-amber-800 border border-amber-200'"
                                          x-text="enr.attendance_percent + '%'">
                                    </span>
                                    <div class="text-[10px] text-slate-500 font-medium mt-1">
                                        <span x-text="enr.completed_hours + 'h / ' + enr.total_hours + 'h'"></span>
                                    </div>
                                </div>
                            </div>

                            <!-- Progress Bar -->
                            <div class="w-full bg-slate-200 rounded-full h-2 overflow-hidden">
                                <div class="h-2 rounded-full transition-all duration-300"
                                     :class="enr.is_eligible ? 'bg-emerald-500' : 'bg-indigo-600'"
                                     :style="'width: ' + Math.min(100, enr.attendance_percent) + '%'">
                                </div>
                            </div>

                            <!-- Actions Row -->
                            <div class="flex flex-wrap items-center justify-between gap-2 pt-2 border-t border-slate-200/70 text-xs">
                                <div class="flex items-center gap-2">
                                    <a :href="enr.course_url" class="inline-flex items-center gap-1 font-bold text-indigo-600 hover:text-indigo-800 hover:underline">
                                        <span>Apri Corso &nearr;</span>
                                    </a>
                                    <span class="text-slate-300">|</span>
                                    <a :href="enr.certificate_url" class="inline-flex items-center gap-1 font-semibold text-emerald-700 hover:underline">
                                        <span>Attestato PDF</span>
                                    </a>
                                    <span class="text-slate-300">|</span>
                                    <a :href="enr.audit_url" class="inline-flex items-center gap-1 font-semibold text-slate-600 hover:underline">
                                        <span>Audit PDF</span>
                                    </a>
                                </div>

                                <div class="flex items-center gap-3">
                                    <form method="POST" :action="enr.reset_url"
                                          @submit="return confirm('Vuoi davvero azzerare tutto il progresso di ' + selectedStudent.name + ' in questo corso? Dovrà riguardare le lezioni dall\'inizio.');">
                                        @csrf
                                        <button type="submit" class="text-amber-600 hover:text-amber-800 font-bold hover:underline cursor-pointer flex items-center gap-1">
                                            <span>🔄 Azzera Corso</span>
                                        </button>
                                    </form>

                                    <form method="POST" :action="enr.destroy_url"
                                          @submit="return confirm('Vuoi davvero rimuovere ' + selectedStudent.name + ' da questo corso?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-rose-600 hover:text-rose-800 font-bold hover:underline cursor-pointer">
                                            Disiscrivi
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </template>

            <template x-if="!selectedStudent || selectedStudent.enrollments.length === 0">
                <div class="py-8 text-center text-slate-400 text-xs">
                    Nessun corso assegnato a questo utente.
                </div>
            </template>

            <div class="flex items-center justify-between pt-4 mt-4 border-t border-slate-200">
                <button type="button"
                        @click="viewCoursesModal = false; assignModalUser = selectedStudent.id; assignModalName = selectedStudent.name"
                        class="px-4 py-2 rounded-xl bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-xs font-bold transition cursor-pointer">
                    + Assegna Altro Corso
                </button>
                <button type="button" @click="viewCoursesModal = false"
                        class="px-4 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold transition cursor-pointer">
                    Chiudi
                </button>
            </div>
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

                <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                    <div class="sm:col-span-2">
                        <label class="block font-bold text-slate-700 mb-1">Email di Accesso *</label>
                        <input type="email" name="email" x-model="editUser.email" required class="w-full rounded-xl border border-slate-300 px-3 py-2">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Ruolo Piattaforma *</label>
                        <select name="role" x-model="editUser.role" class="w-full rounded-xl border border-slate-300 px-3 py-2 bg-white font-semibold">
                            <option value="student">Corsista</option>
                            <option value="admin">Amministratore</option>
                            <option value="tutor">Docente / Tutor</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Stato Account</label>
                        <select name="status" x-model="editUser.status" class="w-full rounded-xl border border-slate-300 px-3 py-2 bg-white font-semibold">
                            <option value="active">● Attivo</option>
                            <option value="suspended">⏸ Sospeso</option>
                            <option value="pending">⏳ In attesa</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Telefono / Recapito</label>
                    <input type="text" name="phone" x-model="editUser.phone" class="w-full rounded-xl border border-slate-300 px-3 py-2">
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

                <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                    <div class="sm:col-span-2">
                        <label class="block font-bold text-slate-700 mb-1">Email *</label>
                        <input type="email" name="email" required class="w-full rounded-xl border border-slate-300 px-3 py-2">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Ruolo</label>
                        <select name="role" class="w-full rounded-xl border border-slate-300 px-3 py-2 bg-white font-semibold">
                            <option value="student" selected>Corsista</option>
                            <option value="admin">Amministratore</option>
                            <option value="tutor">Docente / Tutor</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Password iniziale</label>
                        <input type="text" name="password" value="Corsista2026!" class="w-full rounded-xl border border-slate-300 px-3 py-2 font-mono">
                    </div>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Telefono / Recapito</label>
                    <input type="text" name="phone" class="w-full rounded-xl border border-slate-300 px-3 py-2">
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
