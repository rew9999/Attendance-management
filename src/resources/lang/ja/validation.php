<?php

return [
    'required' => ':attributeを入力してください',
    'email' => ':attributeは有効なメールアドレスである必要があります',
    'min' => [
        'string' => ':attributeは:min文字以上で入力してください',
    ],
    'confirmed' => ':attributeと一致しません',
    'unique' => 'その:attributeは既に使用されています',

    'attributes' => [
        'name' => 'お名前',
        'email' => 'メールアドレス',
        'password' => 'パスワード',
    ],
];
