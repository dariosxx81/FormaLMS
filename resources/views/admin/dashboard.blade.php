@extends('layouts.app')

@section('title', 'Dashboard Direzionale & Metriche — Pannello Admin')

@section('content')
<div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8 space-y-8">

    <!-- Top Admin Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-indigo-50 border border-indigo-100 text-indigo-700 text-xs font-bold mb-1.5">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                <span>Piattaforma FAD Live & Monitoraggio Presenze</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">Cruscotto Multi-Corso & Dashboard</h1>
            <p class="text-xs text-slate-500 mt-0.5">
                Riepilogo in tempo reale di corsi, corsisti registrati, ore partecipate e avanzamento didattico.
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2.5">
            <a href="{{ route('admin.courses.index') }}" class="px-4 py-2.5 rounded-xl bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 text-xs font-bold transition shadow-2xs flex items-center gap-2">
                <span>📚 Catalogo Corsi</span>
                <span class="px-2 py-0.5 rounded-full bg-slate-100 text-slate-800 text-[10px]">{{ $totalCourses }}</span>
            </a>
            <a href="{{ route('admin.users.index') }}" class="px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold transition shadow-xs flex items-center gap-2">
                <span>👥 Anagrafica Corsisti</span>
                <span class="px-2 py-0.5 rounded-full bg-white/20 text-white text-[10px]">{{ $totalStudents }}</span>
            </a>
        </div>
    </div>

    <!-- 4 High-Impact Stat Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl border border-slate-200/90 p-5 shadow-2xs hover:border-slate-300 transition">
            <div class="flex items-center justify-between">
                <div class="text-xs font-bold text-slate-500">Corsi & Moduli</div>
                <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 border border-indigo-100 flex items-center justify-center text-lg">📚</div>
            </div>
            <div class="text-2xl font-black text-slate-900 mt-2">{{ $totalCourses }}</div>
            <div class="text-[11px] text-slate-500 mt-1">
                <strong>{{ $totalModules }}</strong> moduli didattici caricati
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200/90 p-5 shadow-2xs hover:border-slate-300 transition">
            <div class="flex items-center justify-between">
                <div class="text-xs font-bold text-slate-500">Corsisti Iscritti</div>
                <div class="w-10 h-10 rounded-xl bg-sky-50 text-sky-600 border border-sky-100 flex items-center justify-center text-lg">👥</div>
            </div>
            <div class="text-2xl font-black text-slate-900 mt-2">{{ $totalStudents }}</div>
            <div class="text-[11px] text-slate-500 mt-1">
                <strong>{{ $totalEnrollments }}</strong> iscrizioni totali ai corsi
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200/90 p-5 shadow-2xs hover:border-slate-300 transition">
            <div class="flex items-center justify-between">
                <div class="text-xs font-bold text-slate-500">Ore Partecipate Totali</div>
                <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 border border-amber-100 flex items-center justify-center text-lg">⏱</div>
            </div>
            <div class="text-2xl font-black text-slate-900 mt-2">{{ number_format($totalHoursDelivered, 1, ',', '') }}h</div>
            <div class="text-[11px] text-slate-500 mt-1">
                FAD {{ number_format($totalAsyncHoursWatched, 1, ',', '') }}h • Zoom {{ number_format($totalSyncHoursCompleted, 1, ',', '') }}h
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200/90 p-5 shadow-2xs hover:border-slate-300 transition">
            <div class="flex items-center justify-between">
                <div class="text-xs font-bold text-slate-500">Attestati Sbloccati</div>
                <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 border border-emerald-100 flex items-center justify-center text-lg">🏅</div>
            </div>
            <div class="text-2xl font-black text-emerald-600 mt-2">{{ $eligibleCertificates }}</div>
            <div class="text-[11px] text-slate-500 mt-1">
                su <strong>{{ $totalEnrollments }}</strong> iscrizioni ({{ $totalEnrollments > 0 ? round(($eligibleCertificates / $totalEnrollments) * 100) : 0 }}%)
            </div>
        </div>
    </div>

    <!-- Charts Section: Ore Partecipate, Moduli e Distribuzione Frequenza -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- Main Bar Chart: Ore Partecipate & Iscritti per Corso -->
        <div class="lg:col-span-8 bg-white rounded-2xl border border-slate-200/90 p-6 shadow-2xs space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 pb-3">
                <div>
                    <h2 class="text-base font-extrabold text-slate-900">Partecipazione & Moduli per Corso</h2>
                    <p class="text-xs text-slate-500">Confronto tra numero iscritti e moduli didattici caricati</p>
                </div>
                <div class="flex items-center gap-4 text-xs font-semibold">
                    <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded-md bg-indigo-600"></span> Iscritti</span>
                    <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded-md bg-emerald-500"></span> Moduli Video</span>
                </div>
            </div>

            <div class="h-64 sm:h-72 w-full relative">
                <canvas id="courseBarChart"></canvas>
            </div>
        </div>

        <!-- Donut Chart: Ripartizione Ore & Completamento -->
        <div class="lg:col-span-4 bg-white rounded-2xl border border-slate-200/90 p-6 shadow-2xs space-y-4 flex flex-col justify-between">
            <div class="border-b border-slate-100 pb-3">
                <h2 class="text-base font-extrabold text-slate-900">Ripartizione Ore Formazione</h2>
                <p class="text-xs text-slate-500">Frequenza Piattaforma FAD vs Ore Zoom</p>
            </div>

            <div class="h-48 relative flex items-center justify-center">
                <canvas id="hoursDoughnutChart"></canvas>
            </div>

            <div class="pt-3 border-t border-slate-100 space-y-2 text-xs">
                <div class="flex items-center justify-between">
                    <span class="text-slate-500 inline-flex items-center gap-1.5">
                        <span class="w-2.5 h-2.5 rounded-full bg-indigo-600"></span> Ore Piattaforma FAD
                    </span>
                    <span class="font-extrabold text-slate-900">{{ number_format($totalAsyncHoursWatched, 1, ',', '') }}h</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-slate-500 inline-flex items-center gap-1.5">
                        <span class="w-2.5 h-2.5 rounded-full bg-sky-400"></span> Ore Sincrone Zoom
                    </span>
                    <span class="font-extrabold text-slate-900">{{ number_format($totalSyncHoursCompleted, 1, ',', '') }}h</span>
                </div>
            </div>
        </div>
    </div>

    <!-- 2 Columns: Ultimi Corsi Aggiunti vs Ultimi Utenti Registrati -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- Column 1: Ultimi Corsi Formativi -->
        <div class="bg-white rounded-2xl border border-slate-200/90 p-6 shadow-2xs space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div>
                    <h2 class="text-base font-extrabold text-slate-900">Ultimi Corsi nel Catalogo</h2>
                    <p class="text-xs text-slate-500">Edizioni formative create di recente</p>
                </div>
                <a href="{{ route('admin.courses.index') }}" class="text-xs font-bold text-indigo-600 hover:underline">
                    Vedi Tutti ({{ $totalCourses }}) &rarr;
                </a>
            </div>

            <div class="space-y-3">
                @forelse($recentCourses as $c)
                    <div class="p-3.5 rounded-xl border border-slate-200/80 hover:border-slate-300 bg-slate-50/50 hover:bg-white transition flex items-center justify-between gap-3">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-slate-50 to-slate-100 border border-slate-200/90 overflow-hidden flex items-center justify-center p-1 shadow-2xs shrink-0">
                                @if($c->badge_image_url)
                                    <img src="{{ $c->badge_image_url }}" alt="Badge" class="w-full h-full object-contain drop-shadow-2xs">
                                @else
                                    <div class="w-full h-full rounded-lg bg-indigo-50/70 border border-indigo-100 flex items-center justify-center text-indigo-600 font-bold text-sm">
                                        🎓
                                    </div>
                                @endif
                            </div>
                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <span class="font-mono text-[10px] font-bold text-slate-500">[{{ $c->code }}]</span>
                                    <span class="font-extrabold text-xs text-slate-900 truncate">{{ $c->title }}</span>
                                </div>
                                <div class="text-[11px] text-slate-400 mt-0.5">
                                    {{ $c->modules_count }} moduli • {{ $c->enrollments_count }} iscritti • {{ $c->total_hours }}h totali
                                </div>
                            </div>
                        </div>

                        <a href="{{ route('admin.courses.show', $c) }}" class="px-2.5 py-1.5 rounded-lg bg-white hover:bg-slate-100 border border-slate-200 text-slate-700 font-bold text-xs shrink-0 transition">
                            Apri &rarr;
                        </a>
                    </div>
                @empty
                    <div class="p-6 text-center text-xs text-slate-400">Nessun corso presente.</div>
                @endforelse
            </div>
        </div>

        <!-- Column 2: Ultimi Utenti Registrati -->
        <div class="bg-white rounded-2xl border border-slate-200/90 p-6 shadow-2xs space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div>
                    <h2 class="text-base font-extrabold text-slate-900">Ultimi Utenti Registrati</h2>
                    <p class="text-xs text-slate-500">Nuovi corsisti entrati in piattaforma</p>
                </div>
                <a href="{{ route('admin.users.index') }}" class="text-xs font-bold text-indigo-600 hover:underline">
                    Vedi Anagrafica ({{ $totalStudents }}) &rarr;
                </a>
            </div>

            <div class="space-y-3">
                @forelse($recentStudents as $s)
                    <div class="p-3.5 rounded-xl border border-slate-200/80 hover:border-slate-300 bg-slate-50/50 hover:bg-white transition flex items-center justify-between gap-3">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="w-10 h-10 rounded-xl bg-slate-900 text-white font-extrabold text-xs flex items-center justify-center shrink-0">
                                {{ strtoupper(substr($s->first_name, 0, 1) . substr($s->last_name, 0, 1)) }}
                            </div>
                            <div class="min-w-0">
                                <div class="font-extrabold text-xs text-slate-900 truncate">{{ $s->full_name }}</div>
                                <div class="text-[11px] text-slate-400 truncate">
                                    {{ $s->email }} • <span class="font-mono">{{ $s->fiscal_code ?: 'CF N/D' }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="text-right shrink-0">
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $s->status === 'active' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                                {{ $s->status === 'active' ? 'Attivo' : 'In attesa' }}
                            </span>
                            <div class="text-[10px] text-slate-400 mt-1">
                                {{ $s->enrollments->count() }} {{ $s->enrollments->count() === 1 ? 'corso' : 'corsi' }}
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="p-6 text-center text-xs text-slate-400">Nessun corsista registrato.</div>
                @endforelse
            </div>
        </div>

    </div>

    <!-- Avanzamento Recente Iscritti (Clean Table with Popover Actions) -->
    <div class="bg-white rounded-2xl border border-slate-200/90 shadow-2xs">
        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
            <div>
                <h3 class="text-sm font-extrabold text-slate-900">Avanzamento Recente Iscritti</h3>
                <p class="text-xs text-slate-500">Stato di completamento corsi e rilascio attestati dei corsisti attivi</p>
            </div>
            <a href="{{ route('admin.users.index') }}" class="text-xs font-bold text-indigo-600 hover:underline">
                Vedi anagrafica completa &rarr;
            </a>
        </div>

        <div class="overflow-x-auto min-h-[220px]">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50/70 border-b border-slate-200 text-[11px] text-slate-400 font-bold uppercase">
                        <th class="py-3.5 px-6">Corsista</th>
                        <th class="py-3.5 px-4">Corso</th>
                        <th class="py-3.5 px-4">Ore Svolte</th>
                        <th class="py-3.5 px-4">Frequenza</th>
                        <th class="py-3.5 px-6 text-right">Azioni</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($recentEnrollments as $index => $enr)
                        <tr x-data="{ rowMenu: false }" class="hover:bg-slate-50/60 transition">
                            <td class="py-3.5 px-6">
                                <div class="font-bold text-slate-900">{{ $enr->user->full_name }}</div>
                                <div class="text-[11px] text-slate-400 font-mono">{{ $enr->user->fiscal_code }}</div>
                            </td>
                            <td class="py-3.5 px-4">
                                <a href="{{ route('admin.courses.show', $enr->course) }}" class="font-mono font-bold text-indigo-600 hover:underline">
                                    {{ $enr->course->code }}
                                </a>
                            </td>
                            <td class="py-3.5 px-4 text-slate-600">
                                <strong>{{ $enr->total_hours_completed }}h</strong>
                                @if($enr->course->has_sync_hours)
                                    <span class="text-slate-400 text-[11px]">({{ $enr->sync_hours_completed }}h Zoom + {{ $enr->async_hours_completed }}h FAD)</span>
                                @else
                                    <span class="text-slate-400 text-[11px]">(100% FAD)</span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="px-2.5 py-0.5 rounded-full font-bold text-[11px] {{ $enr->is_eligible_for_certificate ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                                    {{ $enr->attendance_percent }}%
                                </span>
                            </td>
                            <td class="py-3.5 px-6 text-right relative" @click.outside="rowMenu = false">
                                <button type="button" @click="rowMenu = !rowMenu"
                                        class="px-2.5 py-1.5 rounded-lg border border-slate-200 hover:bg-slate-100 text-slate-600 font-bold cursor-pointer transition">
                                    &ctdot;
                                </button>
                                <div x-cloak x-show="rowMenu" x-transition
                                     class="absolute right-6 {{ $loop->last || $loop->iteration > 4 ? 'bottom-full mb-1' : 'top-full mt-1' }} w-52 bg-white rounded-xl border border-slate-200 shadow-2xl p-1.5 z-50 text-left space-y-1">
                                    <a href="{{ route('admin.enrollments.dossier-pdf', $enr) }}" class="block px-3 py-2 rounded-lg hover:bg-slate-100 text-slate-700 font-semibold">
                                        📋 Fascicolo Probante PDF
                                    </a>
                                    <a href="{{ route('student.enrollments.certificate', $enr) }}" class="block px-3 py-2 rounded-lg hover:bg-slate-100 font-semibold {{ $enr->is_eligible_for_certificate ? 'text-emerald-700 font-bold' : 'text-slate-400' }}">
                                        🎓 Attestato PDF
                                    </a>
                                    <a href="{{ route('admin.courses.show', $enr->course) }}" class="block px-3 py-2 rounded-lg hover:bg-slate-100 text-indigo-600 font-semibold">
                                        ⚙️ Scheda Corso
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-8 text-center text-slate-400 text-xs">Nessuna iscrizione registrata di recente.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- Chart.js CDN for Modern Interactive Charts -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const primaryColor = getComputedStyle(document.documentElement).getPropertyValue('--brand-primary').trim() || '#4f46e5';

    // 1. Bar Chart: Partecipazione e Moduli
    const ctxBar = document.getElementById('courseBarChart');
    if (ctxBar) {
        new Chart(ctxBar, {
            type: 'bar',
            data: {
                labels: @json($chartCourseLabels),
                datasets: [
                    {
                        label: 'Numero Iscritti',
                        data: @json($chartCourseEnrollments),
                        backgroundColor: primaryColor,
                        borderRadius: 8,
                        barThickness: 20,
                    },
                    {
                        label: 'Moduli Video',
                        data: @json($chartCourseModules),
                        backgroundColor: '#10b981',
                        borderRadius: 8,
                        barThickness: 20,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1, color: '#94a3b8', font: { size: 11 } },
                        grid: { color: '#f1f5f9' }
                    },
                    x: {
                        ticks: { color: '#64748b', font: { size: 11, weight: 'bold' } },
                        grid: { display: false }
                    }
                }
            }
        });
    }

    // 2. Doughnut Chart: Ripartizione Ore
    const ctxDonut = document.getElementById('hoursDoughnutChart');
    if (ctxDonut) {
        new Chart(ctxDonut, {
            type: 'doughnut',
            data: {
                labels: @json($chartHoursDistribution['labels']),
                datasets: [{
                    data: @json($chartHoursDistribution['data']),
                    backgroundColor: [primaryColor, '#38bdf8'],
                    borderWidth: 3,
                    borderColor: '#ffffff',
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '72%',
                plugins: {
                    legend: { display: false }
                }
            }
        });
    }
});
</script>
@endsection
