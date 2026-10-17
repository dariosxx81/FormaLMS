@extends('layouts.app')

@section('title', 'Pannello Admin — ' . ($settings['platform_name'] ?? 'FormaFAD LMS'))

@section('content')
<div x-data="{ newCourseModal: false }" class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8 space-y-8">

    <!-- Top Admin Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-slate-900">Cruscotto Multi-Corso</h1>
            <p class="text-xs text-slate-500 mt-0.5">
                Panoramica corsi formativi, codici d'accesso corsisti, stato avanzamento e rilascio attestati.
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2.5">
            <a href="{{ route('admin.users.index') }}" class="px-3.5 py-2 rounded-xl bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 text-xs font-bold transition shadow-2xs">
                👥 Gestisci Iscritti & CSV
            </a>
            <button @click="newCourseModal = true" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-xs font-bold transition shadow-xs cursor-pointer">
                + Nuovo Corso
            </button>
        </div>
    </div>

    <!-- 4 Essential KPI Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl border border-slate-200/90 p-5 shadow-2xs flex items-center justify-between">
            <div>
                <div class="text-xs font-semibold text-slate-400">Corsi Configurati</div>
                <div class="text-2xl font-extrabold text-slate-900 mt-1">{{ $courses->count() }}</div>
            </div>
            <div class="w-10 h-10 rounded-xl bg-indigo-50 border border-indigo-200 flex items-center justify-center text-base">📚</div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200/90 p-5 shadow-2xs flex items-center justify-between">
            <div>
                <div class="text-xs font-semibold text-slate-400">Corsisti Registrati</div>
                <div class="text-2xl font-extrabold text-slate-900 mt-1">{{ $totalStudents }}</div>
            </div>
            <div class="w-10 h-10 rounded-xl bg-indigo-50 border border-indigo-200 flex items-center justify-center text-base">👥</div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200/90 p-5 shadow-2xs flex items-center justify-between">
            <div>
                <div class="text-xs font-semibold text-slate-400">Ore Video Erogate</div>
                <div class="text-2xl font-extrabold text-slate-900 mt-1">{{ number_format($totalAsyncHoursWatched, 1, ',', '') }}h</div>
            </div>
            <div class="w-10 h-10 rounded-xl bg-indigo-50 border border-indigo-200 flex items-center justify-center text-base">🎬</div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200/90 p-5 shadow-2xs flex items-center justify-between">
            <div>
                <div class="text-xs font-semibold text-slate-400">Attestati Sbloccati</div>
                <div class="text-2xl font-extrabold text-emerald-600 mt-1">{{ $eligibleCertificates }}</div>
            </div>
            <div class="w-10 h-10 rounded-xl bg-emerald-50 border border-emerald-200 flex items-center justify-center text-base">🏅</div>
        </div>
    </div>

    <!-- Streamlined Course Catalog Cards -->
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <h2 class="text-base font-extrabold text-slate-900">Catalogo Corsi Formativi</h2>
            <span class="text-xs text-slate-400">{{ $courses->where('is_active', true)->count() }} attivi su {{ $courses->count() }}</span>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            @foreach($courses as $course)
                <div x-data="{ menuOpen: false }" class="bg-white rounded-2xl border {{ $course->is_active ? 'border-slate-200' : 'border-amber-300 bg-slate-50/60 opacity-85' }} p-5 shadow-2xs hover:border-slate-300 transition flex flex-col justify-between gap-4">
                    <div>
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex flex-wrap items-center gap-1.5">
                                <span class="px-2.5 py-0.5 rounded-lg bg-slate-900 text-white font-mono text-[11px] font-bold">
                                    {{ $course->code }}
                                </span>
                                <span class="px-2.5 py-0.5 rounded-lg bg-indigo-50 border border-indigo-200 text-[10px] font-bold uppercase">
                                    {{ $course->course_type }}
                                </span>
                                @if($course->is_active)
                                    <span class="px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 text-[10px] font-bold">Attivo</span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full bg-amber-100 text-amber-800 text-[10px] font-bold">Disattivato</span>
                                @endif
                                <span class="px-2 py-0.5 rounded-lg bg-slate-100 border border-slate-200 text-slate-700 font-mono text-[10px] font-bold" title="Codice da comunicare ai corsisti per sbloccare questo corso">
                                    🔑 Cod. Accesso: {{ $course->access_code }}
                                </span>
                            </div>

                            <!-- Discreet Actions (⋯) Dropdown -->
                            <div class="relative" @click.outside="menuOpen = false">
                                <button type="button" @click="menuOpen = !menuOpen"
                                        class="w-8 h-8 rounded-lg hover:bg-slate-100 border border-slate-200 flex items-center justify-center text-slate-500 font-bold cursor-pointer"
                                        title="Azioni Corso">
                                    &ctdot;
                                </button>
                                <div x-cloak x-show="menuOpen" x-transition
                                     class="absolute right-0 mt-1.5 w-56 bg-white rounded-xl border border-slate-200 shadow-xl p-1.5 z-20 text-xs space-y-1">
                                    <a href="{{ route('admin.courses.show', ['course' => $course, 'tab' => 'settings']) }}" class="block px-3 py-2 rounded-lg hover:bg-slate-100 text-slate-700 font-semibold">
                                        ✏️ Modifica & Configura Corso
                                    </a>
                                    <a href="{{ route('admin.courses.show', ['course' => $course, 'tab' => 'modules']) }}" class="block px-3 py-2 rounded-lg hover:bg-slate-100 text-slate-700 font-semibold">
                                        🎬 Gestisci Moduli Video
                                    </a>
                                    <form method="POST" action="{{ route('admin.courses.toggle-status', $course) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="w-full text-left px-3 py-2 rounded-lg hover:bg-slate-100 font-semibold cursor-pointer {{ $course->is_active ? 'text-amber-700' : 'text-emerald-700' }}">
                                            {{ $course->is_active ? '⏸ Disattiva Corso' : '▶ Riattiva Corso' }}
                                        </button>
                                    </form>
                                    <div class="border-t border-slate-100 my-1"></div>
                                    <a href="{{ route('admin.courses.export-pdf', $course) }}" class="block px-3 py-2 rounded-lg hover:bg-slate-100 text-slate-700 font-semibold">
                                        📄 Scarica Registro PDF
                                    </a>
                                    <a href="{{ route('admin.courses.export-csv', $course) }}" class="block px-3 py-2 rounded-lg hover:bg-slate-100 text-slate-700 font-semibold">
                                        📊 Esporta Excel / CSV
                                    </a>
                                    <div class="border-t border-slate-100 my-1"></div>
                                    <form method="POST" action="{{ route('admin.courses.destroy', $course) }}"
                                           onsubmit="return confirm('Sei sicuro di voler eliminare definitivamente il corso {{ addslashes($course->title) }} e tutti i suoi moduli?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="w-full text-left px-3 py-2 rounded-lg hover:bg-red-50 text-red-600 font-bold cursor-pointer">
                                            🗑 Elimina Corso
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <a href="{{ route('admin.courses.show', $course) }}" class="block font-extrabold text-base text-slate-900 hover:text-indigo-600 mt-2.5 leading-snug">
                            {{ $course->title }}
                        </a>
                    </div>

                    <div class="pt-3 border-t border-slate-100 flex flex-wrap items-center justify-between gap-3 text-xs">
                        <div class="flex flex-wrap items-center gap-3 text-slate-500 font-medium">
                            @if($course->has_sync_hours)
                                <span>⏱ <strong>{{ $course->total_hours }}h</strong> ({{ $course->sync_required_hours }}h Zoom + {{ $course->async_required_hours }}h FAD)</span>
                            @else
                                <span>⏱ <strong>{{ $course->total_hours }}h</strong> (100% Piattaforma FAD — Zoom disattivato)</span>
                            @endif
                            <span>•</span>
                            <span>🎬 <strong>{{ $course->modules_count }}</strong> moduli</span>
                            <span>•</span>
                            <span>👥 <strong>{{ $course->enrollments_count }}</strong> iscritti</span>
                        </div>

                        <a href="{{ route('admin.courses.show', $course) }}"
                           class="px-3.5 py-1.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-xs font-bold transition shadow-2xs">
                            Gestisci Corso &rarr;
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Clean Recent Enrollments Table with Row Actions (⋯) -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-2xs overflow-visible">
        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
            <div>
                <h3 class="text-sm font-extrabold text-slate-900">Avanzamento Recente Iscritti</h3>
            </div>
            <a href="{{ route('admin.users.index') }}" class="text-xs font-bold text-indigo-600 hover:underline">
                Vedi anagrafica completa &rarr;
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50/70 border-b border-slate-200 text-[11px] text-slate-400 font-bold uppercase">
                        <th class="py-3 px-5">Corsista</th>
                        <th class="py-3 px-4">Corso</th>
                        <th class="py-3 px-4">Ore Svolte</th>
                        <th class="py-3 px-4">Frequenza</th>
                        <th class="py-3 px-5 text-right">Azioni</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($recentEnrollments as $enr)
                        <tr x-data="{ rowMenu: false }" class="hover:bg-slate-50/60">
                            <td class="py-3 px-5">
                                <div class="font-bold text-slate-900">{{ $enr->user->full_name }}</div>
                                <div class="text-[11px] text-slate-400 font-mono">{{ $enr->user->fiscal_code }}</div>
                            </td>
                            <td class="py-3 px-4">
                                <a href="{{ route('admin.courses.show', $enr->course) }}" class="font-mono font-bold text-indigo-600 hover:underline">
                                    {{ $enr->course->code }}
                                </a>
                            </td>
                            <td class="py-3 px-4 text-slate-600">
                                <strong>{{ $enr->total_hours_completed }}h</strong>
                                @if($enr->course->has_sync_hours)
                                    <span class="text-slate-400">({{ $enr->sync_hours_completed }}h Zoom + {{ $enr->async_hours_completed }}h FAD)</span>
                                @else
                                    <span class="text-slate-400">(100% Piattaforma FAD)</span>
                                @endif
                            </td>
                            <td class="py-3 px-4">
                                <span class="px-2.5 py-0.5 rounded-full font-bold text-[11px] {{ $enr->is_eligible_for_certificate ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                                    {{ $enr->attendance_percent }}%
                                </span>
                            </td>
                            <td class="py-3 px-5 text-right relative" @click.outside="rowMenu = false">
                                <button type="button" @click="rowMenu = !rowMenu" class="px-2.5 py-1 rounded-lg border border-slate-200 hover:bg-slate-100 text-slate-600 font-bold cursor-pointer">
                                    &ctdot;
                                </button>
                                <div x-cloak x-show="rowMenu" x-transition
                                     class="absolute right-5 mt-1 w-48 bg-white rounded-xl border border-slate-200 shadow-xl p-1.5 z-20 text-left space-y-1">
                                    <a href="{{ route('admin.enrollments.dossier-pdf', $enr) }}" class="block px-3 py-1.5 rounded-lg hover:bg-slate-100 text-slate-700 font-semibold">
                                        📋 Fascicolo Probante PDF
                                    </a>
                                    <a href="{{ route('student.enrollments.certificate', $enr) }}" class="block px-3 py-1.5 rounded-lg hover:bg-slate-100 font-semibold {{ $enr->is_eligible_for_certificate ? 'text-emerald-700' : 'text-slate-400' }}">
                                        🎓 Attestato PDF
                                    </a>
                                    <a href="{{ route('admin.courses.show', $enr->course) }}" class="block px-3 py-1.5 rounded-lg hover:bg-slate-100 text-indigo-600 font-semibold">
                                        ⚙️ Scheda Corso
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal: Create New Course (with Sync Hours Toggle & Access Code) -->
    <div x-cloak x-show="newCourseModal" class="fixed inset-0 z-50 bg-slate-950/70 backdrop-blur-xs flex items-center justify-center p-4 overflow-y-auto">
        <div @click.away="newCourseModal = false"
             x-data="{ enableSync: true, syncH: 20, asyncH: 5 }"
             class="bg-white rounded-2xl max-w-2xl w-full p-6 sm:p-8 shadow-2xl border border-slate-200 my-8">
            <div class="flex items-center justify-between border-b border-slate-200 pb-4 mb-5">
                <div>
                    <h3 class="text-lg font-extrabold text-slate-900">Crea Nuovo Corso nel Catalogo FAD</h3>
                    <p class="text-xs text-slate-500">Configura i parametri, scegli se abilitare le ore Zoom o usare solo le ore della piattaforma e imposta il Codice Corso.</p>
                </div>
                <button @click="newCourseModal = false" class="text-slate-400 hover:text-slate-700 text-xl font-bold cursor-pointer">&times;</button>
            </div>

            <form method="POST" action="{{ route('admin.courses.store') }}" class="space-y-4 text-xs">
                @csrf
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Codice Identificativo *</label>
                        <input type="text" name="code" required placeholder="Es. AGG-GT-2026-04" class="w-full rounded-xl border border-slate-300 px-3 py-2 font-mono uppercase">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">🔑 Codice Segreto Iscrizione</label>
                        <input type="text" name="invite_code" placeholder="Es. TURISMO2026 (opz.)" class="w-full rounded-xl border border-slate-300 px-3 py-2 font-mono uppercase">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Tipologia *</label>
                        <select name="course_type" class="w-full rounded-xl border border-slate-300 px-3 py-2">
                            <option value="aggiornamento">Aggiornamento (Allegato 4)</option>
                            <option value="specializzazione">Specializzazione (Allegato 3)</option>
                            <option value="professionale">Formazione Professionale</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Titolo Ufficiale del Corso *</label>
                    <input type="text" name="title" required placeholder="Es. Corso di Aggiornamento Professionale Guide Turistiche" class="w-full rounded-xl border border-slate-300 px-3 py-2">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Ambito Tematico / Territoriale</label>
                        <input type="text" name="thematic_area" value="Area storico-artistica e Diritto del Turismo" class="w-full rounded-xl border border-slate-300 px-3 py-2">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Estremi Provvedimento Autorizzazione</label>
                        <input type="text" name="authorization_details" value="Prot. MiTur D.G. n. 0048219 del 12/09/2026" class="w-full rounded-xl border border-slate-300 px-3 py-2">
                    </div>
                </div>

                <!-- Toggle Enable Zoom Sync Hours vs 100% Platform FAD Hours -->
                <div class="bg-slate-50 p-4 rounded-xl border border-slate-200 space-y-3">
                    <input type="hidden" name="enable_sync_hours" :value="enableSync ? '1' : '0'">
                    <label class="flex items-center justify-between gap-3 cursor-pointer pb-2 border-b border-slate-200">
                        <div>
                            <strong class="text-slate-900 block">Abilita Ore Sincrone Esterne (Zoom)</strong>
                            <span class="text-[11px] text-slate-500">Se disattivato, il corso conterà e mostrerà esclusivamente le ore video maturate in piattaforma.</span>
                        </div>
                        <input type="checkbox" x-model="enableSync" class="w-4 h-4 rounded text-indigo-600">
                    </label>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div x-show="enableSync">
                            <label class="block font-bold text-slate-700 mb-1">Ore Sincrone (Zoom) *</label>
                            <input type="number" step="0.5" name="sync_required_hours" x-model.number="syncH" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Ore Piattaforma (FAD) *</label>
                            <input type="number" step="0.5" name="async_required_hours" x-model.number="asyncH" required class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 font-bold text-indigo-600">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Monte Ore Totale Corso *</label>
                            <input type="number" step="0.5" name="total_hours" :value="enableSync ? (Number(syncH) + Number(asyncH)) : Number(asyncH)" required class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 font-bold">
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-1">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Soglia Minima Attestato (%)</label>
                        <input type="number" name="min_attendance_percent" value="80" min="1" max="100" required class="w-full rounded-xl border border-slate-300 px-3 py-2">
                    </div>
                    <label class="flex items-center gap-2.5 p-3 rounded-xl border border-slate-200 bg-slate-50 cursor-pointer">
                        <input type="checkbox" name="strict_tab_pause" value="1" class="rounded text-indigo-600">
                        <span><strong>Auto-Pausa Cambio Scheda</strong></span>
                    </label>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Popup Presenza ogni N min. (0=OFF)</label>
                        <input type="number" name="presence_check_interval_minutes" value="0" min="0" max="60" class="w-full rounded-xl border border-slate-300 px-3 py-2">
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-200">
                    <button type="button" @click="newCourseModal = false" class="px-4 py-2 rounded-xl border border-slate-300 font-semibold text-slate-700 cursor-pointer">Annulla</button>
                    <button type="submit" class="px-5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold cursor-pointer">Crea Corso &rarr;</button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
