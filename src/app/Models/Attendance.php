<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'date', 'start_time', 'end_time'];
    protected $dates = ['start_time', 'end_time'];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($attendance) {
            if (!$attendance->date) {
                $attendance->date = $attendance->start_time->toDateString();
            }
        });
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function breakTimes()
    {
        return $this->hasMany(BreakTime::class)->orderBy('start_time');
    }

    public function getTotalWorkTimeAttribute()
    {
        if (!$this->end_time) return 0;
        $workTime = $this->end_time->diffInSeconds($this->start_time);
        $breakTime = $this->total_break_time;
        return max(0, $workTime - $breakTime);
    }


    public function getTotalBreakTimeAttribute()
    {
        return $this->breakTimes->sum(function ($breakTime) {
            if ($breakTime->end_time) {
                return $breakTime->end_time->diffInSeconds($breakTime->start_time);
            }
        return 0;
        });
    }

    public function getFormattedTotalWorkTimeAttribute()
    {
        return $this->formatSeconds($this->total_work_time);
    }

    public function getFormattedTotalBreakTimeAttribute()
    {
        return $this->formatSeconds($this->total_break_time);
    }

    private function formatSeconds($seconds)
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $secs = $seconds % 60;
        return sprintf('%02d:%02d:%02d', $hours, $minutes, $secs);
    }

    public function getFormattedStartTimeAttribute()
    {
        return $this->start_time->format('H:i:s');
    }

    public function getFormattedEndTimeAttribute()
    {
        return $this->end_time ? $this->end_time->format('H:i:s') : '-';
    }

    public function scopeCurrentOrLastIncomplete(Builder $query, $userId)
    {
        return $query->where('user_id', $userId)
            ->where(function ($q) {
                $q->whereNull('end_time')
                ->orWhere('created_at', '>=', Carbon::now()->subDay());
            })
            ->latest('created_at');
    }
    public function setEndTimeAttribute($value)
    {
        if ($value) {
        $this->attributes['end_time'] = $value;
    } else {
        $this->attributes['end_time'] = null;
    }
    }
    public static function checkAndUpdateMidnightAttendance()
    {
        $now = Carbon::now();
        $yesterday = $now->copy()->subDay()->format('Y-m-d');

        $attendances = self::whereNull('end_time')
            ->where('date', $yesterday)
            ->get();

        foreach ($attendances as $attendance) {
            $attendance->end_time = $now->copy()->startOfDay()->subSecond();
            $attendance->save();

            self::create([
                'user_id' => $attendance->user_id,
                'date' => $now->format('Y-m-d'),
                'start_time' => $now->copy()->startOfDay(),
            ]);
        }
    }
}