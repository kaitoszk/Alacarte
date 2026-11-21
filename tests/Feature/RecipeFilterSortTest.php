<?php

use App\Models\Favorite;
use App\Models\Recipe;
use Illuminate\Support\Carbon;

test('time を指定するとその分数以内のレシピだけが表示される', function () {
    $quick = Recipe::factory()->create(['cooking_time' => 15]);
    Recipe::factory()->create(['cooking_time' => 16]);

    $this->get(route('recipes.index', ['time' => 15]))
        ->assertInertia(fn ($page) => $page
            ->has('recipes.data', 1)
            ->where('recipes.data.0.id', $quick->id)
            ->where('filters.time', 15)
        );
});

test('sort=popular はお気に入り数の多い順、同数なら新しい順に並ぶ', function () {
    $older = Recipe::factory()->create(['created_at' => Carbon::now()->subDays(2)]);
    $newer = Recipe::factory()->create(['created_at' => Carbon::now()->subDay()]);
    $popular = Recipe::factory()->create(['created_at' => Carbon::now()->subDays(3)]);
    Favorite::factory()->count(3)->for($popular)->create();
    Favorite::factory()->for($older)->create();
    Favorite::factory()->for($newer)->create();

    $this->get(route('recipes.index', ['sort' => 'popular']))
        ->assertInertia(fn ($page) => $page
            ->where('recipes.data.0.id', $popular->id)
            ->where('recipes.data.1.id', $newer->id)
            ->where('recipes.data.2.id', $older->id)
            ->where('filters.sort', 'popular')
        );
});

test('キーワード・調理時間・並び順は組み合わせられる', function () {
    $hit = Recipe::factory()->create(['title' => '鶏の照り焼き', 'cooking_time' => 20]);
    Recipe::factory()->create(['title' => '鶏の煮込み', 'cooking_time' => 90]);
    Recipe::factory()->create(['title' => '豚の生姜焼き', 'cooking_time' => 20]);

    $this->get(route('recipes.index', ['q' => '鶏', 'time' => 30, 'sort' => 'popular']))
        ->assertInertia(fn ($page) => $page
            ->has('recipes.data', 1)
            ->where('recipes.data.0.id', $hit->id)
            ->where('filters', ['q' => '鶏', 'time' => 30, 'sort' => 'popular'])
        );
});

test('条件を指定しないときの filters は既定値になる', function () {
    $this->get(route('recipes.index'))
        ->assertInertia(fn ($page) => $page->where('filters', ['q' => null, 'time' => null, 'sort' => 'new']));
});

test('不正な time / sort はバリデーションエラーになる', function (array $query, string $key) {
    $this->get(route('recipes.index', $query))->assertSessionHasErrors($key);
})->with([
    'time=10' => [['time' => 10], 'time'],
    'time=abc' => [['time' => 'abc'], 'time'],
    'sort=views' => [['sort' => 'views'], 'sort'],
]);

test('ページ送りのリンクに time と sort が残る', function () {
    Recipe::factory()->count(21)->create(['cooking_time' => 10]);

    $this->get(route('recipes.index', ['time' => 15, 'sort' => 'popular']))
        ->assertInertia(fn ($page) => $page
            ->where('recipes.meta.links.2.url', fn (string $url) => str_contains($url, 'time=15') && str_contains($url, 'sort=popular') && str_contains($url, 'page=2'))
        );
});
