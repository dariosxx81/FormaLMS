@extends('layouts.app')

@section('title', 'Pannello Admin — ' . ($settings['platform_name'] ?? 'FormaFAD LMS'))

@section('content')
<div x-data="{ newCourseModal: false }" class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8 space-y-8">

    <!-- Top Admin Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <span class="text-xs font-bold uppercase tracking-wider text-indigo-600">Direzione Didattica & Controllo</span>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900">Cruscotto Multi-Corso & Tracciamento FAD</h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-0.5">
                Gestisci il catalogo corsi, i moduli video asincroni, le iscrizioni, il report ore Zoom e la generazione degli Attestati Ministeriali (Allegati 3 e 4).
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2.5">
            <a href="{{ route('admin.users.index') }}" class="px-4 py-2.5 rounded-xl bg-white hover:bg-slate-50 text-slate-700 border border-slate-300 text-xs font-bold transition shadow-2xs">
                + Iscrivi / Importa Corsisti (CSV)
            </a>
            <button @click="newCourseModal = true" class="px-4 py-2.5 rounded-xl bg-slate-900 hover:bg-indigo-600 text-white text-xs font-bold transition shadow-xs cursor-pointer">
                + Crea Nuovo Corso FAD
            </button>
        </div>
    </div>

    <!-- KPI Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-2xs">
            <div class="text-xs font-semibold text-slate-500">Corsi a Catalogo</div>
            <div class="text-2xl font-extrabold text-slate-900 mt-1">{{ $courses->count() }}</div>
            <div class="text-[11px] text-emerald-600 font-semibold mt-1">Multi-corso attivo</div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-2xs">
            <div class="text-xs font-semibold text-slate-500">Corsisti Registrati</div>
            <div class="text-2xl font-extrabold text-slate-900 mt-1">{{ $totalStudents }}</div>
            <div class="text-[11px] text-slate-400 mt-1">
                @if($pendingStudents > 0)
                    <span class="text-amber-600 font-bold">{{ $pendingStudents }} da approvare</span>
                @else
                    Anagrafica ministeriale completa
                @endif
            </div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-2xs">
            <div class="text-xs font-semibold text-slate-500">Iscrizioni Attive</div>
            <div class="text-2xl font-extrabold text-indigo-600 mt-1">{{ $totalEnrollments }}</div>
            <div class="text-[11px] text-slate-400 mt-1">Sincrono + Asincrono</div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-2xs">
            <div class="text-xs font-semibold text-slate-500">Ore Video FAD Erogate</div>
            <div class="text-2xl font-extrabold text-slate-900 mt-1">{{ number_format($totalAsyncHoursWatched, 1, ',', '') }}h</div>
            <div class="text-[11px] text-slate-400 mt-1">Tracciate con Heartbeat 15s</div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-2xs col-span-2 lg:col-span-1">
            <div class="text-xs font-semibold text-slate-500">Attestati Idonei (&ge;80%)</div>
            <div class="text-2xl font-extrabold text-emerald-600 mt-1">{{ $eligibleCertificates }}</div>
            <div class="text-[11px] text-emerald-700 font-medium mt-1">Pronti in PDF (Allegato 4)</div>
        </div>
    </div>

    <!-- Multi-Course Catalog Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-2xs overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
            <div>
                <h2 class="text-base font-extrabold text-slate-900">Catalogo Corsi Formativi (Aggiornamento & Specializzazione)</h2>
                <p class="text-xs text-slate-500">Clicca su "Gestisci Corso & Registro" per amministrare i moduli video, caricare le dispense PDF, inserire le ore Zoom e scaricare i report.</p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-[11px] font-bold uppercase text-slate-500">
                        <th class="py-3.5 px-5">Codice & Titolo Corso</th>
                        <th class="py-3.5 px-4">Tipologia / Ambito</th>
                        <th class="py-3.5 px-4">Monte Ore</th>
                        <th class="py-3.5 px-4">Moduli FAD</th>
                        <th class="py-3.5 px-4">Iscritti</th>
                        <th class="py-3.5 px-4">Controllo Player</th>
                        <th class="py-3.5 px-5 text-right">Azioni & Export Ministeriali</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 text-xs">
                    @foreach($courses as $course)
                        <tr class="hover:bg-slate-50/80 transition">
                            <td class="py-4 px-5">
                                <div class="flex items-center gap-2">
                                    <span class="px-2 py-0.5 rounded bg-slate-900 text-white font-mono text-[11px] font-bold">{{ $course->code }}</span>
                                    <a href="{{ route('admin.courses.show', $course) }}" class="font-extrabold text-sm text-slate-900 hover:text-indigo-600">
                                        {{ $course->title }}
                                    </a>
                                </div>
                                @if($course->authorization_details)
                                    <div class="text-[11px] text-slate-500 mt-1">Provv. Aut.: {{ $course->authorization_details }}</div>
                                @endif
                            </td>
                            <td class="py-4 px-4">
                                <span class="px-2 py-0.5 rounded-md bg-indigo-50 text-indigo-700 font-bold uppercase text-[10px] border border-indigo-200">
                                    {{ $course->course_type }}
                                </span>
                                <div class="text-[11px] text-slate-500 mt-1 max-w-xs truncate">{{ $course->thematic_area }}</div>
                            </td>
                            <td class="py-4 px-4">
                                <div class="font-bold text-slate-900">{{ $course->total_hours }}h totali</div>
                                <div class="text-[11px] text-slate-500">{{ $course->sync_required_hours }}h Zoom + {{ $course->async_required_hours }}h FAD</div>
                            </td>
                            <td class="py-4 px-4">
                                <span class="font-bold text-slate-800">{{ $course->modules_count }} moduli</span>
                            </td>
                            <td class="py-4 px-4">
                                <span class="px-2.5 py-1 rounded-full bg-slate-100 text-slate-800 font-bold">
                                    {{ $course->enrollments_count }} corsisti
                                </span>
                            </td>
                            <td class="py-4 px-4">
                                <div class="space-y-0.5 text-[11px]">
                                    <div class="text-emerald-700 font-semibold">&check; Anti-Skip + Heartbeat</div>
                                    @if($course->strict_tab_pause)
                                        <div class="text-amber-700 font-semibold">• Pausa Cambio Tab ON</div>
                                    @endif
                                    @if($course->presence_check_interval_minutes > 0)
                                        <div class="text-indigo-700 font-semibold">• Popup ogni {{ $course->presence_check_interval_minutes }}m</div>
                                    @endif
                                </div>
                            </td>
                            <td class="py-4 px-5 text-right">
                                <div class="inline-flex flex-wrap items-center justify-end gap-1.5">
                                    <a href="{{ route('admin.courses.show', $course) }}"
                                       class="px-3 py-1.5 rounded-lg bg-slate-900 hover:bg-indigo-600 text-white font-bold transition">
                                        Gestisci Corso & Registro &rarr;
                                    </a>
                                    <a href="{{ route('admin.courses.export-pdf', $course) }}"
                                       class="px-2.5 py-1.5 rounded-lg bg-red-50 hover:bg-red-100 text-red-700 border border-red-200 font-bold transition"
                                       title="Scarica Registro Ufficiale Presenze PDF">
                                        PDF Registro
                                    </a>
                                    <a href="{{ route('admin.courses.export-csv', $course) }}"
                                       class="px-2.5 py-1.5 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 font-bold transition"
                                       title="Esporta Registro Excel/CSV">
                                        CSV
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Bottom Grid: Latest Enrollments & Live Activity Log -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        <!-- Latest Enrollments Progress -->
        <div class="lg:col-span-7 bg-white rounded-2xl border border-slate-200 p-6 shadow-2xs">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-base font-extrabold text-slate-900">Stato Avanzamento Corsisti ( tempo reale )</h3>
                    <p class="text-xs text-slate-500">Riepilogo rapido delle ultime iscrizioni e idoneità all'Attestato Allegato 4.</p>
                </div>
                <a href="{{ route('admin.users.index') }}" class="text-xs font-bold text-indigo-600 hover:underline">Vedi tutti &rarr;</a>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="border-b border-slate-200 text-[11px] text-slate-400 uppercase">
                            <th class="pb-2">Corsista</th>
                            <th class="pb-2">Corso</th>
                            <th class="pb-2">Zoom + FAD</th>
                            <th class="pb-2">% Freq.</th>
                            <th class="pb-2 text-right">Attestato</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($recentEnrollments as $enr)
                            <tr>
                                <td class="py-3 pr-2">
                                    <div class="font-bold text-slate-900">{{ $enr->user->full_name }}</div>
                                    <div class="text-[11px] text-slate-400 font-mono">{{ $enr->user->fiscal_code }}</div>
                                </td>
                                <td class="py-3 pr-2">
                                    <a href="{{ route('admin.courses.show', $enr->course) }}" class="font-mono font-bold text-indigo-600 hover:underline">
                                        {{ $enr->course->code }}
                                    </a>
                                </td>
                                <td class="py-3 pr-2">
                                    <span class="font-semibold">{{ $enr->sync_hours_completed }}h</span> +
                                    <span class="font-bold text-indigo-600">{{ $enr->async_hours_completed }}h</span>
                                    = <strong>{{ $enr->total_hours_completed }}h</strong>
                                </td>
                                <td class="py-3 pr-2">
                                    <span class="px-2 py-0.5 rounded-full font-bold text-[11px] {{ $enr->is_eligible_for_certificate ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                                        {{ $enr->attendance_percent }}%
                                    </span>
                                </td>
                                <td class="py-3 text-right">
                                    <a href="{{ route('student.enrollments.certificate', $enr) }}"
                                       class="text-xs font-bold {{ $enr->is_eligible_for_certificate ? 'text-emerald-600 hover:underline' : 'text-slate-400 hover:text-slate-600' }}">
                                        PDF Allegato 4 &darr;
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Live Telemetry & Activity Feed -->
        <div class="lg:col-span-5 bg-white rounded-2xl border border-slate-200 p-6 shadow-2xs">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-base font-extrabold text-slate-900">Flusso Attività & Log Telemetrico</h3>
                    <p class="text-xs text-slate-500">Eventi registrati con indirizzo IP e timestamp.</p>
                </div>
                <a href="{{ route('admin.logs.index') }}" class="text-xs font-bold text-indigo-600 hover:underline">Registro completo &rarr;</a>
            </div>

            <div class="space-y-3 max-h-80 overflow-y-auto pr-1">
                @foreach($recentActivities as $act)
                    <div class="text-xs border-l-2 border-slate-300 pl-3 py-0.5">
                        <div class="flex items-center justify-between text-[11px] text-slate-400">
                            <span class="font-mono font-bold text-indigo-600 uppercase">{{ $act->action_type }}</span>
                            <span>{{ $act->created_at->format('d/m H:i:s') }} • IP {{ $act->ip_address }}</span>
                        </div>
                        <div class="font-semibold text-slate-800 mt-0.5">{{ $act->description }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Modal: Create New Course -->
    <div x-cloak x-show="newCourseModal" class="fixed inset-0 z-50 bg-slate-950/70 backdrop-blur-xs flex items-center justify-center p-4 overflow-y-auto">
        <div @click.away="newCourseModal = false" class="bg-white rounded-2xl max-w-2xl w-full p-6 sm:p-8 shadow-2xl border border-slate-200 my-8">
            <div class="flex items-center justify-between border-b border-slate-200 pb-4 mb-5">
                <div>
                    <h3 class="text-lg font-extrabold text-slate-900">Crea Nuovo Corso nel Catalogo FAD</h3>
                    <p class="text-xs text-slate-500">Configura i parametri formativi, il monte ore Sincrono/Asincrono e le regole del Player Video.</p>
                </div>
                <button @click="newCourseModal = false" class="text-slate-400 hover:text-slate-700 text-xl font-bold">&times;</button>
            </div>

            <form method="POST" action="{{ route('admin.courses.store') }}" class="space-y-4 text-xs">
                @csrf
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Codice Corso *</label>
                        <input type="text" name="code" required placeholder="Es. AGG-GT-2026-04" class="w-full rounded-xl border border-slate-300 px-3 py-2 font-mono uppercase">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Tipologia *</label>
                        <select name="course_type" class="w-full rounded-xl border border-slate-300 px-3 py-2">
                            <option value="aggiornamento">Corso di Aggiornamento (Allegato 4)</option>
                            <option value="specializzazione">Corso di Specializzazione (Allegato 3)</option>
                            <option value="professionale">Formazione Professionale / Camerale</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Soglia Minima Attestato (%)</label>
                        <input type="number" name="min_attendance_percent" value="80" min="1" max="100" required class="w-full rounded-xl border border-slate-300 px-3 py-2">
                    </div>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Titolo Ufficiale del Corso *</label>
                    <input type="text" name="title" required placeholder="Es. Corso di Aggiornamento Professionale Guide Turistiche - Patrimonio Storico-Artistico" class="w-full rounded-xl border border-slate-300 px-3 py-2">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Ambito Tematico / Territoriale (Art. 2 Linee Guida B)</label>
                        <input type="text" name="thematic_area" value="Area storico-artistica e Diritto del Turismo, accessibilità e inclusività" class="w-full rounded-xl border border-slate-300 px-3 py-2">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Estremi Provvedimento Autorizzazione Ministero</label>
                        <input type="text" name="authorization_details" value="Prot. MiTur D.G. n. 0048219 del 12/09/2026" class="w-full rounded-xl border border-slate-300 px-3 py-2">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 bg-slate-50 p-4 rounded-xl border border-slate-200">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Monte Ore Totale *</label>
                        <input type="number" step="0.5" name="total_hours" value="25" required class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 font-bold">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">di cui Ore Sincrone (Zoom) *</label>
                        <input type="number" step="0.5" name="sync_required_hours" value="20" required class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">di cui Ore Asincrone (FAD) *</label>
                        <input type="number" step="0.5" name="async_required_hours" value="5" required class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 font-bold text-indigo-600">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-1">
                    <label class="flex items-center gap-2.5 p-3 rounded-xl border border-slate-200 bg-slate-50">
                        <input type="checkbox" name="strict_tab_pause" value="1" class="rounded text-indigo-600">
                        <span><strong>Auto-Pausa su cambio scheda</strong> (ferma il video se l'utente cambia finestra)</span>
                    </label>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Popup "Sei presente?" ogni N minuti (0 = disattivato)</label>
                        <input type="number" name="presence_check_interval_minutes" value="0" min="0" max="60" class="w-full rounded-xl border border-slate-300 px-3 py-2">
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-200">
                    <button type="button" @click="newCourseModal = false" class="px-4 py-2 rounded-xl border border-slate-300 font-semibold text-slate-700">Annulla</button>
                    <button type="submit" class="px-5 py-2 rounded-xl bg-slate-900 hover:bg-indigo-600 text-white font-bold cursor-pointer">Crea Corso e Vai ai Moduli &rarr;</button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
