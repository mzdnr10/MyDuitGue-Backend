<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    protected function successResponse(
        string $message,
        mixed $data = null,
        int $status = 200
    ): JsonResponse {
        return response()->json([
            'responseCode' => '00',
            'responseMessage' => $message,
            'data' => $data,
        ], $status);
    }

    protected function errorResponse(
        string $code,
        string $message,
        int $status,
        mixed $data = null
    ): JsonResponse {
        return response()->json([
            'responseCode' => $code,
            'responseMessage' => $message,
            'data' => $data,
        ], $status);
    }
}
