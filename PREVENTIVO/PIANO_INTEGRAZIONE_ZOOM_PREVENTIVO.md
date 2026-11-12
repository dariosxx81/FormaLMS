# Piano di Integrazione Zoom Live nei Moduli FormaLMS
*Documento di Analisi Tecnica e Preventivazione Modulo Zoom Sincrono*

---

## 🎯 Obiettivo del Modulo Aggiuntivo
Permettere ai corsisti di accedere alle lezioni sincrone Zoom direttamente da FormaLMS (passando automaticamente `nome`, `cognome`, `email` e `codice fiscale`), tracciando in modo automatico e certificato le **ore effettive di permanenza** per l'accreditamento FAD ministeriale (Linee Guida B - D.M. 88/2024).

---

## 🏗️ Architettura del Flusso Operativo

```
[ Docente / Admin ]
       │
       ▼
1. Configura Modulo con tipo "zoom" (Meeting ID, Data/Ora, Durata)
       │
       ▼
[ Corsista su FormaLMS ]
       │
       ▼
2. Clicca su "Entra nella Lezione Zoom" dal Player Didattico
       │
       ▼
[ Server FormaLMS ] ──(Chiamata Server-to-Server)──► [ Zoom Cloud API ]
   Passa: Nome, Cognome, Email, CF                    Registrant API
       ▲                                                    │
       │                                                    ▼
       └────────────── Ritorna Join URL Univoco ────────────┘
       │
       ▼
3. Il Corsista entra nella Stanza Zoom con Identità Verificata & Bloccata
       │
       ▼
[ Durante / Fine Lezione ]
       │
       ▼
[ Zoom Webhook & Reports API ] ──(Dati di Presenza)──► [ Server FormaLMS ]
   Invia: Timestamp Ingresso, Uscita, Durata esatta (secondi)
       │
       ▼
4. Matching automatico corsista per Email/CF
5. Aggiornamento automatico ore sincrone (`sync_hours_completed`)
6. Ricalcolo percentuale corso & sblocco Attestato di Frequenza
7. Registrazione nel Fascicolo Probante e Registro Presenze PDF
```

---

## 🔍 Le 3 Sfide Risolte dall'Integrazione

### 1. Riconoscimento Certo del Corsista (No Accessi Anonimi)
* **Criticità classica**: Con link pubblici, i corsisti entrano con nomi di fantasia (*"iPhone di Mario"*, *"iPad"*), invalidando il registro presenze.
* **Soluzione FormaLMS**: Il sistema registra programmaticamente l'utente tramite le **Registrant API di Zoom** prima di aprire la stanza, generando un link nominativo con token crittografico. Il corsista accede senza dover inserire credenziali e con nome non modificabile.

### 2. Modalità di Fruizione Flessibile
* **Opzione A (Consigliata per stabilità)**: Reindirizzamento autenticato all'App Zoom nativa (desktop o smartphone) per la massima fluidità audio/video.
* **Opzione B (Embedded)**: Finestra Zoom incorporata direttamente nella pagina della lezione tramite *Zoom Web Meeting SDK*, senza uscire dalla piattaforma.

### 3. Recupero Telemetrico Automatico delle Ore di Frequenza
* **Reports API a fine lezione**: Interrogazione automatica di `GET /v2/report/meetings/{meetingId}/participants`.
* **Webhooks in tempo reale**: Ricezione eventi `meeting.participant_joined`, `meeting.participant_left`, `meeting.ended`.
* **Calcolo cumulativo**: Se un allievo perde la connessione e rientra, i tempi delle sessioni vengono sommati al secondo.
* **Aggiornamento automatico e sblocco**: Al raggiungimento della soglia minima, la barra diventa verde e viene inviata l'email di conseguimento attestato.

---

## 📦 Elementi da Sviluppare (Voci di Costo / Preventivazione)

| Voce di Sviluppo | Descrizione Tecnica | Complessità |
| :--- | :--- | :--- |
| **1. Service Layer Zoom API (S2S OAuth)** | Autenticazione Server-to-Server, gestione token, refresh, rate-limit. | Media |
| **2. Estensione Moduli Didattici** | Nuovo tipo modulo `zoom`, inserimento Meeting ID, passcode, data/ora e impostazioni. | Bassa |
| **3. Flusso Registrant & One-Click Join** | Registrazione automatica partecipante via API e generazione Join URL personalizzato. | Media |
| **4. Engine Sincronizzazione Presenze** | Script e endpoint di sincronizzazione report presenze Zoom con conversione in ore sincrone. | Media |
| **5. Controller Webhook Real-Time** | Ricezione sicura eventi Zoom con validazione `Webhook Secret Token` e salvataggio log. | Media-Alta |
| **6. Aggiornamento Fascicolo Probante** | Inclusione automatica dei dettagli Zoom (join/leave time, durata, IP) nel PDF ministeriale. | Bassa |

---

## 🔑 Prerequisiti Lato Cliente
1. Account Zoom a pagamento (Pro, Business o Enterprise).
2. Creazione di una App di tipo **Server-to-Server OAuth** nel *Zoom App Marketplace* con i permessi:
   * `meeting:read:admin`, `meeting:write:admin`
   * `report:read:admin`
3. Inserimento delle 4 chiavi in piattaforma:
   * `ZOOM_ACCOUNT_ID`
   * `ZOOM_CLIENT_ID`
   * `ZOOM_CLIENT_SECRET`
   * `ZOOM_WEBHOOK_SECRET_TOKEN`
