<?php

namespace App\Observers;

use App\Mail\DataDisetujuiMail;
use App\Mail\DataDitolakMail;
use App\Models\Validasi;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;

class ValidasiObserver
{
    public function created(Validasi $validasi): void
    {
        // Invalidate dashboard cache
        Cache::forget('dashboard:dinas:nasional');
        Cache::forget('statistik:nasional');
        Cache::tags(['peta'])->flush();
    }
}
