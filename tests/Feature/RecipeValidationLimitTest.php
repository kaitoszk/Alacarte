<?php

use App\Models\Recipe;
use App\Models\User;
use Illuminate\Http\UploadedFile;

/** 上限ちょうどではなく、通常の有効な入力 */
function validRecipeInput(): array
{
    return [
        'title' => '肉じゃが',
        'description' => '定番の肉じゃがです。',
        'recipe_image' => UploadedFile::fake()->create('recipe.jpg', 100, 'image/jpeg'),
        'cooking_time' => 30,
        'servings' => 2,
        'ingredients' => [['name' => 'じゃがいも', 'quantity' => '3個']],
        'steps' => [['description' => '煮込む']],
    ];
}

/** 上限ちょうどの入力（すべて受け付けられるべき値） */
function maxRecipeInput(): array
{
    return [
        ...validRecipeInput(),
        'description' => str_repeat('あ', 2000),
        'cooking_time' => 1440,
        'servings' => 100,
        'ingredients' => array_fill(0, 50, ['name' => '塩', 'quantity' => '少々']),
        'steps' => [
            [
                'description' => str_repeat('あ', 2000),
                'images' => array_map(fn ($i) => UploadedFile::fake()->create("step{$i}.jpg", 10, 'image/jpeg'), range(1, 3)),
            ],
            ...array_fill(0, 49, ['description' => '混ぜる']),
        ],
    ];
}

dataset('上限超過の入力', [
    '調理時間' => [fn () => ['cooking_time' => 1441], 'cooking_time'],
    '人数' => [fn () => ['servings' => 101], 'servings'],
    '説明の文字数' => [fn () => ['description' => str_repeat('あ', 2001)], 'description'],
    '材料の件数' => [fn () => ['ingredients' => array_fill(0, 51, ['name' => '塩', 'quantity' => '少々'])], 'ingredients'],
    '手順の件数' => [fn () => ['steps' => array_fill(0, 51, ['description' => '混ぜる'])], 'steps'],
    '手順の文字数' => [fn () => ['steps' => [['description' => str_repeat('あ', 2001)]]], 'steps.0.description'],
    '手順画像の枚数' => [fn () => ['steps' => [[
        'description' => '混ぜる',
        'images' => array_map(fn ($i) => UploadedFile::fake()->create("step{$i}.jpg", 10, 'image/jpeg'), range(1, 4)),
    ]]], 'steps.0.images'],
]);

test('投稿: 上限を超える入力はバリデーションエラーになる', function (Closure $override, string $errorKey) {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('recipes.store'), [...validRecipeInput(), ...$override()]);

    $response->assertSessionHasErrors($errorKey);
    expect(Recipe::count())->toBe(0);
})->with('上限超過の入力');

test('更新: 上限を超える入力はバリデーションエラーになる', function (Closure $override, string $errorKey) {
    $owner = User::factory()->create();
    $recipe = Recipe::factory()->for($owner)->create(['title' => '元のタイトル']);

    $response = $this->actingAs($owner)->put(route('recipes.update', $recipe), [...validRecipeInput(), ...$override()]);

    $response->assertSessionHasErrors($errorKey);
    expect($recipe->fresh()->title)->toBe('元のタイトル');
})->with('上限超過の入力');

test('投稿: 上限ちょうどの入力は受け付けられる', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('recipes.store'), maxRecipeInput());

    $response->assertSessionHasNoErrors();
    $recipe = Recipe::first();
    expect($recipe->ingredients)->toHaveCount(50)
        ->and($recipe->steps)->toHaveCount(50);
});

test('更新: 上限ちょうどの入力は受け付けられる', function () {
    $owner = User::factory()->create();
    $recipe = Recipe::factory()->for($owner)->create();

    $response = $this->actingAs($owner)->put(route('recipes.update', $recipe), maxRecipeInput());

    $response->assertSessionHasNoErrors();
    expect($recipe->fresh()->cooking_time)->toBe(1440)
        ->and($recipe->fresh()->servings)->toBe(100);
});
