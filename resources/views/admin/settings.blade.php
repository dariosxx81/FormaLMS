@extends('layouts.app')

@section('title', 'Impostazioni White-Label Ente — Pannello Admin')

@section('content')
<div class="max-w-5xl mx-auto py-8 px-4 sm:px-6 lg:px-8"
     x-data="{
        platformName: @js($settings['platform_name'] ?? 'FormaFAD LMS'),
        providerName: @js($settings['provider_name'] ?? 'Ente di Formazione Accreditato'),
        logoPreview: @js($settings['logo_url'] ?? ''),
        logoBg: @js($settings['logo_bg'] ?? 'white'),
        logoSize: @js($settings['logo_size'] ?? 'md'),
        removeLogo: false,
        primaryColor: @js($settings['primary_color'] ?? '#4f46e5'),
        secondaryColor: @js($settings['secondary_color'] ?? '#0f172a'),
        accentColor: @js($settings['accent_color'] ?? '#10b981'),
        presets: [
            { name: 'Indigo FormaFAD', primary: '#4f46e5', secondary: '#0f172a', accent: '#10b981' },
            { name: 'Blu Camera di Commercio', primary: '#0284c7', secondary: '#0c4a6e', accent: '#059669' },
            { name: 'Smeraldo Turismo', primary: '#0d9488', secondary: '#134e4a', accent: '#16a34a' },
            { name: 'Bordeaux Accademia', primary: '#be123c', secondary: '#4c0519', accent: '#d97706' },
            { name: 'Oro Chiaro & Blu (Test Contrasto Scuro)', primary: '#facc15', secondary: '#172554', accent: '#10b981' }
        ],
        hexToRgb(hex) {
            const clean = (hex || '#4f46e5').replace('#', '');
            if (clean.length !== 6) return [79, 70, 229];
            return [
                parseInt(clean.substring(0, 2), 16) || 0,
                parseInt(clean.substring(2, 4), 16) || 0,
                parseInt(clean.substring(4, 6), 16) || 0
            ];
        },
        getContrastText(hex) {
            const [r, g, b] = this.hexToRgb(hex);
            const yiq = ((r * 299) + (g * 587) + (b * 114)) / 1000;
            return yiq >= 155 ? '#0f172a' : '#ffffff';
        },
        getReadableOnLight(hex) {
            const [r, g, b] = this.hexToRgb(hex);
            const yiq = ((r * 299) + (g * 587) + (b * 114)) / 1000;
            if (yiq <= 165) return hex;
            const dr = Math.max(0, Math.round(r * 0.50)).toString(16).padStart(2, '0');
            const dg = Math.max(0, Math.round(g * 0.50)).toString(16).padStart(2, '0');
            const db = Math.max(0, Math.round(b * 0.50)).toString(16).padStart(2, '0');
            return '#' + dr + dg + db;
        },
        getRgba(hex, alpha) {
            const [r, g, b] = this.hexToRgb(hex);
            return `rgba(${r}, ${g}, ${b}, ${alpha})`;
        },
        applyPreset(p) {
            this.primaryColor = p.primary;
            this.secondaryColor = p.secondary;
            this.accentColor = p.accent;
            this.updateCssVars();
        },
        updateCssVars() {
            const root = document.documentElement.style;
            root.setProperty('--brand-primary', this.primaryColor);
            root.setProperty('--brand-primary-contrast', this.getContrastText(this.primaryColor));
            root.setProperty('--brand-primary-readable', this.getReadableOnLight(this.primaryColor));
            root.setProperty('--brand-primary-soft', this.getRgba(this.primaryColor, 0.10));
            root.setProperty('--brand-primary-border', this.getRgba(this.primaryColor, 0.26));

            root.setProperty('--brand-secondary', this.secondaryColor);
            root.setProperty('--brand-secondary-contrast', this.getContrastText(this.secondaryColor));

            root.setProperty('--brand-accent', this.accentColor);
            root.setProperty('--brand-accent-contrast', this.getContrastText(this.accentColor));
            root.setProperty('--brand-accent-readable', this.getReadableOnLight(this.accentColor));
            root.setProperty('--brand-accent-soft', this.getRgba(this.accentColor, 0.12));
            root.setProperty('--brand-accent-border', this.getRgba(this.accentColor, 0.28));
        },
        onLogoSelected(event) {
            const file = event.target.files[0];
            if (!file) return;
            this.removeLogo = false;
            const reader = new FileReader();
            reader.onload = (e) => { this.logoPreview = e.target.result; };
            reader.readAsDataURL(file);
        },
        setDemoLogo(mode) {
            this.removeLogo = false;
            if (mode === 'white') {
                this.logoBg = 'dark';
                const svg = `<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 230 56' width='230' height='56'><circle cx='28' cy='28' r='18' fill='#38bdf8'/><path d='M20 28l5 5 11-11' stroke='#0f172a' stroke-width='3.5' fill='none' stroke-linecap='round'/><text x='54' y='25' font-family='Plus Jakarta Sans, sans-serif' font-size='15' font-weight='800' fill='#ffffff'>FORMACAMERA</text><text x='54' y='42' font-family='Plus Jakarta Sans, sans-serif' font-size='10' font-weight='600' fill='#cbd5e1'>LOGO BIANCO SU SFONDO SCURO</text></svg>`;
                this.logoPreview = 'data:image/svg+xml;utf8,' + encodeURIComponent(svg);
            } else {
                this.logoBg = 'white';
                const svg = `<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 230 56' width='230' height='56'><circle cx='28' cy='28' r='18' fill='${this.primaryColor}'/><path d='M20 28l5 5 11-11' stroke='${this.getContrastText(this.primaryColor)}' stroke-width='3.5' fill='none' stroke-linecap='round'/><text x='54' y='25' font-family='Plus Jakarta Sans, sans-serif' font-size='15' font-weight='800' fill='#0f172a'>ENTE FORMAZIONE</text><text x='54' y='42' font-family='Plus Jakarta Sans, sans-serif' font-size='10' font-weight='700' fill='#475569'>ACCREDITATO MINISTERO</text></svg>`;
                this.logoPreview = 'data:image/svg+xml;utf8,' + encodeURIComponent(svg);
            }
        }
     }">

    <div class="bg-white rounded-2xl border border-slate-200 p-6 sm:p-8 shadow-2xs space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-100 pb-5">
            <div>
                <span class="text-xs font-bold uppercase tracking-wider text-indigo-600">Personalizzazione SaaS / White-Label con Contrasto Automatico WCAG</span>
                <h1 class="text-2xl font-extrabold text-slate-900 mt-0.5">Logo Aziendale, Colori Brand & Dati Ente Erogatore</h1>
                <p class="text-xs text-slate-500 mt-1">
                    Il motore grafico calcola automaticamente il contrasto del testo (bianco/nero), genera tinte morbide al 10% per i badge e mantiene il Video Player sempre in tema scuro ad alto contrasto.
                </p>
            </div>
        </div>

        <!-- LIVE MULTI-ELEMENT CONTRAST PREVIEW -->
        <div class="rounded-2xl border border-slate-200 overflow-hidden shadow-xs">
            <!-- 1. Top Institutional Bar Preview -->
            <div class="px-4 py-2 text-[11px] font-bold flex flex-wrap items-center justify-between gap-2"
                 :style="'background-color: ' + secondaryColor + '; color: ' + getContrastText(secondaryColor)">
                <span>1. Barra Istituzionale (Contrasto Testo Automatico: <strong x-text="getContrastText(secondaryColor) === '#ffffff' ? 'Bianco' : 'Scuro'"></strong>)</span>
                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold"
                      :style="'background-color: ' + accentColor + '; color: ' + getContrastText(accentColor)">
                    ✓ Attestato Sbloccato (80%)
                </span>
            </div>

            <!-- 2. Main Header + Buttons + 10% Soft Badge + Protected Dark Player Preview -->
            <div class="p-4 sm:p-5 bg-white space-y-4">
                <div class="flex flex-wrap items-center justify-between gap-4 pb-4 border-b border-slate-100">
                    <div class="flex items-center gap-3">
                        <template x-if="logoPreview && !removeLogo">
                            <img :src="logoPreview"
                                 alt="Logo Preview"
                                 :class="{
                                    'h-8 max-w-[140px]': logoSize === 'sm',
                                    'h-11 max-w-[180px]': logoSize === 'md',
                                    'h-14 max-w-[230px]': logoSize === 'lg',
                                    'bg-white border border-slate-200 p-1 shadow-2xs': logoBg === 'white',
                                    'bg-slate-900 border border-slate-700 p-1.5 shadow-xs': logoBg === 'dark',
                                    'bg-transparent p-0.5': logoBg === 'transparent'
                                 }"
                                 class="w-auto object-contain rounded-xl transition">
                        </template>
                        <template x-if="!logoPreview || removeLogo">
                            <div class="w-11 h-11 rounded-xl flex items-center justify-center font-extrabold text-base shadow-xs"
                                 :style="'background-color: ' + primaryColor + '; color: ' + getContrastText(primaryColor)">
                                FAD
                            </div>
                        </template>
                        <div>
                            <div class="font-extrabold text-slate-900 text-base" x-text="platformName"></div>
                            <div class="text-xs text-slate-500" x-text="providerName"></div>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <span class="px-3.5 py-2 rounded-xl text-xs font-bold shadow-2xs"
                              :style="'background-color: ' + primaryColor + '; color: ' + getContrastText(primaryColor)">
                            Bottone Primario (Testo Auto-Contrasto)
                        </span>
                        <span class="px-3 py-1.5 rounded-xl text-xs font-bold border"
                              :style="'background-color: ' + getRgba(primaryColor, 0.10) + '; border-color: ' + getRgba(primaryColor, 0.28) + '; color: ' + getReadableOnLight(primaryColor)">
                            Badge Tinta 10% (Sempre Leggibile)
                        </span>
                    </div>
                </div>

                <!-- 3. Protected Dark Video Player Preview -->
                <div class="bg-slate-900 text-white rounded-xl p-3.5 flex flex-wrap items-center justify-between gap-3 text-xs border border-slate-800">
                    <div class="flex items-center gap-2">
                        <span class="px-2 py-0.5 rounded bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 font-bold text-[10px]">
                            🛡 PLAYER VIDEO PROTETTO
                        </span>
                        <span class="text-slate-200 font-semibold">Il Player Video e i box telemetria restano sempre in tema scuro ad alto contrasto</span>
                    </div>
                    <div class="w-36 h-2 bg-slate-800 rounded-full overflow-hidden">
                        <div class="h-full w-3/4 rounded-full" :style="'background-color: ' + primaryColor"></div>
                    </div>
                </div>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data" class="space-y-6 text-xs">
            @csrf

            <!-- SEZIONE 1: LOGO AZIENDALE & OPZIONI SFONDO / DIMENSIONE -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 p-5 rounded-2xl bg-slate-50 border border-slate-200">
                <!-- Caricamento Logo + Opzioni Box -->
                <div class="lg:col-span-5 space-y-3.5">
                    <div class="font-extrabold text-sm text-slate-900">
                        1. Logo Aziendale & Adattamento Visivo
                    </div>
                    <p class="text-[11px] text-slate-500">
                        Carica il tuo logo e scegli lo sfondo del riquadro (usa <strong>Sfondo Scuro</strong> se il tuo logo ha scritte bianche/chiare) e la grandezza.
                    </p>

                    <div class="p-4 rounded-xl bg-white border border-dashed border-slate-300 flex flex-col items-center justify-center text-center gap-2.5">
                        <template x-if="logoPreview && !removeLogo">
                            <div class="space-y-2">
                                <div class="inline-block rounded-xl"
                                     :class="{
                                        'bg-white border border-slate-200 p-2': logoBg === 'white',
                                        'bg-slate-900 border border-slate-700 p-2': logoBg === 'dark',
                                        'bg-slate-100 p-2': logoBg === 'transparent'
                                     }">
                                    <img :src="logoPreview" alt="Logo" class="h-12 mx-auto object-contain">
                                </div>
                                <div>
                                    <label class="inline-flex items-center gap-1.5 text-[11px] text-red-600 font-bold cursor-pointer">
                                        <input type="checkbox" name="remove_logo" value="1" x-model="removeLogo" class="rounded text-red-600">
                                        <span>Rimuovi logo e usa icona testo "FAD"</span>
                                    </label>
                                </div>
                            </div>
                        </template>
                        <template x-if="!logoPreview || removeLogo">
                            <div class="text-slate-400 text-[11px] py-1">Nessun logo grafico caricato (attiva icona badge "FAD")</div>
                        </template>

                        <div class="flex flex-wrap items-center justify-center gap-1.5">
                            <label class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 font-bold text-xs cursor-pointer transition">
                                <span>📁 Carica File Logo...</span>
                                <input type="file" name="logo_file" accept="image/png,image/jpeg,image/svg+xml,image/webp" @change="onLogoSelected($event)" class="hidden">
                            </label>
                            <button type="button" @click="setDemoLogo('dark')" class="px-2.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-300 font-bold text-[11px] cursor-pointer">
                                Demo Logo Scuro
                            </button>
                            <button type="button" @click="setDemoLogo('white')" class="px-2.5 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-white font-bold text-[11px] cursor-pointer">
                                Demo Logo Bianco
                            </button>
                        </div>
                    </div>

                    <!-- Selettori Sfondo Box Logo & Altezza Logo -->
                    <div class="grid grid-cols-2 gap-2.5">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1 text-[11px]">Sfondo Box Logo</label>
                            <select name="logo_bg" x-model="logoBg" class="w-full rounded-xl border border-slate-300 bg-white px-2.5 py-2 text-xs font-bold">
                                <option value="white">Chiaro (Bianco)</option>
                                <option value="dark">Scuro (Per loghi bianchi)</option>
                                <option value="transparent">Trasparente</option>
                            </select>
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1 text-[11px]">Dimensione Logo</label>
                            <select name="logo_size" x-model="logoSize" class="w-full rounded-xl border border-slate-300 bg-white px-2.5 py-2 text-xs font-bold">
                                <option value="sm">Compatto (32px)</option>
                                <option value="md">Standard (44px)</option>
                                <option value="lg">Grande (56px)</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-600 mb-1 text-[11px]">Oppure URL diretto del Logo</label>
                        <input type="text" name="logo_url" x-model="logoPreview" placeholder="https://.../logo-ente.png" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-1.5 text-xs">
                    </div>
                </div>

                <!-- Scelta Colori Base & Palette Rapide -->
                <div class="lg:col-span-7 space-y-4">
                    <div class="font-extrabold text-sm text-slate-900">
                        2. Colori Base del Brand (Con Protezione Leggibilità Automatica)
                    </div>
                    <p class="text-[11px] text-slate-500">
                        Anche se scegli un colore molto chiaro (es. Giallo Oro), il sistema imposta automaticamente il testo scuro sui bottoni e scurisce i link su sfondo bianco per garantire sempre una lettura perfetta.
                    </p>

                    <!-- Preset Buttons -->
                    <div>
                        <div class="text-[11px] font-bold text-slate-600 mb-1.5">Palette Istituzionali Testate (1-Click):</div>
                        <div class="flex flex-wrap gap-2">
                            <template x-for="p in presets" :key="p.name">
                                <button type="button"
                                        @click="applyPreset(p)"
                                        class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl bg-white hover:bg-slate-100 border border-slate-200 shadow-2xs text-[11px] font-bold text-slate-800 cursor-pointer transition">
                                    <span class="flex items-center gap-0.5">
                                        <span class="w-3 h-3 rounded-full border border-black/10" :style="'background-color:' + p.primary"></span>
                                        <span class="w-3 h-3 rounded-full border border-black/10" :style="'background-color:' + p.secondary"></span>
                                        <span class="w-3 h-3 rounded-full border border-black/10" :style="'background-color:' + p.accent"></span>
                                    </span>
                                    <span x-text="p.name"></span>
                                </button>
                            </template>
                        </div>
                    </div>

                    <!-- 3 Color Pickers -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-1">
                        <div class="p-3 rounded-xl bg-white border border-slate-200">
                            <label class="block font-bold text-slate-700 text-[11px] mb-1">Colore Primario (Bottoni & Tab)</label>
                            <div class="flex items-center gap-2">
                                <input type="color" x-model="primaryColor" @input="updateCssVars()" class="w-9 h-9 rounded-lg border border-slate-300 cursor-pointer p-0.5 bg-white shrink-0">
                                <input type="text" name="primary_color" x-model="primaryColor" @input="updateCssVars()" class="w-full rounded-lg border border-slate-300 px-2.5 py-1.5 font-mono text-xs uppercase">
                            </div>
                            <div class="text-[10px] text-slate-400 mt-1">
                                Testo: <strong class="text-slate-700" x-text="getContrastText(primaryColor) === '#ffffff' ? 'Bianco (#FFF)' : 'Scuro (#0F172A)'"></strong>
                            </div>
                        </div>

                        <div class="p-3 rounded-xl bg-white border border-slate-200">
                            <label class="block font-bold text-slate-700 text-[11px] mb-1">Barra Superiore Ente</label>
                            <div class="flex items-center gap-2">
                                <input type="color" x-model="secondaryColor" @input="updateCssVars()" class="w-9 h-9 rounded-lg border border-slate-300 cursor-pointer p-0.5 bg-white shrink-0">
                                <input type="text" name="secondary_color" x-model="secondaryColor" @input="updateCssVars()" class="w-full rounded-lg border border-slate-300 px-2.5 py-1.5 font-mono text-xs uppercase">
                            </div>
                            <div class="text-[10px] text-slate-400 mt-1">
                                Testo: <strong class="text-slate-700" x-text="getContrastText(secondaryColor) === '#ffffff' ? 'Bianco (#FFF)' : 'Scuro (#0F172A)'"></strong>
                            </div>
                        </div>

                        <div class="p-3 rounded-xl bg-white border border-slate-200">
                            <label class="block font-bold text-slate-700 text-[11px] mb-1">Colore Accento (Attestati)</label>
                            <div class="flex items-center gap-2">
                                <input type="color" x-model="accentColor" @input="updateCssVars()" class="w-9 h-9 rounded-lg border border-slate-300 cursor-pointer p-0.5 bg-white shrink-0">
                                <input type="text" name="accent_color" x-model="accentColor" @input="updateCssVars()" class="w-full rounded-lg border border-slate-300 px-2.5 py-1.5 font-mono text-xs uppercase">
                            </div>
                            <div class="text-[10px] text-slate-400 mt-1">
                                Testo: <strong class="text-slate-700" x-text="getContrastText(accentColor) === '#ffffff' ? 'Bianco (#FFF)' : 'Scuro (#0F172A)'"></strong>
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
                <button type="submit" class="px-6 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 font-bold cursor-pointer shadow-xs transition">
                    Salva Logo, Colori e Configurazione White-Label
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
