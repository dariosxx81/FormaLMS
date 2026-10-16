<?php

namespace Database\Seeders;

use App\Models\ActivityLog;
use App\Models\Course;
use App\Models\CourseModule;
use App\Models\Enrollment;
use App\Models\ModuleAttachment;
use App\Models\ModuleProgress;
use App\Models\PlatformSetting;
use App\Models\User;
use App\Models\WatchLog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        PlatformSetting::setValues([
            'platform_name' => 'FormaFAD LMS',
            'platform_subtitle' => 'Piattaforma Telematica White-Label per Corsi di Aggiornamento e Specializzazione (Linee Guida B)',
            'provider_name' => 'Ente di Formazione Accreditato',
            'provider_legal_rep' => 'Dott.ssa Paola Gentile',
            'provider_address' => 'Via dell\'Umiltà 48, 00187 Roma (RM)',
            'ateco_code' => '79.90',
            'cp_istat_code' => '3.4.1.5.2',
            'support_email' => 'segreteria@formalms.it',
            'allow_public_registration' => '1',
            'auto_approve_registration' => '1',
        ]);

        // Admin Account
        $admin = User::create([
            'name' => 'Dario Admin (Direzione)',
            'first_name' => 'Dario',
            'last_name' => 'Amministratore',
            'fiscal_code' => 'DRMMNS80A01H501X',
            'birth_date' => '1980-01-01',
            'birth_city' => 'Roma',
            'birth_province' => 'RM',
            'birth_country' => 'Italia',
            'email' => 'admin@formalms.it',
            'role' => 'admin',
            'status' => 'active',
            'password' => Hash::make('password'),
            'last_login_at' => now(),
            'last_login_ip' => '127.0.0.1',
        ]);

        // Course 1: 25h Total (20h Zoom Sync + 5h Async Video FAD - 5 Modules x 1h)
        $course1 = Course::create([
            'code' => 'AGG-GT-2026-01',
            'title' => 'Corso di Aggiornamento Professionale Guide Turistiche: Patrimonio Storico-Artistico, Museale e Diritto del Turismo',
            'course_type' => 'aggiornamento',
            'thematic_area' => 'Storia dell\'Arte, Disciplina dei Beni Culturali e del Paesaggio, Diritto del Turismo e Accessibilità (Art. 2 comma 4 Linee Guida B)',
            'authorization_details' => 'Provv. Ministero del Turismo prot. n. 0039412 del 02/10/2026',
            'training_center_name' => 'Polo Formativo Accreditato - Aula Telematica Nazionale',
            'training_center_city' => 'Roma (RM)',
            'description' => 'Percorso di aggiornamento obbligatorio (25 ore complessive di cui 20 ore in modalità sincrona su piattaforma Zoom e 5 ore in modalità FAD asincrona tracciata H24).',
            'total_hours' => 25.00,
            'sync_required_hours' => 20.00,
            'async_required_hours' => 5.00,
            'min_attendance_percent' => 80,
            'strict_tab_pause' => false,
            'presence_check_interval_minutes' => 0,
            'allow_self_enrollment' => true,
            'is_active' => true,
            'starts_at' => now()->subDays(10),
            'ends_at' => now()->addMonths(2),
        ]);

        $c1ModulesData = [
            [
                'sort_order' => 1,
                'title' => 'Riforma della Professione di Guida Turistica: Legge 190/2023 e D.M. 88/2024',
                'instructor_name' => 'Prof. Avv. Roberto Ferretti',
                'description' => 'Quadro normativo nazionale, Elenco Nazionale delle Guide Turistiche, obblighi di aggiornamento triennale e disciplina sanzionatoria.',
                'video_url' => 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/BigBuckBunny.mp4',
                'attachment_title' => 'Dispensa Modulo 1 - Testo Coordinato Legge 190-2023 e DM 88-2024',
            ],
            [
                'sort_order' => 2,
                'title' => 'Codice dei Beni Culturali e del Paesaggio: Nuovi Modelli di Valorizzazione Museale',
                'instructor_name' => 'Prof.ssa Chiara Montanari',
                'description' => 'Analisi della tutela e valorizzazione del patrimonio storico-artistico e museale italiano negli itinerari guidati contemporanei.',
                'video_url' => 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ElephantsDream.mp4',
                'attachment_title' => 'Slide Modulo 2 - Valorizzazione Patrimonio Museale e Siti UNESCO',
            ],
            [
                'sort_order' => 3,
                'title' => 'Accessibilità e Inclusività dell\'Offerta Turistica per Persone con Disabilità',
                'instructor_name' => 'Dott.ssa Laura Pellegrini',
                'description' => 'Progettazione di visite guidate accessibili, superamento delle barriere sensoriali e cognitive e accoglienza inclusiva.',
                'video_url' => 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerBlazes.mp4',
                'attachment_title' => 'Guida Operativa Modulo 3 - Protocolli di Accessibilità Turistica',
            ],
            [
                'sort_order' => 4,
                'title' => 'Storytelling Digitale e Metodologie di Comunicazione del Patrimonio Monumentale',
                'instructor_name' => 'Prof. Alessandro De Luca',
                'description' => 'Tecniche narrative avanzate per la mediazione culturale, gestione di gruppi eterogenei e strumenti digitali di supporto.',
                'video_url' => 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerEscapes.mp4',
                'attachment_title' => 'Dispensa Modulo 4 - Tecniche di Mediazione Culturale e Storytelling',
            ],
            [
                'sort_order' => 5,
                'title' => 'Adempimenti Fiscali, Sicurezza dei Gruppi e Deontologia Professionale',
                'instructor_name' => 'Dott. Stefano Marchetti',
                'description' => 'Inquadramento ATECO 79.90, responsabilità civile della guida turistica, copertura assicurativa e gestione delle emergenze.',
                'video_url' => 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/WeAreGoingOnBullrun.mp4',
                'attachment_title' => 'Formulario Modulo 5 - Adempimenti Deontologici e Sicurezza Gruppi',
            ],
        ];

        $c1Modules = [];
        foreach ($c1ModulesData as $mData) {
            $mod = CourseModule::create([
                'course_id' => $course1->id,
                'sort_order' => $mData['sort_order'],
                'title' => $mData['title'],
                'instructor_name' => $mData['instructor_name'],
                'description' => $mData['description'],
                'video_source_type' => 'url',
                'video_url' => $mData['video_url'],
                'duration_seconds' => 3600, // 1 ora (3600s)
                'is_published' => true,
            ]);

            ModuleAttachment::create([
                'course_module_id' => $mod->id,
                'title' => $mData['attachment_title'],
                'file_path' => 'demo-dispense/modulo-' . $mod->sort_order . '.pdf',
                'file_size_kb' => rand(210, 680),
                'download_count' => rand(4, 19),
            ]);

            $c1Modules[] = $mod;
        }

        // Course 2: Second Update Course (25h)
        $course2 = Course::create([
            'code' => 'AGG-GT-2026-02',
            'title' => 'Corso di Aggiornamento Guide Turistiche: Area Archeologica, Geografia Turistica ed Enogastronomia',
            'course_type' => 'aggiornamento',
            'thematic_area' => 'Archeologia, Geografia e Patrimonio Demo-Etno-Antropologico (Art. 2 Linee Guida B)',
            'authorization_details' => 'Provv. Ministero del Turismo prot. n. 0041890 del 10/10/2026',
            'training_center_name' => 'Polo Formativo Accreditato - Aula Telematica Nazionale',
            'training_center_city' => 'Roma (RM)',
            'description' => 'Secondo modulo da 25 ore (20h Sincrone Zoom + 5h FAD Asincrone) valido per il raggiungimento delle 50 ore triennali.',
            'total_hours' => 25.00,
            'sync_required_hours' => 20.00,
            'async_required_hours' => 5.00,
            'min_attendance_percent' => 80,
            'strict_tab_pause' => false,
            'presence_check_interval_minutes' => 0,
            'allow_self_enrollment' => true,
            'is_active' => true,
            'starts_at' => now()->subDays(3),
            'ends_at' => now()->addMonths(3),
        ]);

        for ($i = 1; $i <= 5; $i++) {
            $m2 = CourseModule::create([
                'course_id' => $course2->id,
                'sort_order' => $i,
                'title' => "Modulo {$i} — Itinerari Archeologici, Paesaggio Storico e Tradizioni Territoriali",
                'instructor_name' => 'Prof. Lorenzo Bellini',
                'description' => 'Lezione videoregistrata di approfondimento specialistico con casi studio territoriali.',
                'video_source_type' => 'url',
                'video_url' => 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/BigBuckBunny.mp4',
                'duration_seconds' => 3600,
                'is_published' => true,
            ]);
            ModuleAttachment::create([
                'course_module_id' => $m2->id,
                'title' => "Dispensa Modulo {$i} - Cartografia e Siti Archeologici",
                'file_path' => "demo-dispense/archeo-{$i}.pdf",
                'file_size_kb' => 420,
                'download_count' => 3,
            ]);
        }

        // Course 3: Specialization Course (50h - Art. 5 comma 1 Linee Guida B) with strict controls ON
        $course3 = Course::create([
            'code' => 'SPEC-GT-2026-01',
            'title' => 'Corso di Specializzazione: Tecniche di Comunicazione per l\'Infanzia, l\'Adolescenza e la Terza Età',
            'course_type' => 'specializzazione',
            'thematic_area' => 'Art. 2 comma 2 lett. i) - Tecniche di comunicazione per l\'infanzia, l\'adolescenza e la terza età',
            'authorization_details' => 'Provv. Ministero del Turismo prot. n. 0045102 del 14/10/2026',
            'training_center_name' => 'Polo Formativo Accreditato - Aula Telematica Nazionale',
            'training_center_city' => 'Roma (RM)',
            'description' => 'Corso di specializzazione da 50 ore erogabile interamente in modalità telematica ai sensi dell\'Art. 5 comma 1 delle Linee Guida B (40h Sincrone + 10h Asincrone).',
            'total_hours' => 50.00,
            'sync_required_hours' => 40.00,
            'async_required_hours' => 10.00,
            'min_attendance_percent' => 80,
            'strict_tab_pause' => true,
            'presence_check_interval_minutes' => 15,
            'allow_self_enrollment' => true,
            'is_active' => true,
            'starts_at' => now(),
            'ends_at' => now()->addMonths(4),
        ]);

        for ($i = 1; $i <= 5; $i++) {
            CourseModule::create([
                'course_id' => $course3->id,
                'sort_order' => $i,
                'title' => "Modulo Specializzazione {$i} — Pedagogia del Patrimonio e Didattica Museale (2 ore)",
                'instructor_name' => 'Prof.ssa Valeria Giordano',
                'description' => 'Modulo avanzato da 2 ore con verifica presenza attiva.',
                'video_source_type' => 'url',
                'video_url' => 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ElephantsDream.mp4',
                'duration_seconds' => 7200,
                'is_published' => true,
            ]);
        }

        // Student 1: Elena Bianchi (100% completed -> Certificate ready!)
        $student1 = User::create([
            'name' => 'Elena Bianchi',
            'first_name' => 'Elena',
            'last_name' => 'Bianchi',
            'fiscal_code' => 'BNCLNE84D52H501Q',
            'birth_date' => '1984-04-12',
            'birth_city' => 'Roma',
            'birth_province' => 'RM',
            'birth_country' => 'Italia',
            'phone' => '+39 347 8821904',
            'national_list_number' => 'ENGT-RM-01492',
            'email' => 'elena.bianchi@guideroma.it',
            'role' => 'student',
            'status' => 'active',
            'password' => Hash::make('password'),
            'last_login_at' => now()->subHours(2),
            'last_login_ip' => '93.44.118.21',
        ]);

        $enr1 = Enrollment::create([
            'user_id' => $student1->id,
            'course_id' => $course1->id,
            'status' => 'completed',
            'sync_hours_completed' => 20.00,
            'enrolled_at' => now()->subDays(8),
        ]);

        foreach ($c1Modules as $idx => $mod) {
            ModuleProgress::create([
                'user_id' => $student1->id,
                'course_id' => $course1->id,
                'course_module_id' => $mod->id,
                'watched_seconds' => 3600,
                'max_position_seconds' => 3600,
                'last_position_seconds' => 3600,
                'completion_percent' => 100.0,
                'is_completed' => true,
                'started_at' => now()->subDays(7 - $idx),
                'completed_at' => now()->subDays(7 - $idx)->addHour(),
                'last_watched_at' => now()->subDays(7 - $idx)->addHour(),
            ]);

            WatchLog::create([
                'user_id' => $student1->id,
                'course_id' => $course1->id,
                'course_module_id' => $mod->id,
                'session_token' => Str::random(32),
                'ip_address' => '93.44.118.21',
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/130.0.0.0 Safari/537.36',
                'start_position_seconds' => 0,
                'end_position_seconds' => 3600,
                'seconds_counted' => 3600,
                'started_at' => now()->subDays(7 - $idx)->setTime(21, 15),
                'last_heartbeat_at' => now()->subDays(7 - $idx)->setTime(22, 15),
            ]);
        }
        $enr1->recalculateProgress();

        // Student 2: Marco Valeri (19h Zoom + 3/5 FAD = 22h / 25h = 88% -> Eligible, 2 modules remaining)
        $student2 = User::create([
            'name' => 'Marco Valeri',
            'first_name' => 'Marco',
            'last_name' => 'Valeri',
            'fiscal_code' => 'VLRMRC79H18H501W',
            'birth_date' => '1979-06-18',
            'birth_city' => 'Viterbo',
            'birth_province' => 'VT',
            'birth_country' => 'Italia',
            'phone' => '+39 338 4190233',
            'national_list_number' => 'ENGT-VT-00381',
            'email' => 'marco.valeri@guideroma.it',
            'role' => 'student',
            'status' => 'active',
            'password' => Hash::make('password'),
            'last_login_at' => now()->subMinutes(45),
            'last_login_ip' => '151.38.92.104',
        ]);

        $enr2 = Enrollment::create([
            'user_id' => $student2->id,
            'course_id' => $course1->id,
            'status' => 'active',
            'sync_hours_completed' => 19.00,
            'enrolled_at' => now()->subDays(6),
        ]);

        foreach ($c1Modules as $idx => $mod) {
            if ($idx < 3) {
                ModuleProgress::create([
                    'user_id' => $student2->id,
                    'course_id' => $course1->id,
                    'course_module_id' => $mod->id,
                    'watched_seconds' => 3600,
                    'max_position_seconds' => 3600,
                    'last_position_seconds' => 3600,
                    'completion_percent' => 100.0,
                    'is_completed' => true,
                    'started_at' => now()->subDays(5 - $idx),
                    'completed_at' => now()->subDays(5 - $idx)->addHour(),
                    'last_watched_at' => now()->subDays(5 - $idx)->addHour(),
                ]);
                WatchLog::create([
                    'user_id' => $student2->id,
                    'course_id' => $course1->id,
                    'course_module_id' => $mod->id,
                    'session_token' => Str::random(32),
                    'ip_address' => '151.38.92.104',
                    'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 14_5) AppleWebKit/605.1.15 Safari/605.1.15',
                    'start_position_seconds' => 0,
                    'end_position_seconds' => 3600,
                    'seconds_counted' => 3600,
                    'started_at' => now()->subDays(5 - $idx)->setTime(23, 0),
                    'last_heartbeat_at' => now()->subDays(5 - $idx)->setTime(23, 59),
                ]);
            } elseif ($idx === 3) {
                ModuleProgress::create([
                    'user_id' => $student2->id,
                    'course_id' => $course1->id,
                    'course_module_id' => $mod->id,
                    'watched_seconds' => 1440, // 24 min
                    'max_position_seconds' => 1440,
                    'last_position_seconds' => 1440,
                    'completion_percent' => 40.0,
                    'is_completed' => false,
                    'started_at' => now()->subHours(3),
                    'last_watched_at' => now()->subHours(2),
                ]);
            }
        }
        $enr2->recalculateProgress();

        // Student 3: Sara Lombardi (17h Zoom + 2/5 FAD = 19h / 25h = 76% -> 1 module away from 80% threshold!)
        $student3 = User::create([
            'name' => 'Sara Lombardi',
            'first_name' => 'Sara',
            'last_name' => 'Lombardi',
            'fiscal_code' => 'LMBSRA88L62F839T',
            'birth_date' => '1988-07-22',
            'birth_city' => 'Napoli',
            'birth_province' => 'NA',
            'birth_country' => 'Italia',
            'phone' => '+39 320 7751092',
            'national_list_number' => 'ENGT-NA-02104',
            'email' => 'sara.lombardi@guideroma.it',
            'role' => 'student',
            'status' => 'active',
            'password' => Hash::make('password'),
            'last_login_at' => now()->subHours(1),
            'last_login_ip' => '79.18.204.55',
        ]);

        $enr3 = Enrollment::create([
            'user_id' => $student3->id,
            'course_id' => $course1->id,
            'status' => 'active',
            'sync_hours_completed' => 17.00,
            'enrolled_at' => now()->subDays(4),
        ]);

        foreach ($c1Modules as $idx => $mod) {
            if ($idx < 2) {
                ModuleProgress::create([
                    'user_id' => $student3->id,
                    'course_id' => $course1->id,
                    'course_module_id' => $mod->id,
                    'watched_seconds' => 3600,
                    'max_position_seconds' => 3600,
                    'last_position_seconds' => 3600,
                    'completion_percent' => 100.0,
                    'is_completed' => true,
                    'started_at' => now()->subDays(3 - $idx),
                    'completed_at' => now()->subDays(3 - $idx)->addHour(),
                    'last_watched_at' => now()->subDays(3 - $idx)->addHour(),
                ]);
            }
        }
        $enr3->recalculateProgress();

        // Student 4: Davide Moretti (Multi-course enrolled)
        $student4 = User::create([
            'name' => 'Davide Moretti',
            'first_name' => 'Davide',
            'last_name' => 'Moretti',
            'fiscal_code' => 'MRTDVD82B14F205Z',
            'birth_date' => '1982-02-14',
            'birth_city' => 'Milano',
            'birth_province' => 'MI',
            'birth_country' => 'Italia',
            'phone' => '+39 349 1120987',
            'national_list_number' => 'ENGT-MI-00912',
            'email' => 'davide.moretti@guideroma.it',
            'role' => 'student',
            'status' => 'active',
            'password' => Hash::make('password'),
        ]);

        Enrollment::create([
            'user_id' => $student4->id,
            'course_id' => $course1->id,
            'status' => 'active',
            'sync_hours_completed' => 20.00,
            'enrolled_at' => now()->subDays(2),
        ])->recalculateProgress();

        Enrollment::create([
            'user_id' => $student4->id,
            'course_id' => $course2->id,
            'status' => 'active',
            'sync_hours_completed' => 20.00,
            'enrolled_at' => now()->subDays(1),
        ])->recalculateProgress();

        ActivityLog::record('system_seed', 'Inizializzato catalogo multi-corso FAD e registri presenze ministeriali', $admin->id, $course1->id);
        ActivityLog::record('module_complete', 'Elena Bianchi ha completato tutti i 5 moduli FAD (100% frequenza raggiunta)', $student1->id, $course1->id, $c1Modules[4]->id);
        ActivityLog::record('certificate_download', 'Generato Attestato di Frequenza Allegato 4 per Elena Bianchi', $student1->id, $course1->id);
        ActivityLog::record('module_complete', 'Marco Valeri ha completato in fascia notturna (23:59) il Modulo 3', $student2->id, $course1->id, $c1Modules[2]->id);
    }
}
