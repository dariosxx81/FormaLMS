# PREVENTIVO INTEGRATIVO — MODULO ADD-ON "ZOOM LIVE CLOUD API & TELEMETRIA SINCRONA CERTIFICATA"
## Automazione Server-to-Server OAuth, Identità Bloccata, Webhook Real-Time e Certificazione Ministeriale

---

**Rif. Documento:** PREV-2026/FORMACAMERA-ZOOM-02 (Add-On al Rif. PREV-2026/FORMACAMERA-FAD-01)  
**Data di Emissione:** 2 Novembre 2026  
**Validità Offerta:** 30 giorni  
**Committente:** Spett.le **FormaCamera — Azienda Speciale della Camera di Commercio di Roma**  
**Cortese Attenzione:** **Dott.ssa Daniela De Vita**  
**Fornitore / Sviluppatore:** Dario — Consulente Sviluppo Web & Software Freelance  
**Oggetto:** Sviluppo e integrazione del Modulo Aggiuntivo opzionale **"Zoom Live Cloud API"** per l'automazione completa delle aule sincrone, riconoscimento certificato del corsista (Nome, Cognome, Codice Fiscale bloccati), calcolo automatico al secondo delle presenze dai server Zoom (anche a finestra browser chiusa), blocco anti-manomissione delle ore sincrone e integrazione nei Fascicoli Probanti Ministeriali (D.M. 26 giugno 2024, n. 88).

---

