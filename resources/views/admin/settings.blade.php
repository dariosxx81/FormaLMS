@extends('layouts.app')

@section('title', 'Impostazioni White-Label Ente — Pannello Admin')

@section('content')
<div class="max-w-5xl mx-auto py-8 px-4 sm:px-6 lg:px-8"
     x-data="{
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
            { name: 'Oro Chiaro & Blu (Test Contrasto Tonale)', primary: '#facc15', secondary: '#1e3a8a', accent: '#10b981' },
            { name: 'Tema Chiaro Sabbia & Terracotta', primary: '#ea580c', secondary: '#fef3c7', accent: '#059669' }
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

    <div class="bg-white rounded-2xl border border-slate-200 p-6 sm:p-8 shadow-2xs space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-100 pb-5">
            <div>
                <span class="text-xs font-bold uppercase tracking-wider text-indigo-600">Personalizzazione SaaS / White-Label con Accordatura Automatica Font & Sfondo</span>
                <h1 class="text-2xl font-extrabold text-slate-900 mt-0.5">Logo Aziendale, Colori Brand & Dati Ente Erogatore</h1>
                <p class="text-xs text-slate-500 mt-1">
                    Nessun elemento resta con colori fissi: sfondi, banner, bottoni e font si accordano automaticamente ai colori scelti dal tuo brand, e puoi scegliere se mostrare <strong>solo il logo grafico</strong> senza scritte accanto.
                </p>
            </div>
        </div>

        <!-- LIVE MULTI-ELEMENT CONTRAST PREVIEW -->
        <div class="rounded-2xl border border-slate-200 overflow-hidden shadow-xs">
            <!-- 1. Top Institutional Bar Preview -->
            <div class="px-4 py-2 text-[11px] font-bold flex flex-wrap items-center justify-between gap-2"
                 :style="'background-color: ' + secondaryColor + '; color: ' + getContrastText(secondaryColor)">
                <span>1. Barra & Banner Istituzionali (Sfondo: <strong class="uppercase font-mono" x-text="secondaryColor"></strong> • Font Accordato: <strong class="uppercase font-mono" x-text="getContrastText(secondaryColor)"></strong>)</span>
                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold"
                      :style="'background-color: ' + accentColor + '; color: ' + getContrastText(accentColor)">
                    ✓ Attestato Sbloccato (80%)
                </span>
            </div>

            <!-- 2. Main Header + Solo-Logo Preview + Buttons + 10% Soft Badge + Harmonized Banner Preview -->
            <div class="p-4 sm:p-5 bg-white space-y-4">
                <div class="flex flex-wrap items-center justify-between gap-4 pb-4 border-b border-slate-100">
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
                            <div class="w-11 h-11 rounded-xl flex items-center justify-center font-extrabold text-base shadow-xs"
                                 :style="'background-color: ' + primaryColor + '; color: ' + getContrastText(primaryColor)">
                                FAD
                            </div>
                        </template>
                        <!-- Mostra testo accanto al logo solo se hideBrandText è disattivato oppure se non c'è logo -->
                        <div x-show="!(logoPreview && !removeLogo && hideBrandText)">
                            <div class="font-extrabold text-slate-900 text-base" x-text="platformName"></div>
                            <div class="text-xs text-slate-500" x-text="providerName"></div>
                        </div>
                        <template x-if="logoPreview && !removeLogo && hideBrandText">
                            <span class="px-2.5 py-1 rounded-full bg-indigo-50 border border-indigo-200 text-[10px] font-bold">
                                ✓ Modalità "Solo Logo" attiva (scritta nascosta)
                            </span>
                        </template>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <span class="px-3.5 py-2 rounded-xl text-xs font-bold shadow-2xs"
                              :style="'background-color: ' + primaryColor + '; color: ' + getContrastText(primaryColor)">
                            Bottone Primario (Font <span class="uppercase font-mono" x-text="getContrastText(primaryColor)"></span>)
                        </span>
                        <span class="px-3 py-1.5 rounded-xl text-xs font-bold border"
                              :style="'background-color: ' + getRgba(primaryColor, 0.10) + '; border-color: ' + getRgba(primaryColor, 0.28) + '; color: ' + getReadableOnLight(primaryColor)">
                            Badge Tinta 10% Accordata
                        </span>
                    </div>
                </div>

                <!-- 3. Harmonized Banner / Card Preview (replaces fixed #0f172a!) -->
                <div class="bg-slate-900 rounded-xl p-4 flex flex-wrap items-center justify-between gap-3 text-xs border border-slate-800 transition">
                    <div class="space-y-1">
                        <div class="flex items-center gap-2">
                            <span class="px-2.5 py-0.5 rounded-lg bg-slate-800 border border-slate-700 font-bold text-[10px] text-indigo-300">
                                🎨 BANNER & CARD ARMONIZZATI COL BRAND
                            </span>
                            <span class="text-white font-extrabold">Titolo Banner Principale</span>
                        </div>
                        <p class="text-slate-300 text-[11px]">
                            Sfondi, sottotitoli e riquadri interni si accordano automaticamente ai colori scelti (nessun elemento fisso <code class="font-mono">#0f172a</code>).
                        </p>
                    </div>
                    <div class="w-36 h-2.5 bg-slate-800 rounded-full overflow-hidden border border-slate-700">
                        <div class="h-full w-3/4 rounded-full" :style="'background-color: ' + primaryColor"></div>
                    </div>
                </div>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data" class="space-y-6 text-xs">
            @csrf

            <!-- SEZIONE 1: LOGO AZIENDALE & OPZIONI SFONDO / DIMENSIONE / SOLO LOGO -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 p-5 rounded-2xl bg-slate-50 border border-slate-200">
                <!-- Caricamento Logo + Opzioni Box -->
                <div class="lg:col-span-5 space-y-3.5">
                    <div class="font-extrabold text-sm text-slate-900">
                        1. Logo Aziendale & Opzione "Solo Logo"
                    </div>
                    <p class="text-[11px] text-slate-500">
                        Carica il tuo logo grafico e scegli se <strong>nascondere la scritta accanto</strong> (ideale quando il logo contiene già il nome dell'azienda).
                    </p>

                    <div class="p-4 rounded-xl bg-white border border-dashed border-slate-300 flex flex-col items-center justify-center text-center gap-2.5">
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
                            <button type="button" @click="setDemoLogo('white')" class="px-2.5 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 font-bold text-[11px] cursor-pointer">
                                Demo Logo Bianco
                            </button>
                        </div>
                    </div>

                    <!-- OPZIONE NASCONDI SCRITTA ACCANTO AL LOGO -->
                    <label class="flex items-start gap-2.5 p-3 rounded-xl bg-white border border-slate-300 cursor-pointer hover:border-indigo-500 transition shadow-2xs">
                        <input type="checkbox" name="hide_brand_text_with_logo" value="1" x-model="hideBrandText" class="mt-0.5 rounded text-indigo-600">
                        <span class="text-[11px] leading-snug">
                            <strong class="text-slate-900 block">Mostra solo il Logo (nascondi scritta accanto)</strong>
                            <span class="text-slate-500">Quando è presente il logo grafico, rimuove il testo del nome piattaforma e sottotitolo accanto al logo nell'intestazione.</span>
                        </span>
                    </label>

                    <!-- Selettori Sfondo Box Logo & Altezza Logo -->
                    <div class="grid grid-cols-2 gap-2.5">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1 text-[11px]">Sfondo Box Logo</label>
                            <select name="logo_bg" x-model="logoBg" class="w-full rounded-xl border border-slate-300 bg-white px-2.5 py-2 text-xs font-bold">
                                <option value="white">Chiaro (Bianco)</option>
                                <option value="dark">Colore Brand (Per loghi bianchi)</option>
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
                        <input type="text" name="logo_url" x-model="logoUrlInput" @input="logoPreview = logoUrlInput" placeholder="https://.../logo-ente.png" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-1.5 text-xs">
                    </div>
                </div>

                <!-- Scelta Colori Base & Palette Rapide -->
                <div class="lg:col-span-7 space-y-4">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div class="font-extrabold text-sm text-slate-900">
                            2. Colori del Brand & Accordatura Automatica Font/Sfondo
                        </div>
                        <button type="button"
                                @click="harmonizeFromPrimary()"
                                class="px-3 py-1.5 rounded-xl bg-indigo-50 border border-indigo-200 font-bold text-[11px] cursor-pointer hover:opacity-90 transition">
                            🎨 Ricalcola Secondario dal Primario
                        </button>
                    </div>
                    <p class="text-[11px] text-slate-500">
                        Tutti gli elementi della piattaforma (inclusi i banner grandi, i titoli, i bottoni secondari e le card) si accordano con i colori scelti qui sotto. Se scegli uno sfondo chiaro, anche il font scuro viene generato come <strong>tonalità profonda coordinata al tuo colore</strong> (mai un grigio/nero fisso).
                    </p>

                    <label class="inline-flex items-center gap-2 px-3 py-2 rounded-xl bg-white border border-slate-200 cursor-pointer text-[11px]">
                        <input type="checkbox" x-model="autoHarmonize" class="rounded text-indigo-600">
                        <span><strong>Accordatura automatica in tempo reale:</strong> quando cambi il <em>Colore Primario</em>, aggiorna automaticamente anche il <em>Colore Secondario (Banner & Sfondi)</em> in tono coordinato.</span>
                    </label>

                    <!-- Preset Buttons -->
                    <div>
                        <div class="text-[11px] font-bold text-slate-600 mb-1.5">Palette Istituzionali Coordinate (1-Click):</div>
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
                                <input type="color" x-model="primaryColor" @input="onPrimaryColorChanged()" class="w-9 h-9 rounded-lg border border-slate-300 cursor-pointer p-0.5 bg-white shrink-0">
                                <input type="text" name="primary_color" x-model="primaryColor" @input="onPrimaryColorChanged()" class="w-full rounded-lg border border-slate-300 px-2.5 py-1.5 font-mono text-xs uppercase">
                            </div>
                            <div class="text-[10px] text-slate-500 mt-1.5 flex items-center gap-1.5">
                                <span>Font accordato:</span>
                                <span class="px-1.5 py-0.5 rounded font-mono font-bold text-[10px] uppercase border border-black/10"
                                      :style="'background-color:' + primaryColor + '; color:' + getContrastText(primaryColor)"
                                      x-text="getContrastText(primaryColor)"></span>
                            </div>
                        </div>

                        <div class="p-3 rounded-xl bg-white border border-slate-200">
                            <label class="block font-bold text-slate-700 text-[11px] mb-1">Banner, Header & Bottoni Secondari</label>
                            <div class="flex items-center gap-2">
                                <input type="color" x-model="secondaryColor" @input="updateCssVars()" class="w-9 h-9 rounded-lg border border-slate-300 cursor-pointer p-0.5 bg-white shrink-0">
                                <input type="text" name="secondary_color" x-model="secondaryColor" @input="updateCssVars()" class="w-full rounded-lg border border-slate-300 px-2.5 py-1.5 font-mono text-xs uppercase">
                            </div>
                            <div class="text-[10px] text-slate-500 mt-1.5 flex items-center gap-1.5">
                                <span>Font accordato:</span>
                                <span class="px-1.5 py-0.5 rounded font-mono font-bold text-[10px] uppercase border border-black/10"
                                      :style="'background-color:' + secondaryColor + '; color:' + getContrastText(secondaryColor)"
                                      x-text="getContrastText(secondaryColor)"></span>
                            </div>
                        </div>

                        <div class="p-3 rounded-xl bg-white border border-slate-200">
                            <label class="block font-bold text-slate-700 text-[11px] mb-1">Colore Accento (Attestati & Idoneità)</label>
                            <div class="flex items-center gap-2">
                                <input type="color" x-model="accentColor" @input="updateCssVars()" class="w-9 h-9 rounded-lg border border-slate-300 cursor-pointer p-0.5 bg-white shrink-0">
                                <input type="text" name="accent_color" x-model="accentColor" @input="updateCssVars()" class="w-full rounded-lg border border-slate-300 px-2.5 py-1.5 font-mono text-xs uppercase">
                            </div>
                            <div class="text-[10px] text-slate-500 mt-1.5 flex items-center gap-1.5">
                                <span>Font accordato:</span>
                                <span class="px-1.5 py-0.5 rounded font-mono font-bold text-[10px] uppercase border border-black/10"
                                      :style="'background-color:' + accentColor + '; color:' + getContrastText(accentColor)"
                                      x-text="getContrastText(accentColor)"></span>
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
