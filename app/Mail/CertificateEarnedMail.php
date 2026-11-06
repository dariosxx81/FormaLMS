<?php

namespace App\Mail;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\PlatformSetting;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CertificateEarnedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public Course $course,
        public Enrollment $enrollment,
        public array $settings
    ) {}

    public function envelope(): Envelope
    {
        $platformName = $this->settings['platform_name'] ?? 'FormaLMS';
        $defaultSubject = "🏆 Congratulazioni! Hai conseguito l'Attestato per il corso [{$this->course->code}] — {$platformName}";
        $subjectTemplate = !empty($this->settings['mail_certificate_subject']) ? $this->settings['mail_certificate_subject'] : $defaultSubject;

        $subject = PlatformSetting::renderMailText($subjectTemplate, [
            'platform_name' => $platformName,
            'full_name' => $this->user->full_name,
            'first_name' => $this->user->first_name,
            'last_name' => $this->user->last_name,
            'course_title' => $this->course->title,
            'course_code' => $this->course->code,
        ]);

        return new Envelope(
            subject: $subject,
        );
    }

    public function content(): Content
    {
        $platformName = $this->settings['platform_name'] ?? 'FormaLMS';
        $bodyTemplate = $this->settings['mail_certificate_body'] ?? '';
        $customBody = !empty($bodyTemplate) ? PlatformSetting::renderMailText($bodyTemplate, [
            'nome' => $this->user->first_name,
            'cognome' => $this->user->last_name,
            'nome_completo' => $this->user->full_name,
            'email' => $this->user->email,
            'piattaforma' => $platformName,
            'ente' => $this->settings['provider_name'] ?? 'Formacamera',
            'corso' => $this->course->title,
            'certificato_numero' => $this->enrollment->certificate_number,
        ]) : null;

        return new Content(
            view: 'emails.certificate-earned',
            with: [
                'customBody' => $customBody,
            ],
        );
    }
}
