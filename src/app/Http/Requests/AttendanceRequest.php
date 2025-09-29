<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Rules\AttendanceTime;

class AttendanceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
        'check_in_time' => [
            'required',
            'date_format:H:i',
            new AttendanceTime,
        ],
        'check_out_time' => [
            'required',
            'date_format:H:i',
        ],
        'breaks.*.break_in_time' => [
            'nullable', // 空でもエラーにならないようにnullableを追加
            'date_format:H:i',
            'after_or_equal:check_in_time',
            'before_or_equal:check_out_time',
        ],
        'breaks.*.break_out_time' => [
            'nullable', // 空でもエラーにならないようにnullableを追加
            'date_format:H:i',
            'after_or_equal:breaks.*.break_in_time',
            'before_or_equal:check_out_time',
        ],
        'note' => 'required|string',
    ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array
     */
    public function messages()
    {
        return [
        // 出勤・退勤の不適切な値のルール
        'check_in_time.required' => '出勤時間を入力してください。',
        'check_out_time.required' => '退勤時間を入力してください。',

        // 休憩時間の不適切な値のルール
        'breaks.*.break_in_time.date_format' => '休憩時間が不適切な値です',
        'breaks.*.break_in_time.after_or_equal' => '休憩時間が不適切な値です',
        'breaks.*.break_in_time.before_or_equal' => '休憩時間が不適切な値です',

        // 休憩終了時間の不適切な値のルール
        'breaks.*.break_out_time.date_format' => '休憩時間もしくは退勤時間が不適切な値です',
        'breaks.*.break_out_time.after_or_equal' => '休憩時間もしくは退勤時間が不適切な値です',
        'breaks.*.break_out_time.before_or_equal' => '休憩時間もしくは退勤時間が不適切な値です',

        // 備考のルール
        'note.required' => '備考を記入してください',
        'breaks.*.break_in_time.required' => '休憩時間を入力してください。',
        'breaks.*.break_out_time.required' => '休憩時間を入力してください。',
    ];
    }
}
