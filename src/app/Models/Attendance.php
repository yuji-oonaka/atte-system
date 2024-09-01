<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'date',
        'start_time',
        'end_time',
    ];

    protected $dates = ['start_time', 'end_time'];

    public function breakTimes()
    {
        return $this->hasMany(BreakTime::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getTotalBreakTimeAttribute()
    {
        return $this->breakTimes->sum('duration');
    }

    public function getTotalWorkTimeAttribute()
    {
        if (!$this->end_time) {
            return 0;
        }

        $totalMinutes = $this->end_time->diffInMinutes($this->start_time);
        return $totalMinutes - $this->total_break_time;
    }
}