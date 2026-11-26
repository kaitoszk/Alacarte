<?php

use App\Models\Recipe;
use App\Models\User;

test('post_max_size を超える投稿は元の画面に戻り、日本語のエラーになる', function () {
    $user = User::factory()->create();
    $tooLarge = ini_parse_quantity(ini_get('post_max_size')) + 1;

    $response = $this->actingAs($user)
        ->from(route('recipes.create'))
        ->call('POST', route('recipes.store'), server: ['CONTENT_LENGTH' => $tooLarge]);

    $response->assertRedirect(route('recipes.create'))
        ->assertSessionHasErrors(['upload' => '送信データが大きすぎます。画像の枚数やサイズを減らしてください。']);
    expect(Recipe::count())->toBe(0);
});

test('post_max_size を超える更新も元の画面に戻り、日本語のエラーになる', function () {
    $owner = User::factory()->create();
    $recipe = Recipe::factory()->for($owner)->create(['title' => '元のタイトル']);
    $tooLarge = ini_parse_quantity(ini_get('post_max_size')) + 1;

    $response = $this->actingAs($owner)
        ->from(route('recipes.edit', $recipe))
        // 超過時は PHP が本文を捨てるので _method は届かない。フォームが付けるヘッダーで PUT になる
        ->call('POST', route('recipes.update', $recipe), server: [
            'CONTENT_LENGTH' => $tooLarge,
            'HTTP_X_HTTP_METHOD_OVERRIDE' => 'PUT',
        ]);

    $response->assertRedirect(route('recipes.edit', $recipe))
        ->assertSessionHasErrors('upload');
    expect($recipe->fresh()->title)->toBe('元のタイトル');
});
