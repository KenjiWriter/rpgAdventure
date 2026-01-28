<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CheckMountExpirations implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Delete expired sessions
        // We could also emit events here if we wanted to notify online users via Websockets
        // For now, just clean up DB.
        \App\Models\MountSession::where('expires_at', '<', now())->delete();
    }
}
