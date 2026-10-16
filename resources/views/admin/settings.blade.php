@extends('layouts.app')

@section('title', 'Impostazioni White-Label Ente — Pannello Admin')

@section('content')
<div class="max-w-5xl mx-auto py-8 px-4 sm:px-6 lg:px-8"
     x-data="{
        platformName: @js($settings['platform_name'] ?? 'FormaFAD LMS'),
        providerName: @js($settings['provider_name'] ?? 'Ente di Formazione Accreditato'),
        logoPreview: @js($settings['logo_url'] ?? ''),
        removeLogo: false,
        primaryColor: @js($settings['primary_color'] ?? '#4f46e5'),
        secondaryColor: @js($settings['secondary_color'] ?? '#0f172a'),
        accentColor: @js($settings['accent_color'] ?? '#10b981'),
        presets: [
            { name: 'Indigo FormaFAD', primary: '#4f46e5', secondary: '#0f172a', accent: '#10b981' },
            { name: 'Blu Camera di Commercio', primary: '#0284c7', secondary: '#0c4a6e', accent: '#059669' },
            { name: 'Smeraldo Turismo', primary: '#0d9488', secondary: '#134e4a', accent: '#16a34a' },
            { name: 'Bordeaux Accademia', primary: '#be123c', secondary: '#4c0519', accent: '#d97706' },
            { name: 'Blu & Oro Ministeriale', primary: '#1d4ed8', secondary: '#172554', accent: '#ca8a04' }
        ],
        applyPreset(p) {
            this.primaryColor = p.primary;
            this.secondaryColor = p.secondary;
            this.accentColor = p.accent;
            document.documentElement.style.setProperty('--brand-primary', p.primary);
            document.documentElement.style.setProperty('--brand-secondary', p.secondary);
            document.documentElement.style.setProperty('--brand-accent', p.accent);
        },
        updateCssVars() {
            document.documentElement.style.setProperty('--brand-primary', this.primaryColor);
            document.documentElement.style.setProperty('--brand-secondary', this.secondaryColor);
            document.documentElement.style.setProperty('--brand-accent', this.accentColor);
        },
        onLogoSelected(event) {
            const file = event.target.files[0];
            if (!file) return;
            this.removeLogo = false;
            const reader = new FileReader();
            reader.onload = (e) => { this.logoPreview = e.target.result; };
            reader.readAsDataURL(file);
        }
     }">

    <div class="bg-white rounded-2xl border border-slate-200 p-6 sm:p-8 shadow-2xs space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-100 pb-5">
            <div>
                <span class="text-xs font-bold uppercase tracking-wider text-indigo-600">Personalizzazione SaaS / White-Label</span>
                <h1 class="text-2xl font-extrabold text-slate-900 mt-0.5">Logo Aziendale, Colori Brand & Dati Ente Erogatore</h1>
                <p class="text-xs text-slate-500 mt-1">
                    Carica il logo del tuo Ente, scegli la palette colori e imposta i dati ministeriali: le modifiche si applicano istantaneamente a tutta la piattaforma e agli Attestati PDF (Allegato 3 e Allegato 4).
                </p>
            </div>
        </div>

        <!-- LIVE PREVIEW BAR -->
        <div class="rounded-2xl border border-slate-200 overflow-hidden shadow-xs">
            <div class="px-4 py-2 text-[11px] font-bold uppercase tracking-wider text-white flex items-center justify-between"
                 :style="'background-color: ' + secondaryColor">
                <span>Anteprima Live Intestazione & Colori Brand</span>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold text-white" :style="'background-color: ' + accentColor">
                    ✓ Attestato Sbloccato (80%)
                </span>
            </div>
            <div class="p-4 bg-white flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <template x-if="logoPreview && !removeLogo">
                        <img :src="logoPreview" alt="Logo Preview" class="h-12 w-auto max-w-[180px] object-contain rounded-xl border border-slate-200 bg-white p-1">
                    </template>
                    <template x-if="!logoPreview || removeLogo">
                        <div class="w-11 h-11 rounded-xl text-white flex items-center justify-center font-extrabold text-base shadow-xs"
                             :style="'background-color: ' + primaryColor">
                            FAD
                        </div>
                    </template>
                    <div>
                        <div class="font-extrabold text-slate-900 text-base" x-text="platformName"></div>
                        <div class="text-xs text-slate-500" x-text="providerName"></div>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <span class="px-3.5 py-2 rounded-xl text-white text-xs font-bold shadow-2xs"
                          :style="'background-color: ' + primaryColor">
                        Pulsante Primario Brand
                    </span>
                    <span class="px-3.5 py-2 rounded-xl text-white text-xs font-bold shadow-2xs"
                          :style="'background-color: ' + secondaryColor">
                        Sfondo Istituzionale
                    </span>
                </div>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data" class="space-y-6 text-xs">
            @csrf

            <!-- SEZIONE 1: LOGO AZIENDALE & COLORI BASE -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 p-5 rounded-2xl bg-slate-50 border border-slate-200">
                <!-- Caricamento Logo -->
                <div class="lg:col-span-5 space-y-3">
                    <div class="font-extrabold text-sm text-slate-900 flex items-center gap-2">
                        <span>1. Logo Aziendale / Ente Accreditato</span>
                    </div>
                    <p class="text-[11px] text-slate-500">
                        Carica il logo ufficiale (PNG, JPG, SVG o WEBP con sfondo trasparente consigliato). Verrà mostrato nella barra di navigazione, nel login e negli attestati.
                    </p>

                    <div class="p-4 rounded-xl bg-white border border-dashed border-slate-300 flex flex-col items-center justify-center text-center gap-2">
                        <template x-if="logoPreview && !removeLogo">
                            <div class="space-y-2">
                                <img :src="logoPreview" alt="Logo" class="h-14 mx-auto object-contain">
                                <label class="inline-flex items-center gap-1.5 text-[11px] text-red-600 font-bold cursor-pointer">
                                    <input type="checkbox" name="remove_logo" value="1" x-model="removeLogo" class="rounded text-red-600">
                                    <span>Rimuovi logo e usa icona testo "FAD"</span>
                                </label>
                            </div>
                        </template>
                        <template x-if="!logoPreview || removeLogo">
                            <div class="text-slate-400 text-[11px] py-2">Nessun logo grafico caricato (attiva icona badge "FAD")</div>
                        </template>

                        <label class="mt-1 inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs cursor-pointer transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                            <span>Scegli File Logo dal Dispositivo...</span>
                            <input type="file" name="logo_file" accept="image/png,image/jpeg,image/svg+xml,image/webp" @change="onLogoSelected($event)" class="hidden">
                        </label>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-600 mb-1 text-[11px]">Oppure incolla URL diretto del Logo (opzionale)</label>
                        <input type="text" name="logo_url" x-model="logoPreview" placeholder="https://.../logo-ente.png" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-1.5 text-xs">
                    </div>
                </div>

                <!-- Scelta Colori Base & Palette Rapide -->
                <div class="lg:col-span-7 space-y-4">
                    <div class="font-extrabold text-sm text-slate-900">
                        2. Colori Base del Brand (Personalizzazione Tema)
                    </div>
                    <p class="text-[11px] text-slate-500">
                        Scegli una delle palette istituzionali preimpostate con 1 click oppure seleziona i codici colore esatti del tuo Ente tramite il selettore colore.
                    </p>

                    <!-- Preset Buttons -->
                    <div>
                        <div class="text-[11px] font-bold text-slate-600 mb-1.5">Palette Istituzionali Pronte (1-Click):</div>
                        <div class="flex flex-wrap gap-2">
                            <template x-for="p in presets" :key="p.name">
                                <button type="button"
                                        @click="applyPreset(p)"
                                        class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl bg-white hover:bg-slate-100 border border-slate-200 shadow-2xs text-[11px] font-bold text-slate-800 cursor-pointer transition">
                                    <span class="flex items-center gap-0.5">
                                        <span class="w-3 h-3 rounded-full" :style="'background-color:' + p.primary"></span>
                                        <span class="w-3 h-3 rounded-full" :style="'background-color:' + p.secondary"></span>
                                        <span class="w-3 h-3 rounded-full" :style="'background-color:' + p.accent"></span>
                                    </span>
                                    <span x-text="p.name"></span>
                                </button>
                            </template>
                        </div>
                    </div>

                    <!-- 3 Color Pickers -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-1">
                        <div class="p-3 rounded-xl bg-white border border-slate-200">
                            <label class="block font-bold text-slate-700 text-[11px] mb-1.5">Colore Primario (Bottoni & Link)</label>
                            <div class="flex items-center gap-2">
                                <input type="color" x-model="primaryColor" @input="updateCssVars()" class="w-9 h-9 rounded-lg border border-slate-300 cursor-pointer p-0.5 bg-white shrink-0">
                                <input type="text" name="primary_color" x-model="primaryColor" @input="updateCssVars()" class="w-full rounded-lg border border-slate-300 px-2.5 py-1.5 font-mono text-xs uppercase">
                            </div>
                        </div>

                        <div class="p-3 rounded-xl bg-white border border-slate-200">
                            <label class="block font-bold text-slate-700 text-[11px] mb-1.5">Colore Secondario (Header & Box)</label>
                            <div class="flex items-center gap-2">
                                <input type="color" x-model="secondaryColor" @input="updateCssVars()" class="w-9 h-9 rounded-lg border border-slate-300 cursor-pointer p-0.5 bg-white shrink-0">
                                <input type="text" name="secondary_color" x-model="secondaryColor" @input="updateCssVars()" class="w-full rounded-lg border border-slate-300 px-2.5 py-1.5 font-mono text-xs uppercase">
                            </div>
                        </div>

                        <div class="p-3 rounded-xl bg-white border border-slate-200">
                            <label class="block font-bold text-slate-700 text-[11px] mb-1.5">Colore Accento (Badge & Idoneità)</label>
                            <div class="flex items-center gap-2">
                                <input type="color" x-model="accentColor" @input="updateCssVars()" class="w-9 h-9 rounded-lg border border-slate-300 cursor-pointer p-0.5 bg-white shrink-0">
                                <input type="text" name="accent_color" x-model="accentColor" @input="updateCssVars()" class="w-full rounded-lg border border-slate-300 px-2.5 py-1.5 font-mono text-xs uppercase">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SEZIONE 2: DATI ENTE EROGATORE E INTESTAZIONE ATTESTATI -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Nome Piattaforma (Header & Titoli) *</label>
                    <input type="text" name="platform_name" x-model="platformName" required class="w-full rounded-xl border border-slate-300 px-3.5 py-2">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Email Segreteria / Supporto Didattico *</label>
                    <input type="email" name="support_email" value="{{ $settings['support_email'] }}" required class="w-full rounded-xl border border-slate-300 px-3.5 py-2">
                </div>
            </div>

            <div>
                <label class="block font-bold text-slate-700 mb-1">Sottotitolo Descrittivo Piattaforma</label>
                <input type="text" name="platform_subtitle" value="{{ $settings['platform_subtitle'] }}" class="w-full rounded-xl border border-slate-300 px-3.5 py-2">
            </div>

            <div class="pt-4 border-t border-slate-200 grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Denominazione Completa Ente di Formazione (per Attestati) *</label>
                    <input type="text" name="provider_name" x-model="providerName" required class="w-full rounded-xl border border-slate-300 px-3.5 py-2">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Rappresentante Legale / Firmatario Attestati *</label>
                    <input type="text" name="provider_legal_rep" value="{{ $settings['provider_legal_rep'] }}" required class="w-full rounded-xl border border-slate-300 px-3.5 py-2">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="sm:col-span-1">
                    <label class="block font-bold text-slate-700 mb-1">Sede Legale / Operativa Ente</label>
                    <input type="text" name="provider_address" value="{{ $settings['provider_address'] }}" class="w-full rounded-xl border border-slate-300 px-3.5 py-2">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Codice ATECO (Linee Guida B) *</label>
                    <input type="text" name="ateco_code" value="{{ $settings['ateco_code'] }}" required class="w-full rounded-xl border border-slate-300 px-3.5 py-2 font-mono">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Codice CP ISTAT (Linee Guida B) *</label>
                    <input type="text" name="cp_istat_code" value="{{ $settings['cp_istat_code'] }}" required class="w-full rounded-xl border border-slate-300 px-3.5 py-2 font-mono">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                <label class="flex items-center gap-2.5 p-3.5 rounded-xl border border-slate-200 bg-slate-50">
                    <input type="checkbox" name="allow_public_registration" value="1" {{ ($settings['allow_public_registration'] ?? '1') === '1' ? 'checked' : '' }} class="rounded text-indigo-600">
                    <span><strong>Consenti Auto-Registrazione Pubblica</strong> tramite pagina <code>/register</code></span>
                </label>
                <label class="flex items-center gap-2.5 p-3.5 rounded-xl border border-slate-200 bg-slate-50">
                    <input type="checkbox" name="auto_approve_registration" value="1" {{ ($settings['auto_approve_registration'] ?? '1') === '1' ? 'checked' : '' }} class="rounded text-indigo-600">
                    <span><strong>Abilita automaticamente i nuovi iscritti</strong> senza attesa approvazione manuale</span>
                </label>
            </div>

            <div class="flex justify-end pt-4 border-t border-slate-200">
                <button type="submit" class="px-6 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold cursor-pointer shadow-xs transition">
                    Salva Logo, Colori e Configurazione White-Label
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
