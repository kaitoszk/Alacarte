<?php

use App\Models\Ingredient;
use App\Models\Recipe;

/**
 * @param  array<int, string>  $ingredientNames
 */
function searchableRecipe(string $title, array $ingredientNames = []): Recipe
{
    $recipe = Recipe::factory()->create(['title' => $title]);

    foreach ($ingredientNames as $index => $name) {
        Ingredient::factory()->for($recipe)->create(['order_number' => $index + 1, 'name' => $name]);
    }

    return $recipe;
}

test('タイトルに一致するレシピだけが表示される', function () {
    $hit = searchableRecipe('鶏の照り焼き');
    searchableRecipe('豚汁');

    $this->get(route('recipes.index', ['q' => '照り焼き']))
        ->assertInertia(fn ($page) => $page
            ->component('recipes/Index')
            ->has('recipes.data', 1)
            ->where('recipes.data.0.id', $hit->id)
            ->where('filters.q', '照り焼き')
        );
});

test('材料名に一致するレシピも表示される', function () {
    $hit = searchableRecipe('カレー', ['じゃがいも', 'にんじん']);
    searchableRecipe('サラダ', ['レタス']);

    $this->get(route('recipes.index', ['q' => 'にんじん']))
        ->assertInertia(fn ($page) => $page
            ->has('recipes.data', 1)
            ->where('recipes.data.0.id', $hit->id)
        );
});

test('空白で区切った複数の語はすべてを含むレシピに絞られる（全角空白も区切りになる）', function () {
    $both = searchableRecipe('肉じゃが', ['豚肉', '玉ねぎ']);
    searchableRecipe('生姜焼き', ['豚肉']);
    searchableRecipe('オニオンスープ', ['玉ねぎ']);

    $this->get(route('recipes.index', ['q' => '豚肉　玉ねぎ']))
        ->assertInertia(fn ($page) => $page
            ->has('recipes.data', 1)
            ->where('recipes.data.0.id', $both->id)
        );
});

test('q が空なら全件を表示する', function () {
    Recipe::factory()->count(3)->create();

    $this->get(route('recipes.index', ['q' => '']))
        ->assertInertia(fn ($page) => $page
            ->has('recipes.data', 3)
            ->where('filters.q', null)
        );
});

test('LIKE のワイルドカードはエスケープして渡す', function () {
    // テストの SQLite は LIKE で \ をエスケープとして扱わないため、組み立てたバインド値で確認する（本番の MySQL では \ がエスケープになる）
    expect(Recipe::query()->search('100%_\\')->getBindings())
        ->toContain('%100\\%\\_\\\\%');
});

test('q が50文字を超えるとバリデーションエラーになる', function () {
    $this->get(route('recipes.index', ['q' => str_repeat('あ', 51)]))
        ->assertSessionHasErrors('q');
});

test('ページ送りのリンクに検索語が残る', function () {
    foreach (range(1, 21) as $i) {
        searchableRecipe("鶏料理{$i}");
    }

    $this->get(route('recipes.index', ['q' => '鶏']))
        ->assertInertia(fn ($page) => $page
            ->has('recipes.data', 20)
            ->where('recipes.meta.links.2.url', fn (string $url) => str_contains($url, 'q=') && str_contains($url, 'page=2'))
        );
});
