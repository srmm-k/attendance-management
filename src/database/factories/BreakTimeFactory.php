<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\BreakTime;

class BreakTimeFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = BreakTime::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        // ランダムな時刻を生成する代わりに、テストが期待する値のシンプルなひな形を定義
        return [
            // デフォルトの休憩時間を定義（テストデータで上書きされることを前提にシンプルな値）
            'break_in_time' => $this->faker->time('H:i:s', '12:00:00'),
            'break_out_time' => $this->faker->time('H:i:s', '13:00:00'),
        ];
    }
}
