<?php

namespace App\Providers;

use App\Models\SentEmail;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(MessageSending::class, function (MessageSending $event) {
            try {
                $msg = $event->message;

                // Ensure Reply-To header is set globally if configured
                $replyAddress = config('mail.reply_to.address');
                if ($replyAddress && empty($msg->getReplyTo())) {
                    $msg->setReplyTo([new \Symfony\Component\Mime\Address($replyAddress, config('mail.reply_to.name') ?: '')]);
                }

                $to = [];
                if ($msg->getTo()) {
                    foreach ($msg->getTo() as $addr) {
                        $to[] = $addr->getAddress();
                    }
                }
                $from = null;
                if ($msg->getFrom() && count($msg->getFrom()) > 0) {
                    $from = $msg->getFrom()[0]->getAddress();
                }

                $html = $msg->getHtmlBody();
                $text = $msg->getTextBody();
                if (!$html && $text) {
                    $html = '<div style="font-family: sans-serif; white-space: pre-wrap;">' . e($text) . '</div>';
                }

                SentEmail::create([
                    'to_email' => !empty($to) ? implode(', ', $to) : 'destinatario-non-specificato',
                    'from_email' => $from,
                    'subject' => (string) ($msg->getSubject() ?? '(Nessun oggetto)'),
                    'body_html' => $html,
                    'body_text' => $text,
                    'status' => 'sent',
                ]);
            } catch (\Throwable $e) {
                Log::warning('Errore salvataggio SentEmail: ' . $e->getMessage());
            }
        });
    }
}
