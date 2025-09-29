<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BreakTime extends Model
{
    use HasFactory;

    protected $table = 'breaks';

    protected $fillable = [
        'attendance_id',
        'break_in_time',
        'break_out_time',
    ];

    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }
}
