<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Recipe extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'recipe_image_path',
        'cooking_time',
        'servings',
        'view_count',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function ingredients(): HasMany
    {
        return $this->hasMany(Ingredient::class);
    }

    public function steps(): HasMany
    {
        return $this->hasMany(Step::class);
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(RecipeComment::class);
    }

    /**
     * 閲覧者がお気に入り登録済みかを is_favorited として付与する。
     * サブクエリ1本で解決するので一覧でも N+1 にならない。ゲスト（null）は常に false。
     *
     * @param  Builder<Recipe>  $query
     * @return Builder<Recipe>
     */
    public function scopeWithFavoritedBy(Builder $query, ?User $user): Builder
    {
        return $query->withExists([
            'favorites as is_favorited' => fn ($favorites) => $favorites->where('user_id', $user?->id),
        ]);
    }

    /**
     * キーワード検索。空白（全角・半角）で区切った語をすべて含むレシピに絞る（AND）。
     * 1語ごとに「タイトル または 材料名」の部分一致で探す。LIKE のワイルドカードはエスケープする。
     *
     * @param  Builder<Recipe>  $query
     * @return Builder<Recipe>
     */
    public function scopeSearch(Builder $query, ?string $keyword): Builder
    {
        $words = preg_split('/[\s　]+/u', trim((string) $keyword), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        foreach ($words as $word) {
            $pattern = '%'.addcslashes($word, '\\%_').'%';

            $query->where(fn (Builder $recipe) => $recipe
                ->where('title', 'like', $pattern)
                ->orWhereHas('ingredients', fn (Builder $ingredient) => $ingredient->where('name', 'like', $pattern)));
        }

        return $query;
    }
}
