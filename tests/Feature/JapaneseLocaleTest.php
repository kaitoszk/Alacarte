<?php

use App\Models\User;
use Illuminate\Http\UploadedFile;

test('日本語ロケールではログイン失敗とバリデーションのメッセージが日本語になる', function () {
    config(['app.locale' => 'ja']);
    app()->setLocale('ja');
    $user = User::factory()->create();

    $this->post(route('login'), ['email' => $user->email, 'password' => 'wrong-password'])
        ->assertSessionHasErrors(['email' => 'メールアドレスまたはパスワードが正しくありません。']);

    $this->post(route('login'), ['email' => '', 'password' => ''])
        ->assertSessionHasErrors(['email' => 'メールアドレスを入力してください。']);
});

test('日本語ロケールでは PHP の段階で失敗したアップロードのメッセージが日本語になる', function () {
    config(['app.locale' => 'ja']);
    app()->setLocale('ja');
    $user = User::factory()->create();

    // upload_max_filesize を超えたファイルは PHP が UPLOAD_ERR_INI_SIZE にする
    $failedUpload = new UploadedFile(
        UploadedFile::fake()->create('recipe.jpg', 100, 'image/jpeg')->getPathname(),
        'recipe.jpg',
        'image/jpeg',
        UPLOAD_ERR_INI_SIZE,
        true,
    );

    $this->actingAs($user)->post(route('recipes.store'), ['recipe_image' => $failedUpload])
        ->assertSessionHasErrors(['recipe_image' => 'レシピ画像のアップロードに失敗しました。ファイルサイズが大きすぎる可能性があります。']);
});
