<?php

namespace Database\Seeders\Support;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * シード用の料理写真（database/seeders/images）を public ディスクにコピーする。
 * 写真の出典とライセンスは docs/image-credits.md に記録している。
 */
class SeedPhoto
{
    /**
     * レシピ名 => 写真のファイル名（database/seeders/images/recipes 配下）
     *
     * @var array<string, string>
     */
    private const RECIPES = [
        '基本の肉じゃが' => 'nikujaga.jpg',
        'ふわとろオムライス' => 'omurice.jpg',
        '鶏むね肉の唐揚げ' => 'karaage.jpg',
        '豚バラ大根の煮物' => 'pork-daikon.jpg',
        '簡単チキンカレー' => 'chicken-curry.jpg',
        '和風だしの親子丼' => 'oyakodon.jpg',
        'トマトとツナのパスタ' => 'tomato-tuna-pasta.jpg',
        'ほうれん草のナムル' => 'spinach-namul.jpg',
        '鮭のホイル焼き' => 'salmon-foil.jpg',
        '野菜たっぷり味噌汁' => 'miso-soup.jpg',
        'なすの煮浸し' => 'eggplant-nibitashi.jpg',
        '豆腐ハンバーグ' => 'tofu-hamburg.jpg',
        '鶏むね肉の照り焼き' => 'chicken-teriyaki.jpg',
        'きんぴらごぼう' => 'kinpira-gobo.jpg',
        '簡単チャーハン' => 'fried-rice.jpg',
        'ぶり大根' => 'buri-daikon.jpg',
        'ミネストローネ' => 'minestrone.jpg',
        '豚しゃぶサラダ' => 'pork-shabu-salad.jpg',
        '厚揚げの煮物' => 'atsuage-nimono.jpg',
        '手作り餃子' => 'gyoza.jpg',
    ];

    /**
     * レシピ名に対応する写真を storage にコピーしてパスを返す。写真がなければ null。
     */
    public static function storeForRecipe(string $title): ?string
    {
        $file = self::RECIPES[$title] ?? null;

        return $file === null ? null : self::copy("recipes/{$file}", 'recipes');
    }

    /**
     * 手順用の汎用写真（database/seeders/images/steps 配下）からランダムに1枚コピーしてパスを返す。写真がなければ null。
     */
    public static function storeRandomStep(): ?string
    {
        $files = glob(database_path('seeders/images/steps/*.jpg')) ?: [];

        if ($files === []) {
            return null;
        }

        return self::copy('steps/'.basename($files[array_rand($files)]), 'steps');
    }

    private static function copy(string $source, string $directory): ?string
    {
        $sourcePath = database_path("seeders/images/{$source}");

        if (! is_file($sourcePath)) {
            return null;
        }

        $path = $directory.'/'.Str::uuid().'.jpg';
        Storage::disk('public')->put($path, (string) file_get_contents($sourcePath));

        return $path;
    }
}
