<?php

use App\Models\MealPlan;
use App\Models\User;

test('本人は献立から外せる', function () {
    $owner = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($owner)->create();

    expect($owner->can('delete', $mealPlan))->toBeTrue();
});

test('本人以外は献立から外せない', function () {
    $mealPlan = MealPlan::factory()->create();

    expect(User::factory()->create()->can('delete', $mealPlan))->toBeFalse();
});
