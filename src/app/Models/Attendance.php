<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Application;

class Attendance extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */

    protected $fillable = [
        'user_id',
        'date',
        'check_in_time',
        'check_out_time',
        'break_time',
        'total_time',
        'note',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date' => 'date',
    ];

    /**
     * Get the user that owns the attendance.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * この勤怠レコードに紐づく複数の休憩レコードを取得
     */
    public function breaks()
    {
        return $this->hasMany(BreakTime::class);
    }

    public function application()
    {
        return $this->hasOne(Application::class, 'attendance_id');
    }
}
