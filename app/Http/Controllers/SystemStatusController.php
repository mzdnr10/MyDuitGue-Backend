<?php

namespace App\Http\Controllers;

use App\Services\SystemStatusService;

class SystemStatusController extends Controller
{
    public function __construct(private SystemStatusService $systemStatusService)
    {
    }

    public function index()
    {
        if ($this->systemStatusService->isDatabaseReady()) {
            return $this->successResponse('Database connection is ready.', [
                'status' => 'connected',
                'database' => config('database.default'),
            ]);
        }

        return $this->errorResponse('SYSTEM_500', 'Database connection failed.', 500, [
            'status' => 'error',
            'database' => config('database.default'),
        ]);
    }
}
