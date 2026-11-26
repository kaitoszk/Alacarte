<?php

use App\Models\Recipe;
use App\Models\Step;
use App\Models\StepImage;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');

    $this->owner = User::factory()->create();
    $this->recipe = Recipe::factory()->for($this->owner)->create();
    $this->step = Step::factory()->for($this->recipe)->create(['order_number' => 1]);
    $this->images = StepImage::factory()->count(2)->for($this->step)->create();
});

/** 更新リクエストの共通部分。steps だけ差し替えて使う */
function updatePayload(array $steps): array
{
    return [
        'title' => '更新後のタイトル',
        'cooking_time' => 30,
        'servings' => 2,
        'ingredients' => [['name' => '玉ねぎ', 'quantity' => '1個']],
        'steps' => $steps,
    ];
}

test('残す画像の id を送ると、既存の手順画像はファイルごと残る', function () {
    $paths = $this->images->pluck('step_image_path')->all();

    $this->actingAs($this->owner)->put(route('recipes.update', $this->recipe), updatePayload([
        ['description' => '炒める', 'kept_image_ids' => $this->images->pluck('id')->all()],
    ]))->assertRedirect(route('recipes.show', $this->recipe));

    $step = $this->recipe->fresh()->steps()->with('stepImages')->first();
    expect($step->stepImages->pluck('step_image_path')->all())->toBe($paths);
    foreach ($paths as $path) {
        Storage::disk('public')->assertExists($path);
    }
});

test('送らなかった画像だけが DB とストレージから消える', function () {
    [$kept, $removed] = $this->images->all();

    $this->actingAs($this->owner)->put(route('recipes.update', $this->recipe), updatePayload([
        ['description' => '炒める', 'kept_image_ids' => [$kept->id]],
    ]))->assertRedirect();

    $step = $this->recipe->fresh()->steps()->with('stepImages')->first();
    expect($step->stepImages->pluck('step_image_path')->all())->toBe([$kept->step_image_path]);
    Storage::disk('public')->assertExists($kept->step_image_path);
    Storage::disk('public')->assertMissing($removed->step_image_path);
});

test('手順を並べ替えても、残した画像は送った手順に付く', function () {
    $this->actingAs($this->owner)->put(route('recipes.update', $this->recipe), updatePayload([
        ['description' => '新しい最初の手順'],
        ['description' => '炒める', 'kept_image_ids' => $this->images->pluck('id')->all()],
    ]))->assertRedirect();

    $steps = $this->recipe->fresh()->steps()->with('stepImages')->orderBy('order_number')->get();
    expect($steps[0]->stepImages)->toHaveCount(0)
        ->and($steps[1]->stepImages)->toHaveCount(2);
});

test('残す画像と新しい画像を同じ手順に付けられる', function () {
    $this->actingAs($this->owner)->put(route('recipes.update', $this->recipe), updatePayload([
        [
            'description' => '炒める',
            'kept_image_ids' => [$this->images[0]->id],
            'images' => [UploadedFile::fake()->create('new.jpg', 100, 'image/jpeg')],
        ],
    ]))->assertRedirect();

    $step = $this->recipe->fresh()->steps()->with('stepImages')->first();
    expect($step->stepImages)->toHaveCount(2)
        ->and($step->stepImages[0]->step_image_path)->toBe($this->images[0]->step_image_path);
});

test('他人のレシピの画像 id は受け付けず、その画像は変化しない', function () {
    $otherImage = StepImage::factory()->create();

    $this->actingAs($this->owner)->put(route('recipes.update', $this->recipe), updatePayload([
        ['description' => '炒める', 'kept_image_ids' => [$otherImage->id]],
    ]))->assertSessionHasErrors('steps.0.kept_image_ids.0');

    expect($otherImage->fresh()->step_id)->toBe($otherImage->step_id);
    Storage::disk('public')->assertExists($otherImage->step_image_path);
    expect($this->recipe->fresh()->title)->not->toBe('更新後のタイトル');
});

test('同じ画像を2つの手順に残すことはできない', function () {
    $id = $this->images[0]->id;

    $this->actingAs($this->owner)->put(route('recipes.update', $this->recipe), updatePayload([
        ['description' => '炒める', 'kept_image_ids' => [$id]],
        ['description' => '煮る', 'kept_image_ids' => [$id]],
    ]))->assertSessionHasErrors('steps.0.kept_image_ids.0');
});

test('1手順の上限3枚は、残す画像と新しい画像の合計で判定する', function () {
    $this->actingAs($this->owner)->put(route('recipes.update', $this->recipe), updatePayload([
        [
            'description' => '炒める',
            'kept_image_ids' => $this->images->pluck('id')->all(),
            'images' => [
                UploadedFile::fake()->create('a.jpg', 100, 'image/jpeg'),
                UploadedFile::fake()->create('b.jpg', 100, 'image/jpeg'),
            ],
        ],
    ]))->assertSessionHasErrors('steps.0.images');

    expect($this->recipe->fresh()->steps()->first()->stepImages()->count())->toBe(2);
});

test('合計20枚の上限は、残す画像と新しい画像の合計で判定する', function () {
    // 既存画像 2枚 + 新しい画像 19枚（3枚 × 6手順 + 1枚）= 21枚。新しい画像だけなら上限内
    $steps = [['description' => '炒める', 'kept_image_ids' => $this->images->pluck('id')->all()]];
    foreach (range(1, 6) as $i) {
        $steps[] = [
            'description' => "手順{$i}",
            'images' => collect(range(1, 3))->map(fn ($j) => UploadedFile::fake()->create("{$i}-{$j}.jpg", 10, 'image/jpeg'))->all(),
        ];
    }
    $steps[] = ['description' => '盛り付ける', 'images' => [UploadedFile::fake()->create('last.jpg', 10, 'image/jpeg')]];

    $this->actingAs($this->owner)->put(route('recipes.update', $this->recipe), updatePayload($steps))
        ->assertSessionHasErrors('steps');
});
