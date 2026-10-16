@extends('layouts.app')

@section('title', 'Area Corsista — ' . ($settings['platform_name'] ?? 'FormaFAD LMS'))

@section('content')
<div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8 space-y-8">

    <!-- Welcome Banner & Profile Summary -->
    <div class="bg-slate-900 text-white rounded-2xl p-6 sm:p-8 shadow-md border border-slate-800 flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-500/20 text-emerald-300 text-xs font-semibold border border-emerald-500/30 mb-3">
                <span>Posizione Anagrafica Verificata • Fruizione Asincrona H24</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-extrabold">Benvenuto, {{ $user->full_name }}</h1>
            <p class="text-slate-300 text-xs sm:text-sm mt-1">
                Codice Fiscale: <strong class="font-mono text-white">{{ $user->fiscal_code ?: 'N/D' }}</strong>
                @if($user->birth_date)
                    • Nato/a il <strong>{{ $user->birth_date->format('d/m/Y') }}</strong> a <strong>{{ $user->birth_city }} ({{ $user->birth_province }})</strong>
                @endif
                @if($user->national_list_number)
                    • Elenco Nazionale: <strong class="text-indigo-300">{{ $user->national_list_number }}</strong>
                @endif
            </p>
        </div>

        <div class="grid grid-cols-3 gap-3 w-full lg:w-auto text-center">
            <div class="bg-slate-800/90 border border-slate-700 rounded-xl px-4 py-3">
                <div class="text-xl font-extrabold text-white">{{ $enrollments->count() }}</div>
                <div class="text-[11px] text-slate-400">Corsi Iscritti</div>
            </div>
            <div class="bg-slate-800/90 border border-slate-700 rounded-xl px-4 py-3">
                <div class="text-xl font-extrabold text-indigo-400">{{ number_format($enrollments->sum('total_hours_completed'), 1, ',', '') }}h</div>
                <div class="text-[11px] text-slate-400">Ore Totali Maturate</div>
            </div>
            <div class="bg-slate-800/90 border border-slate-700 rounded-xl px-4 py-3">
                <div class="text-xl font-extrabold text-emerald-400">{{ $enrollments->where('is_eligible_for_certificate', true)->count() }}</div>
                <div class="text-[11px] text-slate-400">Attestati Pronti</div>
            </div>
        </div>
    </div>

    <!-- Enrolled Courses Section -->
    <div>
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="text-lg font-extrabold text-slate-900">I Miei Percorsi Formativi Attivi</h2>
                <p class="text-xs text-slate-500">Il sistema somma automaticamente le ore sincrone (Zoom) certificate dalla segreteria e le ore video FAD svolte in piattaforma.</p>
            </div>
        </div>

        @if($enrollments->isEmpty())
            <div class="bg-white rounded-2xl border border-slate-200 p-8 text-center">
                <p class="text-sm font-semibold text-slate-700">Non risulti ancora iscritto a nessun corso.</p>
                <p class="text-xs text-slate-500 mt-1">Puoi iscriverti a uno dei corsi attivi nel catalogo sottostante.</p>
            </div>
        @else
            <div class="space-y-6">
                @foreach($enrollments as $enrollment)
                    @php
                        $course = $enrollment->course;
                        $modules = $course->modules;
                        $completedModulesCount = $modules->filter(fn($m) => $progressByModule[$m->id]->is_completed ?? false)->count();
                    @endphp
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
                        <div class="p-6 sm:p-7">
                            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 border-b border-slate-100 pb-5">
                                <div>
                                    <div class="flex flex-wrap items-center gap-2 mb-2">
                                        <span class="px-2.5 py-0.5 rounded-md bg-slate-900 text-white text-xs font-mono font-bold">{{ $course->code }}</span>
                                        <span class="px-2.5 py-0.5 rounded-md bg-indigo-50 text-indigo-700 text-xs font-semibold border border-indigo-200 uppercase">
                                            Corso di {{ ucfirst($course->course_type) }}
                                        </span>
                                        @if($course->thematic_area)
                                            <span class="text-xs text-slate-500 font-medium">• Ambito: {{ $course->thematic_area }}</span>
                                        @endif
                                    </div>
                                    <h3 class="text-xl font-extrabold text-slate-900">{{ $course->title }}</h3>
                                    @if($course->authorization_details)
                                        <p class="text-xs text-slate-500 mt-0.5">Autorizzazione Ministeriale: <strong>{{ $course->authorization_details }}</strong></p>
                                    @endif
                                </div>

                                <div class="flex flex-wrap items-center gap-3 shrink-0">
                                    @if($enrollment->is_eligible_for_certificate)
                                        <a href="{{ route('student.enrollments.certificate', $enrollment) }}"
                                           class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold shadow-xs transition">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                            <span>Scarica Attestato Ufficiale (Allegato 4 PDF)</span>
                                        </a>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-amber-50 text-amber-800 border border-amber-200 text-xs font-semibold">
                                            <span>Attestato sbloccato al {{ $course->min_attendance_percent }}% ({{ $course->minimum_required_hours }}h min.)</span>
                                        </span>
                                    @endif

                                    <a href="{{ route('student.courses.show', $course) }}"
                                       class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-slate-900 hover:bg-indigo-600 text-white text-xs font-bold shadow-xs transition">
                                        <span>Entra nell'Aula Video FAD</span>
                                        <span>&rarr;</span>
                                    </a>
                                </div>
                            </div>

                            <!-- Unified Attendance Progress Bar (Zoom + FAD) -->
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-5 bg-slate-50 rounded-xl p-4 border border-slate-200/80">
                                <div>
                                    <div class="text-[11px] font-semibold text-slate-500 uppercase">Ore Sincrone (Zoom)</div>
                                    <div class="text-lg font-extrabold text-slate-900 mt-0.5">
                                        {{ number_format($enrollment->sync_hours_completed, 1, ',', '') }}h
                                        <span class="text-xs font-normal text-slate-500">/ {{ number_format($course->sync_required_hours, 0) }}h previste</span>
                                    </div>
                                </div>
                                <div>
                                    <div class="text-[11px] font-semibold text-slate-500 uppercase">Ore Asincrone (Video FAD)</div>
                                    <div class="text-lg font-extrabold text-indigo-600 mt-0.5">
                                        {{ number_format($enrollment->async_hours_completed, 2, ',', '') }}h
                                        <span class="text-xs font-normal text-slate-500">/ {{ number_format($course->async_required_hours, 0) }}h ({{ $completedModulesCount }}/{{ $modules->count() }} moduli)</span>
                                    </div>
                                </div>
                                <div>
                                    <div class="text-[11px] font-semibold text-slate-500 uppercase">Totale Ore Certificate</div>
                                    <div class="text-lg font-extrabold text-slate-900 mt-0.5">
                                        {{ number_format($enrollment->total_hours_completed, 2, ',', '') }}h
                                        <span class="text-xs font-normal text-slate-500">su {{ number_format($course->total_hours, 0) }}h totali</span>
                                    </div>
                                </div>
                                <div>
                                    <div class="flex items-center justify-between text-xs font-bold mb-1">
                                        <span class="text-slate-700">Frequenza Complessiva</span>
                                        <span class="{{ $enrollment->is_eligible_for_certificate ? 'text-emerald-600' : 'text-indigo-600' }}">{{ $enrollment->attendance_percent }}%</span>
                                    </div>
                                    <div class="w-full h-2.5 bg-slate-200 rounded-full overflow-hidden">
                                        <div class="h-full rounded-full transition-all duration-500 {{ $enrollment->is_eligible_for_certificate ? 'bg-emerald-500' : 'bg-indigo-600' }}"
                                             style="width: {{ min(100, $enrollment->attendance_percent) }}%"></div>
                                    </div>
                                    <div class="text-[10px] text-slate-500 mt-1">
                                        Soglia ministeriale: {{ $course->min_attendance_percent }}% ({{ $course->minimum_required_hours }} ore)
                                    </div>
                                </div>
                            </div>

                            <!-- Modules Quick Strip -->
                            <div class="mt-5">
                                <div class="text-xs font-bold text-slate-700 mb-2.5">Moduli Video Asincroni del Corso (fruibili H24 con salvataggio automatico):</div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
                                    @foreach($modules as $mod)
                                        @php
                                            $mp = $progressByModule[$mod->id] ?? null;
                                            $pct = $mp ? $mp->completion_percent : 0;
                                            $done = $mp ? $mp->is_completed : false;
                                        @endphp
                                        <a href="{{ route('student.courses.module', [$course, $mod]) }}"
                                           class="group rounded-xl p-3.5 border transition flex flex-col justify-between {{ $done ? 'bg-emerald-50/50 border-emerald-200 hover:border-emerald-400' : 'bg-white border-slate-200 hover:border-indigo-400' }}">
                                            <div>
                                                <div class="flex items-center justify-between gap-2 text-[11px] font-bold mb-1">
                                                    <span class="text-slate-500">MODULO {{ $mod->sort_order }}</span>
                                                    @if($done)
                                                        <span class="px-1.5 py-0.5 rounded bg-emerald-100 text-emerald-800 text-[10px]">100% Visto</span>
                                                    @elseif($pct > 0)
                                                        <span class="px-1.5 py-0.5 rounded bg-indigo-100 text-indigo-800 text-[10px]">{{ $pct }}%</span>
                                                    @else
                                                        <span class="text-slate-400">{{ $mod->formatted_duration }}</span>
                                                    @endif
                                                </div>
                                                <div class="text-xs font-bold text-slate-900 group-hover:text-indigo-600 line-clamp-2">{{ $mod->title }}</div>
                                            </div>
                                            <div class="mt-3">
                                                <div class="w-full h-1.5 bg-slate-100 rounded-full overflow-hidden">
                                                    <div class="h-full {{ $done ? 'bg-emerald-500' : 'bg-indigo-600' }}" style="width: {{ $pct }}%"></div>
                                                </div>
                                            </div>
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <!-- Available Courses Catalog & Personal Activity Log -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        <!-- Available Courses -->
        <div class="lg:col-span-7 bg-white rounded-2xl border border-slate-200 p-6 shadow-2xs">
            <h3 class="text-base font-extrabold text-slate-900">Altri Corsi Disponibili a Catalogo</h3>
            <p class="text-xs text-slate-500 mt-0.5">Puoi iscriverti autonomamente agli altri percorsi di aggiornamento o specializzazione attivi.</p>

            @if($availableCourses->isEmpty())
                <p class="text-xs text-slate-500 mt-4 py-4 border-t border-slate-100">Sei già iscritto a tutti i corsi attualmente attivi a catalogo.</p>
            @else
                <div class="mt-4 space-y-3">
                    @foreach($availableCourses as $ac)
                        <div class="p-4 rounded-xl border border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-4 hover:bg-slate-50/60 transition">
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="px-2 py-0.5 rounded bg-slate-100 text-slate-800 font-mono text-[11px] font-bold">{{ $ac->code }}</span>
                                    <span class="text-xs text-indigo-600 font-semibold">{{ $ac->total_hours }}h totali ({{ $ac->modules_count }} moduli FAD)</span>
                                </div>
                                <div class="font-bold text-sm text-slate-900 mt-1">{{ $ac->title }}</div>
                                <div class="text-xs text-slate-500">{{ $ac->thematic_area }}</div>
                            </div>
                            <form method="POST" action="{{ route('student.courses.enroll', $ac) }}" class="shrink-0">
                                @csrf
                                <button type="submit" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold transition cursor-pointer">
                                    Iscriviti al Corso &rarr;
                                </button>
                            </form>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- Recent Personal Telemetry Log -->
        <div class="lg:col-span-5 bg-white rounded-2xl border border-slate-200 p-6 shadow-2xs">
            <h3 class="text-base font-extrabold text-slate-900">Il Tuo Registro Attività Recenti</h3>
            <p class="text-xs text-slate-500 mt-0.5">Tracciamento certificato delle tue sessioni di studio in piattaforma.</p>

            <div class="mt-4 space-y-2.5 max-h-72 overflow-y-auto pr-1">
                @forelse($recentLogs as $log)
                    <div class="text-xs border-l-2 border-indigo-500 pl-3 py-1">
                        <div class="font-semibold text-slate-800">{{ $log->description }}</div>
                        <div class="text-[11px] text-slate-400">
                            {{ $log->created_at->format('d/m/Y H:i:s') }} • IP {{ $log->ip_address }}
                        </div>
                    </div>
                @empty
                    <p class="text-xs text-slate-400 py-4">Nessuna attività registrata.</p>
                @endforelse
            </div>
        </div>
    </div>

</div>
@endsection
