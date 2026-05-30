<?php

namespace App\Jobs;

use App\Mail\DataDiajukanMail;
use App\Models\DataPertanian;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendValidasiNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        public readonly DataPertanian $dataPertanian
    ) {}

    public function handle(): void
    {
        // Find bhabinkamtibmas assigned to this wilayah or parent wilayah
        $validators = User::where('role', 'bhabinkamtibmas')
            ->where('is_active', true)
            ->where(function ($q) {
                $q->where('wilayah_id', $this->dataPertanian->wilayah_id)
                  ->orWhere('wilayah_id', $this->dataPertanian->wilayah?->parent_id);
            })
            ->get();

        foreach ($validators as $validator) {
            Mail::to($validator->email)->queue(
                new DataDiajukanMail($this->dataPertanian, $validator)
            );
        }
    }
}
