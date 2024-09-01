<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class BreakTime extends Model
{
    use HasFactory;

    protected $fillable = ['attendance_id', 'start_time', 'end_time'];

    protected $dates = ['start_time', 'end_time'];

    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }

    public function getDurationAttribute()
    {
        if ($this->end_time) {
            return $this->end_time->diffInMinutes($this->start_time);
        }
        return Carbon::now()->diffInMinutes($this->start_time); // 休憩中の場合は現在時刻までの経過時間を返す
    }
}