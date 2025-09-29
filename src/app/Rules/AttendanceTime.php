<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class AttendanceTime implements Rule
{
    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        // フォームから両方の値を取得
        $checkInTime = request('check_in_time');
        $checkOutTime = request('check_out_time');

        // 両方の値が存在する場合のみ比較
        if ($checkInTime && $checkOutTime) {
            $inTime = strtotime($checkInTime);
            $outTime = strtotime($checkOutTime);
            
            // 出勤時間が退勤時間より後、または同じ場合は失敗
            return $inTime < $outTime;
        }

        // 片方、または両方の値がない場合はチェックをパス
        return true;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return '出勤時間もしくは退勤時間が不適切な値です';
    }
}
