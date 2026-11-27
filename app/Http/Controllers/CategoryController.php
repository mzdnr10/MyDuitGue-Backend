<?php

namespace App\Http\Controllers;

use App\Services\CategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function __construct(private CategoryService $categoryService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        return $this->successResponse(
            'Categories retrieved successfully.',
            $this->categoryService->listFor($request->user())
        );
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(['income', 'expense', 'both'])],
        ]);

        return $this->successResponse(
            'Category created successfully.',
            $this->categoryService->create($request->user(), $validated),
            201
        );
    }

    public function show(Request $request, int $category): JsonResponse
    {
        return $this->successResponse(
            'Category retrieved successfully.',
            $this->categoryService->findVisible($request->user(), $category)
        );
    }

    public function update(Request $request, int $category): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'type' => ['sometimes', 'required', Rule::in(['income', 'expense', 'both'])],
        ]);

        $updatedCategory = $this->categoryService->update($request->user(), $category, $validated);

        if ($updatedCategory === null) {
            return $this->errorResponse(
                'VALIDATION_422',
                'Category type conflicts with existing transactions.',
                422,
                ['errors' => ['type' => ['The category type conflicts with existing transactions.']]]
            );
        }

        return $this->successResponse('Category updated successfully.', $updatedCategory);
    }

    public function destroy(Request $request, int $category): JsonResponse
    {
        $this->categoryService->delete($request->user(), $category);

        return $this->successResponse('Category deleted successfully.');
    }
}
