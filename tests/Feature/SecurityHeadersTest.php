<?php

use App\Models\Recipe;
use App\Models\User;
use Illuminate\Testing\TestResponse;

function assertSecurityHeaders(TestResponse $response): void
{
    $response
        ->assertHeader('X-Frame-Options', 'DENY')
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
        ->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
}

test('ゲストのトップページにセキュリティヘッダーが付く', function () {
    $response = $this->get(route('home'));

    $response->assertOk();
    assertSecurityHeaders($response);
});

test('ログイン中のレシピ詳細にセキュリティヘッダーが付く', function () {
    $user = User::factory()->create();
    $recipe = Recipe::factory()->create();

    $response = $this->actingAs($user)->get(route('recipes.show', $recipe));

    $response->assertOk();
    assertSecurityHeaders($response);
});

test('存在しない URL の 404 にもセキュリティヘッダーが付く', function () {
    $response = $this->get('/no-such-page');

    $response->assertNotFound();
    assertSecurityHeaders($response);
});
