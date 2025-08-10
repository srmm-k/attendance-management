<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
        'break_in_time_1',
        'break_out_time_1',
        'break_in_time_2',
        'break_out_time_2',
        'break_time',
        'total_time',
        'note',
    ];

    /**
     * Get the user that owns the attendance.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
