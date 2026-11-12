<?php

namespace App\Mail;

use App\Models\Course;
use App\Models\PlatformSetting;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CourseRedeemedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public Course $course,
        public array $settings,
        public ?string $accessCode = null
    ) {}

    public function envelope(): Envelope
    {
        $platformName = $this->settings['platform_name'] ?? 'FormaLMS';
        $defaultSubject = "Corso Sbloccato con Successo: [{$this->course->code}] {$this->course->title} — {$platformName}";
        $subjectTemplate = !empty($this->settings['mail_redeemed_subject']) ? $this->settings['mail_redeemed_subject'] : $defaultSubject;

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
        $bodyTemplate = $this->settings['mail_redeemed_body'] ?? '';
        $customBody = !empty($bodyTemplate) ? PlatformSetting::renderMailText($bodyTemplate, [
            'nome' => $this->user->first_name,
            'cognome' => $this->user->last_name,
            'nome_completo' => $this->user->full_name,
            'first_name' => $this->user->first_name,
            'last_name' => $this->user->last_name,
            'full_name' => $this->user->full_name,
            'email' => $this->user->email,
            'piattaforma' => $platformName,
            'platform_name' => $platformName,
            'ente' => $this->settings['provider_name'] ?? 'Formacamera',
            'provider_name' => $this->settings['provider_name'] ?? 'Formacamera',
            'corso' => $this->course->title,
            'course_title' => $this->course->title,
            'codice_corso' => $this->course->code,
            'course_code' => $this->course->code,
            'access_code' => $this->accessCode ?? '',
        ]) : null;

        return new Content(
            view: 'emails.course-redeemed',
            with: [
                'customBody' => $customBody,
            ],
        );
    }
}
