<?php

use App\Models\Recipe;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');

    $this->owner = User::factory()->create();
});

// ---- 下書き保存 ----

test('タイトルだけで下書き保存でき、続きを書けるよう編集画面に戻る', function () {
    $response = $this->actingAs($this->owner)->post(route('recipes.store'), [
        'publish' => false,
        'title' => '書きかけの肉じゃが',
        'recipe_image' => null,
        'cooking_time' => '',
        'servings' => '',
        'ingredients' => [['name' => '', 'quantity' => '']],
        'steps' => [['description' => '']],
    ]);

    $recipe = Recipe::sole();
    $response->assertSessionHasNoErrors()->assertRedirect(route('recipes.edit', $recipe));
    expect($recipe->published_at)->toBeNull()
        ->and($recipe->recipe_image_path)->toBeNull()
        ->and($recipe->cooking_time)->toBeNull()
        ->and($recipe->servings)->toBeNull()
        // 全項目が空の行は捨てられる
        ->and($recipe->ingredients)->toHaveCount(0)
        ->and($recipe->steps)->toHaveCount(0);
});

test('下書きでは途中まで入力した行を、空欄を空文字にして保存する', function () {
    $this->actingAs($this->owner)->post(route('recipes.store'), [
        'publish' => false,
        'title' => '書きかけ',
        'ingredients' => [
            ['name' => 'じゃがいも', 'quantity' => ''],
            ['name' => '', 'quantity' => ''],
        ],
        'steps' => [
            ['description' => ''],
            ['description' => '煮込む'],
        ],
    ])->assertSessionHasNoErrors();

    $recipe = Recipe::sole();
    expect($recipe->ingredients->map->only('name', 'quantity')->all())->toBe([['name' => 'じゃがいも', 'quantity' => '']])
        ->and($recipe->steps->pluck('description')->all())->toBe(['煮込む'])
        ->and($recipe->steps->first()->order_number)->toBe(1);
});

test('下書きでも画像だけ付けた手順は残る', function () {
    $this->actingAs($this->owner)->post(route('recipes.store'), [
        'publish' => false,
        'title' => '書きかけ',
        'steps' => [
            ['description' => ''],
            ['description' => '', 'images' => [UploadedFile::fake()->create('step.jpg', 100, 'image/jpeg')]],
        ],
    ])->assertSessionHasNoErrors();

    $recipe = Recipe::sole();
    expect($recipe->steps)->toHaveCount(1)
        ->and($recipe->steps->first()->description)->toBe('')
        ->and($recipe->steps->first()->stepImages)->toHaveCount(1);
});

test('下書きでもタイトルは必須', function () {
    $this->actingAs($this->owner)->post(route('recipes.store'), [
        'publish' => false,
        'title' => '',
    ])->assertSessionHasErrors('title');

    expect(Recipe::count())->toBe(0);
});

test('下書きでも型と上限のチェックはかかる', function (array $input, string $errorKey) {
    $this->actingAs($this->owner)->post(route('recipes.store'), [
        'publish' => false,
        'title' => '書きかけ',
        ...$input,
    ])->assertSessionHasErrors($errorKey);
})->with([
    'タイトルが長すぎる' => [['title' => str_repeat('あ', 256)], 'title'],
    '完成写真が画像でない' => [['recipe_image' => UploadedFile::fake()->create('a.pdf', 10, 'application/pdf')], 'recipe_image'],
    '調理時間が上限超過' => [['cooking_time' => 1441], 'cooking_time'],
    '人数が数値でない' => [['servings' => 'たくさん'], 'servings'],
    '材料名が長すぎる' => [['ingredients' => [['name' => str_repeat('あ', 256), 'quantity' => '']]], 'ingredients.0.name'],
]);

// ---- 公開 ----

test('必須項目が欠けていると公開できず、何も保存されない', function () {
    $this->actingAs($this->owner)->post(route('recipes.store'), [
        'publish' => true,
        'title' => '肉じゃが',
    ])->assertSessionHasErrors(['recipe_image', 'cooking_time', 'servings', 'ingredients', 'steps']);

    expect(Recipe::count())->toBe(0);
});

test('下書きを公開すると published_at が入り、公開側の一覧に出る', function () {
    $draft = Recipe::factory()->for($this->owner)->draft()->create(['recipe_image_path' => null]);

    $this->actingAs($this->owner)->put(route('recipes.update', $draft), [
        'publish' => true,
        'title' => '肉じゃが',
        'recipe_image' => UploadedFile::fake()->create('recipe.jpg', 100, 'image/jpeg'),
        'cooking_time' => 30,
        'servings' => 2,
        'ingredients' => [['name' => 'じゃがいも', 'quantity' => '3個']],
        'steps' => [['description' => '煮込む']],
    ])->assertSessionHasNoErrors()->assertRedirect(route('recipes.show', $draft));

    expect($draft->fresh()->published_at)->not->toBeNull();

    $this->get(route('recipes.index'))->assertInertia(fn ($page) => $page
        ->component('recipes/Index')
        ->has('recipes.data', 1)
        ->where('recipes.data.0.id', $draft->id)
    );
});

