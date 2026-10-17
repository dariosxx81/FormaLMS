@extends('layouts.app')

@section('title', 'Gestione Corso ' . $course->code . ' — Pannello Admin')

@section('content')
<div x-data="{
        tab: @js(request('tab', 'enrollments')),
        addModuleModal: false,
        editModuleModal: false,
        editMod: { id: null, sort_order: 1, title: '', instructor_name: '', video_source_type: 'url', video_url: '', duration_minutes: 60, description: '', is_published: true },
        enrollStudentModal: false,
        zoomCsvModal: false,
        attachModalModuleId: null,
        attachModalModuleTitle: '',
        openEditModule(mod) {
            this.editMod = {
                id: mod.id,
                sort_order: mod.sort_order,
                title: mod.title,
                instructor_name: mod.instructor_name || '',
                video_source_type: mod.video_source_type || 'url',
                video_url: mod.video_url || '',
                duration_minutes: Math.max(1, Math.round((mod.duration_seconds || 3600) / 60)),
                description: mod.description || '',
                is_published: !!mod.is_published
            };
            this.editModuleModal = true;
        }
     }"
     class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8 space-y-6">

    <!-- Clean Course Header & Dropdown Actions -->
    <div x-data="{ exportMenu: false }" class="bg-white rounded-2xl p-6 shadow-2xs border {{ $course->is_active ? 'border-slate-200' : 'border-amber-300 bg-amber-50/20' }} flex flex-col lg:flex-row lg:items-center justify-between gap-4">
        <div>
            <div class="flex flex-wrap items-center gap-2 text-xs mb-1.5">
                <a href="{{ route('admin.dashboard') }}" class="text-slate-500 hover:text-indigo-600 font-bold">&larr; Torna ai Corsi</a>
                <span class="text-slate-300">/</span>
                <span class="px-2.5 py-0.5 rounded-lg bg-slate-900 font-mono font-bold text-[11px]">{{ $course->code }}</span>
                <span class="px-2.5 py-0.5 rounded-lg bg-indigo-50 border border-indigo-200 font-bold uppercase text-[10px]">{{ $course->course_type }}</span>
                @if($course->is_active)
                    <span class="px-2.5 py-0.5 rounded-full bg-emerald-100 text-emerald-800 font-bold text-[11px]">Corso Attivo</span>
                @else
                    <span class="px-2.5 py-0.5 rounded-full bg-amber-100 text-amber-800 font-bold text-[11px]">Corso Disattivato</span>
                @endif
                <span class="px-2.5 py-0.5 rounded-lg bg-slate-100 border border-slate-200 text-slate-800 font-mono text-[11px] font-bold">
                    🔑 Codice Accesso Corsisti: {{ $course->access_code }}
                </span>
            </div>
            <h1 class="text-xl font-extrabold text-slate-900">{{ $course->title }}</h1>
            <p class="text-xs text-slate-500 mt-1">
                @if($course->has_sync_hours)
                    Monte ore: <strong>{{ $course->total_hours }}h</strong> ({{ $course->sync_required_hours }}h Zoom + {{ $course->async_required_hours }}h Piattaforma FAD)
                @else
                    Modalità: <strong>100% Piattaforma FAD ({{ $course->total_hours }}h — Ore Zoom disattivate)</strong>
                @endif
                • Soglia Attestato: <strong>{{ $course->min_attendance_percent }}% ({{ $course->minimum_required_hours }}h)</strong>
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2 shrink-0">
            <a href="{{ route('student.courses.show', $course) }}"
               class="px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition">
                &#9654; Apri Player
            </a>

            <form method="POST" action="{{ route('admin.courses.toggle-status', $course) }}">
                @csrf
                @method('PATCH')
                <button type="submit" class="px-3.5 py-2 rounded-xl border text-xs font-bold transition cursor-pointer {{ $course->is_active ? 'bg-amber-50 hover:bg-amber-100 text-amber-800 border-amber-200' : 'bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border-emerald-200' }}">
                    {{ $course->is_active ? '⏸ Disattiva Corso' : '▶ Riattiva Corso' }}
                </button>
            </form>

            <div class="relative" @click.outside="exportMenu = false">
                <button type="button" @click="exportMenu = !exportMenu"
                        class="px-3.5 py-2 rounded-xl bg-white hover:bg-slate-50 text-slate-700 border border-slate-300 text-xs font-bold transition cursor-pointer flex items-center gap-1.5">
                    <span>📥 Registro & Azioni</span>
                    <span>&darr;</span>
                </button>
                <div x-cloak x-show="exportMenu" x-transition
                      class="absolute right-0 mt-1.5 w-56 bg-white rounded-xl border border-slate-200 shadow-xl p-1.5 z-30 text-xs space-y-1">
                    <a href="{{ route('admin.courses.export-pdf', $course) }}" class="block px-3 py-2 rounded-lg hover:bg-slate-100 text-slate-700 font-bold">
                        📄 Registro Ufficiale (PDF)
                    </a>
                    <a href="{{ route('admin.courses.export-csv', $course) }}" class="block px-3 py-2 rounded-lg hover:bg-slate-100 text-slate-700 font-bold">
                        📊 Foglio Excel / CSV
                    </a>
                    <div class="border-t border-slate-100 my-1"></div>
                    <form method="POST" action="{{ route('admin.courses.destroy', $course) }}"
                          onsubmit="return confirm('Sei sicuro di voler eliminare definitivamente questo corso e tutti i suoi moduli?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="w-full text-left px-3 py-2 rounded-lg hover:bg-red-50 text-red-600 font-bold cursor-pointer">
                            🗑 Elimina Corso Definitivamente
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Clean Navigation Tabs + Contextual Primary Action -->
    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 pb-3">
        <div class="flex flex-wrap items-center gap-1.5">
            <button @click="tab = 'enrollments'"
                    :class="tab === 'enrollments' ? 'bg-indigo-600 shadow-xs' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200'"
                    class="px-4 py-2 rounded-xl text-xs font-bold transition cursor-pointer">
                👥 Iscritti & Frequenza ({{ $course->enrollments->count() }})
            </button>
            <button @click="tab = 'modules'"
                    :class="tab === 'modules' ? 'bg-indigo-600 shadow-xs' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200'"
                    class="px-4 py-2 rounded-xl text-xs font-bold transition cursor-pointer">
                🎬 Moduli Video & Dispense ({{ $course->modules->count() }})
            </button>
            <button @click="tab = 'settings'"
                    :class="tab === 'settings' ? 'bg-indigo-600 shadow-xs' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200'"
                    class="px-4 py-2 rounded-xl text-xs font-bold transition cursor-pointer">
                ⚙️ Modifica Corso & Regole
            </button>
        </div>

        <div class="flex items-center gap-2">
            @if($course->has_sync_hours)
                <button x-show="tab === 'enrollments'" @click="zoomCsvModal = true" class="px-3 py-2 rounded-xl bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 text-xs font-bold transition cursor-pointer">
                    📊 Importa CSV Zoom
                </button>
            @endif
            <button x-show="tab === 'enrollments'" @click="enrollStudentModal = true" class="px-3.5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-xs font-bold transition cursor-pointer">
                + Iscrivi Corsista
            </button>
            <button x-show="tab === 'modules'" @click="addModuleModal = true" class="px-3.5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-xs font-bold transition cursor-pointer">
                + Nuovo Modulo Video
            </button>
        </div>
    </div>

    <!-- TAB 1: ENROLLMENTS, HOURS & CERTIFICATES -->
    <div x-show="tab === 'enrollments'" class="bg-white rounded-2xl border border-slate-200 shadow-2xs overflow-visible">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50/80 border-b border-slate-200 text-[11px] font-bold uppercase text-slate-400">
                        <th class="py-3.5 px-5">Corsista</th>
                        @if($course->has_sync_hours)
                            <th class="py-3.5 px-4">Ore Sincrone (Zoom)</th>
                        @endif
                        <th class="py-3.5 px-4">Moduli Video FAD</th>
                        <th class="py-3.5 px-4">Frequenza Maturata</th>
                        <th class="py-3.5 px-5 text-right">Azioni</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($course->enrollments as $enr)
                        @php
                            $u = $enr->user;
                            $userMods = ($progressMatrix[$u->id] ?? collect())->keyBy('course_module_id');
                        @endphp
                        <tr x-data="{ rowOpen: false }" class="hover:bg-slate-50/70 transition">
                            <td class="py-3.5 px-5">
                                <div class="font-extrabold text-sm text-slate-900">{{ $u->full_name }}</div>
                                <div class="text-[11px] text-slate-400 font-mono">{{ $u->fiscal_code }} • {{ $u->email }}</div>
                            </td>

                            @if($course->has_sync_hours)
                                <!-- Inline Editable Sync Hours (Zoom) -->
                                <td class="py-3.5 px-4">
                                    <form method="POST" action="{{ route('admin.enrollments.sync-hours', $enr) }}" class="flex items-center gap-1.5">
                                        @csrf
                                        @method('PUT')
                                        <input type="number" step="0.5" min="0" max="200" name="sync_hours_completed"
                                               value="{{ $enr->sync_hours_completed }}"
                                               class="w-16 rounded-lg border border-slate-300 px-2 py-1 text-xs font-bold text-slate-900">
                                        <span class="text-slate-400">/ {{ $course->sync_required_hours }}h</span>
                                        <button type="submit" class="px-2 py-1 rounded-lg bg-slate-100 hover:bg-indigo-600 text-slate-700 font-bold text-[10px] transition cursor-pointer">
                                            Salva
                                        </button>
                                    </form>
                                </td>
                            @endif

                            <!-- Per-Module Progress Pills -->
                            <td class="py-3.5 px-4">
                                <div class="flex flex-wrap items-center gap-1">
                                    @foreach($course->modules as $m)
                                        @php
                                            $mp = $userMods[$m->id] ?? null;
                                            $done = $mp ? $mp->is_completed : false;
                                            $pct = $mp ? $mp->completion_percent : 0;
                                        @endphp
                                        <span class="px-2 py-0.5 rounded-md text-[10px] font-bold border {{ $done ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : ($pct > 0 ? 'bg-indigo-50 border-indigo-200' : 'bg-slate-100 text-slate-500 border-slate-200') }}"
                                              title="Modulo {{ $m->sort_order }}: {{ $m->title }}">
                                            M{{ $m->sort_order }}: {{ $done ? '100%' : $pct . '%' }}
                                        </span>
                                    @endforeach
                                </div>
                            </td>

                            <td class="py-3.5 px-4">
                                <div class="flex items-center gap-2">
                                    <span class="font-extrabold text-sm text-slate-900">{{ $enr->total_hours_completed }}h / {{ $course->total_hours }}h</span>
                                    <span class="px-2 py-0.5 rounded-full text-[11px] font-bold {{ $enr->is_eligible_for_certificate ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                                        {{ $enr->attendance_percent }}%
                                    </span>
                                </div>
                                <div class="text-[10px] text-slate-400">
                                    @if($course->has_sync_hours)
                                        {{ $enr->async_hours_completed }}h FAD su {{ $course->async_required_hours }}h
                                    @else
                                        100% maturato in piattaforma FAD
                                    @endif
                                </div>
                            </td>

                            <td class="py-3.5 px-5 text-right relative" @click.outside="rowOpen = false">
                                <button type="button" @click="rowOpen = !rowOpen" class="px-2.5 py-1.5 rounded-lg border border-slate-200 hover:bg-slate-100 text-slate-600 font-bold cursor-pointer">
                                    Azioni &ctdot;
                                </button>
                                <div x-cloak x-show="rowOpen" x-transition
                                     class="absolute right-5 mt-1 w-56 bg-white rounded-xl border border-slate-200 shadow-xl p-1.5 z-20 text-left space-y-1">
                                    <a href="{{ route('student.enrollments.certificate', $enr) }}"
                                       class="block px-3 py-2 rounded-lg hover:bg-slate-100 font-bold {{ $enr->is_eligible_for_certificate ? 'text-emerald-700' : 'text-slate-400' }}">
                                        🎓 Scarica Attestato (PDF)
                                    </a>
                                    <a href="{{ route('admin.enrollments.dossier-pdf', $enr) }}"
                                       class="block px-3 py-2 rounded-lg hover:bg-slate-100 text-slate-700 font-semibold">
                                        📋 Fascicolo Probante (PDF)
                                    </a>
                                    <div class="border-t border-slate-100 my-1"></div>
                                    <form method="POST" action="{{ route('admin.enrollments.destroy', $enr) }}"
                                          onsubmit="return confirm('Rimuovere l\'iscrizione di {{ addslashes($u->full_name) }} da questo corso?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="w-full text-left px-3 py-2 rounded-lg hover:bg-red-50 text-red-600 font-bold cursor-pointer">
                                            🗑 Rimuovi dal Corso
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-8 text-center text-slate-400">Nessun corsista ancora iscritto a questo corso.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- TAB 2: VIDEO MODULES & PDF DISPENSE (WITH EDIT, TOGGLE & DELETE) -->
    <div x-cloak x-show="tab === 'modules'" class="space-y-4">
        @forelse($course->modules as $mod)
            <div class="bg-white rounded-2xl border {{ $mod->is_published ? 'border-slate-200' : 'border-amber-300 bg-slate-50/70 opacity-85' }} p-5 shadow-2xs flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                <div class="space-y-1.5">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="px-2.5 py-0.5 rounded bg-slate-900 text-white font-bold text-xs">MODULO {{ $mod->sort_order }}</span>
                        <span class="px-2 py-0.5 rounded bg-indigo-50 text-indigo-700 font-semibold text-xs border border-indigo-200">
                            Durata: {{ $mod->formatted_duration }} ({{ round($mod->duration_seconds / 60) }} min)
                        </span>
                        @if($mod->is_published)
                            <span class="px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 text-[10px] font-bold">Pubblicato</span>
                        @else
                            <span class="px-2 py-0.5 rounded-full bg-amber-100 text-amber-800 text-[10px] font-bold">Nascosto (Bozza)</span>
                        @endif
                        <span class="text-xs text-slate-500">Docente: <strong>{{ $mod->instructor_name ?: 'N/D' }}</strong></span>
                    </div>
                    <h3 class="text-base font-extrabold text-slate-900">{{ $mod->title }}</h3>
                    @if($mod->description)
                        <p class="text-xs text-slate-500">{{ $mod->description }}</p>
                    @endif
                    <div class="text-[11px] text-slate-400 font-mono truncate max-w-2xl">
                        Sorgente ({{ strtoupper($mod->video_source_type) }}): {{ $mod->video_url }}
                    </div>

                    <!-- Attachments Pill List with Delete Option -->
                    <div class="pt-2 flex flex-wrap items-center gap-2">
                        @foreach($mod->attachments as $att)
                            <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-slate-100 border border-slate-200 text-xs font-semibold">
                                <a href="{{ route('student.attachments.download', $att) }}" class="inline-flex items-center gap-1.5 hover:text-indigo-700">
                                    <span class="text-red-600 font-bold">PDF</span>
                                    <span>{{ $att->title }}</span>
                                    <span class="text-[10px] text-slate-400">({{ $att->download_count }} dl)</span>
                                </a>
                                <form method="POST" action="{{ route('admin.attachments.destroy', $att) }}" class="inline"
                                      onsubmit="return confirm('Rimuovere questa dispensa PDF?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="ml-1 text-slate-400 hover:text-red-600 font-bold cursor-pointer" title="Elimina Dispensa">&times;</button>
                                </form>
                            </div>
                        @endforeach
                        <button type="button"
                                @click="attachModalModuleId = {{ $mod->id }}; attachModalModuleTitle = @js($mod->title)"
                                class="px-2.5 py-1 rounded-lg border border-dashed border-indigo-400 text-indigo-600 hover:bg-indigo-50 text-xs font-bold cursor-pointer">
                            + Allega Dispensa PDF
                        </button>
                    </div>
                </div>

                <!-- Module Actions: Play, Edit, Toggle Status, Delete -->
                <div class="flex flex-wrap items-center gap-2 shrink-0">
                    <a href="{{ route('student.courses.module', [$course, $mod]) }}"
                       class="px-3 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition">
                        &#9654; Player
                    </a>

                    <button type="button"
                            @click="openEditModule(@js($mod))"
                            class="px-3 py-2 rounded-xl bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 text-xs font-bold transition cursor-pointer">
                        ✏️ Modifica
                    </button>

                    <form method="POST" action="{{ route('admin.modules.toggle-status', $mod) }}">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="px-3 py-2 rounded-xl border text-xs font-bold transition cursor-pointer {{ $mod->is_published ? 'bg-amber-50 hover:bg-amber-100 text-amber-800 border-amber-200' : 'bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border-emerald-200' }}">
                            {{ $mod->is_published ? '⏸ Nascondi' : '▶ Pubblica' }}
                        </button>
                    </form>

                    <form method="POST" action="{{ route('admin.modules.destroy', $mod) }}"
                          onsubmit="return confirm('Sei sicuro di voler eliminare il modulo {{ addslashes($mod->title) }}?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="px-3 py-2 rounded-xl bg-red-50 hover:bg-red-100 text-red-600 border border-red-200 text-xs font-bold transition cursor-pointer" title="Elimina Modulo">
                            🗑
                        </button>
                    </form>
                </div>
            </div>
        @empty
            <div class="bg-white rounded-2xl border border-slate-200 p-8 text-center text-xs text-slate-400">
                Nessun modulo video presente in questo corso. Clicca su <strong>+ Nuovo Modulo Video</strong> per aggiungerne uno.
            </div>
        @endforelse
    </div>

    <!-- TAB 3: COURSE SETTINGS, SYNC HOURS TOGGLE & ACCESS CODE -->
    <div x-cloak x-show="tab === 'settings'"
         x-data="{
            enableSync: @js($course->has_sync_hours),
            syncH: {{ (float) $course->sync_required_hours }},
            asyncH: {{ (float) $course->async_required_hours }}
         }"
         class="bg-white rounded-2xl border border-slate-200 p-6 sm:p-8 shadow-2xs">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-100 pb-4 mb-6">
            <div>
                <h2 class="text-lg font-extrabold text-slate-900">Modifica Corso, Ore Zoom/FAD & Codice Accesso</h2>
                <p class="text-xs text-slate-500">Modifica i dati del corso, disattiva o attiva le ore sincrone Zoom e imposta il Codice Invito per i corsisti.</p>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.courses.update', $course) }}" class="space-y-5 text-xs">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Codice Corso *</label>
                    <input type="text" name="code" value="{{ $course->code }}" required class="w-full rounded-xl border border-slate-300 px-3.5 py-2 font-mono uppercase">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">🔑 Codice Segreto Corsisti</label>
                    <input type="text" name="invite_code" value="{{ $course->invite_code }}" placeholder="{{ $course->code }}" class="w-full rounded-xl border border-slate-300 px-3.5 py-2 font-mono uppercase">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Tipologia Corso *</label>
                    <select name="course_type" class="w-full rounded-xl border border-slate-300 px-3.5 py-2">
                        <option value="aggiornamento" {{ $course->course_type === 'aggiornamento' ? 'selected' : '' }}>Aggiornamento (Allegato 4)</option>
                        <option value="specializzazione" {{ $course->course_type === 'specializzazione' ? 'selected' : '' }}>Specializzazione (Allegato 3)</option>
                        <option value="professionale" {{ $course->course_type === 'professionale' ? 'selected' : '' }}>Formazione Professionale</option>
                    </select>
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Stato Corso</label>
                    <select name="is_active" class="w-full rounded-xl border border-slate-300 px-3.5 py-2 font-bold">
                        <option value="1" {{ $course->is_active ? 'selected' : '' }}>✓ Attivo (Fruibile)</option>
                        <option value="0" {{ !$course->is_active ? 'selected' : '' }}>⏸ Disattivato / Sospeso</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block font-bold text-slate-700 mb-1">Titolo del Corso *</label>
                <input type="text" name="title" value="{{ $course->title }}" required class="w-full rounded-xl border border-slate-300 px-3.5 py-2">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Ambito Tematico e/o Territoriale (Art. 2 Linee Guida B)</label>
                    <input type="text" name="thematic_area" value="{{ $course->thematic_area }}" class="w-full rounded-xl border border-slate-300 px-3.5 py-2">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Estremi Provvedimento di Autorizzazione</label>
                    <input type="text" name="authorization_details" value="{{ $course->authorization_details }}" class="w-full rounded-xl border border-slate-300 px-3.5 py-2">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Ente / Polo Formativo</label>
                    <input type="text" name="training_center_name" value="{{ $course->training_center_name }}" class="w-full rounded-xl border border-slate-300 px-3.5 py-2">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Città e Provincia Sede</label>
                    <input type="text" name="training_center_city" value="{{ $course->training_center_city }}" class="w-full rounded-xl border border-slate-300 px-3.5 py-2">
                </div>
            </div>

            <!-- Toggle Enable/Disable Zoom Hours -->
            <div class="bg-slate-50 p-5 rounded-2xl border border-slate-200 space-y-4">
                <input type="hidden" name="enable_sync_hours" :value="enableSync ? '1' : '0'">
                <label class="flex items-center justify-between gap-4 cursor-pointer pb-3 border-b border-slate-200">
                    <div>
                        <strong class="text-slate-900 text-sm block">Abilita Ore Sincrone Esterne (Zoom)</strong>
                        <span class="text-xs text-slate-500">
                            Disattivando questa opzione, il corso diventa <strong>100% Piattaforma FAD</strong>: il campo Ore Zoom scompare e l'avanzamento viene calcolato solo sulle ore maturate nei moduli video della piattaforma.
                        </span>
                    </div>
                    <input type="checkbox" x-model="enableSync" class="w-5 h-5 rounded text-indigo-600 shrink-0">
                </label>

                <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                    <div x-show="enableSync">
                        <label class="block font-bold text-slate-700 mb-1">Ore Sincrone (Zoom) *</label>
                        <input type="number" step="0.5" name="sync_required_hours" x-model.number="syncH" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Ore Piattaforma (FAD) *</label>
                        <input type="number" step="0.5" name="async_required_hours" x-model.number="asyncH" required class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 font-bold text-indigo-600">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Monte Ore Totale *</label>
                        <input type="number" step="0.5" name="total_hours" :value="enableSync ? (Number(syncH) + Number(asyncH)) : Number(asyncH)" required class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 font-bold">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">% Minima Attestato *</label>
                        <input type="number" name="min_attendance_percent" value="{{ $course->min_attendance_percent }}" required class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2">
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-1">
                <label class="flex items-center gap-2.5 p-3.5 rounded-xl border border-slate-200 bg-slate-50 cursor-pointer">
                    <input type="checkbox" name="strict_tab_pause" value="1" {{ $course->strict_tab_pause ? 'checked' : '' }} class="rounded text-indigo-600">
                    <span><strong>Auto-Pausa Cambio Scheda</strong> (ferma il video se il corsista cambia tab)</span>
                </label>
                <label class="flex items-center gap-2.5 p-3.5 rounded-xl border border-slate-200 bg-slate-50 cursor-pointer">
                    <input type="checkbox" name="allow_self_enrollment" value="1" {{ $course->allow_self_enrollment ? 'checked' : '' }} class="rounded text-indigo-600">
                    <span><strong>Consenti Sblocco con Codice Corso</strong></span>
                </label>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Popup Presenza ogni N min. (0 = OFF)</label>
                    <input type="number" name="presence_check_interval_minutes" value="{{ $course->presence_check_interval_minutes }}" min="0" max="120" class="w-full rounded-xl border border-slate-300 px-3 py-2">
                </div>
            </div>

            <div class="flex justify-end pt-3 border-t border-slate-100">
                <button type="submit" class="px-6 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold cursor-pointer shadow-xs">
                    Salva Modifiche Corso
                </button>
            </div>
        </form>
    </div>

    <!-- MODAL: Add New Video Module -->
    <div x-cloak x-show="addModuleModal" class="fixed inset-0 z-50 bg-slate-950/70 backdrop-blur-xs flex items-center justify-center p-4">
        <div @click.away="addModuleModal = false" x-data="{ sourceType: 'youtube' }" class="bg-white rounded-2xl max-w-xl w-full p-6 shadow-2xl border border-slate-200">
            <div class="flex items-center justify-between border-b border-slate-200 pb-3 mb-4">
                <h3 class="text-base font-extrabold text-slate-900">Aggiungi Modulo Video</h3>
                <button @click="addModuleModal = false" class="text-slate-400 hover:text-slate-700 text-xl font-bold cursor-pointer">&times;</button>
            </div>

            <form method="POST" action="{{ route('admin.courses.modules.store', $course) }}" enctype="multipart/form-data" class="space-y-4 text-xs">
                @csrf
                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">N. Ordine *</label>
                        <input type="number" name="sort_order" value="{{ $course->modules->count() + 1 }}" required class="w-full rounded-xl border border-slate-300 px-3 py-2">
                    </div>
                    <div class="col-span-2">
                        <label class="block font-bold text-slate-700 mb-1">Docente / Relatore</label>
                        <input type="text" name="instructor_name" placeholder="Es. Prof.ssa Elena Conti" class="w-full rounded-xl border border-slate-300 px-3 py-2">
                    </div>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Titolo della Lezione / Modulo *</label>
                    <input type="text" name="title" required placeholder="Es. Legislazione Turistica e Accessibilità Museale" class="w-full rounded-xl border border-slate-300 px-3 py-2">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Modalità Sorgente Video *</label>
                        <select name="video_source_type" x-model="sourceType" class="w-full rounded-xl border border-slate-300 px-3 py-2">
                            <option value="youtube">🛡 YouTube Mascherato (No-Logo)</option>
                            <option value="url">URL Diretto Video (.mp4 / CDN)</option>
                            <option value="upload">Upload File .MP4 dal PC</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Durata Certificata (minuti) *</label>
                        <input type="number" name="duration_minutes" value="60" min="1" required class="w-full rounded-xl border border-slate-300 px-3 py-2 font-bold">
                    </div>
                </div>

                <div x-show="sourceType === 'url' || sourceType === 'youtube'">
                    <label class="block font-bold text-slate-700 mb-1" x-text="sourceType === 'youtube' ? 'Link o ID Video YouTube' : 'URL del file Video (.mp4)'"></label>
                    <input type="text" name="video_url" value="https://youtu.be/M7lc1UVf-VE" class="w-full rounded-xl border border-slate-300 px-3 py-2 font-mono">
                </div>

                <div x-show="sourceType === 'upload'">
                    <label class="block font-bold text-slate-700 mb-1">Seleziona File Video .MP4</label>
                    <input type="file" name="video_file" accept="video/mp4,video/webm" class="w-full rounded-xl border border-slate-300 px-3 py-2">
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Programma / Argomenti del Modulo</label>
                    <textarea name="description" rows="2" class="w-full rounded-xl border border-slate-300 px-3 py-2"></textarea>
                </div>

                <div class="flex justify-end gap-2 pt-3 border-t border-slate-200">
                    <button type="button" @click="addModuleModal = false" class="px-4 py-2 rounded-xl border border-slate-300 font-semibold cursor-pointer">Annulla</button>
                    <button type="submit" class="px-5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold cursor-pointer">Salva Modulo</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL: Edit Existing Video Module -->
    <div x-cloak x-show="editModuleModal" class="fixed inset-0 z-50 bg-slate-950/70 backdrop-blur-xs flex items-center justify-center p-4">
        <div @click.away="editModuleModal = false" class="bg-white rounded-2xl max-w-xl w-full p-6 shadow-2xl border border-slate-200">
            <div class="flex items-center justify-between border-b border-slate-200 pb-3 mb-4">
                <h3 class="text-base font-extrabold text-slate-900">Modifica Modulo Video</h3>
                <button @click="editModuleModal = false" class="text-slate-400 hover:text-slate-700 text-xl font-bold cursor-pointer">&times;</button>
            </div>

            <form method="POST" :action="'/admin/modules/' + editMod.id" enctype="multipart/form-data" class="space-y-4 text-xs">
                @csrf
                @method('PUT')
                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">N. Ordine *</label>
                        <input type="number" name="sort_order" x-model="editMod.sort_order" required class="w-full rounded-xl border border-slate-300 px-3 py-2">
                    </div>
                    <div class="col-span-2">
                        <label class="block font-bold text-slate-700 mb-1">Docente / Relatore</label>
                        <input type="text" name="instructor_name" x-model="editMod.instructor_name" class="w-full rounded-xl border border-slate-300 px-3 py-2">
                    </div>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Titolo della Lezione / Modulo *</label>
                    <input type="text" name="title" x-model="editMod.title" required class="w-full rounded-xl border border-slate-300 px-3 py-2">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Modalità Sorgente Video *</label>
                        <select name="video_source_type" x-model="editMod.video_source_type" class="w-full rounded-xl border border-slate-300 px-3 py-2">
                            <option value="youtube">🛡 YouTube Mascherato (No-Logo)</option>
                            <option value="url">URL Diretto Video (.mp4 / CDN)</option>
                            <option value="upload">Upload File .MP4 dal PC</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Durata Certificata (minuti) *</label>
                        <input type="number" name="duration_minutes" x-model="editMod.duration_minutes" min="1" required class="w-full rounded-xl border border-slate-300 px-3 py-2 font-bold">
                    </div>
                </div>

                <div x-show="editMod.video_source_type === 'url' || editMod.video_source_type === 'youtube'">
                    <label class="block font-bold text-slate-700 mb-1">Link YouTube o URL Video (.mp4)</label>
                    <input type="text" name="video_url" x-model="editMod.video_url" class="w-full rounded-xl border border-slate-300 px-3 py-2 font-mono">
                </div>

                <div x-show="editMod.video_source_type === 'upload'">
                    <label class="block font-bold text-slate-700 mb-1">Sostituisci File Video .MP4 (opzionale)</label>
                    <input type="file" name="video_file" accept="video/mp4,video/webm" class="w-full rounded-xl border border-slate-300 px-3 py-2">
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Programma / Argomenti del Modulo</label>
                    <textarea name="description" x-model="editMod.description" rows="2" class="w-full rounded-xl border border-slate-300 px-3 py-2"></textarea>
                </div>

                <label class="flex items-center gap-2 p-2.5 rounded-xl bg-slate-50 border border-slate-200 cursor-pointer">
                    <input type="checkbox" name="is_published" value="1" x-model="editMod.is_published" class="rounded text-indigo-600">
                    <span class="font-bold text-slate-700">Modulo pubblicato e visibile ai corsisti</span>
                </label>

                <div class="flex justify-end gap-2 pt-3 border-t border-slate-200">
                    <button type="button" @click="editModuleModal = false" class="px-4 py-2 rounded-xl border border-slate-300 font-semibold cursor-pointer">Annulla</button>
                    <button type="submit" class="px-5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold cursor-pointer">Salva Modifiche</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL: Attach PDF Dispensa -->
    <div x-cloak x-show="attachModalModuleId !== null" class="fixed inset-0 z-50 bg-slate-950/70 backdrop-blur-xs flex items-center justify-center p-4">
        <div @click.away="attachModalModuleId = null" class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-200">
            <h3 class="text-base font-extrabold text-slate-900">Allega Dispensa PDF al Modulo</h3>
            <p class="text-xs text-slate-500 mt-0.5" x-text="attachModalModuleTitle"></p>

            <form method="POST" :action="'/admin/modules/' + attachModalModuleId + '/attachments'" enctype="multipart/form-data" class="mt-4 space-y-4 text-xs">
                @csrf
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Titolo Dispensa / Slide Didattiche *</label>
                    <input type="text" name="title" required placeholder="Es. Slide Lezione - Normativa Beni Culturali (PDF)" class="w-full rounded-xl border border-slate-300 px-3 py-2">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">File PDF (opzionale)</label>
                    <input type="file" name="attachment_file" accept=".pdf,.doc,.docx,.zip" class="w-full rounded-xl border border-slate-300 px-3 py-2">
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="attachModalModuleId = null" class="px-4 py-2 rounded-xl border border-slate-300 font-semibold cursor-pointer">Annulla</button>
                    <button type="submit" class="px-5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold cursor-pointer">Allega Dispensa</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL: Enroll Existing Student -->
    <div x-cloak x-show="enrollStudentModal" class="fixed inset-0 z-50 bg-slate-950/70 backdrop-blur-xs flex items-center justify-center p-4">
        <div @click.away="enrollStudentModal = false" class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-200">
            <h3 class="text-base font-extrabold text-slate-900">Iscrivi Corsista a {{ $course->code }}</h3>
            <form method="POST" action="{{ route('admin.enrollments.assign') }}" class="mt-4 space-y-4 text-xs">
                @csrf
                <input type="hidden" name="course_id" value="{{ $course->id }}">
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Seleziona Corsista dall'Anagrafica *</label>
                    <select name="user_id" required class="w-full rounded-xl border border-slate-300 px-3 py-2">
                        @forelse($availableStudents as $stu)
                            <option value="{{ $stu->id }}">{{ $stu->full_name }} ({{ $stu->fiscal_code }})</option>
                        @empty
                            <option value="">Tutti i corsisti registrati sono già iscritti</option>
                        @endforelse
                    </select>
                </div>
                @if($course->has_sync_hours)
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Ore Sincrone (Zoom) già svolte</label>
                        <input type="number" step="0.5" name="sync_hours_completed" value="{{ $course->sync_required_hours }}" class="w-full rounded-xl border border-slate-300 px-3 py-2">
                    </div>
                @endif
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="enrollStudentModal = false" class="px-4 py-2 rounded-xl border border-slate-300 font-semibold cursor-pointer">Annulla</button>
                    <button type="submit" class="px-5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold cursor-pointer">Iscrivi Ora</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL: Import Zoom Attendance CSV -->
    @if($course->has_sync_hours)
        <div x-cloak x-show="zoomCsvModal" class="fixed inset-0 z-50 bg-slate-950/70 backdrop-blur-xs flex items-center justify-center p-4">
            <div @click.away="zoomCsvModal = false" class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-slate-200">
                <h3 class="text-base font-extrabold text-slate-900">Importa Report Ore Sincrone da Zoom (CSV)</h3>
                <p class="text-xs text-slate-500 mt-1">Incolla le righe dal report Zoom nel formato <code>email_o_codice_fiscale;ore_sincrone</code>.</p>
                <form method="POST" action="{{ route('admin.courses.import-zoom-csv', $course) }}" class="mt-4 space-y-4 text-xs">
                    @csrf
                    <textarea name="zoom_csv_content" rows="5" class="w-full rounded-xl border border-slate-300 p-3 font-mono text-xs"
                              placeholder="elena.bianchi@guideroma.it;20&#10;marco.valeri@guideroma.it;20&#10;sara.lombardi@guideroma.it;18.5"></textarea>
                    <div class="flex justify-end gap-2">
                        <button type="button" @click="zoomCsvModal = false" class="px-4 py-2 rounded-xl border border-slate-300 font-semibold cursor-pointer">Annulla</button>
                        <button type="submit" class="px-5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold cursor-pointer">Aggiorna Ore Zoom</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

</div>
@endsection
