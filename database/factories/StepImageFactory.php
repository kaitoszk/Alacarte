<?php

namespace Database\Factories;

use App\Models\Step;
use App\Models\StepImage;
use Database\Factories\Support\PlaceholderImage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StepImage>
 */
class StepImageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'step_id' => Step::factory(),
            // 上書きされたときに無駄な画像ファイルを作らないよう、クロージャで遅延生成する
            'step_image_path' => fn () => PlaceholderImage::store('steps', 'Step'),
        ];
    }
}
