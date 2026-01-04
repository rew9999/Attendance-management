<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class EmployeeUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $employees = [
            [
                'name' => '山田 太郎',
                'email' => 'yamada@example.com',
                'password' => Hash::make('password123'),
                'role' => 'employee',
            ],
            [
                'name' => '佐藤 花子',
                'email' => 'sato@example.com',
                'password' => Hash::make('password123'),
                'role' => 'employee',
            ],
            [
                'name' => '鈴木 一郎',
                'email' => 'suzuki@example.com',
                'password' => Hash::make('password123'),
                'role' => 'employee',
            ],
            [
                'name' => '田中 美咲',
                'email' => 'tanaka@example.com',
                'password' => Hash::make('password123'),
                'role' => 'employee',
            ],
            [
                'name' => '高橋 健太',
                'email' => 'takahashi@example.com',
                'password' => Hash::make('password123'),
                'role' => 'employee',
            ],
        ];

        foreach ($employees as $employee) {
            User::create($employee);
        }
    }
}
