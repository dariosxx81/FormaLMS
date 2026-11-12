@extends('layouts.app')

@section('title', 'Gestione Corso ' . $course->code . ' — Pannello Admin')

@section('content')
<div x-data="{
        tab: @js(session('active_tab', request('tab', 'enrollments'))),
        addModuleModal: false,
        editModuleModal: false,
        syncZoomModal: false,
        editMod: { id: null, sort_order: 1, title: '', instructor_name: '', video_source_type: 'url', video_url: '', zoom_meeting_id: '', zoom_occurrence_id: '', zoom_passcode: '', zoom_scheduled_at: '', zoom_scheduled_end_at: '', zoom_join_url: '', subtitles_url: '', available_at: '', available_until_at: '', duration_minutes: 60, description: '', is_published: true },
        enrollStudentModal: false,
        zoomCsvModal: false,
        attachModalModuleId: null,
        attachModalModuleTitle: '',
        openEditModule(mod) {
            let sched = '';
            if (mod.zoom_scheduled_at) {
                sched = String(mod.zoom_scheduled_at).substring(0, 16).replace(' ', 'T');
            }
            let schedEnd = '';
            if (mod.zoom_scheduled_end_at) {
                schedEnd = String(mod.zoom_scheduled_end_at).substring(0, 16).replace(' ', 'T');
            }
            let availAt = '';
            if (mod.available_at) {
                availAt = String(mod.available_at).substring(0, 16).replace(' ', 'T');
            }
            let availUntil = '';
            if (mod.available_until_at) {
                availUntil = String(mod.available_until_at).substring(0, 16).replace(' ', 'T');
            }
            const isZoom = (mod.video_source_type === 'zoom');
            this.editMod = {
                id: mod.id,
                sort_order: mod.sort_order,
                title: mod.title,
                instructor_name: mod.instructor_name || '',
                video_source_type: mod.video_source_type || 'url',
                video_url: isZoom ? '' : (mod.video_url || ''),
                zoom_meeting_id: isZoom ? (mod.zoom_meeting_id || '') : '',
                zoom_occurrence_id: isZoom ? (mod.zoom_occurrence_id || '') : '',
                zoom_passcode: isZoom ? (mod.zoom_passcode || '') : '',
                zoom_scheduled_at: sched,
                zoom_scheduled_end_at: schedEnd,
                zoom_join_url: isZoom ? (mod.zoom_join_url || '') : '',
                subtitles_url: mod.subtitles_url || '',
                available_at: availAt,
                available_until_at: availUntil,
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
        <div class="flex items-start gap-4 min-w-0">
            <div class="w-16 h-16 min-w-[64px] min-h-[64px] max-w-[64px] max-h-[64px] rounded-2xl bg-gradient-to-br from-slate-50 to-slate-100 border border-slate-200/90 overflow-hidden flex items-center justify-center p-1.5 shadow-xs shrink-0 mt-0.5">
                @if($course->badge_image_url)
                    <img src="{{ $course->badge_image_url }}" alt="Badge {{ $course->title }}" class="w-full h-full max-w-full max-h-full object-contain drop-shadow-2xs">
                @else
                    <div class="w-full h-full rounded-xl bg-indigo-50/70 border border-indigo-100 flex flex-col items-center justify-center text-indigo-600">
                        <span class="text-2xl leading-none">🎓</span>
                        <span class="text-[9px] font-black uppercase text-indigo-400 mt-0.5">FAD</span>
                    </div>
                @endif
            </div>
            <div class="min-w-0 flex-1">
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

            <form method="POST" action="{{ route('admin.courses.duplicate', $course) }}"
                  onsubmit="return confirm('Duplicare questo corso con tutti i suoi moduli didattici? Verranno generati codici corso univoci.');">
                @csrf
                <button type="submit" class="px-3.5 py-2 rounded-xl bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 text-xs font-bold transition cursor-pointer flex items-center gap-1.5" title="Crea una copia esatta del corso con codice univoco">
                    <span>📋 Duplica Corso</span>
                </button>
            </form>

            <div class="relative" @click.outside="exportMenu = false">
                <button type="button" @click="exportMenu = !exportMenu"
                        class="px-3.5 py-2 rounded-xl bg-white hover:bg-slate-50 text-slate-700 border border-slate-300 text-xs font-bold transition cursor-pointer flex items-center gap-1.5">
                    <span>📥 Esporta Registri & Fascicolo</span>
                    <span>&darr;</span>
                </button>
                <div x-cloak x-show="exportMenu" x-transition
                      class="absolute right-0 mt-1.5 w-64 bg-white rounded-xl border border-slate-200 shadow-xl p-1.5 z-30 text-xs space-y-1">
                    <a href="{{ route('admin.courses.export-dossier-pdf', $course) }}" class="block px-3 py-2 rounded-lg bg-indigo-50/70 hover:bg-indigo-100 text-indigo-700 font-bold">
                        📑 Fascicolo Probante Corso (PDF)
                    </a>
                    <a href="{{ route('admin.courses.export-pdf', $course) }}" class="block px-3 py-2 rounded-lg hover:bg-slate-100 text-slate-700 font-bold">
                        📄 Registro Presenze Ufficiale (PDF)
                    </a>
                    <a href="{{ route('admin.courses.export-csv', $course) }}" class="block px-3 py-2 rounded-lg hover:bg-slate-100 text-slate-700 font-bold">
                        📊 Foglio Excel / CSV
                    </a>
                    <div class="border-t border-slate-100 my-1"></div>
                    <form method="POST" action="{{ route('admin.courses.duplicate', $course) }}"
                          onsubmit="return confirm('Duplicare questo corso con tutti i suoi moduli didattici?');">
                        @csrf
                        <button type="submit" class="w-full text-left px-3 py-2 rounded-lg hover:bg-indigo-50 text-indigo-700 font-bold cursor-pointer">
                            📋 Duplica Corso & Moduli
                        </button>
                    </form>
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
            @php
                $zoomAutoMode = (($settings['zoom_module_enabled'] ?? '0') === '1') || $course->modules->where('video_source_type', 'zoom')->isNotEmpty();
            @endphp
            @if($course->has_sync_hours && !$zoomAutoMode)
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
            <button x-show="tab === 'modules'" @click="syncZoomModal = true" class="px-3.5 py-2 rounded-xl bg-sky-600 hover:bg-sky-500 text-white text-xs font-bold transition cursor-pointer flex items-center gap-1.5 shadow-2xs">
                <span>🎥 Sincronizza Date da Zoom</span>
            </button>
        </div>
    </div>

    <!-- TAB 1: ENROLLMENTS, HOURS & CERTIFICATES -->
    <div x-show="tab === 'enrollments'" class="bg-white rounded-2xl border border-slate-200 shadow-2xs">
        <div>
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
                                <!-- Sync Hours (Zoom): Read-only Certified when Zoom Module is Active, Editable only in Legacy Mode -->
                                <td class="py-3.5 px-4">
                                    @if($zoomAutoMode)
                                        <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-sky-50 border border-sky-200 text-sky-900 font-bold text-xs"
                                             title="Ore certificate in modo inalterabile dai log delle sessioni Zoom">
                                            <span>🔒 {{ $enr->sync_hours_completed }}h / {{ $course->sync_required_hours }}h</span>
                                            <span class="text-[9px] uppercase bg-sky-100 text-sky-700 px-1.5 py-0.5 rounded font-extrabold">Certificato</span>
                                        </div>
                                    @else
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
                                    @endif
                                </td>
                            @endif

                            <!-- Per-Module Progress Pills with individual reset -->
                            <td class="py-3.5 px-4">
                                <div class="flex flex-wrap items-center gap-1">
                                    @foreach($course->modules as $m)
                                        @php
                                            $mp = $userMods[$m->id] ?? null;
                                            $done = $mp ? $mp->is_completed : false;
                                            $pct = $mp ? $mp->completion_percent : 0;
                                        @endphp
                                        <div class="inline-flex items-center rounded-md border text-[10px] font-bold overflow-hidden {{ $done ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : ($pct > 0 ? 'bg-indigo-50 text-indigo-700 border-indigo-200' : 'bg-slate-100 text-slate-500 border-slate-200') }}">
                                            <span class="px-1.5 py-0.5" title="Modulo {{ $m->sort_order }}: {{ $m->title }}">
                                                M{{ $m->sort_order }}: {{ $done ? '100%' : $pct . '%' }}
                                            </span>
                                            @if($pct > 0)
                                                <form method="POST" action="{{ route('admin.enrollments.modules.reset', [$enr, $m]) }}"
                                                      onsubmit="return confirm('Azzerare la visione del modulo M{{ $m->sort_order }} per {{ addslashes($u->full_name) }}?');"
                                                      class="inline border-l border-slate-200">
                                                    @csrf
                                                    <button type="submit" title="Azzera questo modulo" class="px-1 py-0.5 hover:bg-rose-100 hover:text-rose-700 text-slate-400 font-black cursor-pointer">
                                                        &times;
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
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
                                     class="absolute right-5 bottom-full mb-1 sm:bottom-auto sm:top-full sm:mt-1 w-56 bg-white rounded-xl border border-slate-200 shadow-2xl p-1.5 z-50 text-left space-y-1">
                                    <a href="{{ route('student.enrollments.certificate', $enr) }}"
                                       class="block px-3 py-2 rounded-lg hover:bg-slate-100 font-bold {{ $enr->is_eligible_for_certificate ? 'text-emerald-700' : 'text-slate-400' }}">
                                        🎓 Scarica Attestato (PDF)
                                    </a>
                                    <a href="{{ route('admin.enrollments.dossier-pdf', $enr) }}"
                                       class="block px-3 py-2 rounded-lg hover:bg-slate-100 text-slate-700 font-semibold">
                                        📋 Fascicolo Probante (PDF)
                                    </a>
                                    <div class="border-t border-slate-100 my-1"></div>
                                    <form method="POST" action="{{ route('admin.enrollments.reset', $enr) }}"
                                          onsubmit="return confirm('Sei sicuro di voler azzerare l\'intero progresso di {{ addslashes($u->full_name) }} per questo corso? Dovrà riguardare le lezioni.');">
                                        @csrf
                                        <button type="submit" class="w-full text-left px-3 py-2 rounded-lg hover:bg-amber-50 text-amber-700 font-bold cursor-pointer">
                                            🔄 Azzera Progresso Corso
                                        </button>
                                    </form>
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

    <!-- TAB 2: VIDEO MODULES & PDF DISPENSE (WITH DRAG & DROP SORT, EDIT, TOGGLE & DELETE) -->
    <div x-cloak x-show="tab === 'modules'" class="space-y-4">
        @if($course->modules->count() > 1)
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs bg-slate-100/90 rounded-xl px-4 py-2.5 border border-slate-200">
                <div class="flex items-center gap-2 text-slate-700">
                    <span class="text-base select-none">✋</span>
                    <span><strong>Trascina e rilascia</strong> (Drag &amp; Drop) i moduli afferrando la maniglia <span class="font-mono font-bold text-slate-500">⋮⋮</span> per cambiarne l'ordine in tempo reale.</span>
                </div>
                <div id="reorder-toast" class="hidden items-center gap-1.5 px-3 py-1 rounded-lg bg-emerald-100 text-emerald-800 font-bold text-xs transition">
                    <span>✓ Ordine moduli aggiornato!</span>
                </div>
            </div>
        @endif

        <div id="sortable-modules" class="space-y-3">
            @forelse($course->modules as $mod)
                <div class="module-card bg-white rounded-2xl border {{ $mod->is_published ? 'border-slate-200 hover:border-slate-300' : 'border-amber-300 bg-slate-50/70 opacity-85' }} p-4 sm:p-5 shadow-2xs flex flex-col lg:flex-row lg:items-center justify-between gap-4 transition duration-150"
                     data-module-id="{{ $mod->id }}">
                    <div class="flex items-start gap-3 flex-1 min-w-0">
                        <div class="drag-handle cursor-grab active:cursor-grabbing text-slate-300 hover:text-indigo-600 p-1.5 rounded-lg hover:bg-slate-100 transition select-none shrink-0 mt-0.5" title="Trascina per spostare di ordine">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 8h16M4 16h16" />
                            </svg>
                        </div>
                        <div class="space-y-1.5 flex-1 min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="module-order-badge px-2.5 py-0.5 rounded bg-slate-900 text-white font-bold text-xs">MODULO {{ $mod->sort_order }}</span>
                                @if($mod->video_source_type === 'zoom')
                                    <span class="px-2.5 py-0.5 rounded bg-sky-100 text-sky-800 border border-sky-300 font-extrabold text-[11px]">
                                        🎥 LEZIONE SINCRONA LIVE (ZOOM)
                                    </span>
                                @endif
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
                            @if($mod->video_source_type === 'zoom')
                                <div class="text-[11px] text-sky-800 font-mono flex flex-wrap items-center gap-3 bg-sky-50/70 px-3 py-1.5 rounded-lg border border-sky-200">
                                    <span><strong>Meeting ID:</strong> {{ $mod->zoom_meeting_id ?: 'Non specificato' }}</span>
                                    @if($mod->zoom_passcode)
                                        <span>• <strong>Passcode:</strong> {{ $mod->zoom_passcode }}</span>
                                    @endif
                                    @if(!empty($mod->occurrences_list))
                                        <span class="text-sky-900 font-bold">• 📅 {{ count($mod->occurrences_list) }} Ripetizioni Sincronizzate (Totale {{ $mod->formatted_duration }})</span>
                                    @elseif($mod->zoom_scheduled_at)
                                        <span>• <strong>Data/Ora:</strong> {{ $mod->zoom_scheduled_at->format('d/m/Y H:i') }}</span>
                                    @endif
                                </div>
                                @if(!empty($mod->occurrences_list))
                                    <details class="text-[11px] text-slate-600 bg-slate-50 border border-slate-200 rounded-lg p-2 mt-1">
                                        <summary class="cursor-pointer font-bold text-sky-700 hover:text-sky-900 select-none">
                                            Visualizza Calendario Ripetizioni ({{ count($mod->occurrences_list) }} sessioni programmate) ▼
                                        </summary>
                                        <div class="mt-2 space-y-1 max-h-36 overflow-y-auto pl-2 border-l-2 border-sky-400">
                                            @foreach($mod->occurrences_list as $occ)
                                                <div class="flex items-center justify-between text-[10px] pr-2">
                                                    <span><strong>#{{ $occ['index'] }}</strong>: {{ $occ['day_name'] }} {{ $occ['formatted_date'] }} ({{ $occ['formatted_time'] }})</span>
                                                    <span class="font-mono text-slate-400">{{ $occ['status'] }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    </details>
                                @endif
                            @else
                                <div class="text-[11px] text-slate-400 font-mono truncate max-w-2xl">
                                    Sorgente ({{ strtoupper($mod->video_source_type) }}): {{ $mod->video_url }}
                                </div>
                                @if($mod->available_at || $mod->available_until_at)
                                    <div class="text-[11px] font-semibold flex flex-wrap items-center gap-2 mt-1 {{ $mod->is_locked_by_date ? 'text-amber-800 bg-amber-50 border border-amber-200' : 'text-emerald-800 bg-emerald-50 border border-emerald-200' }} px-2.5 py-1 rounded-lg w-fit">
                                        <span>📅 <strong>Disponibilità Programmata:</strong></span>
                                        @if($mod->available_at)
                                            <span>dal {{ $mod->available_at->format('d/m/Y H:i') }}</span>
                                        @endif
                                        @if($mod->available_until_at)
                                            <span>fino al {{ $mod->available_until_at->format('d/m/Y H:i') }}</span>
                                        @endif
                                        @if($mod->is_locked_by_date)
                                            <span class="px-1.5 py-0.5 rounded bg-amber-200 text-amber-900 font-bold text-[10px]">🔒 Non ancora accessibile ai corsisti</span>
                                        @else
                                            <span class="px-1.5 py-0.5 rounded bg-emerald-200 text-emerald-900 font-bold text-[10px]">✓ Attualmente fruibile</span>
                                        @endif
                                    </div>
                                @endif
                            @endif

                            <!-- Attachments Pill List with Delete Option -->
                            <div class="pt-2 flex flex-wrap items-center gap-2">
                                @foreach($mod->attachments as $att)
                                    <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-slate-100 border border-slate-200 text-xs font-semibold">
                                        <a href="{{ route('student.attachments.download', $att) }}" class="inline-flex items-center gap-1.5 hover:text-indigo-700" title="Scarica dispensa PDF">
                                            <span class="text-red-600 font-bold">PDF</span>
                                            <span>{{ $att->title }}</span>
                                            <span class="text-[10px] text-slate-500 font-medium bg-slate-200/70 px-1.5 py-0.5 rounded" title="Download effettuati dai corsisti">📥 {{ $att->download_count }} download</span>
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
                    </div>

                    <!-- Module Actions: Play, Edit, Toggle Status, Delete -->
                    <div class="flex flex-wrap items-center gap-2 shrink-0">
                        @if($mod->video_source_type === 'zoom')
                            <form method="POST" action="{{ route('admin.modules.sync-zoom', $mod) }}">
                                @csrf
                                <button type="submit"
                                        class="px-3 py-2 rounded-xl bg-sky-50 hover:bg-sky-100 text-sky-700 border border-sky-200 text-xs font-bold transition cursor-pointer"
                                        title="Sincronizza i minuti di presenza dal report API Zoom Cloud">
                                    🔄 Sincronizza Presenze Zoom
                                </button>
                            </form>
                        @endif

                        <a href="{{ route('student.courses.module', [$course, $mod]) }}"
                           class="px-3 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition">
                            &#9654; {{ $mod->video_source_type === 'zoom' ? 'Aula Live' : 'Player' }}
                        </a>

                        <button type="button"
                                @click="openEditModule(@js($mod))"
                                class="px-3 py-2 rounded-xl bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 text-xs font-bold transition cursor-pointer">
                            ✏️ Modifica
                        </button>

                        <form method="POST" action="{{ route('admin.modules.duplicate', $mod) }}"
                              onsubmit="return confirm('Duplicare questo modulo didattico all\'interno del corso?');">
                            @csrf
                            <button type="submit" class="px-3 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200 text-xs font-bold transition cursor-pointer flex items-center gap-1" title="Duplica Modulo">
                                <span>📋 Duplica</span>
                            </button>
                        </form>

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
    </div>

    <!-- TAB 3: COURSE SETTINGS, SYNC HOURS TOGGLE & ACCESS CODE -->
    @php
        $calcSyncMin = round($course->modules->where('video_source_type', 'zoom')->sum('duration_seconds') / 60);
        $calcAsyncMin = round($course->modules->where('video_source_type', '!=', 'zoom')->sum('duration_seconds') / 60);
        $calcTotalMin = $calcSyncMin + $calcAsyncMin;
        $hasZoomModules = $calcSyncMin > 0;
    @endphp
    <div x-cloak x-show="tab === 'settings'"
         x-data="{
            hasZoomModules: {{ $hasZoomModules ? 'true' : 'false' }},
            enableSync: {{ ($hasZoomModules || (float) $course->sync_required_hours > 0) ? 'true' : 'false' }},
            syncMin: {{ $hasZoomModules ? $calcSyncMin : round((float) $course->sync_required_hours * 60) }},
            asyncMin: {{ round((float) $course->async_required_hours * 60) }},
            syncHours: {{ $hasZoomModules ? round($calcSyncMin / 60, 2) : (float) $course->sync_required_hours }},
            asyncHours: {{ (float) $course->async_required_hours }},
            onSyncMinInput() {
                this.syncHours = (Number(this.syncMin || 0) / 60).toFixed(2);
            },
            onAsyncMinInput() {
                this.asyncHours = (Number(this.asyncMin || 0) / 60).toFixed(2);
            },
            onSyncHoursInput() {
                this.syncMin = Math.round(Number(this.syncHours || 0) * 60);
            },
            onAsyncHoursInput() {
                this.asyncMin = Math.round(Number(this.asyncHours || 0) * 60);
            },
            get totalMinutes() {
                return (this.hasZoomModules || this.enableSync)
                    ? (Number(this.syncMin || 0) + Number(this.asyncMin || 0))
                    : Number(this.asyncMin || 0);
            },
            get totalHoursFormatted() {
                return (this.totalMinutes / 60).toFixed(2);
            },
            autoCalculateFromModules() {
                this.syncMin = {{ $calcSyncMin }};
                this.asyncMin = {{ max(1, $calcAsyncMin) }};
                if (this.hasZoomModules || this.syncMin > 0) {
                    this.enableSync = true;
                }
                this.onSyncMinInput();
                this.onAsyncMinInput();
            }
         }"
         class="bg-white rounded-2xl border border-slate-200 p-6 sm:p-8 shadow-2xs">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-100 pb-4 mb-6">
            <div>
                <h2 class="text-lg font-extrabold text-slate-900">Modifica Corso, Ore Zoom/FAD & Codice Accesso</h2>
                <p class="text-xs text-slate-500">Configura i parametri del corso, imposta la durata in minuti o in ore e gestisci il Codice Invito per i corsisti.</p>
            </div>
            @if($course->modules->isNotEmpty())
                <button type="button" @click="autoCalculateFromModules()"
                        class="px-3.5 py-2 rounded-xl bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 font-bold text-xs transition cursor-pointer flex items-center gap-1.5 shrink-0"
                        title="Imposta i minuti calcolando automaticamente la somma dei moduli già caricati nel corso">
                    <span>⚡ Calcola Durata dai Moduli</span>
                    <span class="font-mono text-[11px] bg-white text-indigo-800 px-1.5 py-0.5 rounded border border-indigo-200 font-extrabold">
                        {{ $calcTotalMin }} min ({{ round($calcTotalMin / 60, 2) }}h)
                    </span>
                </button>
            @endif
        </div>

        <form method="POST" action="{{ route('admin.courses.update', $course) }}" enctype="multipart/form-data" class="space-y-5 text-xs">
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

            <!-- Badge / Immagine di Copertina del Corso with Live Preview -->
            <div x-data="{
                badgeUrl: '{{ $course->badge_image_url }}',
                previewUrl: '{{ $course->badge_image_url }}',
                fileChosen(e) {
                    const f = e.target.files[0];
                    if (f) {
                        this.previewUrl = URL.createObjectURL(f);
                    }
                },
                updateFromUrl(val) {
                    if (val && val.trim().length > 0) {
                        this.previewUrl = val.trim();
                    }
                }
            }" class="p-4 rounded-2xl bg-slate-50 border border-slate-200 space-y-3">
                <div class="flex items-center justify-between">
                    <div>
                        <strong class="text-slate-900 text-xs block">Immagine / Badge di Riferimento del Corso</strong>
                        <span class="text-[11px] text-slate-500">Visualizzato in evidenza nelle card dei corsi e nella scheda formativa.</span>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-4">
                    <div class="w-16 h-16 min-w-[64px] min-h-[64px] max-w-[64px] max-h-[64px] rounded-2xl bg-white border border-slate-200 overflow-hidden flex items-center justify-center p-1.5 shadow-xs shrink-0">
                        <template x-if="previewUrl">
                            <img :src="previewUrl" alt="Badge" class="w-full h-full max-w-full max-h-full object-contain drop-shadow-2xs">
                        </template>
                        <template x-if="!previewUrl">
                            <div class="w-full h-full rounded-xl bg-indigo-50/70 border border-indigo-100 flex flex-col items-center justify-center text-indigo-600">
                                <span class="text-2xl leading-none">🎓</span>
                                <span class="text-[9px] font-black uppercase text-indigo-400 mt-0.5">FAD</span>
                            </div>
                        </template>
                    </div>

                    <div class="flex-1 min-w-[200px] space-y-2">
                        <div class="flex items-center gap-3">
                            <label class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-white hover:bg-slate-100 text-slate-700 border border-slate-300 font-bold text-xs cursor-pointer shadow-2xs transition">
                                <span>📁 Carica File Immagine...</span>
                                <input type="file" name="badge_image_file" @change="fileChosen" accept="image/png,image/jpeg,image/svg+xml,image/webp" class="hidden">
                            </label>
                            @if($course->badge_image_url)
                                <label class="inline-flex items-center gap-1.5 text-xs text-red-600 font-bold cursor-pointer" @click="previewUrl = null">
                                    <input type="checkbox" name="remove_badge_image" value="1" class="rounded text-red-600">
                                    <span>Rimuovi badge</span>
                                </label>
                            @endif
                        </div>
                        <input type="text" name="badge_image_url" x-model="badgeUrl" @input="updateFromUrl($event.target.value)" placeholder="Oppure incolla URL immagine: https://.../badge.png" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-1.5 text-xs">
                    </div>
                </div>
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

            <!-- Duration & Sync Hours Section -->
            <div class="bg-slate-50 p-5 rounded-2xl border border-slate-200 space-y-4">
                @if($hasZoomModules)
                    <!-- Active Zoom Modules Detected Banner -->
                    <input type="hidden" name="enable_sync_hours" value="1">
                    <div class="flex items-start gap-3.5 p-4 rounded-xl bg-sky-50/80 border border-sky-200 text-sky-950">
                        <span class="text-2xl leading-none mt-0.5">📹</span>
                        <div class="flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <strong class="text-sm font-black text-sky-950">Ore Sincrone Aula Zoom Collegate ai Moduli</strong>
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black bg-sky-600 text-white uppercase tracking-wider">
                                    {{ $course->zoom_modules_count }} {{ $course->zoom_modules_count === 1 ? 'Modulo Zoom' : 'Moduli Zoom' }} Rilevati
                                </span>
                            </div>
                            <p class="text-xs text-sky-800 mt-1 leading-relaxed">
                                Le ore sincrone (<strong>{{ $calcSyncMin }} min / {{ round($calcSyncMin / 60, 2) }} ore</strong>) corrispondono esattamente alla somma delle lezioni Zoom programmate per questo corso. 
                                La frequenza e la presenza dei corsisti vengono certificate in tempo reale dai server Zoom senza necessità di importare file esterni.
                            </p>
                        </div>
                    </div>
                @else
                    <!-- No Zoom Modules: Legacy Manual CSV Mode Toggle -->
                    <input type="hidden" name="enable_sync_hours" :value="enableSync ? '1' : '0'">
                    <label class="flex items-center justify-between gap-4 cursor-pointer pb-3 border-b border-slate-200">
                        <div>
                            <div class="flex items-center gap-2">
                                <strong class="text-slate-900 text-sm block">Ore Sincrone Esterne Manuali (CSV / Webinar)</strong>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-200 text-slate-700">Nessun modulo Zoom</span>
                            </div>
                            <span class="text-xs text-slate-500">
                                Disattivando questa opzione, il corso è <strong>100% Piattaforma FAD Asincrona</strong>: il monte ore e l'avanzamento vengono calcolati solo sui moduli video della piattaforma. Attivala solo se svolgi webinar su altre piattaforme e caricherai le presenze tramite file CSV.
                            </span>
                        </div>
                        <input type="checkbox" x-model="enableSync" class="w-5 h-5 rounded text-indigo-600 shrink-0">
                    </label>
                @endif

                <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                    <!-- Zoom Sync Hours / Minutes -->
                    <div x-show="hasZoomModules || enableSync" class="p-3.5 rounded-xl bg-white border border-slate-200 space-y-2">
                        <div class="flex items-center justify-between">
                            <label class="block font-bold text-slate-800 text-xs">Aula Zoom (Sincrono)</label>
                            <span class="text-[11px] font-mono text-sky-700 font-bold" x-text="syncHours + ' ore'"></span>
                        </div>
                        @if($hasZoomModules)
                            <div>
                                <label class="block text-[10px] uppercase font-bold text-slate-400 mb-0.5">Minuti Sincronizzati</label>
                                <div class="relative">
                                    <input type="number" readonly :value="syncMin"
                                           class="w-full rounded-lg border border-sky-200 bg-sky-50/60 px-3 py-1.5 text-xs font-bold font-mono text-sky-900 pr-10">
                                    <span class="absolute right-3 top-2 text-[10px] text-sky-500 font-bold">min</span>
                                </div>
                            </div>
                            <div>
                                <label class="block text-[10px] uppercase font-bold text-slate-400 mb-0.5">Ore Decimali Corso</label>
                                <div class="relative">
                                    <input type="number" step="any" min="0" name="sync_required_hours" :value="syncHours" readonly
                                           class="w-full rounded-lg border border-sky-200 bg-sky-50/60 px-3 py-1 text-xs font-mono text-sky-800 pr-8">
                                    <span class="absolute right-3 top-1.5 text-[10px] text-sky-500 font-bold">h</span>
                                </div>
                            </div>
                        @else
                            <div>
                                <label class="block text-[10px] uppercase font-bold text-slate-400 mb-0.5">Minuti Diretti</label>
                                <div class="relative">
                                    <input type="number" min="0" step="1" x-model.number="syncMin" @input="onSyncMinInput"
                                           placeholder="Es. 1200"
                                           class="w-full rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-bold font-mono text-slate-900 pr-10">
                                    <span class="absolute right-3 top-2 text-[10px] text-slate-400 font-bold">min</span>
                                </div>
                            </div>
                            <div>
                                <label class="block text-[10px] uppercase font-bold text-slate-400 mb-0.5">Oppure Ore Decimali</label>
                                <div class="relative">
                                    <input type="number" step="any" min="0" name="sync_required_hours" x-model.number="syncHours" @input="onSyncHoursInput"
                                           class="w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-mono text-slate-600 pr-8">
                                    <span class="absolute right-3 top-1.5 text-[10px] text-slate-400">h</span>
                                </div>
                            </div>
                        @endif
                    </div>

                    <!-- Platform FAD Hours / Minutes -->
                    <div class="p-3.5 rounded-xl bg-white border border-slate-200 space-y-2">
                        <div class="flex items-center justify-between">
                            <label class="block font-bold text-slate-800 text-xs">Piattaforma FAD (Asincrono)</label>
                            <span class="text-[11px] font-mono text-indigo-700 font-bold" x-text="asyncHours + ' ore'"></span>
                        </div>
                        <div>
                            <label class="block text-[10px] uppercase font-bold text-slate-400 mb-0.5">Minuti Diretti *</label>
                            <div class="relative">
                                <input type="number" min="1" step="1" x-model.number="asyncMin" @input="onAsyncMinInput" required
                                       placeholder="Es. 60 o 300"
                                       class="w-full rounded-lg border border-indigo-300 bg-white px-3 py-1.5 text-xs font-bold font-mono text-indigo-900 pr-10">
                                <span class="absolute right-3 top-2 text-[10px] text-indigo-400 font-bold">min</span>
                            </div>
                        </div>
                        <div>
                            <label class="block text-[10px] uppercase font-bold text-slate-400 mb-0.5">Oppure Ore Decimali</label>
                            <div class="relative">
                                <input type="number" step="any" min="0.01" name="async_required_hours" x-model.number="asyncHours" @input="onAsyncHoursInput" required
                                       class="w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-mono text-slate-600 pr-8">
                                <span class="absolute right-3 top-1.5 text-[10px] text-slate-400">h</span>
                            </div>
                        </div>
                    </div>

                    <!-- Total Hours / Minutes -->
                    <div class="p-3.5 rounded-xl bg-slate-100/90 border border-slate-200 space-y-2">
                        <div class="flex items-center justify-between">
                            <label class="block font-bold text-slate-800 text-xs">Monte Ore Totale</label>
                            <span class="text-[11px] font-mono text-emerald-700 font-bold" x-text="totalHoursFormatted + ' ore'"></span>
                        </div>
                        <div>
                            <label class="block text-[10px] uppercase font-bold text-slate-400 mb-0.5">Minuti Totali</label>
                            <div class="relative">
                                <input type="text" readonly :value="totalMinutes + ' min'"
                                       class="w-full rounded-lg border border-slate-200 bg-slate-200/60 px-3 py-1.5 text-xs font-black font-mono text-slate-900">
                            </div>
                        </div>
                        <div>
                            <label class="block text-[10px] uppercase font-bold text-slate-400 mb-0.5">Ore Decimali Corso</label>
                            <div class="relative">
                                <input type="number" step="any" min="0.01" name="total_hours" :value="totalHoursFormatted" readonly
                                       class="w-full rounded-lg border border-slate-200 bg-slate-200/60 px-3 py-1 text-xs font-mono text-slate-700 pr-8">
                                <span class="absolute right-3 top-1.5 text-[10px] text-slate-400">h</span>
                            </div>
                        </div>
                    </div>

                    <!-- % Minima -->
                    <div class="p-3.5 rounded-xl bg-white border border-slate-200 flex flex-col justify-between">
                        <div>
                            <label class="block font-bold text-slate-800 text-xs mb-1">% Minima Attestato *</label>
                            <span class="text-[11px] text-slate-500 block mb-2">Standard Linee Guida: 80%</span>
                        </div>
                        <div>
                            <div class="relative">
                                <input type="number" name="min_attendance_percent" value="{{ $course->min_attendance_percent }}" min="1" max="100" required
                                       class="w-full rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-bold font-mono text-slate-900 pr-8">
                                <span class="absolute right-3 top-2 text-[10px] text-slate-400 font-bold">%</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 pt-1">
                <label class="flex items-center gap-2.5 p-3.5 rounded-xl border border-slate-200 bg-slate-50 cursor-pointer">
                    <input type="checkbox" name="strict_tab_pause" value="1" {{ $course->strict_tab_pause ? 'checked' : '' }} class="rounded text-indigo-600">
                    <span class="text-xs"><strong>Auto-Pausa Scheda</strong> (ferma se cambia tab)</span>
                </label>
                <label class="flex items-center gap-2.5 p-3.5 rounded-xl border border-slate-200 bg-slate-50 cursor-pointer">
                    <input type="checkbox" name="allow_self_enrollment" value="1" {{ $course->allow_self_enrollment ? 'checked' : '' }} class="rounded text-indigo-600">
                    <span class="text-xs"><strong>Sblocco con Codice</strong></span>
                </label>
                <label class="flex items-center gap-2.5 p-3.5 rounded-xl border border-slate-200 bg-slate-50 cursor-pointer">
                    <input type="checkbox" name="allow_student_certificate_download" value="1" {{ $course->allow_student_certificate_download ? 'checked' : '' }} class="rounded text-indigo-600">
                    <div class="text-xs">
                        <strong>Download Attestato Corsista</strong>
                        <div class="text-[10px] text-slate-500">Se spento, no download corsista e no invio email</div>
                    </div>
                </label>
                <div>
                    <label class="block font-bold text-slate-700 mb-1 text-xs">Popup Presenza (min, 0=OFF)</label>
                    <input type="number" name="presence_check_interval_minutes" value="{{ $course->presence_check_interval_minutes }}" min="0" max="120" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-xs">
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
                        <label class="block font-bold text-slate-700 mb-1">Modalità Sorgente / Modulo *</label>
                        <select name="video_source_type" x-model="sourceType" class="w-full rounded-xl border border-slate-300 px-3 py-2">
                            <option value="vimeo">🎬 Vimeo (Player Ufficiale / Video Privato)</option>
                            <option value="youtube">🛡 YouTube Mascherato (No-Logo)</option>
                            <option value="url">URL Diretto Video (.mp4 / CDN)</option>
                            <option value="upload">Upload File .MP4 dal PC</option>
                            @if(($settings['zoom_module_enabled'] ?? '0') === '1')
                                <option value="zoom">🎥 Lezione Sincrona Live (Zoom)</option>
                            @endif
                        </select>
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Durata Certificata (minuti) *</label>
                        <input type="number" name="duration_minutes" value="60" min="1" required class="w-full rounded-xl border border-slate-300 px-3 py-2 font-bold">
                    </div>
                </div>

                <div x-show="sourceType === 'url' || sourceType === 'youtube' || sourceType === 'vimeo'">
                    <label class="block font-bold text-slate-700 mb-1" x-text="sourceType === 'vimeo' ? 'Link o ID Video Vimeo (es. https://vimeo.com/76979871)' : (sourceType === 'youtube' ? 'Link o ID Video YouTube' : 'URL del file Video (.mp4)')"></label>
                    <input type="text" name="video_url" :disabled="sourceType === 'zoom'" :value="sourceType === 'vimeo' ? 'https://vimeo.com/76979871' : 'https://youtu.be/M7lc1UVf-VE'" class="w-full rounded-xl border border-slate-300 px-3 py-2 font-mono">
                </div>

                <div x-show="sourceType === 'upload'">
                    <label class="block font-bold text-slate-700 mb-1">Seleziona File Video .MP4</label>
                    <input type="file" name="video_file" accept="video/mp4,video/webm" class="w-full rounded-xl border border-slate-300 px-3 py-2">
                </div>

                <div x-show="sourceType === 'zoom'" class="p-4 rounded-xl bg-sky-50/70 border border-sky-200 space-y-3">
                    <div class="font-extrabold text-sky-900">🎥 Configurazione Aula Virtuale Sincrona Zoom</div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Meeting ID Zoom *</label>
                            <input type="text" name="zoom_meeting_id" placeholder="Es. 812 3456 7890" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 font-mono">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Passcode (opzionale)</label>
                            <input type="text" name="zoom_passcode" placeholder="Es. 123456" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 font-mono">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Inizio Lezione</label>
                            <input type="datetime-local" name="zoom_scheduled_at" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Fine Lezione (opz.)</label>
                            <input type="datetime-local" name="zoom_scheduled_end_at" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2">
                        </div>
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Link Stanza Zoom (opzionale se usi API S2S)</label>
                        <input type="text" name="zoom_join_url" placeholder="https://zoom.us/j/81234567890?pwd=..." class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 font-mono">
                    </div>
                </div>

                <div x-show="sourceType !== 'zoom'" class="p-4 rounded-xl bg-slate-50 border border-slate-200/90 space-y-3">
                    <div class="flex items-center justify-between gap-2">
                        <div class="flex items-center gap-1.5 text-xs font-extrabold text-slate-800">
                            <span>📅 Disponibilità Programmata Modulo</span>
                        </div>
                        <span class="text-[10px] text-slate-500 font-semibold bg-white border border-slate-200 px-2 py-0.5 rounded-md">
                            Opzionale (vuoto = 24/7)
                        </span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                        <div>
                            <label class="block font-bold text-slate-700 text-xs mb-1">
                                Data e Ora Inizio Sblocco
                            </label>
                            <input type="datetime-local" name="available_at"
                                   class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs text-slate-800 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                            <span class="block text-[10px] text-slate-400 mt-1 leading-tight">Accesso bloccato prima di questo orario.</span>
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 text-xs mb-1">
                                Data e Ora Fine Scadenza (opz.)
                            </label>
                            <input type="datetime-local" name="available_until_at"
                                   class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs text-slate-800 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                            <span class="block text-[10px] text-slate-400 mt-1 leading-tight">Termine ultimo oltre cui non è più avviabile.</span>
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Sottotitoli / Accessibilità (URL .vtt o note per non udenti)</label>
                    <input type="text" name="subtitles_url" placeholder="https://.../subtitles.vtt (opzionale, per corsisti non udenti)" class="w-full rounded-xl border border-slate-300 px-3 py-2">
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
                        <label class="block font-bold text-slate-700 mb-1">Modalità Sorgente / Modulo *</label>
                        <select name="video_source_type" x-model="editMod.video_source_type" class="w-full rounded-xl border border-slate-300 px-3 py-2">
                            <option value="vimeo">🎬 Vimeo (Player Ufficiale / Video Privato)</option>
                            <option value="youtube">🛡 YouTube Mascherato (No-Logo)</option>
                            <option value="url">URL Diretto Video (.mp4 / CDN)</option>
                            <option value="upload">Upload File .MP4 dal PC</option>
                            @if(($settings['zoom_module_enabled'] ?? '0') === '1')
                                <option value="zoom">🎥 Lezione Sincrona Live (Zoom)</option>
                            @endif
                        </select>
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Durata Certificata (minuti) *</label>
                        <input type="number" name="duration_minutes" x-model="editMod.duration_minutes" min="1" required class="w-full rounded-xl border border-slate-300 px-3 py-2 font-bold">
                    </div>
                </div>

                <div x-show="editMod.video_source_type === 'url' || editMod.video_source_type === 'youtube' || editMod.video_source_type === 'vimeo'">
                    <label class="block font-bold text-slate-700 mb-1" x-text="editMod.video_source_type === 'vimeo' ? 'Link o ID Video Vimeo (es. https://vimeo.com/76979871)' : (editMod.video_source_type === 'youtube' ? 'Link YouTube' : 'URL del file Video (.mp4)')"></label>
                    <input type="text" name="video_url" :disabled="editMod.video_source_type === 'zoom'" x-model="editMod.video_url" class="w-full rounded-xl border border-slate-300 px-3 py-2 font-mono">
                </div>

                <div x-show="editMod.video_source_type === 'upload'">
                    <label class="block font-bold text-slate-700 mb-1">Sostituisci File Video .MP4 (opzionale)</label>
                    <input type="file" name="video_file" accept="video/mp4,video/webm" class="w-full rounded-xl border border-slate-300 px-3 py-2">
                </div>

                <div x-show="editMod.video_source_type === 'zoom'" class="p-4 rounded-xl bg-sky-50/70 border border-sky-200 space-y-3">
                    <div class="font-extrabold text-sky-900">🎥 Configurazione Aula Virtuale Sincrona Zoom</div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Meeting ID Zoom *</label>
                            <input type="text" name="zoom_meeting_id" x-model="editMod.zoom_meeting_id" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 font-mono">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Passcode (opzionale)</label>
                            <input type="text" name="zoom_passcode" x-model="editMod.zoom_passcode" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 font-mono">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Inizio Lezione</label>
                            <input type="datetime-local" name="zoom_scheduled_at" x-model="editMod.zoom_scheduled_at" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Fine Lezione (opz.)</label>
                            <input type="datetime-local" name="zoom_scheduled_end_at" x-model="editMod.zoom_scheduled_end_at" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2">
                        </div>
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Link Stanza Zoom (opzionale se usi API S2S)</label>
                        <input type="text" name="zoom_join_url" x-model="editMod.zoom_join_url" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 font-mono">
                    </div>
                </div>

                <div x-show="editMod.video_source_type !== 'zoom'" class="p-4 rounded-xl bg-slate-50 border border-slate-200/90 space-y-3">
                    <div class="flex items-center justify-between gap-2">
                        <div class="flex items-center gap-1.5 text-xs font-extrabold text-slate-800">
                            <span>📅 Disponibilità Programmata Modulo</span>
                        </div>
                        <span class="text-[10px] text-slate-500 font-semibold bg-white border border-slate-200 px-2 py-0.5 rounded-md">
                            Opzionale (vuoto = 24/7)
                        </span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                        <div>
                            <label class="block font-bold text-slate-700 text-xs mb-1">
                                Data e Ora Inizio Sblocco
                            </label>
                            <input type="datetime-local" name="available_at" x-model="editMod.available_at"
                                   class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs text-slate-800 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                            <span class="block text-[10px] text-slate-400 mt-1 leading-tight">Accesso bloccato prima di questo orario.</span>
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 text-xs mb-1">
                                Data e Ora Fine Scadenza (opz.)
                            </label>
                            <input type="datetime-local" name="available_until_at" x-model="editMod.available_until_at"
                                   class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs text-slate-800 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                            <span class="block text-[10px] text-slate-400 mt-1 leading-tight">Termine ultimo oltre cui non è più avviabile.</span>
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Sottotitoli / Accessibilità (URL .vtt o note per non udenti)</label>
                    <input type="text" name="subtitles_url" x-model="editMod.subtitles_url" placeholder="https://.../subtitles.vtt (opzionale, per corsisti non udenti)" class="w-full rounded-xl border border-slate-300 px-3 py-2">
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
                @if($course->has_sync_hours && !$zoomAutoMode)
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Ore Sincrone (Zoom) già svolte</label>
                        <input type="number" step="0.5" name="sync_hours_completed" value="0" class="w-full rounded-xl border border-slate-300 px-3 py-2">
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

    <!-- MODAL: Synchronize / Import Meeting & Occurrences from Zoom API -->
    <div x-cloak x-show="syncZoomModal" class="fixed inset-0 z-50 bg-slate-950/70 backdrop-blur-xs flex items-center justify-center p-4">
        <div @click.away="syncZoomModal = false" class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-slate-200">
            <div class="flex items-center justify-between border-b border-slate-200 pb-3 mb-4">
                <div class="flex items-center gap-2">
                    <span class="text-xl">🎥</span>
                    <h3 class="text-base font-extrabold text-slate-900">Sincronizzazione Automatica da Zoom</h3>
                </div>
                <button @click="syncZoomModal = false" class="text-slate-400 hover:text-slate-700 text-xl font-bold cursor-pointer">&times;</button>
            </div>

            <p class="text-xs text-slate-500 mb-4">
                Inserisci l'<strong>ID Meeting</strong> creato su Zoom (anche con più date/occorrenze ricorrenti).
                La piattaforma importerà automaticamente date, orari, passcode e link aula senza interventi manuali.
            </p>

            <form method="POST" action="{{ route('admin.courses.sync-zoom-meeting', $course) }}" class="space-y-4 text-xs">
                @csrf
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Meeting ID Zoom (es. 849 1234 5678) *</label>
                    <input type="text" name="zoom_meeting_id" required placeholder="Inserisci l'ID Meeting Zoom..."
                           class="w-full rounded-xl border border-slate-300 p-3 font-mono text-xs font-bold uppercase">
                </div>

                <div class="p-3 rounded-xl bg-sky-50 border border-sky-200 text-sky-950 text-[11px] space-y-1">
                    <div class="font-bold flex items-center gap-1.5">
                        <span>ℹ️ Come funziona per le riunioni ricorrenti:</span>
                    </div>
                    <ul class="list-disc pl-4 space-y-0.5 text-sky-800">
                        <li>Se la riunione Zoom ha più date programmate (Occurrences), verranno sincronizzate tutte con il rispettivo calendario.</li>
                        <li>Il monte ore complessivo del corso verrà aggiornato automaticamente.</li>
                    </ul>
                </div>

                <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                    <button type="button" @click="syncZoomModal = false" class="px-4 py-2 rounded-xl border border-slate-300 font-semibold cursor-pointer">Annulla</button>
                    <button type="submit" class="px-5 py-2 rounded-xl bg-sky-600 hover:bg-sky-500 text-white font-bold cursor-pointer flex items-center gap-1.5 shadow-2xs">
                        <span>🚀 Avvia Importazione Automatica</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const sortableContainer = document.getElementById('sortable-modules');
    if (!sortableContainer || typeof Sortable === 'undefined') return;

    new Sortable(sortableContainer, {
        handle: '.drag-handle',
        animation: 180,
        ghostClass: 'bg-indigo-50/70',
        chosenClass: 'ring-2',
        onEnd: function () {
            const ids = Array.from(sortableContainer.querySelectorAll('.module-card'))
                .map(el => el.dataset.moduleId);

            // Renumber badges immediately
            sortableContainer.querySelectorAll('.module-order-badge').forEach((badge, idx) => {
                badge.textContent = 'MODULO ' + (idx + 1);
            });

            // Show toast
            const toast = document.getElementById('reorder-toast');
            if (toast) {
                toast.classList.remove('hidden');
                toast.classList.add('inline-flex');
            }

            fetch('{{ route('admin.courses.modules.reorder', $course) }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ module_ids: ids })
            })
            .then(res => res.json())
            .then(data => {
                setTimeout(() => {
                    if (toast) {
                        toast.classList.add('hidden');
                        toast.classList.remove('inline-flex');
                    }
                }, 2500);
            })
            .catch(err => {
                console.error('Errore riordinamento moduli:', err);
            });
        }
    });
});
</script>
@endsection
