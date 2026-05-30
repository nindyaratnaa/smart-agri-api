<?php

namespace App\Mail;

use App\Models\DataPertanian;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AnomalyAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly DataPertanian $dataPertanian,
        public readonly User $recipient
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[Smart Agriculture] ⚠️ Anomali Data Terdeteksi — ' . $this->dataPertanian->komoditas,
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.anomaly-alert');
    }
}
