<?php

use App\Models\Recipe;
use App\Models\StepImage;
use App\Models\User;
use Database\Seeders\RecipeSeeder;
use Illuminate\Support\Facades\Storage;

test('RecipeSeeder はすべてのレシピと手順画像に storage 上の画像を設定する', function () {
    Storage::fake('public');
    User::factory()->count(3)->create();

    $this->seed(RecipeSeeder::class);

    expect(Recipe::count())->toBeGreaterThan(0);

    Recipe::all()->each(fn (Recipe $recipe) => Storage::disk('public')->assertExists($recipe->recipe_image_path));
    StepImage::all()->each(fn (StepImage $image) => Storage::disk('public')->assertExists($image->step_image_path));
});
