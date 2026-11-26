<?php

use App\Models\MealPlan;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Support\Carbon;

beforeEach(function () {
    // UTC 2026-11-25 20:00 = 日本時間 2026-11-26（木）05:00。UTC のままだと「今日」が前日になる時間帯に固定する
    $this->travelTo(Carbon::parse('2026-11-25 20:00:00', 'UTC'));
});

test('献立ページは日本時間の今週（月曜〜日曜）の7日分を、自分の献立だけ返す', function () {
    $user = User::factory()->create();
    $first = Recipe::factory()->create();
    $second = Recipe::factory()->create();
    MealPlan::factory()->for($user)->for($first)->create(['date' => '2026-11-26']);
    MealPlan::factory()->for($user)->for($second)->create(['date' => '2026-11-26']);
    MealPlan::factory()->for($user)->create(['date' => '2026-11-30']); // 翌週
    MealPlan::factory()->create(['date' => '2026-11-26']); // 他人

    $this->actingAs($user)
        ->get(route('meal-plans.index'))
        ->assertInertia(fn ($page) => $page
            ->component('meal-plans/Index')
            ->where('today', '2026-11-26')
            ->where('prevWeek', '2026-11-16')
            ->where('nextWeek', '2026-11-30')
            ->has('days', 7)
            ->where('days.0.date', '2026-11-23')
            ->where('days.6.date', '2026-11-29')
            ->has('days.0.meal_plans', 0)
            ->has('days.3.meal_plans', 2)
            ->where('days.3.meal_plans.0.recipe.id', $first->id)
            ->where('days.3.meal_plans.1.recipe.id', $second->id)
            ->where('days.3.meal_plans.0.recipe.title', $first->title)
        );
});

test('week を指定するとその週を表示し、月曜以外はその週の月曜に丸める', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('meal-plans.index', ['week' => '2026-12-09']))
        ->assertInertia(fn ($page) => $page->where('days.0.date', '2026-12-07'));
});

test('不正な week は今週として扱う', function (string $week) {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('meal-plans.index', ['week' => $week]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('days.0.date', '2026-11-23'));
})->with(['abc', '2026-02-31', '2026/12/01']);

test('元レシピが削除された献立は表示しない', function () {
    $user = User::factory()->create();
    $recipe = Recipe::factory()->create();
    MealPlan::factory()->for($user)->for($recipe)->create(['date' => '2026-11-26']);
    $recipe->delete();

    $this->actingAs($user)
        ->get(route('meal-plans.index'))
        ->assertInertia(fn ($page) => $page->has('days.3.meal_plans', 0));
});

test('他人の公開レシピを今日〜13日後の日付に追加できる', function (string $date) {
    $user = User::factory()->create();
    $recipe = Recipe::factory()->create();

    $this->actingAs($user)
        ->post(route('meal-plans.store', $recipe), ['date' => $date])
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    expect(MealPlan::where('user_id', $user->id)->where('recipe_id', $recipe->id)->whereDate('date', $date)->exists())->toBeTrue();
})->with(['今日（日本時間）' => '2026-11-26', '13日後' => '2026-12-09']);

test('範囲外の日付には追加できない', function (string $date) {
    $user = User::factory()->create();
    $recipe = Recipe::factory()->create();

    $this->actingAs($user)
        ->post(route('meal-plans.store', $recipe), ['date' => $date])
        ->assertSessionHasErrors('date');

    expect(MealPlan::count())->toBe(0);
})->with([
    'UTC では今日だが日本時間では昨日' => '2026-11-25',
    '14日後' => '2026-12-10',
    '形式違い' => '2026/11/27',
    '空' => '',
]);

test('同じ日に同じレシピは二重登録できない', function () {
    $user = User::factory()->create();
    $recipe = Recipe::factory()->create();
    MealPlan::factory()->for($user)->for($recipe)->create(['date' => '2026-11-27']);

    $this->actingAs($user)
        ->post(route('meal-plans.store', $recipe), ['date' => '2026-11-27'])
        ->assertSessionHasErrors(['date' => 'その日にはすでに追加されています。']);

    // 別の日、または他人が同じ日に入れるのは問題ない
    $this->actingAs($user)->post(route('meal-plans.store', $recipe), ['date' => '2026-11-28'])->assertSessionHasNoErrors();
    $this->actingAs(User::factory()->create())->post(route('meal-plans.store', $recipe), ['date' => '2026-11-27'])->assertSessionHasNoErrors();

    expect(MealPlan::count())->toBe(3);
});

test('下書きのレシピは本人でも献立に追加できない', function () {
    $user = User::factory()->create();
    $draft = Recipe::factory()->draft()->for($user)->create();

    $this->actingAs($user)
        ->post(route('meal-plans.store', $draft), ['date' => '2026-11-27'])
        ->assertNotFound();

    expect(MealPlan::count())->toBe(0);
});

test('自分の献立から外せる', function () {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create();

    $this->actingAs($user)
        ->delete(route('meal-plans.destroy', $mealPlan))
        ->assertRedirect();

    expect(MealPlan::count())->toBe(0);
});

test('他人の献立は外せない', function () {
    $mealPlan = MealPlan::factory()->create();

    $this->actingAs(User::factory()->create())
        ->delete(route('meal-plans.destroy', $mealPlan))
        ->assertForbidden();

    expect(MealPlan::count())->toBe(1);
});

test('未ログインは献立の閲覧・追加・外すができない', function () {
    $recipe = Recipe::factory()->create();
    $mealPlan = MealPlan::factory()->create();

    $this->get(route('meal-plans.index'))->assertRedirect(route('login'));
    $this->post(route('meal-plans.store', $recipe), ['date' => '2026-11-27'])->assertRedirect(route('login'));
    $this->delete(route('meal-plans.destroy', $mealPlan))->assertRedirect(route('login'));

    expect(MealPlan::count())->toBe(1);
});

test('レシピ詳細には献立に追加できる日付（日本時間の今日〜13日後）を渡し、ゲストには渡さない', function () {
    $recipe = Recipe::factory()->create();

    $this->actingAs(User::factory()->create())
        ->get(route('recipes.show', $recipe))
        ->assertInertia(fn ($page) => $page
            ->has('mealPlanDates', 14)
            ->where('mealPlanDates.0', '2026-11-26')
            ->where('mealPlanDates.13', '2026-12-09')
        );

    auth()->logout();

    $this->get(route('recipes.show', $recipe))
        ->assertInertia(fn ($page) => $page->has('mealPlanDates', 0));
});
