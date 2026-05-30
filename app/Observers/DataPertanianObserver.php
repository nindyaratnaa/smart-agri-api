<?php

namespace App\Observers;

use App\Jobs\SendValidasiNotification;
use App\Models\DataPertanian;

class DataPertanianObserver
{
    /**
     * Fired after a new DataPertanian is submitted (not draft)
     */
    public function created(DataPertanian $dataPertanian): void
    {
        if (!$dataPertanian->is_draft) {
            // Notify assigned bhabinkamtibmas
            SendValidasiNotification::dispatch($dataPertanian)->delay(now()->addSeconds(5));
        }
    }

    /**
     * Fired after update — e.g., when draft is submitted or status changes
     */
    public function updated(DataPertanian $dataPertanian): void
    {
        // Draft converted to submission
        if ($dataPertanian->wasChanged('is_draft') && !$dataPertanian->is_draft) {
            SendValidasiNotification::dispatch($dataPertanian)->delay(now()->addSeconds(5));
        }
    }
}
