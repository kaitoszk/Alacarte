<?php

use App\Models\User;

test('日本語ロケールではログイン失敗とバリデーションのメッセージが日本語になる', function () {
    config(['app.locale' => 'ja']);
    app()->setLocale('ja');
    $user = User::factory()->create();

    $this->post(route('login'), ['email' => $user->email, 'password' => 'wrong-password'])
        ->assertSessionHasErrors(['email' => 'メールアドレスまたはパスワードが正しくありません。']);

    $this->post(route('login'), ['email' => '', 'password' => ''])
        ->assertSessionHasErrors(['email' => 'メールアドレスを入力してください。']);
});
