<?php

use App\Models\Favorite;
use App\Models\Recipe;
use App\Models\User;

test('ゲストでも公開済みレシピの詳細へ遷移する', function () {
    $recipe = Recipe::factory()->create();

    $this->get(route('recipes.random'))
        ->assertRedirect(route('recipes.show', $recipe));
});

test('下書きは候補に含めない', function () {
    Recipe::factory()->draft()->create();
    $published = Recipe::factory()->create();

    foreach (range(1, 5) as $_) {
        $this->get(route('recipes.random'))->assertRedirect(route('recipes.show', $published));
    }
});

test('調理時間の上限を超えるレシピと調理時間未入力のレシピは候補に含めない', function () {
    Recipe::factory()->create(['cooking_time' => 31]);
    Recipe::factory()->create(['cooking_time' => null]);
    $quick = Recipe::factory()->create(['cooking_time' => 30]);

    foreach (range(1, 5) as $_) {
        $this->get(route('recipes.random', ['time' => 30]))->assertRedirect(route('recipes.show', $quick));
    }
});

test('お気に入りから選ぶと自分のお気に入りだけが候補になる', function () {
    $user = User::factory()->create();
    Recipe::factory()->count(3)->create();
    $favorite = Recipe::factory()->create();
    Favorite::factory()->for($user)->for($favorite)->create();
    // 他人のお気に入りは対象外
    Favorite::factory()->for(User::factory())->for(Recipe::factory())->create();

    foreach (range(1, 5) as $_) {
        $this->actingAs($user)
            ->get(route('recipes.random', ['favorites' => 1]))
            ->assertRedirect(route('recipes.show', $favorite));
    }
});

test('お気に入りの下書き化されたレシピは候補に含めない', function () {
    $user = User::factory()->create();
    $draft = Recipe::factory()->draft()->create();
    Favorite::factory()->for($user)->for($draft)->create();

    $this->actingAs($user)
        ->get(route('recipes.random', ['favorites' => 1]))
        ->assertRedirect(route('home'))
        ->assertSessionHas('info');
});

test('ゲストが favorites を指定しても無視して全体から選ぶ', function () {
    $recipe = Recipe::factory()->create();

    $this->get(route('recipes.random', ['favorites' => 1]))
        ->assertRedirect(route('recipes.show', $recipe));
});

test('該当なしならホームへ戻りメッセージを表示する', function () {
    Recipe::factory()->create(['cooking_time' => 60]);

    $this->followingRedirects()
        ->get(route('recipes.random', ['time' => 15]))
        ->assertInertia(fn ($page) => $page
            ->component('Home')
            ->where('flash.info', '条件に合うレシピが見つかりませんでした。条件を変えてみてください。')
        );
});

test('不正な調理時間はバリデーションエラーになる', function (mixed $time) {
    Recipe::factory()->create();

    $this->from(route('home'))
        ->get(route('recipes.random', ['time' => $time]))
        ->assertRedirect(route('home'))
        ->assertSessionHasErrors('time');
})->with([10, 'abc', -1]);
