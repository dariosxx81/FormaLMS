@extends('layouts.app')

@section('title', 'Corsi — Catalogo & Didattica — Pannello Admin')

@section('content')
<div x-data="{
    newCourseModal: false,
    searchQuery: '',
    typeFilter: 'all',
    allCourses: @js($coursesJson),
    suggestions: [],
    showSuggestions: false,
    selectedSuggestionIndex: -1,
    updateSuggestions() {
        const q = this.searchQuery.trim().toLowerCase();
        if (q.length === 0) {
            this.suggestions = [];
            this.showSuggestions = false;
            return;
        }
        this.suggestions = this.allCourses.filter(c =>
            c.title.toLowerCase().includes(q) ||
            c.code.toLowerCase().includes(q) ||
            (c.access_code && c.access_code.toLowerCase().includes(q)) ||
            (c.thematic_area && c.thematic_area.toLowerCase().includes(q))
        ).slice(0, 6);
        this.showSuggestions = this.suggestions.length > 0;
        this.selectedSuggestionIndex = -1;
    },
    selectSuggestion(course) {
        this.searchQuery = course.title;
        this.showSuggestions = false;
    },
    selectFirstOrNext(direction) {
        if (!this.showSuggestions || this.suggestions.length === 0) return;
        if (direction === 'down') {
            this.selectedSuggestionIndex = (this.selectedSuggestionIndex + 1) % this.suggestions.length;
        } else if (direction === 'up') {
            this.selectedSuggestionIndex = (this.selectedSuggestionIndex - 1 + this.suggestions.length) % this.suggestions.length;
        }
    },
    confirmSelectedSuggestion() {
        if (this.selectedSuggestionIndex >= 0 && this.suggestions[this.selectedSuggestionIndex]) {
            this.selectSuggestion(this.suggestions[this.selectedSuggestionIndex]);
        }
    },
    matchesCourse(course) {
        if (this.typeFilter !== 'all' && course.course_type !== this.typeFilter) {
            return false;
        }
        const q = this.searchQuery.trim().toLowerCase();
        if (!q) return true;
        return course.title.toLowerCase().includes(q) ||
               course.code.toLowerCase().includes(q) ||
               (course.access_code && course.access_code.toLowerCase().includes(q)) ||
               (course.thematic_area && course.thematic_area.toLowerCase().includes(q));
    },
    get filteredCount() {
        return this.allCourses.filter(c => this.matchesCourse(c)).length;
    }
}" class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8 space-y-6">

    <!-- Top Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Catalogo Corsi & Edizioni</h1>
            <p class="text-xs text-slate-500 mt-0.5">
                Gestione didattica, configurazione ore Zoom/FAD, codici d'accesso corsisti e rilascio attestati.
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2.5">
            <a href="{{ route('admin.dashboard') }}" class="px-3.5 py-2 rounded-xl bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 text-xs font-bold transition shadow-2xs">
                &larr; Torna alla Dashboard
            </a>
            <button @click="newCourseModal = true" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold transition shadow-xs cursor-pointer">
                + Nuovo Corso
            </button>
        </div>
    </div>

    <!-- Course Catalog Cards -->
    <div class="space-y-4">
        <!-- Modern Live Search with Autocomplete Popover & Type Filters -->
        <div class="bg-white rounded-2xl border border-slate-200/90 p-4 shadow-2xs space-y-3">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-3">
                <!-- Autocomplete Input Box -->
                <div class="relative flex-1 max-w-xl" @click.outside="showSuggestions = false">
                    <div class="relative">
                        <input type="text"
                               x-model="searchQuery"
                               @input="updateSuggestions"
                               @focus="updateSuggestions"
                               @keydown.arrow-down.prevent="selectFirstOrNext('down')"
                               @keydown.arrow-up.prevent="selectFirstOrNext('up')"
                               @keydown.enter.prevent="confirmSelectedSuggestion"
                               @keydown.escape="showSuggestions = false"
                               placeholder="Cerca per titolo, codice (es. CORSO-2026), codice d'accesso o area tematica..."
                               class="w-full rounded-xl border border-slate-300 bg-slate-50/60 pl-10 pr-9 py-2.5 text-xs font-medium text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                        <span class="absolute left-3.5 top-3 text-slate-400 text-xs">🔍</span>
                        <button type="button"
                                x-show="searchQuery.length > 0"
                                @click="searchQuery = ''; updateSuggestions()"
                                class="absolute right-3 top-2.5 text-slate-400 hover:text-slate-700 text-sm font-bold cursor-pointer">
                            &times;
                        </button>
                    </div>

                    <!-- Autocomplete Dropdown Popover -->
                    <div x-cloak x-show="showSuggestions && suggestions.length > 0" x-transition
                         class="absolute left-0 right-0 top-full mt-1.5 bg-white rounded-2xl border border-slate-200 shadow-2xl p-2 z-40 text-xs divide-y divide-slate-100 max-h-80 overflow-y-auto">
                        <div class="px-3 py-1.5 text-[10px] font-bold uppercase tracking-wider text-slate-400 flex items-center justify-between">
                            <span>Suggerimenti Didattici</span>
                            <span>Invio per selezionare</span>
                        </div>
                        <template x-for="(sug, index) in suggestions" :key="sug.id">
                            <div @click="selectSuggestion(sug)"
                                 :class="index === selectedSuggestionIndex ? 'bg-indigo-50 text-indigo-900' : 'hover:bg-slate-50 text-slate-800'"
                                 class="p-2.5 rounded-xl cursor-pointer transition flex items-center justify-between gap-3">
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <div class="w-8 h-8 rounded-lg bg-slate-100 border border-slate-200 overflow-hidden flex items-center justify-center shrink-0">
                                        <template x-if="sug.badge_image_url">
                                            <img :src="sug.badge_image_url" alt="Badge" class="w-full h-full object-contain">
                                        </template>
                                        <template x-if="!sug.badge_image_url">
                                            <span class="text-xs">🎓</span>
                                        </template>
                                    </div>
                                    <div class="min-w-0">
                                        <div class="font-bold text-xs truncate" x-text="sug.title"></div>
                                        <div class="text-[10px] text-slate-400 mt-0.5 flex items-center gap-1.5">
                                            <span class="font-mono font-bold text-indigo-600" x-text="sug.code"></span>
                                            <span>•</span>
                                            <span x-text="sug.thematic_area || 'Area Generale'"></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-right shrink-0">
                                    <span class="px-2 py-0.5 rounded-md text-[10px] font-bold uppercase"
                                          :class="sug.is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'"
                                          x-text="sug.is_active ? 'Attivo' : 'Disattivato'"></span>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Type Filters Pills -->
                <div class="flex flex-wrap items-center gap-1.5 shrink-0 text-xs">
                    <button type="button" @click="typeFilter = 'all'"
                            :class="typeFilter === 'all' ? 'bg-slate-900 text-white shadow-xs' : 'bg-slate-100 hover:bg-slate-200 text-slate-600'"
                            class="px-3 py-1.5 rounded-xl font-bold transition cursor-pointer">
                        Tutti
                    </button>
                    <button type="button" @click="typeFilter = 'aggiornamento'"
                            :class="typeFilter === 'aggiornamento' ? 'bg-slate-900 text-white shadow-xs' : 'bg-slate-100 hover:bg-slate-200 text-slate-600'"
                            class="px-3 py-1.5 rounded-xl font-bold transition cursor-pointer">
                        Aggiornamento (All. 4)
                    </button>
                    <button type="button" @click="typeFilter = 'specializzazione'"
                            :class="typeFilter === 'specializzazione' ? 'bg-slate-900 text-white shadow-xs' : 'bg-slate-100 hover:bg-slate-200 text-slate-600'"
                            class="px-3 py-1.5 rounded-xl font-bold transition cursor-pointer">
                        Specializzazione (All. 3)
                    </button>
                    <button type="button" @click="typeFilter = 'professionale'"
                            :class="typeFilter === 'professionale' ? 'bg-slate-900 text-white shadow-xs' : 'bg-slate-100 hover:bg-slate-200 text-slate-600'"
                            class="px-3 py-1.5 rounded-xl font-bold transition cursor-pointer">
                        Professionale
                    </button>
                </div>
            </div>

            <div class="flex items-center justify-between text-xs text-slate-400 pt-2 border-t border-slate-100">
                <div>
                    Visualizzati <strong class="text-slate-700" x-text="filteredCount"></strong> corsi
                    <span x-show="searchQuery.trim().length > 0"> per "<strong class="text-indigo-600" x-text="searchQuery"></strong>"</span>
                </div>
                <div x-show="searchQuery.trim().length > 0 || typeFilter !== 'all'">
                    <button type="button" @click="searchQuery = ''; typeFilter = 'all'; updateSuggestions()" class="text-indigo-600 hover:underline font-bold cursor-pointer">
                        Azzera filtri
                    </button>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-between">
            <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">
                Elenco Corsi Formativi ({{ $courses->count() }})
            </span>
            <span class="text-xs text-slate-400">
                {{ $courses->where('is_active', true)->count() }} attivi su {{ $courses->count() }}
            </span>
        </div>

        @if($courses->isEmpty())
            <div class="bg-white rounded-2xl border border-slate-200 p-12 text-center space-y-3">
                <div class="w-12 h-12 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-xl mx-auto font-bold">📚</div>
                <h3 class="text-base font-extrabold text-slate-800">Nessun corso presente nel catalogo</h3>
                <p class="text-xs text-slate-500 max-w-md mx-auto">
                    Crea il tuo primo corso per iniziare a caricare video lezioni, dispense PDF e associare corsisti.
                </p>
                <button @click="newCourseModal = true" class="mt-2 px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold transition cursor-pointer">
                    + Crea Primo Corso
                </button>
            </div>
        @else
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                @foreach($courses as $course)
                    <div x-show="matchesCourse({
                            id: {{ $course->id }},
                            code: '{{ addslashes($course->code) }}',
                            title: '{{ addslashes($course->title) }}',
                            thematic_area: '{{ addslashes($course->thematic_area ?? '') }}',
                            access_code: '{{ addslashes($course->access_code ?? '') }}',
                            course_type: '{{ addslashes($course->course_type) }}'
                         })"
                         x-transition
                         x-data="{ menuOpen: false }"
                         class="bg-white rounded-2xl border {{ $course->is_active ? 'border-slate-200/90' : 'border-amber-300 bg-slate-50/60 opacity-85' }} p-5 shadow-2xs hover:border-slate-300 transition flex flex-col justify-between gap-4">
                        <div>
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex flex-wrap items-center gap-1.5">
                                    <span class="px-2.5 py-0.5 rounded-lg bg-slate-900 text-white font-mono text-[11px] font-bold">
                                        {{ $course->code }}
                                    </span>
                                    <span class="px-2.5 py-0.5 rounded-lg bg-indigo-50 border border-indigo-200 text-indigo-700 text-[10px] font-bold uppercase">
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

                                <!-- Actions Menu -->
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
                                        <form method="POST" action="{{ route('admin.courses.duplicate', $course) }}"
                                              onsubmit="return confirm('Duplicare questo corso con tutti i suoi moduli didattici? Verranno generati codici univoci.');">
                                            @csrf
                                            <button type="submit" class="w-full text-left px-3 py-2 rounded-lg hover:bg-indigo-50 text-indigo-700 font-bold cursor-pointer">
                                                📋 Duplica Corso & Moduli
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

                            <div class="flex items-start gap-4 mt-3">
                                <div class="w-16 h-16 min-w-[64px] min-h-[64px] max-w-[64px] max-h-[64px] rounded-2xl bg-gradient-to-br from-slate-50 to-slate-100 border border-slate-200/90 overflow-hidden flex items-center justify-center p-1.5 shadow-xs shrink-0 group-hover:scale-105 transition-transform duration-200 relative">
                                    @if($course->badge_image_url)
                                        <img src="{{ $course->badge_image_url }}" alt="Badge {{ $course->title }}" class="w-full h-full max-w-full max-h-full object-contain drop-shadow-2xs">
                                    @else
                                        <div class="w-full h-full rounded-xl bg-indigo-50/70 border border-indigo-100 flex flex-col items-center justify-center text-indigo-600">
                                            <span class="text-2xl leading-none">🎓</span>
                                            <span class="text-[9px] font-black uppercase tracking-tight mt-0.5 text-indigo-400">FAD</span>
                                        </div>
                                    @endif
                                </div>
                                <div class="flex-1 min-w-0">
                                    <a href="{{ route('admin.courses.show', $course) }}" class="block font-black text-base text-slate-900 hover:text-indigo-600 leading-snug transition-colors">
                                        {{ $course->title }}
                                    </a>
                                    <div class="text-[11px] text-slate-400 mt-1 flex items-center gap-1.5 font-medium truncate">
                                        <span>🏛 {{ Str::limit($course->thematic_area ?: 'Area Tematica Generale', 40) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="pt-3 border-t border-slate-100 flex flex-wrap items-center justify-between gap-3 text-xs">
                            <div class="flex flex-wrap items-center gap-3 text-slate-500 font-medium">
                                @if($course->has_sync_hours)
                                    <span>⏱ <strong>{{ $course->total_hours }}h</strong> ({{ $course->sync_required_hours }}h Zoom + {{ $course->async_required_hours }}h FAD)</span>
                                @else
                                    <span class="text-indigo-600 font-bold">🎬 100% FAD ({{ $course->total_hours }}h)</span>
                                @endif
                                <span>•</span>
                                <span>👥 <strong>{{ $course->enrollments_count }}</strong> iscritti</span>
                                <span>•</span>
                                <span>🎬 <strong>{{ $course->modules_count }}</strong> moduli</span>
                            </div>

                            <div class="flex items-center gap-2">
                                <a href="{{ route('student.courses.show', $course) }}" class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-[11px] transition">
                                    &#9654; Player
                                </a>
                                <a href="{{ route('admin.courses.show', $course) }}" class="px-3 py-1 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 font-bold text-[11px] transition">
                                    Apri Corso &rarr;
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Zero Results Card When Searching -->
            <div x-cloak x-show="filteredCount === 0"
                 class="bg-white rounded-2xl border border-slate-200 p-10 text-center space-y-2 shadow-2xs">
                <div class="text-3xl">🔍</div>
                <h4 class="text-sm font-extrabold text-slate-800">Nessun corso corrisponde ai criteri di ricerca</h4>
                <p class="text-xs text-slate-400">Nessun risultato trovato per "<span class="font-bold text-slate-600" x-text="searchQuery"></span>".</p>
                <button type="button" @click="searchQuery = ''; typeFilter = 'all'" class="mt-2 px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold cursor-pointer">
                    Ripristina tutti i corsi
                </button>
            </div>
        @endif
    </div>

    <!-- Modal: Create New Course -->
    <div x-cloak x-show="newCourseModal" class="fixed inset-0 z-50 bg-slate-950/70 backdrop-blur-xs flex items-center justify-center p-4 overflow-y-auto">
        <div @click.away="newCourseModal = false"
             x-data="{
                enableSync: false,
                syncMin: 1200,
                asyncMin: 300,
                syncHours: 20,
                asyncHours: 5,
                onSyncMinInput() { this.syncHours = (Number(this.syncMin || 0) / 60).toFixed(2); },
                onAsyncMinInput() { this.asyncHours = (Number(this.asyncMin || 0) / 60).toFixed(2); },
                onSyncHoursInput() { this.syncMin = Math.round(Number(this.syncHours || 0) * 60); },
                onAsyncHoursInput() { this.asyncMin = Math.round(Number(this.asyncHours || 0) * 60); },
                get totalMinutes() { return this.enableSync ? (Number(this.syncMin || 0) + Number(this.asyncMin || 0)) : Number(this.asyncMin || 0); },
                get totalHoursFormatted() { return (this.totalMinutes / 60).toFixed(2); }
             }"
             class="bg-white rounded-2xl max-w-2xl w-full p-6 sm:p-8 shadow-2xl border border-slate-200 my-8">
            <div class="flex items-center justify-between border-b border-slate-200 pb-4 mb-5">
                <div>
                    <h3 class="text-lg font-extrabold text-slate-900">Crea Nuovo Corso nel Catalogo FAD</h3>
                    <p class="text-xs text-slate-500">Configura i parametri didattici, scegli se abilitare le ore Zoom e imposta il Codice Corso.</p>
                </div>
                <button @click="newCourseModal = false" class="text-slate-400 hover:text-slate-700 text-xl font-bold cursor-pointer">&times;</button>
            </div>

            <form method="POST" action="{{ route('admin.courses.store') }}" enctype="multipart/form-data" class="space-y-4 text-xs">
                @csrf
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Codice Identificativo *</label>
                        <input type="text" name="code" required placeholder="Es. CORSO-2026" class="w-full rounded-xl border border-slate-300 px-3 py-2 font-mono uppercase">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">🔑 Codice Segreto Iscrizione</label>
                        <input type="text" name="invite_code" placeholder="Es. INVITO2026 (opz.)" class="w-full rounded-xl border border-slate-300 px-3 py-2 font-mono uppercase">
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
                    <input type="text" name="title" required placeholder="Es. Corso di Formazione Continua" class="w-full rounded-xl border border-slate-300 px-3 py-2">
                </div>

                <!-- Badge Upload with Live Preview -->
                <div x-data="{ previewUrl: null, fileChosen(e) { const f = e.target.files[0]; if(f) { this.previewUrl = URL.createObjectURL(f); } } }" class="p-4 rounded-xl bg-slate-50 border border-slate-200">
                    <label class="block font-bold text-slate-700 mb-1.5">Badge / Immagine di Copertina del Corso (opzionale)</label>
                    <div class="flex items-center gap-3">
                        <div class="w-14 h-14 rounded-xl bg-white border border-slate-200 overflow-hidden flex items-center justify-center p-1 shadow-2xs shrink-0">
                            <template x-if="previewUrl">
                                <img :src="previewUrl" alt="Preview Badge" class="w-full h-full object-contain">
                            </template>
                            <template x-if="!previewUrl">
                                <span class="text-2xl">🎓</span>
                            </template>
                        </div>
                        <div class="flex-1">
                            <input type="file" name="badge_image_file" @change="fileChosen" accept="image/png,image/jpeg,image/svg+xml,image/webp" class="w-full text-xs text-slate-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-white file:text-slate-700 file:shadow-2xs file:border-slate-200 hover:file:bg-slate-100 cursor-pointer">
                            <p class="text-[10px] text-slate-400 mt-1">PNG, JPG, SVG o WEBP (consigliato formato quadrato o stemma trasparente).</p>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Ambito Tematico / Territoriale</label>
                        <input type="text" name="thematic_area" value="Area tematica specialistica" class="w-full rounded-xl border border-slate-300 px-3 py-2">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Estremi Provvedimento Autorizzazione</label>
                        <input type="text" name="authorization_details" value="Delibera n. 128/2026" class="w-full rounded-xl border border-slate-300 px-3 py-2">
                    </div>
                </div>

                <!-- Toggle Enable Zoom Sync Hours vs 100% Platform FAD Hours -->
                <div class="bg-slate-50 p-4 rounded-xl border border-slate-200 space-y-3">
                    <input type="hidden" name="enable_sync_hours" :value="enableSync ? '1' : '0'">
                    <label class="flex items-center justify-between gap-3 cursor-pointer pb-2 border-b border-slate-200">
                        <div>
                            <strong class="text-slate-900 block">Abilita Ore Sincrone Esterne (Zoom)</strong>
                            <span class="text-[11px] text-slate-500">Se disattivato, il corso conterà e mostrerà esclusivamente le ore video maturate in piattaforma (100% FAD).</span>
                        </div>
                        <input type="checkbox" x-model="enableSync" class="w-4 h-4 rounded text-indigo-600">
                    </label>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div x-show="enableSync" class="p-3 rounded-xl bg-white border border-slate-200 space-y-1.5">
                            <div class="flex items-center justify-between">
                                <label class="block font-bold text-slate-800 text-xs">Aula Zoom (Sincrono)</label>
                                <span class="text-[11px] font-mono text-sky-700 font-bold" x-text="syncHours + ' ore'"></span>
                            </div>
                            <div class="relative">
                                <input type="number" min="0" step="1" x-model.number="syncMin" @input="onSyncMinInput" placeholder="Es. 1200" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-bold font-mono text-slate-900 pr-10">
                                <span class="absolute right-3 top-2 text-[10px] text-slate-400 font-bold">min</span>
                            </div>
                            <input type="hidden" name="sync_required_hours" :value="syncHours">
                        </div>
                        <div class="p-3 rounded-xl bg-white border border-slate-200 space-y-1.5">
                            <div class="flex items-center justify-between">
                                <label class="block font-bold text-slate-800 text-xs">Piattaforma FAD (Asincrono)</label>
                                <span class="text-[11px] font-mono text-indigo-700 font-bold" x-text="asyncHours + ' ore'"></span>
                            </div>
                            <div class="relative">
                                <input type="number" min="1" step="1" x-model.number="asyncMin" @input="onAsyncMinInput" required placeholder="Es. 300 o 60" class="w-full rounded-lg border border-indigo-300 bg-white px-3 py-1.5 text-xs font-bold font-mono text-indigo-900 pr-10">
                                <span class="absolute right-3 top-2 text-[10px] text-indigo-400 font-bold">min</span>
                            </div>
                            <input type="hidden" name="async_required_hours" :value="asyncHours">
                        </div>
                        <div class="p-3 rounded-xl bg-slate-100/90 border border-slate-200 space-y-1.5">
                            <div class="flex items-center justify-between">
                                <label class="block font-bold text-slate-800 text-xs">Monte Ore Totale</label>
                                <span class="text-[11px] font-mono text-emerald-700 font-bold" x-text="totalHoursFormatted + ' ore'"></span>
                            </div>
                            <div class="relative">
                                <input type="text" readonly :value="totalMinutes + ' min'" class="w-full rounded-lg border border-slate-200 bg-slate-200/60 px-3 py-1.5 text-xs font-black font-mono text-slate-900">
                            </div>
                            <input type="hidden" name="total_hours" :value="totalHoursFormatted">
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
