<?php

use App\Models\Recipe;
use App\Models\User;
use Illuminate\Support\Facades\Route;

test('投稿系は1分に10回までで、11回目は429になる', function () {
    $user = User::factory()->create();
    $recipe = Recipe::factory()->create();

    for ($i = 0; $i < 10; $i++) {
        $this->actingAs($user)
            ->post(route('comments.store', $recipe), ['description' => "コメント{$i}"])
            ->assertRedirect();
    }

    $this->actingAs($user)
        ->post(route('comments.store', $recipe), ['description' => '11回目'])
        ->assertStatus(429);
});

test('その他の書き込みは1分に60回までで、61回目は429になる', function () {
    $user = User::factory()->create();
    $recipe = Recipe::factory()->create();

    for ($i = 0; $i < 60; $i++) {
        $method = $i % 2 === 0 ? 'post' : 'delete';
        $this->actingAs($user)
            ->{$method}(route($i % 2 === 0 ? 'favorites.store' : 'favorites.destroy', $recipe))
            ->assertRedirect();
    }

    $this->actingAs($user)
        ->post(route('favorites.store', $recipe))
        ->assertStatus(429);
});

test('レート制限はユーザーごとに数える', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();
    $recipe = Recipe::factory()->create();

    for ($i = 0; $i < 10; $i++) {
        $this->actingAs($userA)
            ->post(route('comments.store', $recipe), ['description' => "コメント{$i}"]);
    }

    $this->actingAs($userA)
        ->post(route('comments.store', $recipe), ['description' => '上限超え'])
        ->assertStatus(429);

    $this->actingAs($userB)
        ->post(route('comments.store', $recipe), ['description' => '別ユーザー'])
        ->assertRedirect();
});

test('書き込み系ルートにはすべてレート制限が付いている', function (string $name, string $limiter) {
    $middleware = Route::getRoutes()->getByName($name)->gatherMiddleware();

    expect($middleware)->toContain("throttle:{$limiter}");
})->with([
    ['recipes.store', 'posts'],
    ['comments.store', 'posts'],
    ['recipes.update', 'writes'],
    ['recipes.destroy', 'writes'],
    ['favorites.store', 'writes'],
    ['favorites.destroy', 'writes'],
    ['carts.store', 'writes'],
    ['carts.update', 'writes'],
    ['carts.destroy', 'writes'],
    ['carts.destroyChecked', 'writes'],
    ['follows.store', 'writes'],
    ['follows.destroy', 'writes'],
    ['comments.destroy', 'writes'],
    ['comment-goods.store', 'writes'],
    ['comment-goods.destroy', 'writes'],
]);
