<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 下書き（#19）のために公開日時を持たせる。null なら下書き。
     * 下書きは途中まで保存できるよう、完成写真・調理時間・人数を nullable にする。
     */
    public function up(): void
    {
        Schema::table('recipes', function (Blueprint $table) {
            $table->timestamp('published_at')->nullable()->after('view_count');
            $table->string('recipe_image_path')->nullable()->change();
            $table->integer('cooking_time')->nullable()->change();
            $table->integer('servings')->nullable()->change();
        });

        // 既存のレシピはすべて公開済みとして扱う
        DB::table('recipes')->update(['published_at' => DB::raw('created_at')]);
    }

    /**
     * Reverse the migrations.
     * 下書き（null を含む行）が残っていると NOT NULL に戻せないため、先に下書きを消してから戻すこと。
     */
    public function down(): void
    {
        Schema::table('recipes', function (Blueprint $table) {
            $table->dropColumn('published_at');
            $table->string('recipe_image_path')->nullable(false)->change();
            $table->integer('cooking_time')->nullable(false)->change();
            $table->integer('servings')->nullable(false)->change();
        });
    }
};
