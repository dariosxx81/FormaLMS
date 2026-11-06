@extends('layouts.app')

@section('title', 'Impostazioni Piattaforma — Pannello Admin')

@section('content')
<div class="max-w-6xl mx-auto py-8 px-4 sm:px-6 lg:px-8 space-y-6"
     x-data="{
        activeTab: @js(session('settings_tab', request('tab', 'ente'))),
        platformName: @js($settings['platform_name'] ?? 'FormaFAD LMS'),
        providerName: @js($settings['provider_name'] ?? 'Formacamera'),
        logoPreview: @js($settings['logo_url'] ?? ''),
        logoUrlInput: @js($settings['logo_url'] ?? ''),
        logoBg: @js($settings['logo_bg'] ?? 'white'),
        logoSize: @js($settings['logo_size'] ?? 'md'),
        hideBrandText: @js(($settings['hide_brand_text_with_logo'] ?? '0') === '1'),
        removeLogo: false,
        faviconPreview: @js($settings['favicon_url'] ?? ''),
        faviconUrlInput: @js($settings['favicon_url'] ?? ''),
        removeFavicon: false,
        primaryColor: @js($settings['primary_color'] ?? '#4f46e5'),
        secondaryColor: @js($settings['secondary_color'] ?? '#1e1b4b'),
        accentColor: @js($settings['accent_color'] ?? '#10b981'),
        zoomEnabled: @js(($settings['zoom_module_enabled'] ?? '0') === '1'),
        presets: [
            { name: 'Formacamera Rosso Istituzionale', primary: '#a30000', secondary: '#1c1917', accent: '#059669' },
            { name: 'Blu Camera di Commercio', primary: '#0284c7', secondary: '#0c4a6e', accent: '#059669' },
            { name: 'Indigo Professionale', primary: '#4f46e5', secondary: '#1e1b4b', accent: '#10b981' },
            { name: 'Verde Smeraldo FAD', primary: '#0d9488', secondary: '#134e4a', accent: '#16a34a' },
            { name: 'Bordeaux Accademia', primary: '#be123c', secondary: '#4c0519', accent: '#d97706' }
        ],
        applyPreset(p) {
            this.primaryColor = p.primary;
            this.secondaryColor = p.secondary;
            this.accentColor = p.accent;
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
        onFaviconSelected(event) {
            const file = event.target.files[0];
            if (!file) return;
            this.removeFavicon = false;
            this.faviconUrlInput = '';
            const reader = new FileReader();
            reader.onload = (e) => { this.faviconPreview = e.target.result; };
            reader.readAsDataURL(file);
        }
     }">

    <!-- Header Principale Pulito -->
    <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-2xs flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="px-2.5 py-0.5 rounded-full bg-indigo-50 border border-indigo-200 text-indigo-700 font-bold text-[11px] uppercase tracking-wider">
                    Configurazione Generale
                </span>
                <span class="px-2 py-0.5 rounded-full bg-slate-100 text-slate-700 text-[11px] font-bold">
                    Ente: {{ $settings['provider_name'] ?? 'Formacamera' }}
                </span>
            </div>
            <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Impostazioni Piattaforma</h1>
            <p class="text-xs text-slate-500 mt-0.5">
                Gestisci in modo ordinato i dati legali dell'Ente, la personalizzazione grafica del brand, le regole di registrazione e il modulo Zoom.
            </p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('admin.emails.index') }}"
               class="px-4 py-2.5 rounded-xl border border-slate-200 hover:border-indigo-300 hover:bg-indigo-50/50 text-slate-700 font-bold text-xs transition flex items-center gap-2">
                <span>📧 Gestione Notifiche Email</span>
                <span class="text-[10px] bg-indigo-100 text-indigo-700 font-extrabold px-1.5 py-0.5 rounded-md">Apri &rarr;</span>
            </a>
        </div>
    </div>

    <!-- Form con Navigazione a Schede Chiare -->
    <form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data" class="space-y-6">
        @csrf

        <!-- Barra di Navigazione Tab Orizzontale Pulita -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-2xs p-2 flex flex-wrap gap-1.5">
            <button type="button" @click="activeTab = 'ente'"
                    :class="activeTab === 'ente' ? 'bg-indigo-600 text-white font-extrabold shadow-xs' : 'text-slate-600 hover:bg-slate-100 font-bold'"
                    class="px-4 py-2.5 rounded-xl text-xs sm:text-sm transition cursor-pointer flex items-center gap-2">
                <span>🏛️ Dati Ente & Attestati</span>
            </button>

            <button type="button" @click="activeTab = 'brand'"
                    :class="activeTab === 'brand' ? 'bg-indigo-600 text-white font-extrabold shadow-xs' : 'text-slate-600 hover:bg-slate-100 font-bold'"
                    class="px-4 py-2.5 rounded-xl text-xs sm:text-sm transition cursor-pointer flex items-center gap-2">
                <span>🎨 Logo & Colori Brand</span>
            </button>

            <button type="button" @click="activeTab = 'iscrizioni'"
                    :class="activeTab === 'iscrizioni' ? 'bg-indigo-600 text-white font-extrabold shadow-xs' : 'text-slate-600 hover:bg-slate-100 font-bold'"
                    class="px-4 py-2.5 rounded-xl text-xs sm:text-sm transition cursor-pointer flex items-center gap-2">
                <span>👥 Regole Corsisti & Iscrizioni</span>
            </button>

            <button type="button" @click="activeTab = 'zoom'"
                    :class="activeTab === 'zoom' ? 'bg-indigo-600 text-white font-extrabold shadow-xs' : 'text-slate-600 hover:bg-slate-100 font-bold'"
                    class="px-4 py-2.5 rounded-xl text-xs sm:text-sm transition cursor-pointer flex items-center gap-2">
                <span>🎥 Modulo Aula Zoom Live</span>
                <span :class="zoomEnabled ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-600'"
                      class="text-[10px] font-mono px-1.5 py-0.5 rounded-full font-extrabold uppercase"
                      x-text="zoomEnabled ? 'Attivo' : 'Off'"></span>
            </button>
        </div>

        <!-- ========================================== -->
        <!-- TAB 1: DATI ENTE & ATTESTATI MINISTERIALI -->
        <!-- ========================================== -->
        <div x-show="activeTab === 'ente'" class="bg-white rounded-2xl border border-slate-200 shadow-2xs p-6 space-y-6">
            <div class="border-b border-slate-100 pb-4">
                <h2 class="text-base font-extrabold text-slate-900 flex items-center gap-2">
                    <span>🏛️ Dati Ufficiali Ente di Formazione & Intestazione Attestati</span>
                </h2>
                <p class="text-xs text-slate-500 mt-1">
                    Queste informazioni compaiono nei registri didattici ufficiali e nei certificati PDF rilasciati agli studenti (Allegato 3 e Allegato 4).
                </p>
            </div>

            <!-- Blocco Dati Piattaforma -->
            <div class="space-y-4">
                <h3 class="text-xs font-extrabold text-slate-700 uppercase tracking-wider">Identità del Portale</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Nome del Portale / Piattaforma *</label>
                        <input type="text" name="platform_name" x-model="platformName" required
                               class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 font-semibold focus:ring-2 focus:ring-indigo-500">
                        <p class="text-[11px] text-slate-400 mt-1">Es. FormaFAD LMS oppure AB Formazione</p>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Email Ufficiale Supporto / Segreteria Didattica *</label>
                        <input type="email" name="support_email" value="{{ $settings['support_email'] }}" required
                               class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 font-semibold focus:ring-2 focus:ring-indigo-500">
                        <p class="text-[11px] text-slate-400 mt-1">Comparirà come mittente di riferimento e nelle note di contatto</p>
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block font-bold text-slate-700 mb-1">Sottotitolo / Dicitura Descrittiva (Header)</label>
                        <input type="text" name="platform_subtitle" value="{{ $settings['platform_subtitle'] }}"
                               class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-xs focus:ring-2 focus:ring-indigo-500">
                        <p class="text-[11px] text-slate-400 mt-1">Es. Piattaforma Telematica Certificata per la Formazione Continua di Formacamera</p>
                    </div>
                </div>
            </div>

            <!-- Blocco Ente Erogatore Ufficiale -->
            <div class="space-y-4 pt-4 border-t border-slate-100">
                <h3 class="text-xs font-extrabold text-slate-700 uppercase tracking-wider">Dati per Certificazione & Attestati Ministeriali</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Denominazione Ente Erogatore *</label>
                        <input type="text" name="provider_name" x-model="providerName" required
                               class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 font-semibold focus:ring-2 focus:ring-indigo-500">
                        <p class="text-[11px] text-slate-400 mt-1">Es. Formacamera — Azienda Speciale Camera di Commercio di Roma</p>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Rappresentante Legale / Firmatario Attestati *</label>
                        <input type="text" name="provider_legal_rep" value="{{ $settings['provider_legal_rep'] }}" required
                               class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 font-semibold focus:ring-2 focus:ring-indigo-500">
                        <p class="text-[11px] text-slate-400 mt-1">Nome e titolo del firmatario ufficiale dell'attestato</p>
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block font-bold text-slate-700 mb-1">Sede Legale / Operativa dell'Ente</label>
                        <input type="text" name="provider_address" value="{{ $settings['provider_address'] }}"
                               class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-xs focus:ring-2 focus:ring-indigo-500">
                        <p class="text-[11px] text-slate-400 mt-1">Indirizzo completo inserito a piè di pagina sui PDF generati</p>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Codice ATECO Primario *</label>
                        <input type="text" name="ateco_code" value="{{ $settings['ateco_code'] }}" required
                               class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 font-mono text-xs focus:ring-2 focus:ring-indigo-500">
                        <p class="text-[11px] text-slate-400 mt-1">Codice attività per la conformità ministeriale (es. 79.90 o 85.59)</p>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Codice CP ISTAT Professionale *</label>
                        <input type="text" name="cp_istat_code" value="{{ $settings['cp_istat_code'] }}" required
                               class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 font-mono text-xs focus:ring-2 focus:ring-indigo-500">
                        <p class="text-[11px] text-slate-400 mt-1">Classificazione professione formativa (es. 3.4.1.5.2)</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- TAB 2: LOGO & COLORI BRAND (WHITE-LABEL)  -->
        <!-- ========================================== -->
        <div x-show="activeTab === 'brand'" class="bg-white rounded-2xl border border-slate-200 shadow-2xs p-6 space-y-6">
            <div class="border-b border-slate-100 pb-4">
                <h2 class="text-base font-extrabold text-slate-900 flex items-center gap-2">
                    <span>🎨 Personalizzazione Grafica: Logo, Favicon e Colori</span>
                </h2>
                <p class="text-xs text-slate-500 mt-1">
                    Configura l'identità visiva della piattaforma. Puoi caricare il logo istituzionale e impostare i colori del brand.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 text-xs">
                <!-- Sezione Logo -->
                <div class="space-y-4 bg-slate-50/70 p-5 rounded-2xl border border-slate-200">
                    <h3 class="font-extrabold text-slate-800 text-sm flex items-center gap-1.5">
                        <span>🖼️ Logo Ufficiale Piattaforma</span>
                    </h3>

                    <!-- Box Anteprima Logo -->
                    <div class="p-4 rounded-xl bg-white border border-slate-200 flex flex-col items-center justify-center min-h-[110px] text-center gap-2">
                        <template x-if="logoPreview && !removeLogo">
                            <div class="space-y-2">
                                <img :src="logoPreview" alt="Logo Anteprima"
                                     :class="{
                                        'h-8': logoSize === 'sm',
                                        'h-12': logoSize === 'md',
                                        'h-16': logoSize === 'lg',
                                        'bg-white p-1 border border-slate-100 rounded-lg': logoBg === 'white',
                                        'bg-slate-900 p-2 rounded-lg': logoBg === 'dark',
                                        'bg-transparent': logoBg === 'transparent'
                                     }"
                                     class="max-w-full object-contain mx-auto">
                                <div>
                                    <label class="inline-flex items-center gap-1.5 text-[11px] text-rose-600 font-bold cursor-pointer hover:underline">
                                        <input type="checkbox" name="remove_logo" value="1" x-model="removeLogo" class="rounded text-rose-600">
                                        <span>Rimuovi logo attuale</span>
                                    </label>
                                </div>
                            </div>
                        </template>
                        <template x-if="!logoPreview || removeLogo">
                            <div class="text-slate-400 py-3">
                                <span>Nessun logo caricato (Verrà usato il badge con dicitura testuale)</span>
                            </div>
                        </template>
                    </div>

                    <!-- Upload File o URL -->
                    <div class="space-y-3">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Carica File Logo dal Computer:</label>
                            <input type="file" name="logo_file" accept="image/png,image/jpeg,image/svg+xml,image/webp"
                                   @change="onLogoSelected($event)"
                                   class="w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 cursor-pointer">
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Oppure URL Immagine Logo Esterna:</label>
                            <input type="text" name="logo_url" x-model="logoUrlInput" @input="logoPreview = logoUrlInput; removeLogo = false"
                                   placeholder="https://..." class="w-full rounded-xl border border-slate-300 px-3 py-2 text-xs font-mono">
                        </div>

                        <div class="pt-2 border-t border-slate-200 grid grid-cols-2 gap-3">
                            <div>
                                <label class="block font-bold text-slate-700 mb-1">Dimensione Visualizzazione:</label>
                                <select name="logo_size" x-model="logoSize" class="w-full rounded-xl border border-slate-300 px-3 py-1.5 text-xs">
                                    <option value="sm">Compatta (32px)</option>
                                    <option value="md">Standard (48px)</option>
                                    <option value="lg">Grande (64px)</option>
                                </select>
                            </div>

                            <div>
                                <label class="block font-bold text-slate-700 mb-1">Contenitore Sfondo Logo:</label>
                                <select name="logo_bg" x-model="logoBg" class="w-full rounded-xl border border-slate-300 px-3 py-1.5 text-xs">
                                    <option value="white">Riquadro Bianco</option>
                                    <option value="transparent">Trasparente</option>
                                    <option value="dark">Riquadro Scuro</option>
                                </select>
                            </div>
                        </div>

                        <div class="pt-2">
                            <label class="inline-flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" name="hide_brand_text_with_logo" value="1" x-model="hideBrandText"
                                       class="rounded text-indigo-600 focus:ring-indigo-500">
                                <span class="font-bold text-slate-700 text-xs">Mostra solo il Logo (Nascondi la scritta accanto)</span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Sezione Colori Brand & Palette -->
                <div class="space-y-4 bg-slate-50/70 p-5 rounded-2xl border border-slate-200">
                    <h3 class="font-extrabold text-slate-800 text-sm flex items-center gap-1.5">
                        <span>🎨 Colori Istituzionali della Piattaforma</span>
                    </h3>

                    <!-- Preset Rapidi con 1 Clic -->
                    <div>
                        <label class="block font-bold text-slate-700 mb-1.5">Palette Predefinite:</label>
                        <div class="grid grid-cols-1 gap-1.5">
                            @foreach([
                                ['name' => 'Formacamera Rosso Istituzionale', 'primary' => '#a30000', 'secondary' => '#1c1917', 'accent' => '#059669'],
                                ['name' => 'Blu Camera di Commercio', 'primary' => '#0284c7', 'secondary' => '#0c4a6e', 'accent' => '#059669'],
                                ['name' => 'Indigo FormaFAD', 'primary' => '#4f46e5', 'secondary' => '#1e1b4b', 'accent' => '#10b981'],
                                ['name' => 'Verde Smeraldo FAD', 'primary' => '#0d9488', 'secondary' => '#134e4a', 'accent' => '#16a34a']
                            ] as $p)
                                <button type="button" @click="applyPreset(@js($p))"
                                        class="px-3 py-2 rounded-xl bg-white border border-slate-200 hover:border-indigo-400 text-left flex items-center justify-between text-xs transition cursor-pointer">
                                    <span class="font-bold text-slate-800">{{ $p['name'] }}</span>
                                    <div class="flex items-center gap-1.5 shrink-0">
                                        <span class="w-4 h-4 rounded-full border border-black/10" style="background-color: {{ $p['primary'] }};"></span>
                                        <span class="w-4 h-4 rounded-full border border-black/10" style="background-color: {{ $p['secondary'] }};"></span>
                                        <span class="w-4 h-4 rounded-full border border-black/10" style="background-color: {{ $p['accent'] }};"></span>
                                    </div>
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <!-- Selettori Colore Manuali -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-3 border-t border-slate-200">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Colore Primario</label>
                            <div class="flex items-center gap-2">
                                <input type="color" x-model="primaryColor" class="w-8 h-8 rounded-lg border border-slate-300 cursor-pointer p-0.5 bg-white shrink-0">
                                <input type="text" name="primary_color" x-model="primaryColor" class="w-full rounded-lg border border-slate-300 px-2 py-1.5 font-mono text-xs uppercase">
                            </div>
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Banner & Barre</label>
                            <div class="flex items-center gap-2">
                                <input type="color" x-model="secondaryColor" class="w-8 h-8 rounded-lg border border-slate-300 cursor-pointer p-0.5 bg-white shrink-0">
                                <input type="text" name="secondary_color" x-model="secondaryColor" class="w-full rounded-lg border border-slate-300 px-2 py-1.5 font-mono text-xs uppercase">
                            </div>
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Badge & Accenti</label>
                            <div class="flex items-center gap-2">
                                <input type="color" x-model="accentColor" class="w-8 h-8 rounded-lg border border-slate-300 cursor-pointer p-0.5 bg-white shrink-0">
                                <input type="text" name="accent_color" x-model="accentColor" class="w-full rounded-lg border border-slate-300 px-2 py-1.5 font-mono text-xs uppercase">
                            </div>
                        </div>
                    </div>

                    <!-- Favicon -->
                    <div class="pt-3 border-t border-slate-200">
                        <label class="block font-bold text-slate-700 mb-1">Favicon della Scheda Browser (.ico / .png / .svg):</label>
                        <div class="flex items-center gap-3">
                            <template x-if="faviconPreview && !removeFavicon">
                                <img :src="faviconPreview" class="w-8 h-8 rounded-lg border border-slate-200 p-1 object-contain bg-white">
                            </template>
                            <input type="file" name="favicon_file" accept="image/x-icon,image/png,image/svg+xml"
                                   @change="onFaviconSelected($event)"
                                   class="text-xs text-slate-500 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-slate-200 hover:file:bg-slate-300 cursor-pointer">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- TAB 3: REGOLE CORSISTI & ISCRIZIONI       -->
        <!-- ========================================== -->
        <div x-show="activeTab === 'iscrizioni'" class="bg-white rounded-2xl border border-slate-200 shadow-2xs p-6 space-y-6">
            <div class="border-b border-slate-100 pb-4">
                <h2 class="text-base font-extrabold text-slate-900 flex items-center gap-2">
                    <span>👥 Regole di Iscrizione e Politiche di Accesso dei Corsisti</span>
                </h2>
                <p class="text-xs text-slate-500 mt-1">
                    Definisci come i partecipanti possono accedere e registrarsi alla piattaforma didattica.
                </p>
            </div>

            <div class="space-y-4">
                <!-- Switch Auto-Registrazione -->
                <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div>
                        <div class="font-extrabold text-slate-800 text-sm">Consentire Auto-Registrazione Pubblica (/register)</div>
                        <p class="text-xs text-slate-500 mt-0.5">
                            Se disattivato, il link di registrazione viene nascosto e solo gli amministratori possono creare corsisti.
                        </p>
                    </div>
                    <label class="inline-flex items-center gap-2 cursor-pointer shrink-0">
                        <input type="checkbox" name="allow_public_registration" value="1"
                               {{ ($settings['allow_public_registration'] ?? '1') === '1' ? 'checked' : '' }}
                               class="w-4 h-4 rounded text-indigo-600 focus:ring-indigo-500">
                        <span class="font-bold text-xs text-slate-700">Registrazione Aperta</span>
                    </label>
                </div>

                <!-- Switch Approvazione Automatica -->
                <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div>
                        <div class="font-extrabold text-slate-800 text-sm">Attivazione Immediata del Profilo Corsista</div>
                        <p class="text-xs text-slate-500 mt-0.5">
                            Se attivo, i corsisti possono accedere subito dopo la registrazione. Se disattivato, restano in stato "In Attesa" finché la segreteria non li approva.
                        </p>
                    </div>
                    <label class="inline-flex items-center gap-2 cursor-pointer shrink-0">
                        <input type="checkbox" name="auto_approve_registration" value="1"
                               {{ ($settings['auto_approve_registration'] ?? '1') === '1' ? 'checked' : '' }}
                               class="w-4 h-4 rounded text-indigo-600 focus:ring-indigo-500">
                        <span class="font-bold text-xs text-slate-700">Attivazione Automatica</span>
                    </label>
                </div>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- TAB 4: MODULO AULA VIRTUALE ZOOM LIVE     -->
        <!-- ========================================== -->
        <div x-show="activeTab === 'zoom'" class="bg-white rounded-2xl border border-slate-200 shadow-2xs p-6 space-y-6">
            <div class="border-b border-slate-100 pb-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h2 class="text-base font-extrabold text-slate-900 flex items-center gap-2">
                        <span>🎥 Integrazione Zoom Cloud API (Lezioni Sincrone)</span>
                    </h2>
                    <p class="text-xs text-slate-500 mt-1">
                        Abilita la creazione di moduli didattici di tipo "Zoom Live" con calcolo automatico dei tempi e registro presenze unificato.
                    </p>
                </div>

                <label class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-slate-100 border border-slate-300 cursor-pointer shrink-0">
                    <input type="checkbox" name="zoom_module_enabled" value="1" x-model="zoomEnabled"
                           class="w-4 h-4 rounded text-indigo-600 focus:ring-indigo-500">
                    <span class="font-extrabold text-xs text-slate-800" x-text="zoomEnabled ? 'Modulo Zoom Abilitato' : 'Modulo Zoom Disattivato'"></span>
                </label>
            </div>

            <div x-show="zoomEnabled" class="space-y-5">
                <div class="p-4 rounded-xl bg-sky-50 border border-sky-200 text-xs text-sky-950 space-y-1">
                    <div class="font-extrabold flex items-center gap-1.5">
                        <span>ℹ️ Come Funziona l'Integrazione Zoom:</span>
                    </div>
                    <p class="text-sky-800 text-[11px] leading-relaxed">
                        • <strong>Modalità Diretta (Senza credenziali API):</strong> Il docente o tutor apre la riunione da Zoom e i discenti cliccano su "Entra nell'Aula Zoom". La piattaforma traccia i secondi trascorsi in aula e genera i report.<br>
                        • <strong>Modalità API Server-to-Server OAuth:</strong> Inserendo le credenziali qui sotto, la piattaforma iscrive nominativamente il corsista (Nome, Cognome, CF) tramite le API di Zoom Marketplace e sincronizza gli eventi di join/leave.
                    </p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Zoom Account ID (S2S OAuth)</label>
                        <input type="text" name="zoom_account_id" value="{{ $settings['zoom_account_id'] ?? '' }}"
                               placeholder="Es. aBcDeFgHiJkLmNoPqRsTuV"
                               class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 font-mono text-xs focus:ring-2 focus:ring-indigo-500">
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Zoom Client ID</label>
                        <input type="text" name="zoom_client_id" value="{{ $settings['zoom_client_id'] ?? '' }}"
                               placeholder="Es. 1a2b3c4d5e6f7g8h9i0j"
                               class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 font-mono text-xs focus:ring-2 focus:ring-indigo-500">
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Zoom Client Secret</label>
                        <input type="password" name="zoom_client_secret" value="{{ $settings['zoom_client_secret'] ?? '' }}"
                               placeholder="••••••••••••••••••••••••"
                               class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 font-mono text-xs focus:ring-2 focus:ring-indigo-500">
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Zoom Webhook Secret Token</label>
                        <input type="password" name="zoom_webhook_secret_token" value="{{ $settings['zoom_webhook_secret_token'] ?? '' }}"
                               placeholder="••••••••••••••••••••••••"
                               class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 font-mono text-xs focus:ring-2 focus:ring-indigo-500">
                    </div>
                </div>

                <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 flex flex-wrap items-center justify-between gap-2 text-[11px]">
                    <div>
                        <span class="font-bold text-slate-700">Endpoint Webhook per Zoom Marketplace:</span>
                        <code class="ml-1.5 px-2 py-0.5 rounded bg-white border border-slate-200 font-mono text-slate-800">{{ url('/webhooks/zoom') }}</code>
                    </div>
                    <span class="text-slate-500">Eventi: <code>meeting.participant_joined</code>, <code>meeting.participant_left</code></span>
                </div>
            </div>
        </div>

        <!-- Barra di Salvataggio Sticky in Basso -->
        <div class="sticky bottom-4 bg-white/95 backdrop-blur-xs border border-slate-200 rounded-2xl p-4 shadow-xl flex items-center justify-between gap-4 z-20">
            <div class="text-xs text-slate-500 flex items-center gap-1.5">
                <span>💾</span>
                <span>Tutte le modifiche inserite nelle diverse schede vengono salvate contemporaneamente.</span>
            </div>
            <button type="submit"
                    class="px-6 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-extrabold text-xs shadow-md transition cursor-pointer flex items-center gap-2">
                <span>Salva Tutte le Impostazioni</span>
            </button>
        </div>
    </form>
</div>
@endsection
