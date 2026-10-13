<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CreateAdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminPassword = Hash::make('Test@123456');
        $userPassword = Hash::make('Test@12345');
        $user = [
            [
                'name' => 'Super Admin',
                'type' => 0,
                'email' => "test.admin@yopmail.com",
                'mobile' => 9999919999,
                'password' => $adminPassword,
                'status' => 1
            ],
            [
                'name' => 'Test User S',
                'type' => 2,
                'email' => "exampleuser00@yopmail.com",
                'mobile' => 9999919991,
                'password' => $userPassword,
                'status' => 1
            ]
        ];

        User::insert($user);
    }
}
