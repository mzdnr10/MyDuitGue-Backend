<?php

namespace App\Http\Controllers;

use App\Services\ServiceCatalogService;
use Illuminate\Http\Request;

class ServicesController extends Controller
{
    public function __construct(private ServiceCatalogService $serviceCatalogService)
    {
    }

    public function index(Request $request)
    {
        return $this->categoryAndAccount($request);
    }

    public function categoryAndAccount(Request $request)
    {
        return $this->successResponse(
            'Services retrieved successfully.',
            $this->serviceCatalogService->categoriesAndAccounts(
                $request->user(),
                $request->input('type')
            ),
            201
        );
    }
}