test('完成写真を保存済みの下書きは、送り直さなくても公開できる', function () {
    $draft = Recipe::factory()->for($this->owner)->draft()->create();

    $this->actingAs($this->owner)->put(route('recipes.update', $draft), [
        'publish' => true,
        'title' => '肉じゃが',
        'cooking_time' => 30,
        'servings' => 2,
        'ingredients' => [['name' => 'じゃがいも', 'quantity' => '3個']],
        'steps' => [['description' => '煮込む']],
    ])->assertSessionHasNoErrors();

    expect($draft->fresh()->published_at)->not->toBeNull();
});

test('完成写真のない下書きは、写真を付けないと公開できない', function () {
    $draft = Recipe::factory()->for($this->owner)->draft()->create(['recipe_image_path' => null]);

    $this->actingAs($this->owner)->put(route('recipes.update', $draft), [
        'publish' => true,
        'title' => '肉じゃが',
        'cooking_time' => 30,
        'servings' => 2,
        'ingredients' => [['name' => 'じゃがいも', 'quantity' => '3個']],
        'steps' => [['description' => '煮込む']],
    ])->assertSessionHasErrors('recipe_image');

    expect($draft->fresh()->published_at)->toBeNull();
});

test('下書きを下書きのまま更新すると、未公開のまま編集画面に戻る', function () {
    $draft = Recipe::factory()->for($this->owner)->draft()->create();

    $this->actingAs($this->owner)->put(route('recipes.update', $draft), [
        'publish' => false,
        'title' => 'タイトルだけ変更',
    ])->assertSessionHasNoErrors()->assertRedirect(route('recipes.edit', $draft));

    expect($draft->fresh())
        ->title->toBe('タイトルだけ変更')
        ->published_at->toBeNull();
});

test('公開済みのレシピは publish=false を送っても下書きに戻らず、必須チェックがかかる', function () {
    $recipe = Recipe::factory()->for($this->owner)->create();
    $publishedAt = $recipe->published_at;

    $this->actingAs($this->owner)->put(route('recipes.update', $recipe), [
        'publish' => false,
        'title' => 'タイトルだけ',
    ])->assertSessionHasErrors(['cooking_time', 'servings', 'ingredients', 'steps']);

    $this->actingAs($this->owner)->put(route('recipes.update', $recipe), [
        'publish' => false,
        'title' => '肉じゃが',
        'cooking_time' => 30,
        'servings' => 2,
        'ingredients' => [['name' => 'じゃがいも', 'quantity' => '3個']],
        'steps' => [['description' => '煮込む']],
    ])->assertSessionHasNoErrors()->assertRedirect(route('recipes.show', $recipe));

    expect($recipe->fresh()->published_at?->equalTo($publishedAt))->toBeTrue();
});

// ---- 削除 ----

test('下書きを削除すると下書き一覧に戻る', function () {
    $draft = Recipe::factory()->for($this->owner)->draft()->create();

    $this->actingAs($this->owner)->delete(route('recipes.destroy', $draft))
        ->assertRedirect(route('drafts.index'));

    expect(Recipe::find($draft->id))->toBeNull();
});

test('公開済みのレシピを削除するとレシピ一覧に戻る', function () {
    $recipe = Recipe::factory()->for($this->owner)->create();

    $this->actingAs($this->owner)->delete(route('recipes.destroy', $recipe))
        ->assertRedirect(route('recipes.index'));
});

// ---- /drafts ----

test('下書き一覧には本人の下書きだけが、更新日時の新しい順に出る', function () {
    $older = Recipe::factory()->for($this->owner)->draft()->create(['updated_at' => now()->subDay()]);
    $newer = Recipe::factory()->for($this->owner)->draft()->create(['updated_at' => now()]);
    Recipe::factory()->for($this->owner)->create();
    Recipe::factory()->draft()->create();

    $this->actingAs($this->owner)->get(route('drafts.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('drafts/Index')
            ->has('drafts.data', 2)
            ->where('drafts.data.0.id', $newer->id)
            ->where('drafts.data.0.is_published', false)
            ->where('drafts.data.1.id', $older->id)
            ->has('drafts.meta.links')
        );
});

test('下書き一覧は未ログインだとログイン画面へリダイレクトされる', function () {
    $this->get(route('drafts.index'))->assertRedirect(route('login'));
});

test('下書きの詳細では is_published が false で渡る', function () {
    $draft = Recipe::factory()->for($this->owner)->draft()->create(['recipe_image_path' => null]);

    $this->actingAs($this->owner)->get(route('recipes.show', $draft))
        ->assertInertia(fn ($page) => $page
            ->component('recipes/Show')
            ->where('recipe.data.is_published', false)
            ->where('recipe.data.recipe_image_url', null)
        );
});
