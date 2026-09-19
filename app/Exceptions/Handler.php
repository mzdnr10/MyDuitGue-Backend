<?php

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    protected function invalidJson($request, ValidationException $exception): \Symfony\Component\HttpFoundation\Response
    {
        return response()->json([
            'responseCode' => 'VALIDATION_422',
            'responseMessage' => 'The given data was invalid.',
            'data' => [
                'errors' => $exception->errors(),
            ],
        ], $exception->status);
    }

    protected function unauthenticated($request, AuthenticationException $exception): \Symfony\Component\HttpFoundation\Response
    {
        return response()->json([
            'responseCode' => 'AUTH_401',
            'responseMessage' => 'Unauthenticated.',
            'data' => null,
        ], 401);
    }
}
