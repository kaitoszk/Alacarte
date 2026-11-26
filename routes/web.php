<?php

use App\Http\Controllers\CartController;
use App\Http\Controllers\CommentGoodController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\FollowController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\RecipeCommentController;
use App\Http\Controllers\RecipeController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

// 認証系のリダイレクト先として route('dashboard') が各所から参照されているため、
// 名前は残したまま自分のプロフィール（マイページ）へ送るだけにする。
Route::get('dashboard', function (Request $request) {
    return to_route('users.show', $request->user());
})->middleware(['auth', 'verified'])->name('dashboard');

// recipes/create が recipes/{recipe} に飲まれないよう、認証ルートを先に登録する
Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('recipes', RecipeController::class)->except(['index', 'show'])
        ->middlewareFor('store', 'throttle:posts')
        ->middlewareFor(['update', 'destroy'], 'throttle:writes');

    Route::get('favorites', [FavoriteController::class, 'index'])->name('favorites.index');
    Route::post('recipes/{recipe}/favorite', [FavoriteController::class, 'store'])->middleware('throttle:writes')->name('favorites.store');
    Route::delete('recipes/{recipe}/favorite', [FavoriteController::class, 'destroy'])->middleware('throttle:writes')->name('favorites.destroy');

    Route::get('shopping-list', [CartController::class, 'index'])->name('carts.index');
    // checked が {cart} に解決されないよう、{cart} 系より先に登録する
    Route::delete('shopping-list/checked', [CartController::class, 'destroyChecked'])->middleware('throttle:writes')->name('carts.destroyChecked');
    Route::patch('shopping-list/{cart}', [CartController::class, 'update'])->middleware('throttle:writes')->name('carts.update');
    Route::delete('shopping-list/{cart}', [CartController::class, 'destroy'])->middleware('throttle:writes')->name('carts.destroy');
    Route::post('recipes/{recipe}/cart', [CartController::class, 'store'])->middleware('throttle:writes')->name('carts.store');

    Route::post('users/{user}/follow', [FollowController::class, 'store'])->middleware('throttle:writes')->name('follows.store');
    Route::delete('users/{user}/follow', [FollowController::class, 'destroy'])->middleware('throttle:writes')->name('follows.destroy');

    Route::post('recipes/{recipe}/comments', [RecipeCommentController::class, 'store'])->middleware('throttle:posts')->name('comments.store');
    Route::delete('comments/{comment}', [RecipeCommentController::class, 'destroy'])->middleware('throttle:writes')->name('comments.destroy');

    Route::post('comments/{comment}/good', [CommentGoodController::class, 'store'])->middleware('throttle:writes')->name('comment-goods.store');
    Route::delete('comments/{comment}/good', [CommentGoodController::class, 'destroy'])->middleware('throttle:writes')->name('comment-goods.destroy');
});

Route::resource('recipes', RecipeController::class)->only(['index', 'show']);

Route::get('users/{user}', [UserController::class, 'show'])->name('users.show');

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
