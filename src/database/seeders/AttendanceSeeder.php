<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Attendance;
use App\Models\User;

class AttendanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        if (User::count() === 0) {
            echo "ユーザーが1人もいません。先にUserSeederを実行してください。\n";
        }

        User::all()->each(function ($user) {
            for ($i = 0; $i < 30; $i++) {
                //勤務日をランダムに決める
                if (rand(0, 10) > 2) {
                    Attendance::factory()->for($user)->create([
                        'date' => now()->subDays($i)->format('Y-m-d')
                    ]);
                }
            }
        });
    }
}
