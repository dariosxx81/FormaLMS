@extends('layouts.app')

@section('title', 'Libreria Media & File — ' . ($settings['platform_name'] ?? 'FormaFAD LMS'))

@section('content')
<div x-data="{
    uploadModal: false,
    copiedId: null,
    copyToClipboard(url, id) {
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(url);
        } else {
            const input = document.createElement('input');
            input.value = url;
            document.body.appendChild(input);
            input.select();
            document.execCommand('copy');
            document.body.removeChild(input);
        }
        this.copiedId = id;
        setTimeout(() => this.copiedId = null, 2500);
    }
}" class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8 space-y-6">

    <!-- Top Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 mb-1">
                <a href="{{ route('admin.dashboard') }}" class="hover:text-indigo-600 font-bold">&larr; Dashboard</a>
                <span>/</span>
                <span class="font-bold text-slate-700">Media & File</span>
            </div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Gestione Media, Badge & Video</h1>
            <p class="text-xs text-slate-500 mt-0.5">
                Carica badge, immagini di copertina e video locali per generare istantaneamente il link pubblico da incollare nei corsi e moduli.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
            <button @click="uploadModal = true"
                    class="px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold transition shadow-xs flex items-center gap-2 cursor-pointer">
                <span>📁 + Carica Nuovo File</span>
            </button>
        </div>
    </div>

    <!-- Alert Success -->
    @if(session('success'))
        <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-semibold flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span>✓</span>
                <span>{{ session('success') }}</span>
            </div>
            <button @click="$el.parentElement.remove()" class="text-emerald-600 hover:text-emerald-900 font-bold">&times;</button>
        </div>
    @endif

    <!-- 4 Stats / Categories Quick Tabs -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <a href="{{ route('admin.media.index') }}"
           class="p-4 rounded-2xl border transition {{ !$typeFilter ? 'bg-indigo-50/60 border-indigo-200 shadow-2xs' : 'bg-white border-slate-200/90 hover:border-slate-300' }}">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500">Tutti i File</span>
                <span class="text-base">🗂</span>
            </div>
            <div class="text-2xl font-black text-slate-900 mt-2">{{ $stats['total'] }}</div>
        </a>

        <a href="{{ route('admin.media.index', ['type' => 'badge']) }}"
           class="p-4 rounded-2xl border transition {{ $typeFilter === 'badge' ? 'bg-indigo-50/60 border-indigo-200 shadow-2xs' : 'bg-white border-slate-200/90 hover:border-slate-300' }}">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500">Badge & Copertine</span>
                <span class="text-base">🎓</span>
            </div>
            <div class="text-2xl font-black text-slate-900 mt-2">{{ $stats['badges'] }}</div>
        </a>

        <a href="{{ route('admin.media.index', ['type' => 'video']) }}"
           class="p-4 rounded-2xl border transition {{ $typeFilter === 'video' ? 'bg-indigo-50/60 border-indigo-200 shadow-2xs' : 'bg-white border-slate-200/90 hover:border-slate-300' }}">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500">File Video (.mp4)</span>
                <span class="text-base">🎬</span>
            </div>
            <div class="text-2xl font-black text-slate-900 mt-2">{{ $stats['videos'] }}</div>
        </a>

        <a href="{{ route('admin.media.index', ['type' => 'document']) }}"
           class="p-4 rounded-2xl border transition {{ $typeFilter === 'document' ? 'bg-indigo-50/60 border-indigo-200 shadow-2xs' : 'bg-white border-slate-200/90 hover:border-slate-300' }}">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500">Documenti & Dispense</span>
                <span class="text-base">📄</span>
            </div>
            <div class="text-2xl font-black text-slate-900 mt-2">{{ $stats['documents'] }}</div>
        </a>
    </div>

    <!-- Search & Filter Bar -->
    <div class="bg-white rounded-2xl border border-slate-200/90 p-4 shadow-2xs flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <form method="GET" action="{{ route('admin.media.index') }}" class="flex items-center gap-2 flex-1 max-w-md">
            @if($typeFilter)
                <input type="hidden" name="type" value="{{ $typeFilter }}">
            @endif
            <div class="relative w-full">
                <input type="text" name="q" value="{{ $search }}"
                       placeholder="Cerca per titolo file..."
                       class="w-full rounded-xl border border-slate-300 bg-slate-50/50 pl-9 pr-3 py-2 text-xs font-medium focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <span class="absolute left-3 top-2.5 text-slate-400 text-xs">🔍</span>
            </div>
            <button type="submit" class="px-3.5 py-2 rounded-xl bg-slate-900 hover:bg-indigo-600 text-white text-xs font-bold transition cursor-pointer shrink-0">
                Filtra
            </button>
            @if($search)
                <a href="{{ route('admin.media.index', array_filter(['type' => $typeFilter])) }}" class="px-2 py-1 text-xs text-slate-400 hover:text-slate-700">Reset</a>
            @endif
        </form>

        <div class="text-xs text-slate-400 font-medium">
            Trovati <strong>{{ $mediaFiles->total() }}</strong> elementi
        </div>
    </div>

    <!-- Media Grid -->
    @if($mediaFiles->isEmpty())
        <div class="bg-white rounded-2xl border border-slate-200 p-12 text-center space-y-3 shadow-2xs">
            <div class="text-4xl">📂</div>
            <h3 class="text-base font-extrabold text-slate-900">Nessun file presente in questa sezione</h3>
            <p class="text-xs text-slate-500 max-w-md mx-auto">
                Carica badge per i corsi o video per ottenere istantaneamente il link diretto da inserire nella piattaforma.
            </p>
            <button @click="uploadModal = true" class="mt-2 px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold cursor-pointer">
                Carica Primo File
            </button>
        </div>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
            @foreach($mediaFiles as $media)
                <div class="bg-white rounded-2xl border border-slate-200/90 shadow-2xs hover:border-slate-300 transition flex flex-col justify-between overflow-hidden group">
                    <!-- Preview Box -->
                    <div class="h-40 bg-gradient-to-br from-slate-50 to-slate-100 flex items-center justify-center p-3 relative border-b border-slate-100 overflow-hidden">
                        @if($media->media_type === 'badge' || str_starts_with($media->mime_type ?? '', 'image/'))
                            <img src="{{ $media->public_url }}" alt="{{ $media->title }}" class="max-h-full max-w-full object-contain group-hover:scale-105 transition-transform duration-200 drop-shadow-2xs">
                        @elseif($media->media_type === 'video' || str_starts_with($media->mime_type ?? '', 'video/'))
                            <div class="flex flex-col items-center justify-center text-indigo-600">
                                <span class="text-4xl">🎬</span>
                                <span class="text-[10px] font-bold mt-1 text-slate-500">Video MP4</span>
                            </div>
                        @elseif(str_contains($media->mime_type ?? '', 'pdf'))
                            <div class="flex flex-col items-center justify-center text-rose-600">
                                <span class="text-4xl">📄</span>
                                <span class="text-[10px] font-bold mt-1 text-slate-500">Documento PDF</span>
                            </div>
                        @else
                            <div class="flex flex-col items-center justify-center text-slate-400">
                                <span class="text-4xl">📦</span>
                                <span class="text-[10px] font-bold mt-1 text-slate-500">File Media</span>
                            </div>
                        @endif

                        <span class="absolute top-2.5 left-2.5 px-2 py-0.5 rounded-md bg-slate-900/80 backdrop-blur-xs text-white text-[10px] font-bold uppercase tracking-wider">
                            {{ $media->media_type }}
                        </span>

                        <span class="absolute top-2.5 right-2.5 px-2 py-0.5 rounded-md bg-white/90 backdrop-blur-xs text-slate-700 text-[10px] font-bold shadow-2xs">
                            {{ $media->formatted_size }}
                        </span>
                    </div>

                    <!-- Meta & Actions -->
                    <div class="p-4 space-y-3 flex-1 flex flex-col justify-between">
                        <div>
                            <h4 class="font-extrabold text-xs text-slate-900 truncate" title="{{ $media->title }}">
                                {{ $media->title }}
                            </h4>
                            <div class="text-[11px] text-slate-400 mt-0.5 flex items-center justify-between">
                                <span>{{ $media->created_at->format('d/m/Y H:i') }}</span>
                                <span class="font-mono text-[10px] text-slate-500">{{ Str::afterLast($media->file_path, '.') }}</span>
                            </div>
                        </div>

                        <div class="space-y-2 pt-2 border-t border-slate-100">
                            <!-- Direct Copy Button -->
                            <button type="button"
                                    @click="copyToClipboard('{{ $media->public_url }}', {{ $media->id }})"
                                    class="w-full py-2 px-3 rounded-xl border border-indigo-200 bg-indigo-50/70 hover:bg-indigo-100 text-indigo-700 font-bold text-xs flex items-center justify-center gap-1.5 transition cursor-pointer">
                                <span x-show="copiedId !== {{ $media->id }}">📋 Copia Link Pubblico</span>
                                <span x-cloak x-show="copiedId === {{ $media->id }}" class="text-emerald-700 font-black">✓ Link Copiato!</span>
                            </button>

                            <div class="flex items-center justify-between gap-2 pt-1 text-[11px]">
                                <a href="{{ $media->public_url }}" target="_blank" class="text-slate-500 hover:text-indigo-600 font-bold flex items-center gap-1">
                                    <span>Apri File &nearr;</span>
                                </a>

                                <form method="POST" action="{{ route('admin.media.destroy', $media) }}"
                                      onsubmit="return confirm('Sei sicuro di voler eliminare questo file? I corsi che utilizzano questo URL non potranno più caricarlo.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-rose-600 hover:text-rose-800 font-bold cursor-pointer">
                                        Elimina
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-6">
            {{ $mediaFiles->links() }}
        </div>
    @endif

    <!-- Upload Modal -->
    <div x-cloak x-show="uploadModal" class="fixed inset-0 z-50 bg-slate-950/70 backdrop-blur-xs flex items-center justify-center p-4 overflow-y-auto">
        <div @click.away="uploadModal = false"
             x-data="{
                fileObj: null,
                previewUrl: null,
                type: 'badge',
                onFileChange(e) {
                    const f = e.target.files[0];
                    if (f) {
                        this.fileObj = f;
                        if (f.type.startsWith('image/')) {
                            this.previewUrl = URL.createObjectURL(f);
                        } else {
                            this.previewUrl = null;
                        }
                    }
                }
             }"
             class="bg-white rounded-2xl max-w-lg w-full p-6 sm:p-8 shadow-2xl border border-slate-200 my-8">
            <div class="flex items-center justify-between border-b border-slate-200 pb-4 mb-5">
                <div>
                    <h3 class="text-lg font-black text-slate-900">Carica File nella Libreria Media</h3>
                    <p class="text-xs text-slate-500">I file caricati ricevono un URL pubblico permanente riutilizzabile.</p>
                </div>
                <button @click="uploadModal = false" class="text-slate-400 hover:text-slate-700 text-xl font-bold cursor-pointer">&times;</button>
            </div>

            <form method="POST" action="{{ route('admin.media.store') }}" enctype="multipart/form-data" class="space-y-4 text-xs">
                @csrf

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Tipologia Media *</label>
                    <select name="media_type" x-model="type" class="w-full rounded-xl border border-slate-300 px-3 py-2 bg-white">
                        <option value="badge">Badge / Copertina Corso (PNG, JPG, SVG, WEBP)</option>
                        <option value="video">File Video Modulo (MP4, MOV, WEBM)</option>
                        <option value="document">Dispensa / Documento (PDF, DOCX, ZIP)</option>
                        <option value="other">Altro / Risorsa Generica</option>
                    </select>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Titolo / Nome Descrittivo (opzionale)</label>
                    <input type="text" name="title" placeholder="Es. Badge Master Turismo Culturale 2026" class="w-full rounded-xl border border-slate-300 px-3 py-2">
                    <span class="text-[10px] text-slate-400">Se lasciato vuoto, verrà utilizzato il nome originale del file.</span>
                </div>

                <div class="p-4 rounded-xl border-2 border-dashed border-slate-300 bg-slate-50 text-center hover:bg-slate-100/70 transition">
                    <template x-if="previewUrl">
                        <div class="w-24 h-24 mx-auto mb-3 rounded-xl bg-white border border-slate-200 overflow-hidden flex items-center justify-center p-1 shadow-2xs">
                            <img :src="previewUrl" alt="Preview" class="max-h-full max-w-full object-contain">
                        </div>
                    </template>

                    <label class="block cursor-pointer">
                        <span class="text-3xl block mb-1">📤</span>
                        <span class="font-bold text-indigo-600 hover:underline block text-xs">Seleziona un file dal computer</span>
                        <span class="text-[11px] text-slate-400 block mt-1">Dimensione massima supportata: fino a 500 MB</span>
                        <input type="file" name="file" @change="onFileChange" required class="hidden">
                    </label>

                    <template x-if="fileObj">
                        <div class="mt-3 text-slate-700 font-bold text-[11px] bg-white border border-slate-200 py-1 px-3 rounded-lg inline-block">
                            File selezionato: <span x-text="fileObj.name"></span>
                        </div>
                    </template>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-200">
                    <button type="button" @click="uploadModal = false" class="px-4 py-2 rounded-xl border border-slate-300 font-bold text-slate-700 cursor-pointer">Annulla</button>
                    <button type="submit" class="px-5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold cursor-pointer transition">
                        Carica & Ricava Link &rarr;
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
