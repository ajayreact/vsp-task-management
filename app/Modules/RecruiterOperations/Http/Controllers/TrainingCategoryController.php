<?php

namespace App\Modules\RecruiterOperations\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\RecruiterOperations\Http\Requests\TrainingCategoryRequest;
use App\Modules\RecruiterOperations\Models\TrainingCategory;
use App\Modules\RecruiterOperations\Services\TrainingContentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Training categories (levels) for recruiter.training.manage. Categories are
 * deactivated, never deleted, so courses always keep their level.
 */
class TrainingCategoryController extends Controller
{
    public function __construct(protected TrainingContentService $content) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', TrainingCategory::class);

        return Inertia::render('RecruiterOperations/training/manage/categories', [
            'categories' => TrainingCategory::query()
                ->withCount('courses')
                ->ordered()
                ->get()
                ->map(fn (TrainingCategory $category) => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'description' => $category->description,
                    'level_number' => $category->level_number,
                    'sort_order' => $category->sort_order,
                    'is_active' => $category->is_active,
                    'courses_count' => $category->courses_count,
                ])
                ->values()
                ->all(),
        ]);
    }

    public function store(TrainingCategoryRequest $request): RedirectResponse
    {
        $this->content->createCategory($request->validated(), $request->user());

        return to_route('recruiter.training.manage.categories.index')->with('success', 'Category created.');
    }

    public function update(TrainingCategoryRequest $request, TrainingCategory $trainingCategory): RedirectResponse
    {
        $this->content->updateCategory($trainingCategory, $request->validated(), $request->user());

        return to_route('recruiter.training.manage.categories.index')->with('success', 'Category updated.');
    }

    public function toggle(Request $request, TrainingCategory $trainingCategory): RedirectResponse
    {
        $active = ! $trainingCategory->is_active;
        $this->content->setCategoryActive($trainingCategory, $active, $request->user());

        return back()->with('success', $active ? 'Category activated.' : 'Category deactivated.');
    }
}
