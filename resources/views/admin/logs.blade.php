@extends('layouts.app')

@section('title', 'Registro Tracciamento & Audit Log — Pannello Admin')

@section('content')
<div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8 space-y-8">

    <div>
        <span class="text-xs font-bold uppercase tracking-wider text-indigo-600">Documentazione Probante Ministeriale (Art. 3 e Art. 6 Linee Guida B)</span>
        <h1 class="text-2xl font-extrabold text-slate-900">Registro Telemetrico Sessioni Video & Audit Log</h1>
        <p class="text-xs text-slate-500 mt-0.5">
            Ogni sessione di visualizzazione viene registrata con Token univoco, Indirizzo IP, Browser/Dispositivo, secondo iniziale/finale e tempo effettivo certificato.
        </p>
    </div>

    <!-- Filter Bar -->
    <form method="GET" action="{{ route('admin.logs.index') }}" class="bg-white rounded-2xl border border-slate-200 p-4 shadow-2xs flex flex-wrap items-center gap-3 text-xs">
        <div>
            <select name="course_id" class="rounded-xl border border-slate-300 px-3.5 py-2">
                <option value="">-- Tutti i Corsi --</option>
                @foreach($courses as $c)
                    <option value="{{ $c->id }}" {{ (string) $courseId === (string) $c->id ? 'selected' : '' }}>
                        [{{ $c->code }}] {{ $c->title }}
                    </option>
                @endforeach
            </select>
        </div>
        <div>
            <select name="user_id" class="rounded-xl border border-slate-300 px-3.5 py-2">
                <option value="">-- Tutti i Corsisti --</option>
                @foreach($students as $s)
                    <option value="{{ $s->id }}" {{ (string) $userId === (string) $s->id ? 'selected' : '' }}>
                        {{ $s->full_name }} ({{ $s->fiscal_code }})
                    </option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="px-4 py-2 rounded-xl bg-slate-900 text-white font-bold cursor-pointer">Filtra Log</button>
        @if($courseId || $userId)
            <a href="{{ route('admin.logs.index') }}" class="px-3 py-2 text-slate-500 hover:text-slate-800 font-semibold">Mostra Tutti</a>
        @endif
    </form>

    <!-- Watch Logs Table (Session Telemetry) -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-2xs overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200">
            <h2 class="text-base font-extrabold text-slate-900">1. Sessioni di Fruizione Video Certificata (Heartbeat Player)</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-[11px] font-bold uppercase text-slate-500">
                        <th class="py-3 px-4">Data / Ora Inizio & Ultimo Heartbeat</th>
                        <th class="py-3 px-4">Corsista</th>
                        <th class="py-3 px-4">Corso & Modulo</th>
                        <th class="py-3 px-4">Intervallo Posizione</th>
                        <th class="py-3 px-4">Tempo Maturato</th>
                        <th class="py-3 px-4">Indirizzo IP & Dispositivo</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse($watchLogs as $wl)
                        <tr class="hover:bg-slate-50">
                            <td class="py-3 px-4 font-mono text-[11px]">
                                <div class="font-bold text-slate-800">{{ $wl->started_at?->format('d/m/Y H:i:s') }}</div>
                                <div class="text-slate-400">&rarr; {{ $wl->last_heartbeat_at?->format('H:i:s') }}</div>
                            </td>
                            <td class="py-3 px-4">
                                <div class="font-bold text-slate-900">{{ $wl->user?->full_name }}</div>
                                <div class="text-[11px] text-slate-400 font-mono">{{ $wl->user?->fiscal_code }}</div>
                            </td>
                            <td class="py-3 px-4">
                                <span class="font-mono font-bold text-indigo-600">{{ $wl->course?->code }}</span>
                                <div class="text-slate-700 font-medium">Modulo {{ $wl->module?->sort_order }}: {{ $wl->module?->title }}</div>
                            </td>
                            <td class="py-3 px-4 font-mono text-[11px]">
                                da {{ gmdate('H:i:s', $wl->start_position_seconds) }} a {{ gmdate('H:i:s', $wl->end_position_seconds) }}
                            </td>
                            <td class="py-3 px-4">
                                <span class="px-2 py-0.5 rounded bg-emerald-100 text-emerald-800 font-bold">
                                    {{ round($wl->seconds_counted / 60, 1) }} min ({{ $wl->seconds_counted }}s)
                                </span>
                            </td>
                            <td class="py-3 px-4 text-[11px] text-slate-500">
                                <div class="font-mono font-semibold text-slate-700">{{ $wl->ip_address }}</div>
                                <div class="truncate max-w-xs">{{ $wl->user_agent }}</div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-6 text-center text-slate-400">Nessuna sessione video registrata con questi filtri.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Activity Logs Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-2xs overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200">
            <h2 class="text-base font-extrabold text-slate-900">2. Registro Globale Eventi & Attività Piattaforma</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-[11px] font-bold uppercase text-slate-500">
                        <th class="py-3 px-4">Timestamp</th>
                        <th class="py-3 px-4">Tipo Evento</th>
                        <th class="py-3 px-4">Utente</th>
                        <th class="py-3 px-4">Descrizione Attività</th>
                        <th class="py-3 px-4">IP</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @foreach($activityLogs as $act)
                        <tr class="hover:bg-slate-50">
                            <td class="py-3 px-4 font-mono text-[11px] text-slate-500">{{ $act->created_at->format('d/m/Y H:i:s') }}</td>
                            <td class="py-3 px-4">
                                <span class="px-2 py-0.5 rounded bg-slate-100 text-slate-800 font-mono font-bold text-[10px] uppercase">
                                    {{ $act->action_type }}
                                </span>
                            </td>
                            <td class="py-3 px-4 font-bold text-slate-800">{{ $act->user?->full_name ?? 'Sistema' }}</td>
                            <td class="py-3 px-4 text-slate-700">{{ $act->description }}</td>
                            <td class="py-3 px-4 font-mono text-[11px] text-slate-500">{{ $act->ip_address }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
