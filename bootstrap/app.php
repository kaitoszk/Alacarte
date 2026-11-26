<?php

use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Middleware\ValidatePostSize;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // ルート未一致の 404 にも付けるため web グループではなくグローバルに登録する
        $middleware->append(SecurityHeaders::class);

        // post_max_size 超過をフォームのエラーとして返すには、エラーをセッションに積める必要がある。
        // そのためグローバルから外し、セッション開始後の web グループで検査する
        $middleware->remove(ValidatePostSize::class);

        $middleware->web(append: [
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
            ValidatePostSize::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(fn (PostTooLargeException $e) => back()->withErrors([
            'upload' => '送信データが大きすぎます。画像の枚数やサイズを減らしてください。',
        ]));
    })->create();
