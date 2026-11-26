<?php

use App\Models\Favorite;
use App\Models\Ingredient;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->owner = User::factory()->create();
    $this->published = Recipe::factory()->for($this->owner)->create();
    $this->draft = Recipe::factory()->for($this->owner)->draft()->create(['view_count' => 0]);
});

test('新規作成したレシピは公開済みになる', function () {
    Storage::fake('public');

    $this->actingAs($this->owner)->post(route('recipes.store'), [
        'publish' => true,
        'title' => '肉じゃが',
        'recipe_image' => UploadedFile::fake()->create('recipe.jpg', 100, 'image/jpeg'),
        'cooking_time' => 30,
        'servings' => 2,
        'ingredients' => [['name' => 'じゃがいも', 'quantity' => '3個']],
        'steps' => [['description' => '煮込む']],
    ])->assertRedirect();

    expect(Recipe::where('title', '肉じゃが')->first()->published_at)->not->toBeNull();
});

test('みんなのレシピに下書きは出ない', function () {
    $this->get(route('home'))->assertInertia(fn ($page) => $page
        ->has('recipes.data', 1)
        ->where('recipes.data.0.id', $this->published->id)
    );
});

test('フォロー中フィードに下書きは出ない', function () {
    $follower = User::factory()->create();
    $follower->followings()->attach($this->owner);

    $this->actingAs($follower)->get(route('home', ['tab' => 'following']))->assertInertia(fn ($page) => $page
        ->has('recipes.data', 1)
        ->where('recipes.data.0.id', $this->published->id)
    );
});

test('プロフィールの一覧と件数に下書きは含まれない（本人が見ても同じ）', function () {
    $this->actingAs($this->owner)->get(route('users.show', $this->owner))->assertInertia(fn ($page) => $page
        ->where('user.data.recipes_count', 1)
        ->has('recipes.data', 1)
        ->where('recipes.data.0.id', $this->published->id)
    );
});

test('レシピ一覧と検索に下書きは出ない', function () {
    Ingredient::factory()->for($this->draft)->create(['name' => '下書きだけの材料']);

    $this->get(route('recipes.index'))->assertInertia(fn ($page) => $page
        ->has('recipes.data', 1)
        ->where('recipes.data.0.id', $this->published->id)
    );
    $this->get(route('recipes.index', ['q' => '下書きだけの材料']))->assertInertia(fn ($page) => $page
        ->has('recipes.data', 0)
    );
});

test('お気に入り一覧に下書きは出ない', function () {
    $user = User::factory()->create();
    Favorite::factory()->for($user)->for($this->published)->create();
    Favorite::factory()->for($user)->for($this->draft)->create();

    $this->actingAs($user)->get(route('favorites.index'))->assertInertia(fn ($page) => $page
        ->has('recipes.data', 1)
        ->where('recipes.data.0.id', $this->published->id)
    );
});

test('下書きの詳細は本人だけが見られ、閲覧数は増えない', function () {
    $this->actingAs($this->owner)->get(route('recipes.show', $this->draft))->assertInertia(fn ($page) => $page
        ->component('recipes/Show')
        ->where('recipe.data.id', $this->draft->id)
    );

    expect($this->draft->fresh()->view_count)->toBe(0);
});

test('下書きの詳細は他人と未ログインには 404 になる', function () {
    $this->get(route('recipes.show', $this->draft))->assertNotFound();
    $this->actingAs(User::factory()->create())->get(route('recipes.show', $this->draft))->assertNotFound();
});

test('下書きにはお気に入り・買い物リスト追加・コメントができない（本人も 404）', function () {
    $this->actingAs($this->owner);

    $this->post(route('favorites.store', $this->draft))->assertNotFound();
    $this->post(route('carts.store', $this->draft))->assertNotFound();
    $this->post(route('comments.store', $this->draft), ['description' => 'テスト'])->assertNotFound();

    expect($this->draft->favorites()->count())->toBe(0)
        ->and($this->owner->carts()->count())->toBe(0)
        ->and($this->draft->comments()->count())->toBe(0);
});

test('フォロー中フィードとプロフィールは公開日時の新しい順に並ぶ', function () {
    // 作成は古いが、あとから公開したレシピが先頭に来る
    $latePublished = Recipe::factory()->for($this->owner)->create([
        'created_at' => now()->subDays(10),
        'published_at' => now()->addMinute(),
    ]);
    $follower = User::factory()->create();
    $follower->followings()->attach($this->owner);

    $this->actingAs($follower)->get(route('home', ['tab' => 'following']))
        ->assertInertia(fn ($page) => $page->where('recipes.data.0.id', $latePublished->id));
    $this->get(route('users.show', $this->owner))
        ->assertInertia(fn ($page) => $page->where('recipes.data.0.id', $latePublished->id));
});
