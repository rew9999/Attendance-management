<?php

return [
    'defaults' => [
        'guard' => 'web',
        'passwords' => 'users',
    ],

    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],

        // 一般ユーザー用ガード
        'employee' => [
            'driver' => 'session',
            'provider' => 'employees',
        ],

        // 管理者用ガード
        'admin' => [
            'driver' => 'session',
            'provider' => 'admins',
        ],
    ],

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => App\Models\User::class,
        ],

        // 一般ユーザー用プロバイダー
        'employees' => [
            'driver' => 'eloquent',
            'model' => App\Models\User::class,
            'table' => 'users',
            'conditions' => ['role' => 'employee'], // role=employeeのみ
        ],

        // 管理者用プロバイダー
        'admins' => [
            'driver' => 'eloquent',
            'model' => App\Models\User::class,
            'table' => 'users',
            'conditions' => ['role' => 'admin'], // role=adminのみ
        ],
    ],

    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table' => 'password_reset_tokens',
            'expire' => 60,
            'throttle' => 60,
        ],

        'admins' => [
            'provider' => 'admins',
            'table' => 'password_reset_tokens',
            'expire' => 60,
            'throttle' => 60,
        ],
    ],

    'password_timeout' => 10800,
];
