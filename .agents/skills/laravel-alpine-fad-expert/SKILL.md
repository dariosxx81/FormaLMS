---
name: laravel-alpine-fad-expert
description: >-
  Use this skill automatically for any PHP 8.2+, Laravel, and Alpine.js task
  in this workspace. Defines the architecture, database schema, Alpine.js
  anti-skip video player telemetry, hybrid enrollment flows, unified
  synchronous/asynchronous attendance tracking, and Ministerial PDF generation
  (Linee Guida B - Allegato 3 & 4) for White-Label FAD / LMS platforms.
---

# Laravel + Alpine.js Full-Stack FAD / LMS Expert Skill

## 1. Stack Tecnologico & Architettura White-Label
- **Backend**: PHP 8.2+ (`C:\php82\php.exe`) + **Laravel 11/12** con architettura MVC pulita, Eloquent ORM, Form Requests, Middleware di ruolo (`admin` / `student`) e Audit Logger centralizzato.
- **Frontend Reattivo**: **Blade Components + Alpine.js 3.x + Tailwind CSS** (zero dipendenze pesanti SPA, interazioni istantanee per modali, tab, filtri live, upload progress e player video telemetrico).
- **Database**: Configurato in locale su **SQLite** (`database/database.sqlite`) per zero-config demo immediata e 100% compatibile con **MySQL / MariaDB** in produzione tramite migrazioni standard Laravel.
- **Stile & Identità**: **White-Label Neutro Professionale** (nome piattaforma, ente erogatore, codice ATECO/CP ISTAT e colori primari parametrizzabili da impostazioni Admin, così da poter essere presentato a qualsiasi Ente di Formazione senza modifiche al codice).

## 2. Regole di Dominio & Flussi Funzionali

### A. Ruoli & Onboarding Ibrido (`admin` vs `student`)
1. **2 Ruoli Semplici**:
   - `admin`: Gestione catalogo corsi, moduli video, dispense PDF, iscrizioni (manuali, approvazione auto-registrazioni, import CSV), caricamento ore sincrone (Zoom), monitoraggio log e generazione PDF ufficiali.
   - `student` (Corsista): Dashboard corsi assegnati, fruizione player video con ripresa automatica (*auto-resume*), scaricamento dispense PDF, visualizzazione stato avanzamento (Sincrono + Asincrono) e download Attestato al raggiungimento della soglia.
2. **Anagrafica Completa Ministeriale**:
   - Campi utente: `first_name`, `last_name`, `fiscal_code` (CF), `birth_date`, `birth_city`, `birth_province`, `birth_country`, `email`, `phone`, `national_list_number` (opzionale).
3. **Flusso di Iscrizione Ibrido**:
   - **Auto-registrazione pubblica** (`/register`, anche con selezione corso o codice invito) con stato `pending` o `active` gestibile dall'Admin.
   - **Creazione manuale / Importazione Massiva CSV** da Pannello Admin con assegnazione immediata a uno o più corsi.

### B. Catalogo Multi-Corso (Video + Dispense PDF, Zero Quiz)
- Ogni **Corso (`Course`)** supporta:
  - Titolo, codice identificativo, ambito tematico/territoriale (Art. 2 Linee Guida B), estremi provvedimento autorizzazione ministeriale, ente/polo formativo, città/provincia sede.
  - Configurazione monte ore: `total_hours` (es. 25h), `sync_required_hours` (es. 20h su Zoom), `async_required_hours` (es. 5h FAD), `min_attendance_percent` (default `80%`).
  - **Switch di Controllo Player per Corso**:
    - `strict_tab_pause` (boolean): mette in pausa automaticamente il video se l'utente cambia scheda/finestra (`visibilitychange`).
    - `presence_check_interval_minutes` (int, `0` = disattivato): mostra un modale *"Sei ancora presente?"* ogni $N$ minuti mettendo in pausa il video finché l'utente non conferma.
- Ogni **Modulo (`CourseModule`)** supporta:
  - Doppia sorgente video: **Upload diretto file `.mp4`** su storage locale/cloud oppure **URL video diretto** (`.mp4` / HLS).
  - Durata in secondi/minuti, ordine di visualizzazione e **Dispense PDF allegate (`ModuleAttachment`)** scaricabili e tracciate.

### C. Smart Video Player Anti-Skip & Telemetria (`Alpine.data('fadVideoPlayer')`)
- **Regola base Alpine.js**: Registrare sempre la logica in `Alpine.data('fadVideoPlayer', ...)` dentro un blocco `<script>` pulito.
- **Anti-Skip (Blocco Fast-Forward)**:
  - L'utente può tornare indietro liberamente (`currentTime <= maxPositionSeconds`), ma ogni tentativo di `seeking` oltre `maxPositionSeconds + 2` riporta immediatamente `video.currentTime = maxPositionSeconds`.
  - Disabilitare `playbackRate > 1.0` per garantire la corrispondenza 1:1 con il tempo formativo reale.
- **Heartbeat & Auto-Resume**:
  - Ogni **15 secondi** di riproduzione effettiva (e sugli eventi `pause`, `ended`, `beforeunload`), invia `POST /courses/{course}/modules/{module}/heartbeat` con `{ current_time, delta_watched_seconds, event_type }`.
  - Il backend aggiorna `ModuleProgress` (`watched_seconds`, `max_position_seconds`, `completion_percent`, `is_completed`) e aggiorna il record di sessione `WatchLog` (`ip_address`, `user_agent`, `started_at`, `last_heartbeat_at`, `seconds_counted`).
- **Audit Log Universale (`ActivityLog`)**:
  - Traccia `login`, `logout`, `module_start`, `module_complete`, `attachment_download`, `certificate_download`, `sync_hours_import` con IP, User-Agent e timestamp.

### D. Registro Unificato (Zoom Sincrono + FAD Asincrono) & Documenti PDF Ministeriali
- Per ogni iscrizione (`Enrollment`), il sistema calcola:
  - `sync_hours_completed` (inserite manualmente o importate da CSV report Zoom).
  - `async_hours_completed` (calcolate in tempo reale dalla somma dei secondi maturati sui moduli del corso).
  - `total_hours_completed = sync_hours_completed + async_hours_completed`.
  - Semaforo conformità (`>= 80%` del monte ore totale del corso).
- **Esportazioni Ufficiali**:
  1. **Registro Presenze & Log Analitico Ministeriale (PDF / CSV)**: dettaglio sessioni, IP, orari di connessione e ore sincrone/asincrone per ispezioni ministeriali.
  2. **Attestato di Frequenza / Completamento (Allegato 3 / Allegato 4 Linee Guida B)**: generato con layout conforme al modello ufficiale del Ministero del Turismo.
