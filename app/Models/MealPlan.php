<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 献立の1件（ある日に作るレシピ）。1日に複数のレシピを入れられる。
 */
class MealPlan extends Model
{
    use HasFactory;

    /** 「今日」を決めるタイムゾーン。アプリ全体は UTC のまま、献立まわりだけ日本時間で日付を扱う */
    public const TIMEZONE = 'Asia/Tokyo';

    /** 今日から何日分の日付に追加できるか（今日〜13日後） */
    public const ADDABLE_DAYS = 14;

    protected $fillable = [
        'user_id',
        'recipe_id',
        'date',
    ];

    /**
     * date キャストだと SQLite では「Y-m-d 00:00:00」で保存され、Y-m-d との照合（unique 検証・firstOrCreate）がずれる。
     * DB には常に Y-m-d で書き、読むときだけ日付オブジェクトにする。
     */
    protected function date(): Attribute
    {
        return Attribute::make(
            get: fn (string $value) => CarbonImmutable::parse($value)->startOfDay(),
            set: fn (CarbonImmutable|string $value) => CarbonImmutable::parse($value)->toDateString(),
        );
    }

    /** 日本時間の今日（0時） */
    public static function today(): CarbonImmutable
    {
        return CarbonImmutable::now(self::TIMEZONE)->startOfDay();
    }

    /**
     * 追加できる日付（今日〜13日後）を Y-m-d で返す。
     *
     * @return list<string>
     */
    public static function addableDates(): array
    {
        $today = self::today();

        return array_map(fn (int $offset) => $today->addDays($offset)->toDateString(), range(0, self::ADDABLE_DAYS - 1));
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }
}
