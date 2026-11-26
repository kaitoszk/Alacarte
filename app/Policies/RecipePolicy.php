<?php

namespace App\Policies;

use App\Models\Recipe;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class RecipePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(?User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     * 下書きは投稿者本人だけが見られる。他人には存在自体を知らせないよう 404 にする。
     */
    public function view(?User $user, Recipe $recipe): Response
    {
        return $recipe->isPublished() || $user?->id === $recipe->user_id
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    /**
     * お気に入り・買い物リスト追加・コメントができるか。公開前のレシピには本人も含めて付けられない。
     */
    public function interact(User $user, Recipe $recipe): Response
    {
        return $recipe->isPublished() ? Response::allow() : Response::denyAsNotFound();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Recipe $recipe): bool
    {
        return $user->id === $recipe->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Recipe $recipe): bool
    {
        return $user->id === $recipe->user_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Recipe $recipe): bool
    {
        return $user->id === $recipe->user_id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Recipe $recipe): bool
    {
        return $user->id === $recipe->user_id;
    }
}
