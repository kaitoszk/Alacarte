<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * クリックジャッキング・MIME スニッフィング・リファラ漏えいを防ぐヘッダーを付与する。
     * CSP は Vite 開発サーバーと両立させないため本番のみ有効にする。
     */
    public function handle(Request $request, Closure $next): Response
    {
        // ビューの描画より前に nonce を生成しておく（@vite / @routes の <script> に付与される）
        $nonce = app()->isProduction() ? Vite::useCspNonce() : null;

        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        if ($nonce !== null) {
            $response->headers->set('Content-Security-Policy', $this->contentSecurityPolicy($nonce));
        }

        return $response;
    }

    private function contentSecurityPolicy(string $nonce): string
    {
        return implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'nonce-{$nonce}'",
            // Inertia のプログレスバーが nonce なしで <style> を差し込むため unsafe-inline を許可する
            "style-src 'self' 'unsafe-inline' https://fonts.bunny.net",
            "font-src 'self' https://fonts.bunny.net",
            // 投稿フォームの画像プレビューが blob: URL を使う
            "img-src 'self' data: blob:",
            "connect-src 'self'",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'none'",
        ]);
    }
}
