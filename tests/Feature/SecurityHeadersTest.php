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

test('本番環境では nonce 付きの CSP が付き、HTML の script にも同じ nonce が付く', function () {
    $this->app->detectEnvironment(fn () => 'production');

    $response = $this->get(route('home'));

    $response->assertOk();
    assertSecurityHeaders($response);

    $csp = $response->headers->get('Content-Security-Policy');
    expect($csp)
        ->toContain("default-src 'self'")
        ->toContain("object-src 'none'")
        ->toContain("frame-ancestors 'none'")
        ->toMatch("/script-src 'self' 'nonce-[^']+'/");

    preg_match("/'nonce-([^']+)'/", $csp, $matches);
    $nonce = $matches[1];

    // Ziggy（@routes）のインライン script に nonce が渡っていること
    expect($response->getContent())->toContain("<script type=\"text/javascript\" nonce=\"{$nonce}\">");
});

test('本番以外の環境では CSP を付けない', function () {
    $response = $this->get(route('home'));

    $response->assertOk();
    $response->assertHeaderMissing('Content-Security-Policy');
});