> [!IMPORTANT]
> ### QUADRO ECONOMICO DI SINTESI (MODULO ADD-ON OPZIONALE)
> * **STIMA IMPIEGO TECNICO COMPLESSIVO:** **18 Ore di Sviluppo, Integrazione API & Collaudo**  
> * **PREZZO FINALE A CORPO DEL MODULO ADD-ON:** **€ 650,00 (Seicentocinquanta/00) — IVA INCLUSA**  
>   *(Fornitura chiavi in mano comprensiva di sviluppo connettore Server-to-Server OAuth Zoom Workplace Business, webhook real-time, adeguamento PDF ministeriali e supporto alla configurazione delle chiavi nel Zoom Marketplace dell'Ente. Nessun canone mensile o annuale aggiuntivo).*

---

## 1. Perché Attivare il Modulo "Zoom Live Cloud API" (Valore Legale e Operativo)

Nel preventivo base della piattaforma FAD era previsto il caricamento manuale dei fogli presenze da parte della Segreteria.  
Con l'attivazione di questo **Modulo Add-On Zoom Live Cloud API**, la piattaforma **FormaLMS** dialoga direttamente, da server a server, con la licenza **Zoom Workplace Business** di FormaCamera, risolvendo 4 criticità fondamentali in sede di ispezione ministeriale:

1. **Identità Certificata e Bloccata del Corsista (Addio Nomi Anonimi su Zoom):**  
   Quando il corsista clicca su *"Entra nella Lezione Live Zoom"* dentro la piattaforma, il sistema lo registra in tempo reale tramite le **Registrant API di Zoom** trasmettendo **Nome, Cognome, Email e Codice Fiscale** verificati. Zoom restituisce un **Link Personale Univoco con Token Crittografato (`?tk=...`)**: il corsista entra direttamente in aula senza dover digitare il proprio nome e senza poter entrare con nickname irriconoscibili (*"iPhone"*, *"Utente Zoom"*, ecc.).

2. **Tracciamento 100% lato Server Zoom (Anche se il Corsista Chiude il Browser):**  
   I secondi di presenza non dipendono dall'aver lasciato aperta la scheda del browser del corso: vengono acquisiti direttamente dai **Server Cloud di Zoom** tramite **Webhook Real-Time** (`meeting.participant_left` / `meeting.ended`) e **Reports API ufficiali**. Se il corsista si disconnette e rientra più volte nella stessa lezione, il motore somma al secondo tutte le frazioni di presenza effettiva.

3. **Blocco Anti-Manomissione delle Ore Sincrone (`🔒 Certificato`):**  
   In conformità ai requisiti di inalterabilità dei registri per corsi accreditati a livello ministeriale, quando il modulo Zoom è attivo **viene disabilitata la forzatura manuale delle ore sincrone**. Il monte ore sincrono diventa un dato calcolato esclusivamente dai log certificati delle sessioni Zoom svolte.

4. **Prova Ispettiva Completa nei Fascicoli Probanti PDF:**  
   Sia il **Fascicolo Probante Generale del Corso** (per la Segreteria/Ministero) sia l'**Audit Personale PDF del Corsista** riportano una sezione dedicata con **Meeting ID**, **Registrant ID Univoco**, **Timestamp di Primo Ingresso**, **Timestamp di Ultima Uscita** e **Secondi Netti Certificati** da Zoom Cloud.

---

## 2. Prospetto Analitico delle Attività e Stima Oraria

Di seguito il dettaglio tecnico dei componenti software sviluppati per il modulo e il relativo impegno orario:

| # | Componente Tecnico / Attività | Descrizione Dettagliata dell'Intervento | Ore Stimate |
| :-: | :--- | :--- | :-: |
| **1** | **Connettore Server-to-Server OAuth 2.0 Zoom** | Sviluppo del servizio backend (`ZoomService`), negoziazione automatica e caching sicuro degli Access Token OAuth con i server Zoom, pannello di controllo Admin in *Impostazioni -> Modulo Zoom Live* con attivazione/disattivazione istantanea. | **3,5 ore** |
| **2** | **Gestione Moduli Didattici Sincroni & UI Player** | Estensione del database e del gestionale corsi per supportare moduli di tipo *"Aula Sincrona Live Zoom"* (Meeting ID, Passcode, Data/Ora diretta, URL fallback). Interfaccia dedicata nel player corsista con disattivazione intelligente dei blocchi cambio-scheda durante la diretta Zoom. | **3,0 ore** |
| **3** | **Motore Registrant API & One-Click Join con CF** | Chiamata automatica a `POST /v2/meetings/{id}/registrants` al click del corsista, iniezione di Nome, Cognome e Codice Fiscale bloccati, memorizzazione del `zoom_registrant_id` e generazione del link nominativo crittografato (`?tk=`). | **3,0 ore** |
| **4** | **Endpoint Webhook Real-Time & Sincronizzazione API** | Sviluppo di `ZoomWebhookController` (`POST /webhooks/zoom`) con validazione crittografica HMAC-SHA256 (`endpoint.url_validation`), ricezione automatica presenze a fine meeting (`meeting.participant_left` / `meeting.ended`) e comando di sincronizzazione da Report API (`GET /v2/report/meetings/{id}/participants`). | **4,0 ore** |
| **5** | **Riconciliazione Automatica & Blocco Ministeriale** | Calcolo automatico di `sync_hours_completed` dai secondi certificati sui moduli Zoom, apposizione del vincolo di sola lettura (`🔒 Certificato`) sul registro segreteria, invio automatico dell'email di completamento modulo e sblocco dell'Attestato Allegato 4 al raggiungimento dell'80%. | **2,5 ore** |
| **6** | **Adeguamento Fascicoli Probanti PDF & Assistenza** | Integrazione della tabella *"Registro Certificato Sessioni Sincrone Zoom (Meeting ID, Registrant ID, Ingresso/Uscita)"* nei PDF ministeriali e affiancamento per l'inserimento delle chiavi S2S OAuth dell'account Zoom Workplace Business di FormaCamera. | **2,0 ore** |
| | **TOTALE COMPLESSIVO** | **Intero Modulo Add-On "Zoom Live Cloud API" Chiavi in Mano** | **18,0 ore** |

---

## 3. Riepilogo Economico

```
╔══════════════════════════════════════════════════════════════════════════════════════════╗
║  DESCRIZIONE FORNITURA ADD-ON                                                            ║
║  Sviluppo, integrazione e collaudo del Modulo Aggiuntivo "Zoom Live Cloud API":          ║
║  • Connettore Server-to-Server OAuth 2.0 per Zoom Workplace Business                     ║
║  • Accesso One-Click con Nome, Cognome e Codice Fiscale bloccati (Registrant API)        ║
║  • Acquisizione automatica dei secondi di presenza via Webhook Real-Time & Reports API   ║
║  • Blocco anti-manomissione delle ore sincrone e aggiornamento Fascicoli Probanti PDF    ║
║  • Nessun canone ricorrente aggiuntivo                                                   ║
╠══════════════════════════════════════════════════════════════════════════════════════════╣
║  IMPEGNO TECNICO COMPLESSIVO:                               18 Ore                       ║
║  PREZZO FORFETTARIO SCONTATO A CORPO:                       € 650,00                     ║
║  IVA / REGIME FISCALE:                                      INCLUSA                      ║
║  IMPORTO FINALE DOVUTO PER IL MODULO ADD-ON:                € 650,00                     ║
╚══════════════════════════════════════════════════════════════════════════════════════════╝
```

---

Per accettazione e conferma d'ordine del Modulo Add-On:

**Per FormaCamera (Il Committente)**  
*Dott.ssa Daniela De Vita*  
_____________________________________  
*(Data, Timbro e Firma del Legale Rappresentante)*  

**Il Fornitore / Sviluppatore**  
*Dario — Consulente Sviluppo Software*
