<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // 新しい内容が公開される投稿系（レシピ・コメントの作成）はスパム対策で厳しめにする
        RateLimiter::for('posts', function (Request $request) {
            return Limit::perMinute(10)->by($request->user()?->id ?: $request->ip());
        });

        // それ以外の書き込み（更新・削除・お気に入り・フォロー等）は連打対策
        RateLimiter::for('writes', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // 現在のパスワードを確認する操作（パスワード変更・退会）は総当たり対策で厳しくする
        RateLimiter::for('password-confirm', function (Request $request) {
            return Limit::perMinute(6)->by($request->user()?->id ?: $request->ip());
        });
    }
}
