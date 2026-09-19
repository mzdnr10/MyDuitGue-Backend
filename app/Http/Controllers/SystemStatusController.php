<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

class SystemStatusController extends Controller
{
    public function index()
    {
        try {
            DB::connection()->getPdo();

            return $this->successResponse('Database connection is ready.', [
                'status' => 'connected',
                'database' => config('database.default'),
            ]);
        } catch (\Throwable $th) {
            return $this->errorResponse('SYSTEM_500', 'Database connection failed.', 500, [
                'status' => 'error',
                'database' => config('database.default'),
            ]);
        }
    }
}
