<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class SystemStatusService
{
    public function isDatabaseReady(): bool
    {
        try {
            DB::connection()->getPdo();

            return true;
        } catch (\Throwable) {
            return false;
        }
    }
}
