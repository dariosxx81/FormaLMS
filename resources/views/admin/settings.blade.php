@extends('layouts.app')

@section('title', 'White-Label & Ente — Pannello Admin')

@section('content')
<div class="max-w-5xl mx-auto py-8 px-4 sm:px-6 lg:px-8 space-y-6"
     x-data="{
        settingsTab: 'aspetto',
        platformName: @js($settings['platform_name'] ?? 'FormaFAD LMS'),
        providerName: @js($settings['provider_name'] ?? 'Ente di Formazione Accreditato'),
        logoPreview: @js($settings['logo_url'] ?? ''),
        logoUrlInput: @js($settings['logo_url'] ?? ''),
        logoBg: @js($settings['logo_bg'] ?? 'white'),
        logoSize: @js($settings['logo_size'] ?? 'md'),
        hideBrandText: @js(($settings['hide_brand_text_with_logo'] ?? '0') === '1'),
        removeLogo: false,
        autoHarmonize: true,
        primaryColor: @js($settings['primary_color'] ?? '#4f46e5'),
        secondaryColor: @js($settings['secondary_color'] ?? '#1e1b4b'),
        accentColor: @js($settings['accent_color'] ?? '#10b981'),
        presets: [
            { name: 'Indigo FormaFAD', primary: '#4f46e5', secondary: '#1e1b4b', accent: '#10b981' },
            { name: 'Blu Camera di Commercio', primary: '#0284c7', secondary: '#0c4a6e', accent: '#059669' },
            { name: 'Smeraldo Turismo', primary: '#0d9488', secondary: '#134e4a', accent: '#16a34a' },
            { name: 'Bordeaux Accademia', primary: '#be123c', secondary: '#4c0519', accent: '#d97706' },
            { name: 'Oro Chiaro & Blu', primary: '#facc15', secondary: '#1e3a8a', accent: '#10b981' },
            { name: 'Tema Chiaro Sabbia', primary: '#ea580c', secondary: '#fef3c7', accent: '#059669' }
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
        getYiq(hex) {
            const [r, g, b] = this.hexToRgb(hex);
            return ((r * 299) + (g * 587) + (b * 114)) / 1000;
        },
        getDeepHarmonizedShade(hex, factor = 0.20) {
            const [r, g, b] = this.hexToRgb(hex);
            const dr = Math.max(6, Math.min(55, Math.round(r * factor))).toString(16).padStart(2, '0');
            const dg = Math.max(8, Math.min(55, Math.round(g * factor))).toString(16).padStart(2, '0');
            const db = Math.max(12, Math.min(65, Math.round(b * factor))).toString(16).padStart(2, '0');
            return '#' + dr + dg + db;
        },
        mixHex(hex1, hex2, weight = 0.5) {
            const [r1, g1, b1] = this.hexToRgb(hex1);
            const [r2, g2, b2] = this.hexToRgb(hex2);
            const r = Math.max(0, Math.min(255, Math.round(r1 * (1 - weight) + r2 * weight))).toString(16).padStart(2, '0');
            const g = Math.max(0, Math.min(255, Math.round(g1 * (1 - weight) + g2 * weight))).toString(16).padStart(2, '0');
            const b = Math.max(0, Math.min(255, Math.round(b1 * (1 - weight) + b2 * weight))).toString(16).padStart(2, '0');
            return '#' + r + g + b;
        },
        getContrastText(hex) {
            if (this.getYiq(hex) >= 155) {
                return this.getDeepHarmonizedShade(hex, 0.18);
            }
            return '#ffffff';
        },
        getReadableOnLight(hex) {
            if (this.getYiq(hex) <= 160) return hex;
            const [r, g, b] = this.hexToRgb(hex);
            const dr = Math.max(0, Math.round(r * 0.48)).toString(16).padStart(2, '0');
            const dg = Math.max(0, Math.round(g * 0.48)).toString(16).padStart(2, '0');
            const db = Math.max(0, Math.round(b * 0.48)).toString(16).padStart(2, '0');
            return '#' + dr + dg + db;
        },
        getRgba(hex, alpha) {
            const [r, g, b] = this.hexToRgb(hex);
            return `rgba(${r}, ${g}, ${b}, ${alpha})`;
        },
        onPrimaryColorChanged() {
            if (this.autoHarmonize) {
                this.secondaryColor = this.getDeepHarmonizedShade(this.primaryColor, 0.26);
            }
            this.updateCssVars();
        },
        harmonizeFromPrimary() {
            this.secondaryColor = this.getDeepHarmonizedShade(this.primaryColor, 0.26);
            this.updateCssVars();
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

            const secIsLight = this.getYiq(this.secondaryColor) >= 155;
            const secDeep = this.getDeepHarmonizedShade(this.secondaryColor, 0.18);
            const secMuted = secIsLight
                ? this.mixHex(this.secondaryColor, secDeep, 0.78)
                : this.mixHex(this.secondaryColor, '#ffffff', 0.78);
            const secSurface = secIsLight
                ? this.getRgba(secDeep, 0.08)
                : 'rgba(255, 255, 255, 0.11)';
            const secBorder = secIsLight
                ? this.getRgba(secDeep, 0.18)
                : 'rgba(255, 255, 255, 0.20)';
            const secHighlight = secIsLight
                ? this.getReadableOnLight(this.primaryColor)
                : this.mixHex(this.primaryColor, '#ffffff', 0.62);

            root.setProperty('--brand-secondary', this.secondaryColor);
            root.setProperty('--brand-secondary-contrast', this.getContrastText(this.secondaryColor));
            root.setProperty('--brand-secondary-muted', secMuted);
            root.setProperty('--brand-secondary-surface', secSurface);
            root.setProperty('--brand-secondary-border', secBorder);
            root.setProperty('--brand-secondary-highlight', secHighlight);

            root.setProperty('--brand-accent', this.accentColor);
            root.setProperty('--brand-accent-contrast', this.getContrastText(this.accentColor));
            root.setProperty('--brand-accent-readable', this.getReadableOnLight(this.accentColor));
            root.setProperty('--brand-accent-soft', this.getRgba(this.accentColor, 0.12));
            root.setProperty('--brand-accent-border', this.getRgba(this.accentColor, 0.28));

            root.setProperty('--brand-heading', this.getDeepHarmonizedShade(this.secondaryColor, 0.22));
            root.setProperty('--brand-body', this.getDeepHarmonizedShade(this.secondaryColor, 0.32));
        },
        onLogoSelected(event) {
            const file = event.target.files[0];
            if (!file) return;
            this.removeLogo = false;
            this.logoUrlInput = '';
            const reader = new FileReader();
            reader.onload = (e) => { this.logoPreview = e.target.result; };
            reader.readAsDataURL(file);
        },
        setDemoLogo(mode) {
            this.removeLogo = false;
            if (mode === 'white') {
                this.logoBg = 'dark';
                const svg = `<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 230 56' width='230' height='56'><circle cx='28' cy='28' r='18' fill='#38bdf8'/><path d='M20 28l5 5 11-11' stroke='${this.secondaryColor}' stroke-width='3.5' fill='none' stroke-linecap='round'/><text x='54' y='25' font-family='Plus Jakarta Sans, sans-serif' font-size='15' font-weight='800' fill='#ffffff'>FORMACAMERA</text><text x='54' y='42' font-family='Plus Jakarta Sans, sans-serif' font-size='10' font-weight='600' fill='#cbd5e1'>LOGO BIANCO SU SFONDO BRAND</text></svg>`;
                const uri = 'data:image/svg+xml;utf8,' + encodeURIComponent(svg);
                this.logoPreview = uri;
                this.logoUrlInput = uri;
            } else {
                this.logoBg = 'white';
                const svg = `<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 230 56' width='230' height='56'><circle cx='28' cy='28' r='18' fill='${this.primaryColor}'/><path d='M20 28l5 5 11-11' stroke='${this.getContrastText(this.primaryColor)}' stroke-width='3.5' fill='none' stroke-linecap='round'/><text x='54' y='25' font-family='Plus Jakarta Sans, sans-serif' font-size='15' font-weight='800' fill='${this.getDeepHarmonizedShade(this.primaryColor, 0.22)}'>ENTE FORMAZIONE</text><text x='54' y='42' font-family='Plus Jakarta Sans, sans-serif' font-size='10' font-weight='700' fill='#475569'>ACCREDITATO MINISTERO</text></svg>`;
                const uri = 'data:image/svg+xml;utf8,' + encodeURIComponent(svg);
                this.logoPreview = uri;
                this.logoUrlInput = uri;
            }
        }
     }">

    <!-- Clean Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">White-Label & Configurazione Ente</h1>
            <p class="text-xs text-slate-500 mt-0.5">
                Personalizza logo, colori coordinati, intestazione degli attestati PDF e regole di registrazione.
            </p>
        </div>
    </div>

    <!-- Compact Live Preview Card -->
    <div class="bg-white rounded-2xl border border-slate-200/90 overflow-hidden shadow-2xs">
        <div class="px-5 py-2 text-[11px] font-bold flex flex-wrap items-center justify-between gap-2"
             :style="'background-color: ' + secondaryColor + '; color: ' + getContrastText(secondaryColor)">
            <span>Anteprima Live Brand (Sfondo: <strong class="uppercase font-mono" x-text="secondaryColor"></strong> • Font Accordato: <strong class="uppercase font-mono" x-text="getContrastText(secondaryColor)"></strong>)</span>
            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold"
                  :style="'background-color: ' + accentColor + '; color: ' + getContrastText(accentColor)">
                ✓ Attestato Sbloccato (80%)
            </span>
        </div>

        <div class="p-4 sm:p-5 flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <template x-if="logoPreview && !removeLogo">
                    <img :src="logoPreview"
                         alt="Logo Preview"
                         :class="{
                            'h-8 max-w-[160px]': logoSize === 'sm',
                            'h-11 max-w-[210px]': logoSize === 'md',
                            'h-14 max-w-[260px]': logoSize === 'lg',
                            'bg-white border border-slate-200 p-1 shadow-2xs': logoBg === 'white',
                            'logo-box-dark border p-1.5 shadow-xs': logoBg === 'dark',
                            'bg-transparent p-0.5': logoBg === 'transparent'
                         }"
                         class="w-auto object-contain rounded-xl transition">
                </template>
                <template x-if="!logoPreview || removeLogo">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center font-extrabold text-sm shadow-xs"
                         :style="'background-color: ' + primaryColor + '; color: ' + getContrastText(primaryColor)">
                        FAD
                    </div>
                </template>

                <div x-show="!(logoPreview && !removeLogo && hideBrandText)">
                    <div class="font-extrabold text-slate-900 text-sm" x-text="platformName"></div>
                    <div class="text-[11px] text-slate-500" x-text="providerName"></div>
                </div>
                <template x-if="logoPreview && !removeLogo && hideBrandText">
                    <span class="px-2.5 py-1 rounded-full bg-indigo-50 border border-indigo-200 text-[10px] font-bold">
                        Modalità "Solo Logo" attiva
                    </span>
                </template>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <span class="px-3 py-1.5 rounded-xl text-xs font-bold shadow-2xs"
                      :style="'background-color: ' + primaryColor + '; color: ' + getContrastText(primaryColor)">
                    Bottone Primario
                </span>
                <span class="px-3 py-1.5 rounded-xl text-xs font-bold border"
                      :style="'background-color: ' + getRgba(primaryColor, 0.10) + '; border-color: ' + getRgba(primaryColor, 0.28) + '; color: ' + getReadableOnLight(primaryColor)">
                    Badge Tinta 10%
                </span>
            </div>
        </div>
    </div>

    <!-- Single Form with 3 Clean Tabs -->
    <form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data" class="bg-white rounded-2xl border border-slate-200/90 shadow-2xs overflow-hidden text-xs">
        @csrf

        <!-- 3-Tab Navigation Header -->
        <div class="px-6 pt-4 border-b border-slate-200 bg-slate-50/60 flex flex-wrap items-center justify-between gap-3">
            <div class="flex gap-1 overflow-x-auto">
                <button type="button" @click="settingsTab = 'aspetto'"
                        :class="settingsTab === 'aspetto' ? 'border-indigo-600 text-indigo-600 bg-white rounded-t-xl' : 'border-transparent text-slate-500 hover:text-slate-800'"
                        class="px-4 py-2.5 border-b-2 text-xs font-extrabold transition cursor-pointer">
                    1. Aspetto & Logo
                </button>
                <button type="button" @click="settingsTab = 'ente'"
                        :class="settingsTab === 'ente' ? 'border-indigo-600 text-indigo-600 bg-white rounded-t-xl' : 'border-transparent text-slate-500 hover:text-slate-800'"
                        class="px-4 py-2.5 border-b-2 text-xs font-extrabold transition cursor-pointer">
                    2. Dati Ente & Attestati
                </button>
                <button type="button" @click="settingsTab = 'regole'"
                        :class="settingsTab === 'regole' ? 'border-indigo-600 text-indigo-600 bg-white rounded-t-xl' : 'border-transparent text-slate-500 hover:text-slate-800'"
                        class="px-4 py-2.5 border-b-2 text-xs font-extrabold transition cursor-pointer">
                    3. Regole Iscrizione
                </button>
            </div>

            <div class="pb-2">
                <button type="submit" class="px-5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold cursor-pointer shadow-xs transition">
                    Salva Modifiche
                </button>
            </div>
        </div>

        <div class="p-6 sm:p-8">

            <!-- TAB 1: ASPETTO & LOGO -->
            <div x-show="settingsTab === 'aspetto'" class="grid grid-cols-1 lg:grid-cols-12 gap-8">
                <!-- Colonna Logo -->
                <div class="lg:col-span-5 space-y-4">
                    <div>
                        <h2 class="text-sm font-extrabold text-slate-900">Logo Grafico & Modalità "Solo Logo"</h2>
                        <p class="text-[11px] text-slate-500 mt-0.5">Carica il logo aziendale e scegli se nascondere il testo accanto.</p>
                    </div>

                    <div class="p-4 rounded-xl bg-slate-50 border border-dashed border-slate-300 flex flex-col items-center justify-center text-center gap-3">
                        <template x-if="logoPreview && !removeLogo">
                            <div class="space-y-2">
                                <div class="inline-block rounded-xl"
                                     :class="{
                                        'bg-white border border-slate-200 p-2': logoBg === 'white',
                                        'logo-box-dark border p-2': logoBg === 'dark',
                                        'bg-slate-100 p-2': logoBg === 'transparent'
                                     }">
                                    <img :src="logoPreview" alt="Logo" class="h-12 mx-auto object-contain">
                                </div>
                                <div>
                                    <label class="inline-flex items-center gap-1.5 text-[11px] text-red-600 font-bold cursor-pointer">
                                        <input type="checkbox" name="remove_logo" value="1" x-model="removeLogo" class="rounded text-red-600">
                                        <span>Rimuovi logo grafico</span>
                                    </label>
                                </div>
                            </div>
                        </template>
                        <template x-if="!logoPreview || removeLogo">
                            <div class="text-slate-400 text-[11px] py-2">Nessun logo grafico caricato</div>
                        </template>

                        <div class="flex flex-wrap items-center justify-center gap-1.5">
                            <label class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 font-bold text-xs cursor-pointer transition">
                                <span>📁 Carica File Logo...</span>
                                <input type="file" name="logo_file" accept="image/png,image/jpeg,image/svg+xml,image/webp" @change="onLogoSelected($event)" class="hidden">
                            </label>
                            <button type="button" @click="setDemoLogo('dark')" class="px-2.5 py-2 rounded-xl bg-white hover:bg-slate-100 text-slate-700 border border-slate-300 font-bold text-[11px] cursor-pointer">
                                Demo Scuro
                            </button>
                            <button type="button" @click="setDemoLogo('white')" class="px-2.5 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 font-bold text-[11px] cursor-pointer">
                                Demo Bianco
                            </button>
                        </div>
                    </div>

                    <label class="flex items-start gap-2.5 p-3 rounded-xl bg-slate-50 border border-slate-200 cursor-pointer hover:border-indigo-500 transition">
                        <input type="checkbox" name="hide_brand_text_with_logo" value="1" x-model="hideBrandText" class="mt-0.5 rounded text-indigo-600">
                        <span class="text-[11px] leading-snug">
                            <strong class="text-slate-900 block">Mostra solo il Logo (nascondi scritta accanto)</strong>
                            <span class="text-slate-500">Nasconde il nome testuale della piattaforma nell'header quando è presente il logo.</span>
                        </span>
                    </label>

                    <div class="grid grid-cols-2 gap-2.5">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1 text-[11px]">Sfondo Box Logo</label>
                            <select name="logo_bg" x-model="logoBg" class="w-full rounded-xl border border-slate-300 bg-white px-2.5 py-2 text-xs font-bold">
                                <option value="white">Chiaro (Bianco)</option>
                                <option value="dark">Colore Brand</option>
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
                        <label class="block font-bold text-slate-600 mb-1 text-[11px]">URL esterno del Logo (opzionale)</label>
                        <input type="text" name="logo_url" x-model="logoUrlInput" @input="logoPreview = logoUrlInput" placeholder="https://.../logo-ente.png" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-1.5 text-xs">
                    </div>
                </div>

                <!-- Colonna Colori -->
                <div class="lg:col-span-7 space-y-4">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div>
                            <h2 class="text-sm font-extrabold text-slate-900">Palette Colori & Armonizzazione</h2>
                            <p class="text-[11px] text-slate-500 mt-0.5">Sfondi, banner e font si accordano automaticamente ai colori scelti.</p>
                        </div>
                        <button type="button"
                                @click="harmonizeFromPrimary()"
                                class="px-3 py-1.5 rounded-xl bg-indigo-50 border border-indigo-200 font-bold text-[11px] cursor-pointer hover:opacity-90 transition">
                            🎨 Ricalcola Secondario
                        </button>
                    </div>

                    <label class="inline-flex items-center gap-2 px-3 py-2 rounded-xl bg-slate-50 border border-slate-200 cursor-pointer text-[11px]">
                        <input type="checkbox" x-model="autoHarmonize" class="rounded text-indigo-600">
                        <span><strong>Accordatura automatica:</strong> aggiorna il colore secondario quando modifichi il primario.</span>
                    </label>

                    <div>
                        <div class="text-[11px] font-bold text-slate-600 mb-1.5">Palette Pronte (1-Click):</div>
                        <div class="flex flex-wrap gap-2">
                            <template x-for="p in presets" :key="p.name">
                                <button type="button"
                                        @click="applyPreset(p)"
                                        class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl bg-white hover:bg-slate-50 border border-slate-200 shadow-2xs text-[11px] font-bold text-slate-800 cursor-pointer transition">
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

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-1">
                        <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200">
                            <label class="block font-bold text-slate-700 text-[11px] mb-1.5">Colore Primario</label>
                            <div class="flex items-center gap-2">
                                <input type="color" x-model="primaryColor" @input="onPrimaryColorChanged()" class="w-9 h-9 rounded-lg border border-slate-300 cursor-pointer p-0.5 bg-white shrink-0">
                                <input type="text" name="primary_color" x-model="primaryColor" @input="onPrimaryColorChanged()" class="w-full rounded-lg border border-slate-300 bg-white px-2.5 py-1.5 font-mono text-xs uppercase">
                            </div>
                        </div>

                        <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200">
                            <label class="block font-bold text-slate-700 text-[11px] mb-1.5">Banner & Secondario</label>
                            <div class="flex items-center gap-2">
                                <input type="color" x-model="secondaryColor" @input="updateCssVars()" class="w-9 h-9 rounded-lg border border-slate-300 cursor-pointer p-0.5 bg-white shrink-0">
                                <input type="text" name="secondary_color" x-model="secondaryColor" @input="updateCssVars()" class="w-full rounded-lg border border-slate-300 bg-white px-2.5 py-1.5 font-mono text-xs uppercase">
                            </div>
                        </div>

                        <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200">
                            <label class="block font-bold text-slate-700 text-[11px] mb-1.5">Accento & Attestati</label>
                            <div class="flex items-center gap-2">
                                <input type="color" x-model="accentColor" @input="updateCssVars()" class="w-9 h-9 rounded-lg border border-slate-300 cursor-pointer p-0.5 bg-white shrink-0">
                                <input type="text" name="accent_color" x-model="accentColor" @input="updateCssVars()" class="w-full rounded-lg border border-slate-300 bg-white px-2.5 py-1.5 font-mono text-xs uppercase">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 2: DATI ENTE & ATTESTATI -->
            <div x-show="settingsTab === 'ente'" class="space-y-5">
                <div>
                    <h2 class="text-sm font-extrabold text-slate-900">Intestazione Piattaforma & Dati Ufficiali per Attestati PDF</h2>
                    <p class="text-[11px] text-slate-500 mt-0.5">Questi dati compaiono nell'intestazione del portale e nei certificati ministeriali Allegato 3 e Allegato 4.</p>
                </div>

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

                <div class="pt-4 border-t border-slate-100 grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Denominazione Ente di Formazione (per Attestati) *</label>
                        <input type="text" name="provider_name" x-model="providerName" required class="w-full rounded-xl border border-slate-300 px-3.5 py-2">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Rappresentante Legale / Firmatario Attestati *</label>
                        <input type="text" name="provider_legal_rep" value="{{ $settings['provider_legal_rep'] }}" required class="w-full rounded-xl border border-slate-300 px-3.5 py-2">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
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
            </div>

            <!-- TAB 3: REGOLE ISCRIZIONE -->
            <div x-show="settingsTab === 'regole'" class="space-y-4">
                <div>
                    <h2 class="text-sm font-extrabold text-slate-900">Flusso di Registrazione Pubblica Corsisti</h2>
                    <p class="text-[11px] text-slate-500 mt-0.5">Scegli se consentire l'auto-registrazione tramite il link pubblico <code>/register</code> e se attivare subito i profili.</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <label class="flex items-start gap-3 p-4 rounded-xl border border-slate-200 bg-slate-50 cursor-pointer">
                        <input type="checkbox" name="allow_public_registration" value="1" {{ ($settings['allow_public_registration'] ?? '1') === '1' ? 'checked' : '' }} class="mt-0.5 rounded text-indigo-600">
                        <div>
                            <strong class="text-slate-900 block">Consenti Auto-Registrazione Pubblica</strong>
                            <span class="text-[11px] text-slate-500">I corsisti possono compilare autonomamente la scheda anagrafica sulla pagina <code>/register</code>.</span>
                        </div>
                    </label>
                    <label class="flex items-start gap-3 p-4 rounded-xl border border-slate-200 bg-slate-50 cursor-pointer">
                        <input type="checkbox" name="auto_approve_registration" value="1" {{ ($settings['auto_approve_registration'] ?? '1') === '1' ? 'checked' : '' }} class="mt-0.5 rounded text-indigo-600">
                        <div>
                            <strong class="text-slate-900 block">Abilita automaticamente i nuovi iscritti</strong>
                            <span class="text-[11px] text-slate-500">Attiva subito l'account senza attendere l'approvazione manuale della segreteria.</span>
                        </div>
                    </label>
                </div>
            </div>

        </div>

        <!-- Bottom Action Bar -->
        <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex items-center justify-between">
            <span class="text-[11px] text-slate-500">Tutte le modifiche delle 3 schede vengono salvate contemporaneamente.</span>
            <button type="submit" class="px-6 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold cursor-pointer shadow-xs transition">
                Salva Configurazione White-Label
            </button>
        </div>
    </form>
</div>
@endsection
