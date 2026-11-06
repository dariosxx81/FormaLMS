<?php

namespace App\Http\Controllers;

use App\Mail\AccountApprovedMail;
use App\Mail\CertificateEarnedMail;
use App\Mail\CourseAssignedMail;
use App\Mail\CourseRedeemedMail;
use App\Mail\ModuleCompletedMail;
use App\Mail\PasswordResetMail;
use App\Mail\WelcomeRegistrationMail;
use App\Models\ActivityLog;
use App\Models\Course;
use App\Models\CourseModule;
use App\Models\Enrollment;
use App\Models\PlatformSetting;
use App\Models\SentEmail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class AdminEmailController extends Controller
{
    public function index(Request $request)
    {
        $settings = PlatformSetting::getAllSettings();
        $search = trim((string) $request->query('search', ''));

        $query = SentEmail::latest();

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('to_email', 'like', "%{$search}%")
                  ->orWhere('subject', 'like', "%{$search}%");
            });
        }

        $emails = $query->paginate(20)->withQueryString();
        $totalSent = SentEmail::count();

        // Mail configuration summary
        $mailer = config('mail.default');
        $mailConfig = [
            'mailer' => $mailer,
            'host' => config("mail.mailers.{$mailer}.host"),
            'port' => config("mail.mailers.{$mailer}.port"),
            'from_address' => config('mail.from.address'),
            'from_name' => config('mail.from.name'),
        ];

        $courses = Course::orderBy('title')->get();
        $students = User::where('role', 'student')->latest()->limit(50)->get();

        return view('admin.emails.index', compact(
            'settings',
            'emails',
            'search',
            'totalSent',
            'mailConfig',
            'courses',
            'students'
        ));
    }

    public function preview(SentEmail $email)
    {
        return response($email->body_html ?: nl2br(e($email->body_text ?? 'Nessun contenuto disponibile.')), 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
        ]);
    }

    public function sendTest(Request $request)
    {
        $data = $request->validate([
            'test_type' => ['required', 'in:welcome,assigned,redeemed,module_completed,certificate,approved,reset,custom'],
            'recipient_email' => ['required', 'email'],
            'course_id' => ['nullable', 'exists:courses,id'],
            'subject' => ['nullable', 'string', 'max:255'],
            'message' => ['nullable', 'string', 'max:5000'],
        ]);

        $settings = PlatformSetting::getAllSettings();
        $recipient = $data['recipient_email'];

        // Temporary or mock user for previewing mailables
        $user = User::where('email', $recipient)->first() ?? new User([
            'first_name' => 'Mario',
            'last_name' => 'Rossi',
            'email' => $recipient,
            'fiscal_code' => 'RSSMRA80A01H501U',
            'role' => 'student',
        ]);

        try {
            if ($data['test_type'] === 'welcome') {
                $course = !empty($data['course_id']) ? Course::find($data['course_id']) : Course::first();
                Mail::to($recipient)->send(new WelcomeRegistrationMail($user, $course, $settings));
            } elseif ($data['test_type'] === 'assigned') {
                $course = !empty($data['course_id']) ? Course::find($data['course_id']) : Course::first();
                Mail::to($recipient)->send(new CourseAssignedMail($user, $course, $settings));
            } elseif ($data['test_type'] === 'module_completed') {
                $course = !empty($data['course_id']) ? Course::find($data['course_id']) : Course::first();
                $module = $course?->modules()->first() ?? new CourseModule([
                    'sort_order' => 1,
                    'title' => 'Modulo 1 — Legislazione e Deontologia Professionale',
                    'instructor_name' => 'Prof.ssa Elena Conti',
                    'video_source_type' => 'vimeo',
                    'duration_seconds' => 3600,
                ]);
                $enrollment = ($course ? Enrollment::where('course_id', $course->id)->first() : null) ?? new Enrollment([
                    'id' => 99,
                    'user_id' => $user->id,
                    'course_id' => $course?->id ?? 1,
                    'total_hours_completed' => 5.0,
                    'attendance_percent' => 50.0,
                    'is_eligible_for_certificate' => false,
                ]);
                Mail::to($recipient)->send(new ModuleCompletedMail($user, $course, $module, $enrollment, $settings));
            } elseif ($data['test_type'] === 'certificate') {
                $course = !empty($data['course_id']) ? Course::find($data['course_id']) : Course::first();
                $enrollment = Enrollment::where('course_id', $course->id)->first() ?? new Enrollment([
                    'id' => 99,
                    'user_id' => $user->id,
                    'course_id' => $course->id,
                    'certificate_number' => 'ATT-' . strtoupper(substr($course->code, 0, 6)) . '-2026-0001',
                    'certificate_issued_at' => now(),
                    'total_hours_completed' => $course->total_hours,
                    'attendance_percent' => 100.0,
                    'is_eligible_for_certificate' => true,
                ]);
                Mail::to($recipient)->send(new CertificateEarnedMail($user, $course, $enrollment, $settings));
            } elseif ($data['test_type'] === 'redeemed') {
                $course = !empty($data['course_id']) ? Course::find($data['course_id']) : Course::first();
                $code = $course ? $course->code : 'CORSO-DEMO';
                Mail::to($recipient)->send(new CourseRedeemedMail($user, $course, $settings, $code));
            } elseif ($data['test_type'] === 'approved') {
                Mail::to($recipient)->send(new AccountApprovedMail($user, $settings));
            } elseif ($data['test_type'] === 'reset') {
                $fakeResetUrl = route('password.request') . '?token=test_preview_token&email=' . urlencode($recipient);
                Mail::to($recipient)->send(new PasswordResetMail($user, $fakeResetUrl, $settings, 'link'));
            } else {
                $subject = !empty($data['subject']) ? $data['subject'] : 'Email di Test da ' . ($settings['platform_name'] ?? 'FormaLMS');
                $body = !empty($data['message']) ? $data['message'] : 'Questa è un\'email di test inviata dal pannello di amministrazione per verificare la corretta configurazione e il tracciamento dei log.';

                Mail::raw($body, function ($m) use ($recipient, $subject, $settings) {
                    $m->to($recipient)
                      ->subject($subject);
                });
            }

            ActivityLog::record(
                'test_email_sent',
                "Inviata email di test ({$data['test_type']}) a {$recipient}",
                auth()->id()
            );

            return back()->with('success', "Email di test ({$data['test_type']}) inviata con successo a {$recipient}! È ora visibile nel log sottostante.");
        } catch (\Throwable $e) {
            return back()->withErrors(['email_error' => 'Errore durante l\'invio della mail: ' . $e->getMessage()]);
        }
    }

    public function destroy(SentEmail $email)
    {
        $email->delete();

        ActivityLog::record(
            'email_log_deleted',
            "Eliminato log email ID #{$email->id} diretta a {$email->to_email}",
            auth()->id()
        );

        return back()->with('success', 'Log email eliminato con successo.');
    }

    public function updateEmailSettings(Request $request)
    {
        $validated = $request->validate([
            'mail_welcome_subject' => ['nullable', 'string', 'max:255'],
            'mail_welcome_body' => ['nullable', 'string', 'max:5000'],

            'mail_assigned_subject' => ['nullable', 'string', 'max:255'],
            'mail_assigned_body' => ['nullable', 'string', 'max:5000'],

            'mail_redeemed_subject' => ['nullable', 'string', 'max:255'],
            'mail_redeemed_body' => ['nullable', 'string', 'max:5000'],

            'mail_module_completed_subject' => ['nullable', 'string', 'max:255'],
            'mail_module_completed_body' => ['nullable', 'string', 'max:5000'],

            'mail_certificate_subject' => ['nullable', 'string', 'max:255'],
            'mail_certificate_body' => ['nullable', 'string', 'max:5000'],

            'mail_approved_subject' => ['nullable', 'string', 'max:255'],
            'mail_approved_body' => ['nullable', 'string', 'max:5000'],

            'mail_reset_subject' => ['nullable', 'string', 'max:255'],
            'mail_reset_body' => ['nullable', 'string', 'max:5000'],
        ]);

        $settingsToSave = [
            'mail_welcome_active' => $request->boolean('mail_welcome_active') ? '1' : '0',
            'mail_welcome_subject' => trim((string) ($validated['mail_welcome_subject'] ?? '')),
            'mail_welcome_body' => trim((string) ($validated['mail_welcome_body'] ?? '')),

            'mail_assigned_active' => $request->boolean('mail_assigned_active') ? '1' : '0',
            'mail_assigned_subject' => trim((string) ($validated['mail_assigned_subject'] ?? '')),
            'mail_assigned_body' => trim((string) ($validated['mail_assigned_body'] ?? '')),

            'mail_redeemed_active' => $request->boolean('mail_redeemed_active') ? '1' : '0',
            'mail_redeemed_subject' => trim((string) ($validated['mail_redeemed_subject'] ?? '')),
            'mail_redeemed_body' => trim((string) ($validated['mail_redeemed_body'] ?? '')),

            'mail_module_completed_active' => $request->boolean('mail_module_completed_active') ? '1' : '0',
            'mail_module_completed_subject' => trim((string) ($validated['mail_module_completed_subject'] ?? '')),
            'mail_module_completed_body' => trim((string) ($validated['mail_module_completed_body'] ?? '')),

            'mail_certificate_active' => $request->boolean('mail_certificate_active') ? '1' : '0',
            'mail_certificate_subject' => trim((string) ($validated['mail_certificate_subject'] ?? '')),
            'mail_certificate_body' => trim((string) ($validated['mail_certificate_body'] ?? '')),

            'mail_approved_active' => $request->boolean('mail_approved_active') ? '1' : '0',
            'mail_approved_subject' => trim((string) ($validated['mail_approved_subject'] ?? '')),
            'mail_approved_body' => trim((string) ($validated['mail_approved_body'] ?? '')),

            'mail_reset_active' => $request->boolean('mail_reset_active') ? '1' : '0',
            'mail_reset_subject' => trim((string) ($validated['mail_reset_subject'] ?? '')),
            'mail_reset_body' => trim((string) ($validated['mail_reset_body'] ?? '')),
        ];

        PlatformSetting::setValues($settingsToSave);

        ActivityLog::record(
            'email_settings_updated',
            'Aggiornata la configurazione dei template email e le attivazioni notifiche (ON/OFF)',
            auth()->id()
        );

        return back()->with('success', 'Configurazione e testi delle email salvati con successo.');
    }

    public function clear()
    {
        SentEmail::truncate();

        ActivityLog::record(
            'email_logs_cleared',
            'Svuotato completamente il registro dei log email inviate',
            auth()->id()
        );

        return back()->with('success', 'Tutti i log delle email sono stati eliminati.');
    }
}
