@extends('layouts.app')

@section('title', 'I Miei Corsi — ' . ($settings['platform_name'] ?? 'FormaFAD LMS'))

@section('content')
<div x-data="{ studentTab: @js(request('tab') === 'certificates' ? 'certificates' : 'courses') }"
     class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8 space-y-6">

    <!-- Compact Student Welcome Header -->
    <div class="bg-slate-900 text-white rounded-2xl p-6 shadow-xs border border-slate-800 flex flex-col lg:flex-row items-start lg:items-center justify-between gap-5">
        <div>
            <div class="inline-flex items-center gap-2 px-2.5 py-0.5 rounded-full bg-emerald-500/20 text-emerald-300 text-[11px] font-bold border border-emerald-500/30 mb-2">
                <span>Area Studio Personale • Fruizione H24</span>
            </div>
            <h1 class="text-2xl font-extrabold">Ciao, {{ $user->full_name }}</h1>
            <p class="text-slate-300 text-xs mt-1">
                CF: <strong class="font-mono text-white">{{ $user->fiscal_code ?: 'N/D' }}</strong>
                @if($user->national_list_number)
                    • Elenco Nazionale: <strong class="text-indigo-300">{{ $user->national_list_number }}</strong>
                @endif
            </p>
        </div>

        <div class="grid grid-cols-3 gap-2.5 w-full lg:w-auto text-center">
            <div class="bg-slate-800 border border-slate-700 rounded-xl px-4 py-2.5">
                <div class="text-lg font-extrabold text-white">{{ $enrollments->count() }}</div>
                <div class="text-[11px] text-slate-400">Corsi Attivi</div>
            </div>
            <div class="bg-slate-800 border border-slate-700 rounded-xl px-4 py-2.5">
                <div class="text-lg font-extrabold text-indigo-300">{{ number_format($enrollments->sum('total_hours_completed'), 1, ',', '') }}h</div>
                <div class="text-[11px] text-slate-400">Ore Maturate</div>
            </div>
            <div class="bg-slate-800 border border-slate-700 rounded-xl px-4 py-2.5">
                <div class="text-lg font-extrabold text-emerald-400">{{ $enrollments->where('is_eligible_for_certificate', true)->count() }}</div>
                <div class="text-[11px] text-slate-400">Attestati Pronti</div>
            </div>
        </div>
    </div>

    <!-- Clean View Switcher Tabs -->
    <div class="flex items-center justify-between border-b border-slate-200">
        <div class="flex gap-2">
            <button type="button" @click="studentTab = 'courses'"
                    :class="studentTab === 'courses' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-slate-500 hover:text-slate-800'"
                    class="px-4 py-3 border-b-2 text-xs font-extrabold transition cursor-pointer flex items-center gap-2">
                <span>📚 I Miei Corsi</span>
                <span class="px-2 py-0.5 rounded-full bg-slate-100 text-slate-700 text-[10px]">{{ $enrollments->count() }}</span>
            </button>
            <button type="button" @click="studentTab = 'certificates'"
                    :class="studentTab === 'certificates' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-slate-500 hover:text-slate-800'"
                    class="px-4 py-3 border-b-2 text-xs font-extrabold transition cursor-pointer flex items-center gap-2">
                <span>🏅 Attestati & Storico Presenze</span>
            </button>
        </div>
    </div>

    <!-- TAB 1: I MIEI CORSI (Clean Streaming Cards) -->
    <div x-show="studentTab === 'courses'" class="space-y-6">
        @if($enrollments->isEmpty())
            <div class="bg-white rounded-2xl border border-slate-200 p-8 text-center">
                <p class="text-sm font-semibold text-slate-700">Non risulti ancora iscritto a nessun corso.</p>
                <p class="text-xs text-slate-500 mt-1">Puoi iscriverti a uno dei corsi attivi nel catalogo sottostante.</p>
            </div>
        @else
            <div class="grid grid-cols-1 gap-5">
                @foreach($enrollments as $enrollment)
                    @php
                        $course = $enrollment->course;
                        $modules = $course->modules;
                        $completedModulesCount = $modules->filter(fn($m) => $progressByModule[$m->id]->is_completed ?? false)->count();
                    @endphp
                    <div class="bg-white rounded-2xl border border-slate-200/90 shadow-2xs p-6 transition hover:border-slate-300">
                        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-5">
                            <div class="space-y-1.5">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="px-2.5 py-0.5 rounded-md bg-slate-900 text-white text-[11px] font-mono font-bold">{{ $course->code }}</span>
                                    <span class="px-2.5 py-0.5 rounded-md bg-indigo-50 text-indigo-700 text-[11px] font-bold border border-indigo-200 uppercase">
                                        {{ ucfirst($course->course_type) }}
                                    </span>
                                    @if($enrollment->is_eligible_for_certificate)
                                        <span class="px-2.5 py-0.5 rounded-full bg-emerald-100 text-emerald-800 text-[11px] font-bold">
                                            ✓ Idoneo all'Attestato
                                        </span>
                                    @endif
                                </div>
                                <h2 class="text-xl font-extrabold text-slate-900">{{ $course->title }}</h2>
                                <p class="text-xs text-slate-500">
                                    {{ $completedModulesCount }} di {{ $modules->count() }} moduli video completati • Totale maturato: <strong>{{ number_format($enrollment->total_hours_completed, 1, ',', '') }}h / {{ number_format($course->total_hours, 0) }}h</strong> (Zoom {{ number_format($enrollment->sync_hours_completed, 1, ',', '') }}h + FAD {{ number_format($enrollment->async_hours_completed, 1, ',', '') }}h)
                                </p>
                            </div>

                            <!-- Clean Progress & Primary Action -->
                            <div class="flex flex-col sm:flex-row sm:items-center gap-4 shrink-0">
                                <div class="w-full sm:w-44">
                                    <div class="flex items-center justify-between text-xs font-bold mb-1">
                                        <span class="text-slate-500">Avanzamento</span>
                                        <span class="{{ $enrollment->is_eligible_for_certificate ? 'text-emerald-600' : 'text-indigo-600' }}">{{ $enrollment->attendance_percent }}%</span>
                                    </div>
                                    <div class="w-full h-2.5 bg-slate-100 rounded-full overflow-hidden">
                                        <div class="h-full rounded-full transition-all duration-500 {{ $enrollment->is_eligible_for_certificate ? 'bg-emerald-500' : 'bg-indigo-600' }}"
                                             style="width: {{ min(100, $enrollment->attendance_percent) }}%"></div>
                                    </div>
                                    <div class="text-[10px] text-slate-400 mt-1">Soglia attestato: {{ $course->min_attendance_percent }}%</div>
                                </div>

                                <div class="flex flex-wrap items-center gap-2">
                                    @if($enrollment->is_eligible_for_certificate)
                                        <a href="{{ route('student.enrollments.certificate', $enrollment) }}"
                                           class="inline-flex items-center gap-1.5 px-3.5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold shadow-2xs transition">
                                            <span>🏅 Scarica Attestato</span>
                                        </a>
                                    @endif

                                    <a href="{{ route('student.courses.show', $course) }}"
                                       class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold shadow-xs transition">
                                        <span>▶ Riprendi Lezione</span>
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Compact Modules Strip -->
                        <div class="mt-5 pt-4 border-t border-slate-100">
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-2.5">
                                @foreach($modules as $mod)
                                    @php
                                        $mp = $progressByModule[$mod->id] ?? null;
                                        $pct = $mp ? $mp->completion_percent : 0;
                                        $done = $mp ? $mp->is_completed : false;
                                    @endphp
                                    <a href="{{ route('student.courses.module', [$course, $mod]) }}"
                                       class="group rounded-xl p-3 border transition flex flex-col justify-between {{ $done ? 'bg-emerald-50/40 border-emerald-200 hover:border-emerald-400' : 'bg-slate-50/70 border-slate-200 hover:border-indigo-400 hover:bg-white' }}">
                                        <div>
                                            <div class="flex items-center justify-between gap-2 text-[10px] font-bold mb-1">
                                                <span class="text-slate-500">MOD. {{ $mod->sort_order }}</span>
                                                @if($done)
                                                    <span class="text-emerald-700">✓ 100%</span>
                                                @elseif($pct > 0)
                                                    <span class="text-indigo-600">{{ $pct }}%</span>
                                                @else
                                                    <span class="text-slate-400">{{ $mod->formatted_duration }}</span>
                                                @endif
                                            </div>
                                            <div class="text-xs font-bold text-slate-900 group-hover:text-indigo-600 line-clamp-1">{{ $mod->title }}</div>
                                        </div>
                                        <div class="mt-2 w-full h-1 bg-slate-200 rounded-full overflow-hidden">
                                            <div class="h-full {{ $done ? 'bg-emerald-500' : 'bg-indigo-600' }}" style="width: {{ $pct }}%"></div>
                                        </div>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        <!-- Available Courses Catalog (Only if there are un-enrolled courses) -->
        @if($availableCourses->isNotEmpty())
            <div class="bg-white rounded-2xl border border-slate-200/90 p-6 shadow-2xs">
                <h3 class="text-base font-extrabold text-slate-900">Altri Corsi Disponibili a Catalogo</h3>
                <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach($availableCourses as $ac)
                        <div class="p-4 rounded-xl border border-slate-200 flex items-center justify-between gap-4 hover:bg-slate-50 transition">
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="px-2 py-0.5 rounded bg-slate-100 text-slate-800 font-mono text-[11px] font-bold">{{ $ac->code }}</span>
                                    <span class="text-xs text-indigo-600 font-semibold">{{ $ac->total_hours }}h totali ({{ $ac->modules_count }} moduli)</span>
                                </div>
                                <div class="font-bold text-sm text-slate-900 mt-1">{{ $ac->title }}</div>
                            </div>
                            <form method="POST" action="{{ route('student.courses.enroll', $ac) }}" class="shrink-0">
                                @csrf
                                <button type="submit" class="px-3.5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold transition cursor-pointer">
                                    Iscriviti &rarr;
                                </button>
                            </form>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    <!-- TAB 2: ATTESTATI & STORICO PRESENZE (Audit Personale & Certificati PDF) -->
    <div x-show="studentTab === 'certificates'" class="space-y-6">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <!-- Left: Course Certificates & Audit PDF Downloads -->
            <div class="lg:col-span-7 space-y-4">
                <div class="bg-white rounded-2xl border border-slate-200/90 p-6 shadow-2xs space-y-4">
                    <div>
                        <h3 class="text-base font-extrabold text-slate-900">I Tuoi Attestati & Fascicoli Presenze</h3>
                        <p class="text-xs text-slate-500 mt-0.5">Scarica l'Attestato Ufficiale (Allegato 4) o il tuo Fascicolo Audit Personale certificato.</p>
                    </div>

                    <div class="space-y-3">
                        @forelse($enrollments as $enrollment)
                            @php $course = $enrollment->course; @endphp
                            <div class="p-4 rounded-xl border border-slate-200 bg-slate-50/60 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="font-mono font-bold text-xs text-indigo-600">{{ $course->code }}</span>
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $enrollment->is_eligible_for_certificate ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                                            Frequenza: {{ $enrollment->attendance_percent }}% ({{ number_format($enrollment->total_hours_completed, 1, ',', '') }}h / {{ number_format($course->total_hours, 0) }}h)
                                        </span>
                                    </div>
                                    <div class="font-extrabold text-sm text-slate-900 mt-1">{{ $course->title }}</div>
                                    <div class="text-[11px] text-slate-500 mt-0.5">
                                        Ore Zoom: <strong>{{ number_format($enrollment->sync_hours_completed, 1, ',', '') }}h</strong> • Ore Video FAD: <strong>{{ number_format($enrollment->async_hours_completed, 2, ',', '') }}h</strong>
                                    </div>
                                </div>

                                <div class="flex flex-wrap items-center gap-2 shrink-0">
                                    <a href="{{ route('student.enrollments.audit-pdf', $enrollment) }}"
                                       class="px-3 py-2 rounded-xl bg-white hover:bg-slate-100 text-slate-700 border border-slate-300 text-xs font-bold transition">
                                        Scarica il Tuo Fascicolo Audit (PDF)
                                    </a>

                                    @if($enrollment->is_eligible_for_certificate)
                                        <a href="{{ route('student.enrollments.certificate', $enrollment) }}"
                                           class="px-3.5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold transition shadow-2xs">
                                            🏅 Scarica Attestato (Allegato 4)
                                        </a>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <p class="text-xs text-slate-400">Nessuna iscrizione attiva.</p>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- Right: Personal Audit Log -->
            <div class="lg:col-span-5 bg-white rounded-2xl border border-slate-200/90 p-6 shadow-2xs">
                <h3 class="text-base font-extrabold text-slate-900">Il Tuo Registro Audit Personale</h3>
                <p class="text-xs text-slate-500 mt-0.5">Ultime sessioni e salvataggi certificati sul tuo profilo.</p>

                <div class="mt-4 space-y-2.5 max-h-80 overflow-y-auto pr-1">
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

</div>
@endsection
