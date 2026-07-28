<?php

namespace App\Http\Controllers;

use App\Http\Resources\RecipeResource;
use App\Http\Resources\UserProfileResource;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    private const RECIPES_PER_PAGE = 12;

    /**
     * ユーザーのプロフィール。自分・他人どちらも同じ画面で扱う。
     * 公開情報なのでゲストでも閲覧できる。
     */
    public function show(Request $request, User $user): Response
    {
        $user->loadCount(['recipes', 'followers', 'followings']);

        $recipes = $user->recipes()
            ->with('user')
            ->withCount(['favorites', 'comments'])
            ->withFavoritedBy($request->user())
            ->latest()
            ->paginate(self::RECIPES_PER_PAGE);

        return Inertia::render('users/Show', [
            'user' => new UserProfileResource($user),
            'recipes' => RecipeResource::collection($recipes),
            'isFollowing' => (bool) $request->user()?->followings()->whereKey($user->id)->exists(),
        ]);
    }
}
