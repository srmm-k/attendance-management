<?php

namespace Database\Factories;

use App\Models\Application;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

class ApplicationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        // テストで使いやすいデフォルトの修正内容をJSON形式で定義
        $reasonData = [
            'check_in_time' => '09:00',
            'check_out_time' => '18:00',
            'note' => '修正申請のテスト用備考',
            'breaks' => [
                ['break_in_time' => '12:00', 'break_out_time' => '13:00'],
            ],
        ];

        return [
            // デフォルトでは承認待ち(status: 1)として作成
            'target_date' => Carbon::now()->toDateString(),
            'status' => 1,
            'reason' => json_encode($reasonData),
            'attendance_id' => null, // テストで紐づけを行う
            'user_id' => null,       // テストで紐づけを行う
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }
}
