<?php

namespace App\Http\Controllers;

use App\Services\BackofficeService;

class BackofficeController extends Controller
{
    public function __construct(private BackofficeService $backofficeService)
    {
    }

    public function users()
    {
        return $this->successResponse('Users retrieved successfully.', $this->backofficeService->users());
    }

    public function transactions()
    {
        return $this->successResponse('Transactions retrieved successfully.', $this->backofficeService->transactions());
    }

    public function categories()
    {
        return $this->successResponse('Categories retrieved successfully.', $this->backofficeService->categories());
    }

    public function home()
    {
        return $this->successResponse('Backoffice home retrieved successfully.', $this->backofficeService->summary());
    }
}
