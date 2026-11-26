<?php

namespace App\Http\Controllers;

use App\Actions\Recipe\CreateRecipeAction;
use App\Actions\Recipe\UpdateRecipeAction;
use App\Http\Requests\IndexRecipeRequest;
use App\Http\Requests\StoreRecipeRequest;
use App\Http\Requests\UpdateRecipeRequest;
use App\Http\Resources\RecipeCommentResource;
use App\Http\Resources\RecipeResource;
use App\Models\MealPlan;
use App\Models\Recipe;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RecipeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(IndexRecipeRequest $request): Response
    {
        $this->authorize('viewAny', Recipe::class);

        $keyword = $request->validated('q');
        $time = $request->validated('time') !== null ? (int) $request->validated('time') : null;
        $sort = $request->validated('sort') ?? 'new';

        $recipes = Recipe::query()
            ->published()
            ->search($keyword)
            ->cookableWithin($time)
            ->with('user')
            ->withCount(['favorites', 'comments'])
            ->withFavoritedBy($request->user())
            // 人気順はお気に入り数の多い順。同数なら新しい順
            ->when($sort === 'popular', fn ($recipes) => $recipes->orderByDesc('favorites_count'))
            ->latest('published_at')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('recipes/Index', [
            'recipes' => RecipeResource::collection($recipes),
            'filters' => ['q' => $keyword, 'time' => $time, 'sort' => $sort],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        $this->authorize('create', Recipe::class);

        return Inertia::render('recipes/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRecipeRequest $request, CreateRecipeAction $action): RedirectResponse
    {
        $this->authorize('create', Recipe::class);

        $recipe = $action->handle($request->user(), $request->validated(), $request->publishes());

        return $this->redirectAfterSave($recipe);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Recipe $recipe): Response
    {
        $this->authorize('view', $recipe);

        // 下書きは本人のプレビューなので閲覧数に数えない
        if ($recipe->isPublished()) {
            $recipe->increment('view_count');
        }

        $recipe->load(['user', 'ingredients', 'steps.stepImages'])
            ->loadCount(['favorites', 'comments'])
            ->loadExists(['favorites as is_favorited' => fn ($favorites) => $favorites->where('user_id', $request->user()?->id)]);

        // コメントは新しい順。投稿者といいね数・いいね済みをまとめて解決する（N+1対策）
        $comments = $recipe->comments()
            ->with('user')
            ->withCount('goods')
            ->withGoodedBy($request->user())
            ->latest()
            ->get();

        return Inertia::render('recipes/Show', [
            'recipe' => new RecipeResource($recipe),
            'comments' => RecipeCommentResource::collection($comments),
            'isFollowingAuthor' => (bool) $request->user()?->followings()->whereKey($recipe->user_id)->exists(),
            'isInCart' => (bool) $request->user()?->carts()->where('recipe_id', $recipe->id)->exists(),
            // 献立に追加できる日付（日本時間の今日〜13日後）。サーバーの検証と同じ範囲を選択肢に出す
            'mealPlanDates' => $request->user() ? MealPlan::addableDates() : [],
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Recipe $recipe): Response
    {
        $this->authorize('update', $recipe);

        $recipe->load(['ingredients', 'steps.stepImages']);

        return Inertia::render('recipes/Edit', [
            'recipe' => new RecipeResource($recipe),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRecipeRequest $request, Recipe $recipe, UpdateRecipeAction $action): RedirectResponse
    {
        $this->authorize('update', $recipe);

        $action->handle($recipe, $request->validated(), $request->publishes());

        return $this->redirectAfterSave($recipe);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Recipe $recipe): RedirectResponse
    {
        $this->authorize('delete', $recipe);

        $wasPublished = $recipe->isPublished();

        $recipe->delete();

        return to_route($wasPublished ? 'recipes.index' : 'drafts.index');
    }

    /**
     * 下書きは続きを書けるよう編集画面へ、公開したものは詳細へ戻す。
     */
    private function redirectAfterSave(Recipe $recipe): RedirectResponse
    {
        return $recipe->isPublished()
            ? to_route('recipes.show', $recipe)
            : to_route('recipes.edit', $recipe);
    }
}
