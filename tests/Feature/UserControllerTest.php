<?php

use App\Models\Follow;
use App\Models\Recipe;
use App\Models\User;

test('プロフィールにそのユーザーの情報が渡される', function () {
    $user = User::factory()->create(['handle' => 'chef-taro']);

    $this->get(route('users.show', $user))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('users/Show')
            ->where('user.data.handle', 'chef-taro')
            ->where('user.data.id', $user->id)
        );
});

test('プロフィールにはそのユーザーのレシピだけが並ぶ', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    $mine = Recipe::factory()->for($user)->create();
    Recipe::factory()->for($other)->create();

    $this->get(route('users.show', $user))
        ->assertInertia(fn ($page) => $page
            ->has('recipes.data', 1)
            ->where('recipes.data.0.id', $mine->id)
        );
});

test('レシピ数・フォロー中・フォロワーの件数が渡される', function () {
    $user = User::factory()->create();
    $followers = User::factory()->count(2)->create();
    $followings = User::factory()->count(3)->create();

    Recipe::factory()->count(4)->for($user)->create();

    foreach ($followers as $follower) {
        Follow::factory()->create(['follower_id' => $follower->id, 'followed_id' => $user->id]);
    }

    foreach ($followings as $following) {
        Follow::factory()->create(['follower_id' => $user->id, 'followed_id' => $following->id]);
    }

    $this->get(route('users.show', $user))
        ->assertInertia(fn ($page) => $page
            ->where('user.data.recipes_count', 4)
            ->where('user.data.followers_count', 2)
            ->where('user.data.followings_count', 3)
        );
});

test('フォロー済みなら isFollowing が true になる', function () {
    $user = User::factory()->create();
    $target = User::factory()->create();
    Follow::factory()->create(['follower_id' => $user->id, 'followed_id' => $target->id]);

    $this->actingAs($user)
        ->get(route('users.show', $target))
        ->assertInertia(fn ($page) => $page->where('isFollowing', true));
});

test('未フォローなら isFollowing が false になる', function () {
    $user = User::factory()->create();
    $target = User::factory()->create();

    $this->actingAs($user)
        ->get(route('users.show', $target))
        ->assertInertia(fn ($page) => $page->where('isFollowing', false));
});

test('ゲストでもプロフィールを閲覧できる', function () {
    $user = User::factory()->create();

    $this->get(route('users.show', $user))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('isFollowing', false));
});

test('存在しない handle は404になる', function () {
    $this->get('/users/no-such-user')->assertNotFound();
});

test('退会済みユーザーのプロフィールは404になる', function () {
    $user = User::factory()->create();
    $user->delete();

    $this->get(route('users.show', $user))->assertNotFound();
});
