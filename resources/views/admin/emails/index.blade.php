@extends('layouts.app')

@section('title', 'Gestione Email, Template & Log — Pannello Admin')

@section('content')
<div x-data="{
        activeTab: @js(request('tab', 'templates')),
        testModal: false,
        testType: 'welcome',
        previewModal: false,
        previewEmail: { id: null, to: '', subject: '', date: '', url: '' },
        openPreview(email) {
            this.previewEmail = {
                id: email.id,
                to: email.to_email,
                subject: email.subject,
                date: email.created_at_formatted,
                url: '{{ url('/admin/emails') }}/' + email.id + '/preview'
            };
            this.previewModal = true;
        }
     }"
     class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8 space-y-6">

    <!-- Header & Action Buttons -->
    <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-2xs flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="px-2.5 py-0.5 rounded-full bg-indigo-50 border border-indigo-200 text-indigo-700 font-bold text-[11px] uppercase tracking-wider">
                    Sistema Notifiche & Mailer
                </span>
                <span class="px-2 py-0.5 rounded-full bg-slate-100 text-slate-700 font-mono text-[11px] font-bold">
                    Totale inviate: {{ $totalSent }}
                </span>
            </div>
            <h1 class="text-2xl font-extrabold text-slate-900">Gestione Email, Template & Centro Notifiche</h1>
            <p class="text-xs text-slate-500 mt-1">
                Personalizza i testi e l'oggetto di ciascuna email, seleziona con le spunte quali inviare in automatico e consulta il registro dei log.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <button type="button" @click="testModal = true"
                    class="px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                <span>🚀 Invia Email di Test</span>
            </button>

            @if($totalSent > 0)
                <form method="POST" action="{{ route('admin.emails.clear') }}"
                      onsubmit="return confirm('Sei sicuro di voler eliminare tutti i log delle email inviate?');">
                    @csrf
                    <button type="submit"
                            class="px-3.5 py-2.5 rounded-xl border border-slate-200 hover:bg-rose-50 hover:text-rose-700 hover:border-rose-200 text-slate-600 font-bold text-xs transition cursor-pointer">
                        🗑 Svuota Log
                    </button>
                </form>
            @endif
        </div>
    </div>

    <!-- Mailer Configuration Status Card -->
    <div class="bg-gradient-to-r from-slate-900 via-slate-800 to-indigo-950 rounded-2xl p-5 text-white shadow-xs">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="space-y-1">
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-400 animate-pulse"></span>
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-300">Stato Driver Email Corrente</span>
                </div>
                <div class="text-sm font-semibold flex flex-wrap items-center gap-x-4 gap-y-1 text-slate-200">
                    <span>Driver: <strong class="text-emerald-400 uppercase font-mono">{{ $mailConfig['mailer'] }}</strong></span>
                    <span>Mittente: <strong class="text-white">{{ $mailConfig['from_address'] }}</strong> ({{ $mailConfig['from_name'] }})</span>
                    @if($mailConfig['host'])
                        <span>Host: <strong class="text-slate-300 font-mono">{{ $mailConfig['host'] }}:{{ $mailConfig['port'] }}</strong></span>
                    @endif
                </div>
                <p class="text-[11px] text-slate-400">
                    @if($mailConfig['mailer'] === 'log')
                        Le email generate vengono memorizzate nel registro locale e nei log di audit. Connessione SMTP configurabile tramite <code class="text-indigo-300 font-mono">.env</code>.
                    @else
                        Il server inoltra le email tramite il servizio SMTP esterno configurato (<strong class="text-emerald-300">{{ $mailConfig['host'] }}</strong>).
                    @endif
                </p>
            </div>
            <div class="shrink-0 flex items-center gap-2">
                <button type="button" @click="testModal = true" class="px-3 py-1.5 rounded-lg bg-white/10 hover:bg-white/20 text-white font-bold text-xs transition cursor-pointer">
                    Testa Invio Rapido &rarr;
                </button>
            </div>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="flex border-b border-slate-200 gap-2">
        <button type="button"
                @click="activeTab = 'templates'"
                :class="activeTab === 'templates' ? 'border-indigo-600 text-indigo-600 bg-white shadow-2xs font-extrabold' : 'border-transparent text-slate-500 hover:text-slate-800 font-bold'"
                class="px-5 py-3 border-b-2 text-xs sm:text-sm rounded-t-xl transition cursor-pointer flex items-center gap-2">
            <span>⚙️ Configurazione Notifiche & Template Email (ON/OFF)</span>
        </button>
        <button type="button"
                @click="activeTab = 'logs'"
                :class="activeTab === 'logs' ? 'border-indigo-600 text-indigo-600 bg-white shadow-2xs font-extrabold' : 'border-transparent text-slate-500 hover:text-slate-800 font-bold'"
                class="px-5 py-3 border-b-2 text-xs sm:text-sm rounded-t-xl transition cursor-pointer flex items-center gap-2">
            <span>📜 Registro Log Email Inviate</span>
            <span class="px-2 py-0.5 rounded-full bg-slate-100 text-slate-600 text-[10px] font-mono">{{ $totalSent }}</span>
        </button>
    </div>

    <!-- TAB 1: CONFIGURAZIONE & TEMPLATE EMAIL -->
    <div x-show="activeTab === 'templates'" class="space-y-6">

        <!-- Info Box Tag Dinamici -->
        <div class="bg-indigo-50/70 border border-indigo-200 rounded-2xl p-4 text-xs text-indigo-950 flex flex-col md:flex-row md:items-center justify-between gap-3">
            <div>
                <strong class="font-extrabold">📌 Segnaposto Dinamici Supportati:</strong>
                <p class="text-indigo-800 text-[11px] mt-0.5">
                    Puoi inserire questi tag nell'oggetto e nel corpo del messaggio; verranno sostituiti automaticamente con i dati reali del corsista:
                </p>
                <div class="flex flex-wrap gap-1.5 mt-2">
                    <span class="font-mono bg-white px-2 py-0.5 rounded border border-indigo-200 text-indigo-700">{nome}</span>
                    <span class="font-mono bg-white px-2 py-0.5 rounded border border-indigo-200 text-indigo-700">{cognome}</span>
                    <span class="font-mono bg-white px-2 py-0.5 rounded border border-indigo-200 text-indigo-700">{email}</span>
                    <span class="font-mono bg-white px-2 py-0.5 rounded border border-indigo-200 text-indigo-700">{cf}</span>
                    <span class="font-mono bg-white px-2 py-0.5 rounded border border-indigo-200 text-indigo-700">{corso}</span>
                    <span class="font-mono bg-white px-2 py-0.5 rounded border border-indigo-200 text-indigo-700">{ente}</span>
                    <span class="font-mono bg-white px-2 py-0.5 rounded border border-indigo-200 text-indigo-700">{piattaforma}</span>
                </div>
            </div>
            <div class="shrink-0 text-right">
                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-emerald-100 text-emerald-800 font-bold text-[11px]">
                    ✓ Personalizzazione Formacamera Attiva
                </span>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.emails.settings.update') }}" class="space-y-6">
            @csrf

            <!-- 1. Email Benvenuto & Registrazione Corsista -->
            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-2xs space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-3">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-indigo-50 border border-indigo-100 flex items-center justify-center text-lg">
                            🎓
                        </div>
                        <div>
                            <h3 class="font-extrabold text-sm sm:text-base text-slate-900">1. Email di Benvenuto & Conferma Registrazione</h3>
                            <p class="text-xs text-slate-500">Inviata al momento dell'auto-registrazione del corsista sulla piattaforma.</p>
                        </div>
                    </div>
                    <div>
                        <label class="inline-flex items-center gap-2 cursor-pointer select-none">
                            <input type="checkbox" name="mail_welcome_active" value="1"
                                   {{ ($settings['mail_welcome_active'] ?? '1') === '1' ? 'checked' : '' }}
                                   class="w-4 h-4 text-indigo-600 rounded border-slate-300 focus:ring-indigo-500">
                            <span class="text-xs font-bold {{ ($settings['mail_welcome_active'] ?? '1') === '1' ? 'text-emerald-700' : 'text-slate-400' }}">
                                {{ ($settings['mail_welcome_active'] ?? '1') === '1' ? 'Abilitata (Spedita in automatico)' : 'Disattivata' }}
                            </span>
                        </label>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 text-xs">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Oggetto dell'Email:</label>
                        <input type="text" name="mail_welcome_subject"
                               value="{{ $settings['mail_welcome_subject'] ?? 'Benvenuto su {platform_name} — Conferma Registrazione Profilo Corsista' }}"
                               class="w-full rounded-xl border border-slate-300 px-3.5 py-2 text-xs font-semibold focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Testo / Messaggio Principale:</label>
                        <textarea name="mail_welcome_body" rows="3"
                                  class="w-full rounded-xl border border-slate-300 px-3.5 py-2 text-xs focus:ring-2 focus:ring-indigo-500">{{ $settings['mail_welcome_body'] ?? 'ti confermiamo che il tuo profilo anagrafico è stato registrato con successo sulla nostra piattaforma di Formazione a Distanza di Formacamera.' }}</textarea>
                    </div>
                </div>
            </div>

            <!-- 2. Email Assegnazione Corso da Segreteria -->
            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-2xs space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-3">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-sky-50 border border-sky-100 flex items-center justify-center text-lg">
                            📚
                        </div>
                        <div>
                            <h3 class="font-extrabold text-sm sm:text-base text-slate-900">2. Email Assegnazione Corso Didattico</h3>
                            <p class="text-xs text-slate-500">Inviata quando l'amministratore assegna manualmente o massivamente un corso a un corsista.</p>
                        </div>
                    </div>
                    <div>
                        <label class="inline-flex items-center gap-2 cursor-pointer select-none">
                            <input type="checkbox" name="mail_assigned_active" value="1"
                                   {{ ($settings['mail_assigned_active'] ?? '1') === '1' ? 'checked' : '' }}
                                   class="w-4 h-4 text-indigo-600 rounded border-slate-300 focus:ring-indigo-500">
                            <span class="text-xs font-bold {{ ($settings['mail_assigned_active'] ?? '1') === '1' ? 'text-emerald-700' : 'text-slate-400' }}">
                                {{ ($settings['mail_assigned_active'] ?? '1') === '1' ? 'Abilitata (Spedita in automatico)' : 'Disattivata' }}
                            </span>
                        </label>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 text-xs">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Oggetto dell'Email:</label>
                        <input type="text" name="mail_assigned_subject"
                               value="{{ $settings['mail_assigned_subject'] ?? 'Nuovo Corso Assegnato — {course_title}' }}"
                               class="w-full rounded-xl border border-slate-300 px-3.5 py-2 text-xs font-semibold focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Testo / Messaggio Principale:</label>
                        <textarea name="mail_assigned_body" rows="3"
                                  class="w-full rounded-xl border border-slate-300 px-3.5 py-2 text-xs focus:ring-2 focus:ring-indigo-500">{{ $settings['mail_assigned_body'] ?? 'ti comunichiamo che sei stato iscritto al corso "{course_title}". Puoi accedere alla tua area riservata per consultare il programma e iniziare le lezioni.' }}</textarea>
                    </div>
                </div>
            </div>

            <!-- 3. Email Riscatto Codice Invito -->
            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-2xs space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-3">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-amber-50 border border-amber-100 flex items-center justify-center text-lg">
                            🔑
                        </div>
                        <div>
                            <h3 class="font-extrabold text-sm sm:text-base text-slate-900">3. Email Riscatto Corso con Codice Invito</h3>
                            <p class="text-xs text-slate-500">Inviata quando il corsista inserisce autonomamente un codice invito (es. Forma 2026).</p>
                        </div>
                    </div>
                    <div>
                        <label class="inline-flex items-center gap-2 cursor-pointer select-none">
                            <input type="checkbox" name="mail_redeemed_active" value="1"
                                   {{ ($settings['mail_redeemed_active'] ?? '1') === '1' ? 'checked' : '' }}
                                   class="w-4 h-4 text-indigo-600 rounded border-slate-300 focus:ring-indigo-500">
                            <span class="text-xs font-bold {{ ($settings['mail_redeemed_active'] ?? '1') === '1' ? 'text-emerald-700' : 'text-slate-400' }}">
                                {{ ($settings['mail_redeemed_active'] ?? '1') === '1' ? 'Abilitata (Spedita in automatico)' : 'Disattivata' }}
                            </span>
                        </label>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 text-xs">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Oggetto dell'Email:</label>
                        <input type="text" name="mail_redeemed_subject"
                               value="{{ $settings['mail_redeemed_subject'] ?? 'Codice Invito Riscattato con Successo — {course_title}' }}"
                               class="w-full rounded-xl border border-slate-300 px-3.5 py-2 text-xs font-semibold focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Testo / Messaggio Principale:</label>
                        <textarea name="mail_redeemed_body" rows="3"
                                  class="w-full rounded-xl border border-slate-300 px-3.5 py-2 text-xs focus:ring-2 focus:ring-indigo-500">{{ $settings['mail_redeemed_body'] ?? 'hai riscattato con successo il codice invito per il corso "{course_title}". Il corso è ora attivo nella tua dashboard.' }}</textarea>
                    </div>
                </div>
            </div>

            <!-- 4. Email Completamento Modulo Didattico -->
            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-2xs space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-3">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-emerald-50 border border-emerald-100 flex items-center justify-center text-lg">
                            ✅
                        </div>
                        <div>
                            <h3 class="font-extrabold text-sm sm:text-base text-slate-900">4. Email di Completamento Singolo Modulo</h3>
                            <p class="text-xs text-slate-500">Notifica inviata al discente quando completa al 100% la visione di un modulo asincrono.</p>
                        </div>
                    </div>
                    <div>
                        <label class="inline-flex items-center gap-2 cursor-pointer select-none">
                            <input type="checkbox" name="mail_module_completed_active" value="1"
                                   {{ ($settings['mail_module_completed_active'] ?? '1') === '1' ? 'checked' : '' }}
                                   class="w-4 h-4 text-indigo-600 rounded border-slate-300 focus:ring-indigo-500">
                            <span class="text-xs font-bold {{ ($settings['mail_module_completed_active'] ?? '1') === '1' ? 'text-emerald-700' : 'text-slate-400' }}">
                                {{ ($settings['mail_module_completed_active'] ?? '1') === '1' ? 'Abilitata (Spedita in automatico)' : 'Disattivata' }}
                            </span>
                        </label>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 text-xs">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Oggetto dell'Email:</label>
                        <input type="text" name="mail_module_completed_subject"
                               value="{{ $settings['mail_module_completed_subject'] ?? 'Complimenti! Hai completato il modulo didattico {module_title}' }}"
                               class="w-full rounded-xl border border-slate-300 px-3.5 py-2 text-xs font-semibold focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Testo / Messaggio Principale:</label>
                        <textarea name="mail_module_completed_body" rows="3"
                                  class="w-full rounded-xl border border-slate-300 px-3.5 py-2 text-xs focus:ring-2 focus:ring-indigo-500">{{ $settings['mail_module_completed_body'] ?? 'hai completato con successo la visione e lo studio del modulo. Il tuo avanzamento orario è stato registrato nel fascicolo presenze.' }}</textarea>
                    </div>
                </div>
            </div>

            <!-- 5. Email Conseguimento Attestato Finale -->
            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-2xs space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-3">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-amber-50 border border-amber-200 flex items-center justify-center text-lg">
                            🏆
                        </div>
                        <div>
                            <h3 class="font-extrabold text-sm sm:text-base text-slate-900">5. Email Conseguimento Attestato di Frequenza</h3>
                            <p class="text-xs text-slate-500">Inviata quando il discente raggiunge o supera la soglia minima di frequenza richiesta.</p>
                        </div>
                    </div>
                    <div>
                        <label class="inline-flex items-center gap-2 cursor-pointer select-none">
                            <input type="checkbox" name="mail_certificate_active" value="1"
                                   {{ ($settings['mail_certificate_active'] ?? '1') === '1' ? 'checked' : '' }}
                                   class="w-4 h-4 text-indigo-600 rounded border-slate-300 focus:ring-indigo-500">
                            <span class="text-xs font-bold {{ ($settings['mail_certificate_active'] ?? '1') === '1' ? 'text-emerald-700' : 'text-slate-400' }}">
                                {{ ($settings['mail_certificate_active'] ?? '1') === '1' ? 'Abilitata (Spedita in automatico)' : 'Disattivata' }}
                            </span>
                        </label>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 text-xs">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Oggetto dell'Email:</label>
                        <input type="text" name="mail_certificate_subject"
                               value="{{ $settings['mail_certificate_subject'] ?? 'Attestato di Frequenza Conseguito — {course_title}' }}"
                               class="w-full rounded-xl border border-slate-300 px-3.5 py-2 text-xs font-semibold focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Testo / Messaggio Principale:</label>
                        <textarea name="mail_certificate_body" rows="3"
                                  class="w-full rounded-xl border border-slate-300 px-3.5 py-2 text-xs focus:ring-2 focus:ring-indigo-500">{{ $settings['mail_certificate_body'] ?? 'hai completato con successo il percorso formativo e superato la soglia ministeriale di frequenza. Il tuo attestato ufficiale con codice anticontraffazione è pronto per il download.' }}</textarea>
                    </div>
                </div>
            </div>

            <!-- 6. Email Approvazione Account -->
            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-2xs space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-3">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-purple-50 border border-purple-100 flex items-center justify-center text-lg">
                            🛡️
                        </div>
                        <div>
                            <h3 class="font-extrabold text-sm sm:text-base text-slate-900">6. Email Approvazione Account Corsista</h3>
                            <p class="text-xs text-slate-500">Inviata se l'approvazione delle registrazioni è impostata su manuale e l'admin convalida il corsista.</p>
                        </div>
                    </div>
                    <div>
                        <label class="inline-flex items-center gap-2 cursor-pointer select-none">
                            <input type="checkbox" name="mail_approved_active" value="1"
                                   {{ ($settings['mail_approved_active'] ?? '1') === '1' ? 'checked' : '' }}
                                   class="w-4 h-4 text-indigo-600 rounded border-slate-300 focus:ring-indigo-500">
                            <span class="text-xs font-bold {{ ($settings['mail_approved_active'] ?? '1') === '1' ? 'text-emerald-700' : 'text-slate-400' }}">
                                {{ ($settings['mail_approved_active'] ?? '1') === '1' ? 'Abilitata (Spedita in automatico)' : 'Disattivata' }}
                            </span>
                        </label>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 text-xs">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Oggetto dell'Email:</label>
                        <input type="text" name="mail_approved_subject"
                               value="{{ $settings['mail_approved_subject'] ?? 'Account Corsista Approvato — {platform_name}' }}"
                               class="w-full rounded-xl border border-slate-300 px-3.5 py-2 text-xs font-semibold focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Testo / Messaggio Principale:</label>
                        <textarea name="mail_approved_body" rows="3"
                                  class="w-full rounded-xl border border-slate-300 px-3.5 py-2 text-xs focus:ring-2 focus:ring-indigo-500">{{ $settings['mail_approved_body'] ?? 'il tuo account è stato verificato e approvato dalla segreteria didattica. Ora puoi accedere liberamente a tutti i corsi a te assegnati.' }}</textarea>
                    </div>
                </div>
            </div>

            <!-- Pulsante Salva Impostazioni -->
            <div class="sticky bottom-4 bg-white/95 backdrop-blur-xs border border-slate-200 rounded-2xl p-4 shadow-xl flex items-center justify-between gap-4 z-20">
                <div class="text-xs text-slate-500">
                    Le modifiche avranno effetto immediato su tutte le prossime email generate dalla piattaforma.
                </div>
                <button type="submit"
                        class="px-6 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-extrabold text-xs shadow-md transition cursor-pointer flex items-center gap-2">
                    <span>💾 Salva Configurazione Email</span>
                </button>
            </div>
        </form>
    </div>

    <!-- TAB 2: REGISTRO LOG EMAIL INVIATE -->
    <div x-show="activeTab === 'logs'" class="space-y-6">

        <!-- Search & Filters -->
        <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-2xs flex flex-wrap items-center justify-between gap-3 text-xs">
            <form method="GET" action="{{ route('admin.emails.index') }}" class="flex flex-wrap items-center gap-2 flex-1">
                <input type="hidden" name="tab" value="logs">
                <div class="relative flex-1 min-w-[240px]">
                    <input type="text" name="search" value="{{ $search }}"
                           placeholder="Cerca per email destinatario o oggetto..."
                           class="w-full rounded-xl border border-slate-300 px-3.5 py-2 pl-9 text-xs focus:ring-2 focus:ring-indigo-500">
                    <span class="absolute left-3 top-2.5 text-slate-400">🔍</span>
                </div>
                <button type="submit" class="px-4 py-2 rounded-xl bg-slate-900 text-white font-bold cursor-pointer transition">
                    Cerca
                </button>
                @if($search !== '')
                    <a href="{{ route('admin.emails.index', ['tab' => 'logs']) }}" class="px-3 py-2 text-slate-500 hover:text-slate-800 font-semibold">
                        Azzera Ricerca
                    </a>
                @endif
            </form>
        </div>

        <!-- Sent Emails Table -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-2xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-50/80 border-b border-slate-200 text-[11px] font-bold uppercase text-slate-500">
                            <th class="py-3 px-4">Data & Ora</th>
                            <th class="py-3 px-4">Destinatario</th>
                            <th class="py-3 px-4">Oggetto Email</th>
                            <th class="py-3 px-4">Mittente</th>
                            <th class="py-3 px-4 text-center">Stato</th>
                            <th class="py-3 px-4 text-right">Azioni</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @forelse($emails as $item)
                            <tr class="hover:bg-slate-50/60 transition">
                                <td class="py-3.5 px-4 font-mono text-[11px] text-slate-600 whitespace-nowrap">
                                    <div class="font-bold text-slate-800">{{ $item->created_at->format('d/m/Y H:i:s') }}</div>
                                    <div class="text-[10px] text-slate-400">{{ $item->created_at->diffForHumans() }}</div>
                                </td>
                                <td class="py-3.5 px-4">
                                    <span class="font-bold text-slate-900 font-mono text-[11px] bg-slate-100 px-2 py-0.5 rounded">
                                        {{ $item->to_email }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 max-w-md">
                                    <div class="font-extrabold text-slate-900 line-clamp-1">{{ $item->subject }}</div>
                                    @if($item->body_text)
                                        <div class="text-[10px] text-slate-400 line-clamp-1 mt-0.5">
                                            {{ Str::limit(strip_tags($item->body_text), 80) }}
                                        </div>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4 font-mono text-[11px] text-slate-500">
                                    {{ $item->from_email ?: config('mail.from.address') }}
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 uppercase tracking-wider">
                                        ✓ Inviata
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-right space-x-1 whitespace-nowrap">
                                    <button type="button"
                                            @click="openPreview({
                                                id: {{ $item->id }},
                                                to_email: @js($item->to_email),
                                                subject: @js($item->subject),
                                                created_at_formatted: '{{ $item->created_at->format('d/m/Y H:i') }}'
                                            })"
                                            class="px-2.5 py-1.5 rounded-lg border border-slate-200 hover:bg-indigo-50 hover:text-indigo-600 hover:border-indigo-200 text-slate-700 font-bold transition cursor-pointer">
                                        👁 Anteprima
                                    </button>
                                    <form method="POST" action="{{ route('admin.emails.destroy', $item) }}" class="inline"
                                          onsubmit="return confirm('Vuoi eliminare questa riga dal log?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-1.5 rounded-lg hover:bg-rose-50 text-slate-400 hover:text-rose-600 transition cursor-pointer" title="Elimina log">
                                            🗑
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-12 text-center text-slate-400">
                                    <div class="text-3xl mb-2">📭</div>
                                    <div class="font-bold text-sm text-slate-600">Nessuna email registrata</div>
                                    <div class="text-xs text-slate-400 mt-1">
                                        {{ $search !== '' ? 'Nessun risultato corrisponde alla ricerca corrente.' : 'Le email inviate dal sistema o dai test compariranno automaticamente qui.' }}
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($emails->hasPages())
                <div class="p-4 border-t border-slate-200">
                    {{ $emails->links() }}
                </div>
            @endif
        </div>
    </div>

    <!-- MODAL: PREVIEW EMAIL -->
    <div x-cloak x-show="previewModal" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" @click="previewModal = false"></div>
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative w-full max-w-3xl bg-white rounded-2xl shadow-2xl border border-slate-200 overflow-hidden flex flex-col max-h-[90vh]">
                <!-- Modal Header -->
                <div class="p-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between gap-4">
                    <div class="min-w-0">
                        <div class="text-xs text-slate-500">
                            Destinatario: <strong class="font-mono text-slate-900" x-text="previewEmail.to"></strong>
                            • Data: <span class="text-slate-600" x-text="previewEmail.date"></span>
                        </div>
                        <h3 class="text-base font-extrabold text-slate-900 truncate mt-0.5" x-text="previewEmail.subject"></h3>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <a :href="previewEmail.url" target="_blank"
                           class="px-3 py-1.5 rounded-lg border border-slate-300 text-xs font-bold text-slate-700 hover:bg-slate-100 transition">
                            Apri a Schermo Intero ↗
                        </a>
                        <button type="button" @click="previewModal = false"
                                class="w-8 h-8 rounded-lg bg-slate-200 hover:bg-slate-300 text-slate-700 font-black flex items-center justify-center cursor-pointer">
                            &times;
                        </button>
                    </div>
                </div>

                <!-- Modal Body: iFrame Preview -->
                <div class="flex-1 bg-slate-100 p-2 overflow-hidden flex items-center justify-center">
                    <iframe :src="previewEmail.url" class="w-full h-[65vh] bg-white rounded-xl shadow-xs border border-slate-200"></iframe>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL: SEND TEST EMAIL -->
    <div x-cloak x-show="testModal" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" @click="testModal = false"></div>
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative w-full max-w-lg bg-white rounded-2xl shadow-2xl border border-slate-200 p-6 space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div class="flex items-center gap-2">
                        <span class="text-xl">🚀</span>
                        <h3 class="text-base font-extrabold text-slate-900">Invia Email di Test</h3>
                    </div>
                    <button type="button" @click="testModal = false" class="text-slate-400 hover:text-slate-600 font-bold text-lg cursor-pointer">
                        &times;
                    </button>
                </div>

                <form method="POST" action="{{ route('admin.emails.send-test') }}" class="space-y-4 text-xs">
                    @csrf

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Seleziona Tipo di Email da Testare:</label>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                            <label class="border rounded-xl p-2.5 flex flex-col items-center text-center cursor-pointer transition"
                                   :class="testType === 'welcome' ? 'border-indigo-600 bg-indigo-50/50 text-indigo-900 font-bold' : 'border-slate-200 hover:bg-slate-50 text-slate-600'">
                                <input type="radio" name="test_type" value="welcome" x-model="testType" class="sr-only">
                                <span class="text-base mb-1">🎓</span>
                                <span class="text-[11px]">Benvenuto</span>
                            </label>
                            <label class="border rounded-xl p-2.5 flex flex-col items-center text-center cursor-pointer transition"
                                   :class="testType === 'assigned' ? 'border-indigo-600 bg-indigo-50/50 text-indigo-900 font-bold' : 'border-slate-200 hover:bg-slate-50 text-slate-600'">
                                <input type="radio" name="test_type" value="assigned" x-model="testType" class="sr-only">
                                <span class="text-base mb-1">📚</span>
                                <span class="text-[11px]">Corso Assegnato</span>
                            </label>
                            <label class="border rounded-xl p-2.5 flex flex-col items-center text-center cursor-pointer transition"
                                   :class="testType === 'redeemed' ? 'border-indigo-600 bg-indigo-50/50 text-indigo-900 font-bold' : 'border-slate-200 hover:bg-slate-50 text-slate-600'">
                                <input type="radio" name="test_type" value="redeemed" x-model="testType" class="sr-only">
                                <span class="text-base mb-1">🔑</span>
                                <span class="text-[11px]">Codice Invito</span>
                            </label>
                            <label class="border rounded-xl p-2.5 flex flex-col items-center text-center cursor-pointer transition"
                                   :class="testType === 'module_completed' ? 'border-indigo-600 bg-indigo-50/50 text-indigo-900 font-bold' : 'border-slate-200 hover:bg-slate-50 text-slate-600'">
                                <input type="radio" name="test_type" value="module_completed" x-model="testType" class="sr-only">
                                <span class="text-base mb-1">✅</span>
                                <span class="text-[11px]">Modulo Finito</span>
                            </label>
                            <label class="border rounded-xl p-2.5 flex flex-col items-center text-center cursor-pointer transition"
                                   :class="testType === 'certificate' ? 'border-indigo-600 bg-indigo-50/50 text-indigo-900 font-bold' : 'border-slate-200 hover:bg-slate-50 text-slate-600'">
                                <input type="radio" name="test_type" value="certificate" x-model="testType" class="sr-only">
                                <span class="text-base mb-1">🏆</span>
                                <span class="text-[11px]">Attestato</span>
                            </label>
                            <label class="border rounded-xl p-2.5 flex flex-col items-center text-center cursor-pointer transition"
                                   :class="testType === 'approved' ? 'border-indigo-600 bg-indigo-50/50 text-indigo-900 font-bold' : 'border-slate-200 hover:bg-slate-50 text-slate-600'">
                                <input type="radio" name="test_type" value="approved" x-model="testType" class="sr-only">
                                <span class="text-base mb-1">🛡️</span>
                                <span class="text-[11px]">Approvazione</span>
                            </label>
                        </div>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Destinatario (Email):</label>
                        <input type="email" name="recipient_email" required value="{{ auth()->user()->email ?? 'test@example.com' }}"
                               placeholder="es. corsista@example.com"
                               class="w-full rounded-xl border border-slate-300 px-3 py-2 text-xs focus:ring-2 focus:ring-indigo-500">
                    </div>

                    <!-- Corso correlato -->
                    <div x-show="testType === 'welcome' || testType === 'assigned' || testType === 'module_completed' || testType === 'certificate' || testType === 'redeemed'">
                        <label class="block font-bold text-slate-700 mb-1">Corso di Esempio:</label>
                        <select name="course_id" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-xs">
                            @foreach($courses as $c)
                                <option value="{{ $c->id }}">[{{ $c->code }}] {{ $c->title }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                        <button type="button" @click="testModal = false" class="px-4 py-2 rounded-xl border border-slate-200 text-slate-600 font-bold hover:bg-slate-50 transition cursor-pointer">
                            Annulla
                        </button>
                        <button type="submit" class="px-5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold transition shadow-xs cursor-pointer">
                            Invia e Registra Log
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>
@endsection
