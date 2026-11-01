<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $categories = Category::visibleTo($request->user()->id)
            ->orderBy('name')
            ->get();

        return $this->successResponse('Categories retrieved successfully.', $categories);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(['income', 'expense', 'both'])],
        ]);

        $category = $request->user()->categories()->create($validated);

        return $this->successResponse('Category created successfully.', $category->refresh(), 201);
    }

    public function show(Request $request, int $category): JsonResponse
    {
        $category = Category::visibleTo($request->user()->id)->findOrFail($category);

        return $this->successResponse('Category retrieved successfully.', $category);
    }

    public function update(Request $request, int $category): JsonResponse
    {
        $category = $request->user()->categories()->findOrFail($category);
        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'type' => ['sometimes', 'required', Rule::in(['income', 'expense', 'both'])],
        ]);

        if (isset($validated['type']) && $validated['type'] !== 'both') {
            $hasIncompatibleTransactions = $category->transactions()
                ->where('type', '!=', $validated['type'])
                ->exists();

            if ($hasIncompatibleTransactions) {
                return $this->errorResponse(
                    'VALIDATION_422',
                    'Category type conflicts with existing transactions.',
                    422,
                    ['errors' => ['type' => ['The category type conflicts with existing transactions.']]]
                );
            }
        }

        $category->update($validated);

        return $this->successResponse('Category updated successfully.', $category->refresh());
    }

    public function destroy(Request $request, int $category): JsonResponse
    {
        $request->user()->categories()->findOrFail($category)->delete();

        return $this->successResponse('Category deleted successfully.');
    }
}
