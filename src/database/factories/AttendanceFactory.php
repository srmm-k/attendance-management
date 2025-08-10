<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;
use Carbon\Carbon;

class AttendanceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $user = User::inRandomOrder()->first();
        $date = $this->faker->dateTimeBetween('-30 days', 'now');
        $checkInTime = Carbon::parse($date)->hour($this->faker->numberBetween(8, 9))->minute($this->faker->numberBetween(0, 59))->second(0);
        $checkOutTime = Carbon::parse($checkInTime)->addHours($this->faker->numberBetween(8, 10))->addMinutes($this->faker->numberBetween(0, 59));
        $breakTime = $this->faker->numberBetween(30, 90); //休憩時間を３０分〜９０分でランダムに設定
        $totalMinutes = $checkOutTime->diffInMinutes($checkInTime) - $breakTime;
        //合計勤務時間を計算（分単位）

        return [
            'user_id' => $user->id,
            'date' => $date,
            'check_in_time' => $checkInTime,
            'check_out_time' => $checkOutTime,
            'break_time' => $breakTime,
            'total_time' => $totalMinutes,
            'note' => $this->faker->boolean(20) ? $this->faker->sentence() : null,
        ];
    }
}
