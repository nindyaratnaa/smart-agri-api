<?php

namespace App\Jobs;

use App\Models\DataPertanian;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendAnomalyAlert implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public readonly DataPertanian $dataPertanian
    ) {}

    public function handle(): void
    {
        // Notify dinas and pusat level users
        $recipients = User::whereIn('role', ['dinas', 'pusat'])
            ->where('is_active', true)
            ->get();

        foreach ($recipients as $recipient) {
            Mail::to($recipient->email)->queue(
                new \App\Mail\AnomalyAlertMail($this->dataPertanian, $recipient)
            );
        }
    }
}
