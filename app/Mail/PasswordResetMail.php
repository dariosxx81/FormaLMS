<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PasswordResetMail extends Mailable
{
    use Queueable, SerializesModels;

    public User $user;
    public ?string $resetUrl;
    public array $settings;
    public string $mode; // 'link' | 'changed'

    public function __construct(User $user, ?string $resetUrl, array $settings = [], string $mode = 'link')
    {
        $this->user = $user;
        $this->resetUrl = $resetUrl;
        $this->settings = $settings;
        $this->mode = $mode;
    }

    public function envelope(): Envelope
    {
        $platformName = $this->settings['platform_name'] ?? 'Piattaforma FAD';

        $subject = $this->mode === 'changed'
            ? "Conferma modifica password — {$platformName}"
            : "Recupero Password & Link di Reimpostazione — {$platformName}";

        return new Envelope(
            subject: $subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.password-reset',
        );
    }
}
