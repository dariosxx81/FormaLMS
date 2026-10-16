@extends('layouts.app')

@section('title', $activeModule->title . ' — ' . $course->title)

@section('content')
<div x-data="fadVideoPlayer" class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">

    <!-- Top Breadcrumb & Course Summary Bar -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-5 mb-6 border-b border-slate-200">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 mb-1">
                <a href="{{ route('student.dashboard') }}" class="hover:text-indigo-600 font-semibold">&larr; Torna ai Miei Corsi</a>
                <span>/</span>
                <span class="font-mono font-bold text-slate-700">{{ $course->code }}</span>
                <span>/</span>
                <span class="text-indigo-600 font-semibold">Modulo {{ $activeModule->sort_order }} di {{ $modules->count() }}</span>
            </div>
            <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900">{{ $course->title }}</h1>
        </div>

        <!-- Live Telemetry Status & Certificate Button -->
        <div class="flex flex-wrap items-center gap-3">
            <div class="px-3.5 py-2 rounded-xl bg-slate-900 text-white text-xs flex items-center gap-2.5 shadow-2xs">
                <span class="w-2 h-2 rounded-full" :class="isPlaying ? 'bg-emerald-400 animate-ping' : 'bg-amber-400'"></span>
                <div>
                    <div class="font-bold">Totale Corso: <span x-text="totalCourseHours"></span>h / {{ $course->total_hours }}h (<span x-text="courseAttendancePercent"></span>%)</div>
                    <div class="text-[10px] text-slate-400">
                        Sincrone Zoom: {{ $enrollment->sync_hours_completed }}h • Asincrone FAD: <span x-text="asyncCourseHours"></span>h
                    </div>
                </div>
            </div>

            <template x-if="certificateUrl">
                <a :href="certificateUrl"
                   class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold shadow-xs transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    <span>Scarica Attestato (Allegato 4)</span>
                </a>
            </template>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">

        <!-- Left Column (8 cols): Smart Anti-Skip Video Player & Telemetry Controls -->
        <div class="lg:col-span-8 space-y-6">

            <!-- Video Player Card -->
            <div class="bg-slate-950 rounded-2xl overflow-hidden shadow-xl border border-slate-800 relative">

                <!-- Top Telemetry Strip inside Player -->
                <div class="bg-slate-900/95 px-4 py-2.5 border-b border-slate-800 flex flex-wrap items-center justify-between gap-2 text-xs text-slate-300">
                    <div class="flex items-center gap-2">
                        <span class="px-2 py-0.5 rounded bg-indigo-500/20 text-indigo-300 font-bold border border-indigo-500/30">
                            MODULO {{ $activeModule->sort_order }}
                        </span>
                        <span class="font-semibold text-white truncate">{{ $activeModule->title }}</span>
                    </div>
                    <div class="flex items-center gap-3 text-[11px]">
                        <span class="text-slate-400">Anti-Skip: <strong class="text-emerald-400">ATTIVO</strong></span>
                        @if($course->strict_tab_pause)
                            <span class="text-amber-300 font-semibold">• Auto-Pausa Cambio Scheda: ON</span>
                        @endif
                        <span class="text-slate-400" x-show="lastSavedAt">Ultimo salvataggio: <strong class="text-white" x-text="lastSavedAt"></strong></span>
                    </div>
                </div>

                <!-- HTML5 Video Element -->
                <div class="relative aspect-video bg-black flex items-center justify-center">
                    <video x-ref="videoPlayer"
                           class="w-full h-full object-contain"
                           playsinline
                           preload="metadata"
                           @loadedmetadata="onLoadedMetadata()"
                           @timeupdate="onTimeUpdate()"
                           @seeking="onSeeking()"
                           @ratechange="onRateChange()"
                           @play="onPlay()"
                           @pause="onPause()"
                           @ended="onEnded()"
                           controls
                           controlsList="nodownload noplaybackrate">
                        <source src="{{ $activeModule->video_url }}" type="video/mp4">
                        Il tuo browser non supporta il player video HTML5.
                    </video>

                    <!-- Anti-Skip Warning Toast Overlay -->
                    <div x-cloak x-show="skipWarning"
                         x-transition
                         class="absolute top-4 left-1/2 -translate-x-1/2 bg-red-600/95 text-white px-4 py-2.5 rounded-xl shadow-lg text-xs font-bold flex items-center gap-2 border border-red-400 z-20">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        <span>Avanzamento rapido bloccato: per la validità ministeriale devi seguire la lezione senza saltare in avanti. Puoi solo tornare indietro.</span>
                    </div>

                    <!-- Tab Switch Auto-Pause Banner -->
                    <div x-cloak x-show="tabPausedBanner"
                         class="absolute inset-0 bg-slate-950/85 backdrop-blur-xs flex flex-col items-center justify-center p-6 text-center z-20">
                        <div class="w-12 h-12 rounded-full bg-amber-500/20 text-amber-400 flex items-center justify-center mb-3">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <h3 class="text-white font-bold text-base">Riproduzione in pausa (Cambio finestra rilevato)</h3>
                        <p class="text-slate-300 text-xs max-w-md mt-1">Il video è stato messo in pausa perché hai lasciato la scheda della lezione. Clicca qui sotto per riprendere dal punto esatto.</p>
                        <button @click="resumeFromBanner()" class="mt-4 px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold cursor-pointer">
                            Riprendi Lezione
                        </button>
                    </div>

                    <!-- Presence Verification Modal (Optional per Course) -->
                    <div x-cloak x-show="presenceModalOpen"
                         class="absolute inset-0 bg-slate-950/90 backdrop-blur-xs flex flex-col items-center justify-center p-6 text-center z-30">
                        <div class="bg-slate-900 border border-slate-700 rounded-2xl p-6 max-w-md w-full shadow-2xl">
                            <span class="px-2.5 py-1 rounded-full bg-indigo-500/20 text-indigo-300 text-xs font-bold">Verifica Presenza Corsista</span>
                            <h3 class="text-white font-extrabold text-lg mt-3">Sei ancora presente davanti allo schermo?</h3>
                            <p class="text-slate-300 text-xs mt-1">Conferma la tua presenza per proseguire il conteggio certificato delle ore di formazione.</p>
                            <button @click="confirmPresence()" class="mt-5 w-full py-2.5 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs cursor-pointer">
                                Sì, sono presente — Continua la lezione
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Custom Telemetry Progress & Controls Footer -->
                <div class="p-4 sm:p-5 bg-slate-900 text-white space-y-3">
                    <div class="flex flex-wrap items-center justify-between gap-2 text-xs">
                        <div class="flex items-center gap-2">
                            <span class="font-bold text-slate-200">Stato Modulo:</span>
                            <template x-if="isCompleted">
                                <span class="px-2.5 py-0.5 rounded-full bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 font-bold">
                                    Completato 100% (Certificato)
                                </span>
                            </template>
                            <template x-if="!isCompleted">
                                <span class="px-2.5 py-0.5 rounded-full bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 font-bold">
                                    In corso (<span x-text="completionPercent"></span>%)
                                </span>
                            </template>
                        </div>

                        <div class="text-slate-400 font-mono text-xs">
                            Posizione: <span class="text-white font-bold" x-text="formatSeconds(currentTime)"></span>
                            / Max raggiunto: <span class="text-indigo-300 font-bold" x-text="formatSeconds(maxPositionSeconds)"></span>
                            / Durata: {{ $activeModule->formatted_duration }}
                        </div>
                    </div>

                    <!-- Visual Progress Bar -->
                    <div class="w-full h-2.5 bg-slate-800 rounded-full overflow-hidden">
                        <div class="h-full transition-all duration-300"
                             :class="isCompleted ? 'bg-emerald-500' : 'bg-indigo-500'"
                             :style="'width: ' + completionPercent + '%'"></div>
                    </div>

                    <!-- Demo Helper Bar for Dario / Paola Presentation -->
                    <div class="pt-3 border-t border-slate-800 flex flex-wrap items-center justify-between gap-3 text-xs">
                        <div class="text-slate-400">
                            Docente: <strong class="text-slate-200">{{ $activeModule->instructor_name ?: 'Docente Accreditato' }}</strong>
                            • Ripresa automatica attiva dal secondo <span class="font-mono text-indigo-300" x-text="maxPositionSeconds"></span>s
                        </div>

                        <div class="flex items-center gap-2">
                            <button type="button"
                                    @click="sendHeartbeat('manual_save')"
                                    class="px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 font-semibold text-xs transition cursor-pointer">
                                Salva Progresso Ora
                            </button>
                            <button type="button"
                                    x-show="!isCompleted"
                                    @click="simulateCompleteModule()"
                                    class="px-3 py-1.5 rounded-lg bg-amber-500/20 hover:bg-amber-500/30 text-amber-300 border border-amber-500/40 font-bold text-xs transition cursor-pointer"
                                    title="Utile durante la demo con il cliente per mostrare subito il completamento del modulo e lo sblocco dell'attestato">
                                ⚡ Demo: Simula Fine Modulo (100%)
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Module Details & Downloadable Dispense PDF -->
            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-2xs space-y-5">
                <div>
                    <h2 class="text-lg font-extrabold text-slate-900">
                        Modulo {{ $activeModule->sort_order }}: {{ $activeModule->title }}
                    </h2>
                    <p class="text-xs text-slate-600 mt-1.5 leading-relaxed">
                        {{ $activeModule->description ?: 'Lezione videoregistrata valida ai fini dell\'aggiornamento professionale obbligatorio (Art. 6 Linee Guida B - Ministero del Turismo).' }}
                    </p>
                </div>

                <!-- Dispense PDF Allegate -->
                <div class="pt-4 border-t border-slate-100">
                    <h3 class="text-xs font-extrabold uppercase tracking-wider text-slate-500 mb-3">
                        Materiale Didattico e Dispense Scaricabili (Tracciate)
                    </h3>
                    @if($activeModule->attachments->isEmpty())
                        <p class="text-xs text-slate-400">Nessuna dispensa allegata a questo modulo.</p>
                    @else
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            @foreach($activeModule->attachments as $att)
                                <a href="{{ route('student.attachments.download', $att) }}"
                                   class="p-3.5 rounded-xl border border-slate-200 hover:border-indigo-400 hover:bg-indigo-50/40 flex items-center justify-between gap-3 transition group">
                                    <div class="flex items-center gap-3 min-w-0">
                                        <div class="w-9 h-9 rounded-lg bg-red-50 text-red-600 border border-red-200 flex items-center justify-center font-bold text-xs shrink-0">
                                            PDF
                                        </div>
                                        <div class="truncate">
                                            <div class="text-xs font-bold text-slate-900 group-hover:text-indigo-700 truncate">{{ $att->title }}</div>
                                            <div class="text-[11px] text-slate-400">Dispensa Ufficiale • {{ $att->file_size_kb }} KB</div>
                                        </div>
                                    </div>
                                    <span class="text-xs font-bold text-indigo-600 shrink-0">Scarica &darr;</span>
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Right Column (4 cols): Course Playlist (5 Modules) & Rules -->
        <div class="lg:col-span-4 space-y-6">
            <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-2xs">
                <div class="flex items-center justify-between pb-3 mb-3 border-b border-slate-100">
                    <div>
                        <h3 class="text-sm font-extrabold text-slate-900">Piano Didattico Asincrono</h3>
                        <p class="text-[11px] text-slate-500">{{ $modules->count() }} Moduli Video • Fruibili in qualsiasi orario H24</p>
                    </div>
                    <span class="px-2.5 py-1 rounded-lg bg-slate-100 text-slate-800 text-xs font-bold">
                        {{ $course->async_required_hours }}h FAD
                    </span>
                </div>

                <div class="space-y-2.5">
                    @foreach($modules as $mod)
                        @php
                            $mp = $progressMap[$mod->id] ?? null;
                            $isCurrent = $mod->id === $activeModule->id;
                            $done = $mp ? $mp->is_completed : false;
                            $pct = $mp ? $mp->completion_percent : 0;
                        @endphp
                        <a href="{{ route('student.courses.module', [$course, $mod]) }}"
                           class="block p-3.5 rounded-xl border transition {{ $isCurrent ? 'bg-indigo-50/80 border-indigo-500 ring-2 ring-indigo-500/20' : ($done ? 'bg-emerald-50/40 border-emerald-200 hover:border-emerald-400' : 'bg-white border-slate-200 hover:border-slate-300') }}">
                            <div class="flex items-start justify-between gap-2">
                                <div class="flex items-center gap-2">
                                    @if($done)
                                        <span class="w-5 h-5 rounded-full bg-emerald-500 text-white flex items-center justify-center text-xs font-bold shrink-0">&check;</span>
                                    @elseif($isCurrent)
                                        <span class="w-5 h-5 rounded-full bg-indigo-600 text-white flex items-center justify-center text-[10px] font-bold shrink-0">&#9654;</span>
                                    @else
                                        <span class="w-5 h-5 rounded-full bg-slate-200 text-slate-700 flex items-center justify-center text-[10px] font-bold shrink-0">{{ $mod->sort_order }}</span>
                                    @endif
                                    <span class="text-xs font-bold {{ $isCurrent ? 'text-indigo-950' : 'text-slate-900' }}">
                                        Modulo {{ $mod->sort_order }}: {{ $mod->title }}
                                    </span>
                                </div>
                            </div>

                            <div class="mt-2.5 flex items-center justify-between text-[11px] text-slate-500">
                                <span>Durata: {{ $mod->formatted_duration }}</span>
                                <span class="font-bold {{ $done ? 'text-emerald-600' : 'text-indigo-600' }}">
                                    {{ $done ? 'Completato (100%)' : $pct . '% svolto' }}
                                </span>
                            </div>

                            <div class="mt-1.5 w-full h-1.5 bg-slate-200 rounded-full overflow-hidden">
                                <div class="h-full {{ $done ? 'bg-emerald-500' : 'bg-indigo-600' }}" style="width: {{ $pct }}%"></div>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>

            <!-- Ministerial Compliance Info Box -->
            <div class="bg-slate-900 text-slate-200 rounded-2xl p-5 text-xs space-y-2.5 border border-slate-800">
                <div class="font-bold text-white flex items-center gap-2">
                    <svg class="w-4 h-4 text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    <span>Regole di Tracciamento Attive</span>
                </div>
                <ul class="space-y-1.5 text-slate-300 text-[11px] leading-relaxed">
                    <li>• <strong>Ripresa Automatica (Auto-Resume)</strong>: puoi interrompere la visione in qualsiasi momento e riprendere anche di notte o nei giorni successivi dal secondo esatto.</li>
                    <li>• <strong>Blocco Fast-Forward</strong>: non è consentito trascinare il cursore oltre il punto massimo già visualizzato.</li>
                    <li>• <strong>Soglia Attestato ({{ $course->min_attendance_percent }}%)</strong>: l'Attestato Ufficiale (Allegato 4) si sblocca automaticamente al raggiungimento di <strong>{{ $course->minimum_required_hours }} ore</strong> complessive tra Zoom e FAD.</li>
                </ul>
            </div>
        </div>

    </div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('fadVideoPlayer', () => ({
        sessionToken: @json($sessionToken),
        heartbeatUrl: @json(route('student.modules.heartbeat', [$course, $activeModule])),
        durationSeconds: {{ (int) $activeModule->duration_seconds }},
        watchedSeconds: {{ (int) $currentProgress->watched_seconds }},
        maxPositionSeconds: {{ (int) $currentProgress->max_position_seconds }},
        currentTime: {{ (int) $currentProgress->last_position_seconds }},
        completionPercent: {{ (float) $currentProgress->completion_percent }},
        isCompleted: @json((bool) $currentProgress->is_completed),
        strictTabPause: @json((bool) $course->strict_tab_pause),
        presenceIntervalMinutes: {{ (int) $course->presence_check_interval_minutes }},

        totalCourseHours: @json($enrollment->total_hours_completed),
        asyncCourseHours: @json($enrollment->async_hours_completed),
        courseAttendancePercent: @json($enrollment->attendance_percent),
        certificateUrl: @json($enrollment->is_eligible_for_certificate ? route('student.enrollments.certificate', $enrollment) : null),

        isPlaying: false,
        skipWarning: false,
        tabPausedBanner: false,
        presenceModalOpen: false,
        lastSavedAt: null,
        accumulatedDelta: 0,
        lastTickTimestamp: null,
        pendingTabSwitches: 0,
        pendingPresenceConfirms: 0,
        heartbeatTimer: null,
        presenceTimer: null,

        init() {
            // Visibility change listener (Auto-pause if strictTabPause is enabled on course)
            document.addEventListener('visibilitychange', () => {
                if (document.hidden && this.isPlaying) {
                    this.pendingTabSwitches++;
                    if (this.strictTabPause && this.$refs.videoPlayer) {
                        this.$refs.videoPlayer.pause();
                        this.tabPausedBanner = true;
                    }
                }
            });

            // Periodic 15-second heartbeat while playing
            this.heartbeatTimer = setInterval(() => {
                if (this.isPlaying && this.accumulatedDelta >= 5) {
                    this.sendHeartbeat('periodic');
                }
            }, 15000);

            // Optional presence check modal every X minutes
            if (this.presenceIntervalMinutes > 0) {
                this.presenceTimer = setInterval(() => {
                    if (this.isPlaying && this.$refs.videoPlayer) {
                        this.$refs.videoPlayer.pause();
                        this.presenceModalOpen = true;
                    }
                }, this.presenceIntervalMinutes * 60 * 1000);
            }

            window.addEventListener('beforeunload', () => {
                if (this.accumulatedDelta > 0) {
                    this.sendHeartbeat('unload');
                }
            });
        },

        onLoadedMetadata() {
            const video = this.$refs.videoPlayer;
            if (!video) return;
            // Resume from last watched position (or maxPositionSeconds if not finished)
            const resumeTarget = this.currentTime > 0 && this.currentTime < video.duration - 5
                ? this.currentTime
                : (this.maxPositionSeconds < video.duration - 5 ? this.maxPositionSeconds : 0);

            if (resumeTarget > 0 && resumeTarget < video.duration) {
                video.currentTime = resumeTarget;
            }
        },

        onPlay() {
            this.isPlaying = true;
            this.lastTickTimestamp = Date.now();
        },

        onPause() {
            this.isPlaying = false;
            this.lastTickTimestamp = null;
            if (this.accumulatedDelta > 0) {
                this.sendHeartbeat('pause');
            }
        },

        onRateChange() {
            const video = this.$refs.videoPlayer;
            if (video && video.playbackRate > 1.0) {
                video.playbackRate = 1.0;
            }
        },

        onSeeking() {
            const video = this.$refs.videoPlayer;
            if (!video || this.isCompleted) return;

            // Map real video duration to module formal duration if using a shorter sample video
            const scale = this. getScaleFactor(video);
            const scaledTarget = Math.round(video.currentTime * scale);

            if (scaledTarget > this.maxPositionSeconds + Math.ceil(3 * scale)) {
                video.currentTime = Math.max(0, this.maxPositionSeconds / scale);
                this.skipWarning = true;
                setTimeout(() => { this.skipWarning = false; }, 4000);
            }
        },

        onTimeUpdate() {
            const video = this.$refs.videoPlayer;
            if (!video) return;

            const now = Date.now();
            const scale = this.getScaleFactor(video);

            if (this.isPlaying && this.lastTickTimestamp) {
                const elapsedRealSec = Math.max(0, (now - this.lastTickTimestamp) / 1000);
                if (elapsedRealSec <= 2.5) {
                    const scaledDelta = elapsedRealSec * scale;
                    this.accumulatedDelta += scaledDelta;
                }
            }
            this.lastTickTimestamp = now;

            const scaledCurrent = Math.min(this.durationSeconds, Math.round(video.currentTime * scale));
            this.currentTime = scaledCurrent;

            if (scaledCurrent > this.maxPositionSeconds) {
                this.maxPositionSeconds = scaledCurrent;
            }

            if (!this.isCompleted) {
                this.completionPercent = Math.min(100, Math.round((this.maxPositionSeconds / this.durationSeconds) * 1000) / 10);
            }
        },

        onEnded() {
            this.isPlaying = false;
            this.maxPositionSeconds = this.durationSeconds;
            this.currentTime = this.durationSeconds;
            this.accumulatedDelta = Math.max(10, this.accumulatedDelta);
            this.sendHeartbeat('ended');
        },

        getScaleFactor(video) {
            if (!video || !video.duration || isNaN(video.duration) || video.duration <= 0) {
                return 1;
            }
            return this.durationSeconds / video.duration;
        },

        resumeFromBanner() {
            this.tabPausedBanner = false;
            if (this.$refs.videoPlayer) {
                this.$refs.videoPlayer.play();
            }
        },

        confirmPresence() {
            this.presenceModalOpen = false;
            this.pendingPresenceConfirms++;
            this.sendHeartbeat('presence_confirmed');
            if (this.$refs.videoPlayer) {
                this.$refs.videoPlayer.play();
            }
        },

        async simulateCompleteModule() {
            this.currentTime = this.durationSeconds;
            this.maxPositionSeconds = this.durationSeconds;
            this.accumulatedDelta = 60;
            await this.sendHeartbeat('ended');
        },

        async sendHeartbeat(eventType = 'periodic') {
            const deltaToSend = Math.min(60, Math.max(1, Math.round(this.accumulatedDelta)));
            this.accumulatedDelta = 0;

            try {
                const response = await fetch(this.heartbeatUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    },
                    body: JSON.stringify({
                        session_token: this.sessionToken,
                        current_time: this.currentTime,
                        delta_watched_seconds: deltaToSend,
                        event_type: eventType,
                        tab_switches: this.pendingTabSwitches,
                        presence_confirms: this.pendingPresenceConfirms,
                    }),
                });

                if (response.ok) {
                    const data = await response.json();
                    this.pendingTabSwitches = 0;
                    this.pendingPresenceConfirms = 0;
                    this.watchedSeconds = data.module_progress.watched_seconds;
                    this.maxPositionSeconds = data.module_progress.max_position_seconds;
                    this.completionPercent = data.module_progress.completion_percent;
                    this.isCompleted = data.module_progress.is_completed;
                    this.totalCourseHours = data.enrollment.total_hours_completed;
                    this.asyncCourseHours = data.enrollment.async_hours_completed;
                    this.courseAttendancePercent = data.enrollment.attendance_percent;
                    this.certificateUrl = data.enrollment.certificate_url;
                    this.lastSavedAt = data.timestamp;
                }
            } catch (e) {
                console.error('Heartbeat error:', e);
            }
        },

        formatSeconds(sec) {
            sec = Math.max(0, Math.round(sec));
            const h = Math.floor(sec / 3600);
            const m = Math.floor((sec % 3600) / 60);
            const s = sec % 60;
            if (h > 0) {
                return `${h}:${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
            }
            return `${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
        }
    }));
});
</script>
@endsection
