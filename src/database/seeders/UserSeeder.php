<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //管理者ユーザーを作成（デフォルトのパスワードは、「password」）
        User::factory()->admin()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
        ]);

        //一般ユーザーを１０人作成（パスワードは全て「password」）
        User::factory()->count(10)->create([
            'password' => bcrypt('password'),
        ]);
    }
}
