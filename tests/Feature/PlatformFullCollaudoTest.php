<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Course;
use App\Models\CourseModule;
use App\Models\Enrollment;
use App\Models\ModuleAttachment;
use App\Models\ModuleProgress;
use App\Models\PlatformSetting;
use App\Models\User;
use App\Models\WatchLog;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PlatformFullCollaudoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_01_public_pages_and_authentication_and_role_security(): void
    {
        // 1. Login and Register pages render properly
        $this->get('/login')->assertStatus(200)->assertSee('FormaFAD LMS');
        $this->get('/register')->assertStatus(200)->assertSee('Registrazione Anagrafica Corsista');

        // 2. Student can login and access student dashboard, but gets 403 on admin routes
        $student = User::where('email', 'elena.bianchi@guideroma.it')->firstOrFail();

        $loginRes = $this->post('/login', [
            'email' => $student->email,
            'password' => 'password',
        ]);
        $loginRes->assertRedirect(route('student.dashboard'));
        $this->assertAuthenticatedAs($student);

        $this->get('/dashboard')->assertStatus(200)->assertSee('Elena Bianchi');
        $this->get('/admin')->assertStatus(403);
        $this->get('/admin/users')->assertStatus(403);
        $this->get('/admin/logs')->assertStatus(403);
        $this->get('/admin/settings')->assertStatus(403);

        // 3. Suspended user cannot login
        $this->post('/logout');
        $student->update(['status' => 'suspended']);
        $this->post('/login', [
            'email' => $student->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');
        $this->assertGuest();

        // 4. Admin can login and access all admin pages
        $admin = User::where('role', 'admin')->firstOrFail();
        $this->post('/login', [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertRedirect(route('admin.dashboard'));

        $this->get('/admin')->assertStatus(200)->assertSee('Cruscotto Multi-Corso');
        $this->get('/admin/users')->assertStatus(200);
        $this->get('/admin/logs')->assertStatus(200);
        $this->get('/admin/settings')->assertStatus(200);
    }

    public function test_02_hybrid_user_registration_and_csv_bulk_import(): void
    {
        $course = Course::where('code', 'AGG-GT-2026-01')->firstOrFail();

        // 1. Public Self-Registration with full Ministerial fields + automatic course enrollment
        $regResponse = $this->post('/register', [
            'first_name' => 'Chiara',
            'last_name' => 'Romano',
            'fiscal_code' => 'rmnchr87c41h501p',
            'birth_date' => '1987-03-01',
            'birth_city' => 'Roma',
            'birth_province' => 'rm',
            'birth_country' => 'Italia',
            'phone' => '+39 333 9988776',
            'national_list_number' => 'ENGT-RM-09911',
            'email' => 'chiara.romano@guideroma.it',
            'password' => 'Segreta123!',
            'password_confirmation' => 'Segreta123!',
            'course_id' => $course->id,
        ]);

        $regResponse->assertRedirect(route('student.dashboard'));

        $newUser = User::where('email', 'chiara.romano@guideroma.it')->firstOrFail();
        $this->assertEquals('RMNCHR87C41H501P', $newUser->fiscal_code);
        $this->assertEquals('RM', $newUser->birth_province);
        $this->assertDatabaseHas('enrollments', [
            'user_id' => $newUser->id,
            'course_id' => $course->id,
            'status' => 'active',
        ]);

        // 2. Admin manual user creation with multi-course assignment
        $this->post('/logout');
        $admin = User::where('role', 'admin')->firstOrFail();
        $course2 = Course::where('code', 'AGG-GT-2026-02')->firstOrFail();

        $res = $this->actingAs($admin)->post('/admin/users', [
            'first_name' => 'Matteo',
            'last_name' => 'Santoro',
            'fiscal_code' => 'SNTMTT81A10H501L',
            'birth_date' => '1981-01-10',
            'birth_city' => 'Latina',
            'birth_province' => 'LT',
            'birth_country' => 'Italia',
            'email' => 'matteo.santoro@guideroma.it',
            'password' => 'Corsista2026!',
            'course_ids' => [$course->id, $course2->id],
            'sync_hours_completed' => 20,
        ]);
        $res->assertSessionHasNoErrors();

        $matteo = User::where('email', 'matteo.santoro@guideroma.it')->firstOrFail();
        $this->assertEquals(2, Enrollment::where('user_id', $matteo->id)->count());

        // 3. Admin Bulk CSV Import of students
        $csvContent = "Nome;Cognome;CodiceFiscale;Email;DataNascita;Citta;Prov;OreZoom\n"
            . "Federica;Galli;GLLFDR90B42H501K;federica.galli@guideroma.it;1990-02-02;Roma;RM;20\n"
            . "Andrea;Costa;CSTNDR85D15F205V;andrea.costa@guideroma.it;1985-04-15;Milano;MI;18.5";

        $this->actingAs($admin)->post('/admin/users/import-csv', [
            'course_id' => $course->id,
            'default_sync_hours' => 20,
            'csv_content' => $csvContent,
        ])->assertSessionHasNoErrors();

        $federica = User::where('email', 'federica.galli@guideroma.it')->firstOrFail();
        $andrea = User::where('email', 'andrea.costa@guideroma.it')->firstOrFail();

        $this->assertDatabaseHas('enrollments', [
            'user_id' => $federica->id,
            'course_id' => $course->id,
            'sync_hours_completed' => 20.0,
        ]);
        $this->assertDatabaseHas('enrollments', [
            'user_id' => $andrea->id,
            'course_id' => $course->id,
            'sync_hours_completed' => 18.5,
        ]);
    }

    public function test_03_multi_course_creation_video_upload_and_pdf_dispense_tracking(): void
    {
        Storage::fake('public');
        $admin = User::where('role', 'admin')->firstOrFail();

        // 1. Create a new course from Admin
        $this->actingAs($admin)->post('/admin/courses', [
            'code' => 'AGG-GT-2026-99',
            'title' => 'Corso Test Collaudo Enogastronomia e Territorio',
            'course_type' => 'aggiornamento',
            'thematic_area' => 'Area Enogastronomica',
            'authorization_details' => 'Prot. MiTur n. 99999/2026',
            'total_hours' => 25,
            'sync_required_hours' => 20,
            'async_required_hours' => 5,
            'min_attendance_percent' => 80,
            'strict_tab_pause' => 1,
            'presence_check_interval_minutes' => 10,
            'allow_self_enrollment' => 1,
        ])->assertRedirect();

        $newCourse = Course::where('code', 'AGG-GT-2026-99')->firstOrFail();
        $this->assertTrue($newCourse->strict_tab_pause);
        $this->assertEquals(10, $newCourse->presence_check_interval_minutes);

        // 2. Add a Video Module with uploaded .mp4 file
        $fakeVideo = UploadedFile::fake()->create('lezione-zoom-modulo1.mp4', 2048, 'video/mp4');
        $this->actingAs($admin)->post(route('admin.courses.modules.store', $newCourse), [
            'sort_order' => 1,
            'title' => 'Modulo 1 - Storia dell\'Enogastronomia Laziale',
            'instructor_name' => 'Prof. Gino Veronelli',
            'video_source_type' => 'upload',
            'video_file' => $fakeVideo,
            'duration_minutes' => 60,
            'description' => 'Videolezione registrata da Zoom',
        ])->assertSessionHasNoErrors();

        $module = CourseModule::where('course_id', $newCourse->id)->firstOrFail();
        $this->assertEquals(3600, $module->duration_seconds);
        $this->assertStringContainsString('storage/course-videos/', $module->video_url);

        // 3. Attach a PDF Dispensa to the module
        $fakePdf = UploadedFile::fake()->create('dispensa-enogastronomia.pdf', 512, 'application/pdf');
        $this->actingAs($admin)->post(route('admin.modules.attachments.store', $module), [
            'title' => 'Dispensa Ufficiale Enogastronomia PDF',
            'attachment_file' => $fakePdf,
        ])->assertSessionHasNoErrors();

        $attachment = ModuleAttachment::where('course_module_id', $module->id)->firstOrFail();
        $this->assertEquals(0, $attachment->download_count);

        // 4. Student downloads the PDF Dispensa -> download_count increments & ActivityLog is recorded
        $student = User::where('email', 'elena.bianchi@guideroma.it')->firstOrFail();
        $dlResponse = $this->actingAs($student)->get(route('student.attachments.download', $attachment));
        $dlResponse->assertStatus(200);

        $this->assertEquals(1, $attachment->fresh()->download_count);
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $student->id,
            'course_id' => $newCourse->id,
            'course_module_id' => $module->id,
            'action_type' => 'attachment_download',
        ]);
    }

    public function test_04_video_player_heartbeat_telemetry_and_automatic_certificate_unlock(): void
    {
        // Sara Lombardi starts with: 17h Sync (Zoom) + 2h Async (Modules 1 & 2 completed) = 19h / 25h = 76% (< 80%)
        $sara = User::where('email', 'sara.lombardi@guideroma.it')->firstOrFail();
        $course = Course::where('code', 'AGG-GT-2026-01')->firstOrFail();
        $enrollment = Enrollment::where('user_id', $sara->id)->where('course_id', $course->id)->firstOrFail();

        $this->assertEquals(76.0, $enrollment->attendance_percent);
        $this->assertFalse($enrollment->is_eligible_for_certificate);

        // Sara cannot download the certificate yet while at 76%
        $this->actingAs($sara)
            ->get(route('student.enrollments.certificate', $enrollment))
            ->assertSessionHasErrors('certificate');

        // Sara opens Module 3
        $module3 = CourseModule::where('course_id', $course->id)->where('sort_order', 3)->firstOrFail();
        $this->actingAs($sara)
            ->get(route('student.courses.module', [$course, $module3]))
            ->assertStatus(200)
            ->assertSee($module3->title);

        $sessionToken = 'test_session_token_sara_mod3_12345';

        // 1st Heartbeat: Sara watches first 60 seconds
        $hb1 = $this->actingAs($sara)->postJson(route('student.modules.heartbeat', [$course, $module3]), [
            'session_token' => $sessionToken,
            'current_time' => 60,
            'delta_watched_seconds' => 60,
            'event_type' => 'periodic',
            'tab_switches' => 1,
            'presence_confirms' => 0,
        ]);

        $hb1->assertStatus(200)
            ->assertJsonPath('ok', true)
            ->assertJsonPath('module_progress.watched_seconds', 60)
            ->assertJsonPath('module_progress.is_completed', false)
            ->assertJsonPath('enrollment.is_eligible_for_certificate', false);

        // Verify WatchLog session created with IP & seconds_counted = 60
        $this->assertDatabaseHas('watch_logs', [
            'user_id' => $sara->id,
            'course_module_id' => $module3->id,
            'session_token' => $sessionToken,
            'seconds_counted' => 60,
        ]);

        // Final Heartbeat on Module 3: reaches 3600s (1 hour) -> Total hours become 17h Sync + 3h Async = 20h / 25h = 80.0%!
        $hbEnd = $this->actingAs($sara)->postJson(route('student.modules.heartbeat', [$course, $module3]), [
            'session_token' => $sessionToken,
            'current_time' => 3600,
            'delta_watched_seconds' => 60,
            'event_type' => 'ended',
            'tab_switches' => 0,
            'presence_confirms' => 1,
        ]);

        $hbEnd->assertStatus(200)
            ->assertJsonPath('ok', true)
            ->assertJsonPath('module_progress.completion_percent', 100)
            ->assertJsonPath('module_progress.is_completed', true)
            ->assertJsonPath('enrollment.total_hours_completed', 20)
            ->assertJsonPath('enrollment.attendance_percent', 80)
            ->assertJsonPath('enrollment.is_eligible_for_certificate', true);

        $this->assertNotNull($hbEnd->json('enrollment.certificate_url'));

        // Now Sara CAN download her official Allegato 4 PDF Certificate!
        $certResponse = $this->actingAs($sara)->get(route('student.enrollments.certificate', $enrollment->fresh()));
        $certResponse->assertStatus(200);
        $this->assertStringContainsString('application/pdf', $certResponse->headers->get('Content-Type'));
    }

    public function test_05_zoom_sync_hours_inline_update_and_zoom_csv_import(): void
    {
        $admin = User::where('role', 'admin')->firstOrFail();
        $course = Course::where('code', 'AGG-GT-2026-01')->firstOrFail();
        $davide = User::where('email', 'davide.moretti@guideroma.it')->firstOrFail();
        $enrollment = Enrollment::where('user_id', $davide->id)->where('course_id', $course->id)->firstOrFail();

        // 1. Update Sync Hours inline
        $this->actingAs($admin)->put(route('admin.enrollments.sync-hours', $enrollment), [
            'sync_hours_completed' => 15.5,
        ])->assertSessionHasNoErrors();

        $this->assertEquals(15.5, $enrollment->fresh()->sync_hours_completed);

        // 2. Import Zoom Attendance CSV Report by email and by Fiscal Code
        $zoomCsv = "davide.moretti@guideroma.it;20\nLMBSRA88L62F839T;19.5";
        $this->actingAs($admin)->post(route('admin.courses.import-zoom-csv', $course), [
            'zoom_csv_content' => $zoomCsv,
        ])->assertSessionHasNoErrors();

        $this->assertEquals(20.0, $enrollment->fresh()->sync_hours_completed);
    }

    public function test_06_ministerial_pdf_and_csv_exports_allegato3_allegato4_register_and_dossier(): void
    {
        $admin = User::where('role', 'admin')->firstOrFail();
        $course1 = Course::where('code', 'AGG-GT-2026-01')->firstOrFail();
        $elena = User::where('email', 'elena.bianchi@guideroma.it')->firstOrFail();
        $enrElena = Enrollment::where('user_id', $elena->id)->where('course_id', $course1->id)->firstOrFail();

        // 1. Allegato 4 Certificate PDF
        $pdfCert = $this->actingAs($admin)->get(route('student.enrollments.certificate', $enrElena));
        $pdfCert->assertStatus(200);
        $this->assertStringStartsWith('%PDF-', $pdfCert->getContent());

        // 2. Course Official Attendance Register PDF
        $pdfReg = $this->actingAs($admin)->get(route('admin.courses.export-pdf', $course1));
        $pdfReg->assertStatus(200);
        $this->assertStringStartsWith('%PDF-', $pdfReg->getContent());

        // 3. Course Official Attendance Register CSV
        $csvReg = $this->actingAs($admin)->get(route('admin.courses.export-csv', $course1));
        $csvReg->assertStatus(200);
        $this->assertStringContainsString('text/csv', $csvReg->headers->get('Content-Type'));
        $csvBody = $csvReg->streamedContent();
        $this->assertStringContainsString('BNCLNE84D52H501Q', $csvBody);
        $this->assertStringContainsString('IDONEO', $csvBody);

        // 4. Student Analytical Ministerial Dossier PDF ("Documentazione Probante")
        $pdfDossier = $this->actingAs($admin)->get(route('admin.enrollments.dossier-pdf', $enrElena));
        $pdfDossier->assertStatus(200);
        $this->assertStringStartsWith('%PDF-', $pdfDossier->getContent());

        // 5. Allegato 3 Specialization Certificate PDF
        $specCourse = Course::where('code', 'SPEC-GT-2026-01')->firstOrFail();
        $specEnr = Enrollment::create([
            'user_id' => $elena->id,
            'course_id' => $specCourse->id,
            'status' => 'completed',
            'sync_hours_completed' => 45.0,
            'enrolled_at' => now(),
        ])->recalculateProgress();

        $pdfSpec = $this->actingAs($admin)->get(route('student.enrollments.certificate', $specEnr));
        $pdfSpec->assertStatus(200);
        $this->assertStringStartsWith('%PDF-', $pdfSpec->getContent());
    }

    public function test_07_white_label_settings_customization(): void
    {
        $admin = User::where('role', 'admin')->firstOrFail();

        $this->actingAs($admin)->post(route('admin.settings.update'), [
            'platform_name' => 'FormaCamera VideoFAD',
            'platform_subtitle' => 'Piattaforma Ufficiale Azienda Speciale Camera di Commercio di Roma',
            'provider_name' => 'FormaCamera - Azienda Speciale CCIAA Roma',
            'provider_legal_rep' => 'Dott.ssa Paola Gentile',
            'provider_address' => 'Viale Oceano Indiano 17, 00144 Roma (RM)',
            'ateco_code' => '79.90',
            'cp_istat_code' => '3.4.1.5.2',
            'support_email' => 'fad@formacamera.it',
            'allow_public_registration' => '1',
            'auto_approve_registration' => '1',
        ])->assertSessionHasNoErrors();

        $settings = PlatformSetting::getAllSettings();
        $this->assertEquals('FormaCamera VideoFAD', $settings['platform_name']);
        $this->assertEquals('FormaCamera - Azienda Speciale CCIAA Roma', $settings['provider_name']);

        // Verify updated White-Label name appears immediately on Login page
        $this->post('/logout');
        $this->get('/login')->assertStatus(200)->assertSee('FormaCamera VideoFAD');
    }

    public function test_08_masked_youtube_player_and_video_id_extraction(): void
    {
        $admin = User::where('role', 'admin')->firstOrFail();
        $student = User::where('email', 'elena.bianchi@guideroma.it')->firstOrFail();
        $course = Course::where('code', 'AGG-GT-2026-01')->firstOrFail();

        // 1. Verify seeded Module 2 is a Masked YouTube module and extracts the 11-char ID properly
        $mod2 = CourseModule::where('course_id', $course->id)->where('sort_order', 2)->firstOrFail();
        $this->assertTrue($mod2->is_youtube);
        $this->assertEquals('M7lc1UVf-VE', $mod2->youtube_video_id);

        // 2. Verify Admin can add a new Masked YouTube module using a standard youtube.com/watch?v= link
        $this->actingAs($admin)->post(route('admin.courses.modules.store', $course), [
            'sort_order' => 6,
            'title' => 'Modulo 6 Extra - Approfondimento YouTube Mascherato',
            'instructor_name' => 'Prof.ssa Giulia Riva',
            'video_source_type' => 'youtube',
            'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ&feature=youtu.be',
            'duration_minutes' => 45,
            'description' => 'Modulo con sorgente YouTube protetta da No-Logo Shield',
        ])->assertSessionHasNoErrors();

        $mod6 = CourseModule::where('course_id', $course->id)->where('sort_order', 6)->firstOrFail();
        $this->assertTrue($mod6->is_youtube);
        $this->assertEquals('dQw4w9WgXcQ', $mod6->youtube_video_id);

        // 3. Verify Student Course Player renders the Masked YouTube Shield & Custom Anti-Skip Controls
        $res = $this->actingAs($student)->get(route('student.courses.module', [$course, $mod2]));
        $res->assertStatus(200)
            ->assertSee('yt-masked-player')
            ->assertSee('YouTube No-Logo Shield Attivo')
            ->assertSee('YouTube Mascherato (No-Logo / Anti-Skip)');
    }

    public function test_09_single_module_100_percent_fad_course_flow(): void
    {
        $student = User::where('email', 'sara.lombardi@guideroma.it')->firstOrFail();
        $monoCourse = Course::where('code', 'MONO-FAD-2026')->firstOrFail();

        // 1. Verify course has 0 sync hours and 1 single module of 2 hours (7200s)
        $this->assertEquals(0.0, (float) $monoCourse->sync_required_hours);
        $this->assertEquals(2.0, (float) $monoCourse->async_required_hours);
        $this->assertEquals(1, $monoCourse->modules()->count());

        $onlyModule = $monoCourse->modules()->firstOrFail();

        // 2. Student self-enrolls from dashboard
        $this->actingAs($student)
            ->post(route('student.courses.enroll', $monoCourse))
            ->assertRedirect(route('student.courses.show', $monoCourse));

        $enrollment = Enrollment::where('user_id', $student->id)
            ->where('course_id', $monoCourse->id)
            ->firstOrFail();

        $this->assertEquals(0.0, (float) $enrollment->attendance_percent);
        $this->assertFalse($enrollment->is_eligible_for_certificate);

        // 3. Student completes the single module via heartbeat -> reaches 100% and unlocks certificate with 0 Zoom hours
        $hb = $this->actingAs($student)->postJson(route('student.modules.heartbeat', [$monoCourse, $onlyModule]), [
            'session_token' => 'mono_course_session_token_999',
            'current_time' => 7200,
            'delta_watched_seconds' => 60,
            'event_type' => 'ended',
        ]);

        $hb->assertStatus(200)
            ->assertJsonPath('ok', true)
            ->assertJsonPath('module_progress.is_completed', true)
            ->assertJsonPath('enrollment.async_hours_completed', 2)
            ->assertJsonPath('enrollment.total_hours_completed', 2)
            ->assertJsonPath('enrollment.attendance_percent', 100)
            ->assertJsonPath('enrollment.is_eligible_for_certificate', true);

        // 4. Student downloads the unlocked Allegato 4 PDF Certificate for the 1-module course
        $certRes = $this->actingAs($student)->get(route('student.enrollments.certificate', $enrollment->fresh()));
        $certRes->assertStatus(200);
        $this->assertStringStartsWith('%PDF-', $certRes->getContent());
    }

    public function test_10_student_personal_audit_log_and_personal_audit_pdf_download(): void
    {
        $elena = User::where('email', 'elena.bianchi@guideroma.it')->firstOrFail();
        $sara = User::where('email', 'sara.lombardi@guideroma.it')->firstOrFail();
        $course = Course::where('code', 'AGG-GT-2026-01')->firstOrFail();

        $enrElena = Enrollment::where('user_id', $elena->id)->where('course_id', $course->id)->firstOrFail();
        $enrSara = Enrollment::where('user_id', $sara->id)->where('course_id', $course->id)->firstOrFail();

        // 1. Student sees the Personal Audit Box and Download Button inside the Course Player
        $playerRes = $this->actingAs($elena)->get(route('student.courses.show', $course));
        $playerRes->assertStatus(200)
            ->assertSee('Il Tuo Registro Audit Personale')
            ->assertSee('Scarica il Tuo Fascicolo Audit (PDF)');

        // 2. Student can download her own Personal Audit Dossier PDF
        $auditPdf = $this->actingAs($elena)->get(route('student.enrollments.audit-pdf', $enrElena));
        $auditPdf->assertStatus(200);
        $this->assertStringStartsWith('%PDF-', $auditPdf->getContent());

        // 3. Security Check: Student CANNOT download another student's Personal Audit PDF (403 Forbidden)
        $this->actingAs($sara)
            ->get(route('student.enrollments.audit-pdf', $enrElena))
            ->assertStatus(403);
    }
}
