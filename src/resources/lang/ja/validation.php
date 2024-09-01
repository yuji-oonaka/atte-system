<?php

return [
    'required' => ':attributeは必須項目です。',
    'string' => ':attributeは文字列で入力してください。',
    'email' => '有効なメールアドレスを入力してください。',
    'max' => [
        'string' => ':attributeは:max文字以内で入力してください。',
    ],
    'unique' => 'この:attributeは既に使用されています。',
    'confirmed' => ':attributeが確認用と一致しません。',
    'min' => [
        'string' => ':attributeは:min文字以上で入力してください。',
    ],
    'password' => [
        'mixed' => ':attributeは大文字と小文字を含める必要があります。',
        'letters' => ':attributeは文字を含める必要があります。',
        'symbols' => ':attributeは記号を含める必要があります。',
        'numbers' => ':attributeは数字を含める必要があります。',
    ],

    'attributes' => [
        'name' => '名前',
        'email' => 'メールアドレス',
        'password' => 'パスワード',
    ],
];