<?php

use App\Models\User;
use Illuminate\Support\Facades\Storage;

test('ログイン時の auth.user には AuthUserResource で定義したキーだけが含まれる', function () {
    $user = User::factory()->create(['user_image_path' => 'users/avatar.jpg']);

    $response = $this->actingAs($user)->get(route('home'));

    // etc() を付けないので、ここに無いキー（password, created_at 等）が含まれると失敗する
    $response->assertInertia(fn ($page) => $page
        ->has('auth.user', fn ($authUser) => $authUser
            ->where('id', $user->id)
            ->where('name', $user->name)
            ->where('handle', $user->handle)
            ->where('email', $user->email)
            ->where('user_image_url', Storage::url('users/avatar.jpg'))
            ->has('email_verified_at')
        )
    );
});

test('未ログイン時の auth.user は null になる', function () {
    $response = $this->get(route('home'));

    $response->assertInertia(fn ($page) => $page->where('auth.user', null));
});
