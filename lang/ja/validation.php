<?php

/*
 * このアプリで使っているバリデーションルールの日本語メッセージ。
 * 定義していないルールは fallback_locale（en）のメッセージになる。
 */
return [
    'alpha_dash' => ':attributeには英数字・ハイフン・アンダースコアのみ使えます。',
    'array' => ':attributeの形式が正しくありません。',
    'boolean' => ':attributeの値が正しくありません。',
    'confirmed' => ':attributeが確認用の入力と一致しません。',
    'current_password' => 'パスワードが正しくありません。',
    'email' => ':attributeには正しいメールアドレスを入力してください。',
    'image' => ':attributeには画像ファイルを指定してください。',
    'in' => ':attributeの値が正しくありません。',
    'integer' => ':attributeには整数を入力してください。',
    'lowercase' => ':attributeは小文字で入力してください。',
    'max' => [
        'array' => ':attributeは:max件以下にしてください。',
        'file' => ':attributeは:maxKB以下のファイルにしてください。',
        'numeric' => ':attributeは:max以下にしてください。',
        'string' => ':attributeは:max文字以下で入力してください。',
    ],
    'min' => [
        'array' => ':attributeは:min件以上にしてください。',
        'file' => ':attributeは:minKB以上のファイルにしてください。',
        'numeric' => ':attributeは:min以上にしてください。',
        'string' => ':attributeは:min文字以上で入力してください。',
    ],
    'password' => [
        'letters' => ':attributeには英字を1文字以上含めてください。',
        'mixed' => ':attributeには大文字と小文字をそれぞれ1文字以上含めてください。',
        'numbers' => ':attributeには数字を1文字以上含めてください。',
        'symbols' => ':attributeには記号を1文字以上含めてください。',
        'uncompromised' => 'この:attributeは過去に漏えいしたことがあります。別の:attributeにしてください。',
    ],
    'required' => ':attributeを入力してください。',
    'string' => ':attributeには文字列を入力してください。',
    'unique' => 'この:attributeはすでに使われています。',

    'attributes' => [
        'name' => '名前',
        'handle' => 'ユーザーID',
        'email' => 'メールアドレス',
        'password' => 'パスワード',
        'current_password' => '現在のパスワード',
        'title' => 'タイトル',
        'description' => '説明',
        'recipe_image' => 'レシピ画像',
        'cooking_time' => '調理時間',
        'servings' => '人数',
        'ingredients' => '材料',
        'ingredients.*.name' => '材料名',
        'ingredients.*.quantity' => '分量',
        'steps' => '作り方',
        'steps.*.description' => '手順',
        'steps.*.images' => '手順の画像',
        'steps.*.images.*' => '手順の画像',
        'is_checked' => 'チェック',
        'q' => 'キーワード',
        'time' => '調理時間',
        'sort' => '並び順',
    ],
];
