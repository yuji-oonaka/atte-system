<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class Attendance extends Model
{
    use HasFactory;
    // マスアサインメント可能なフィールドと日付として扱うフィールドを指定
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
    // ユーザーと休憩時間のリレーション
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function breakTimes()
    {
        return $this->hasMany(BreakTime::class)->orderBy('start_time');
    }
    // 総勤務時間と総休憩時間を計算するアクセサ
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
    // 総勤務時間と総休憩時間をフォーマットして返すアクセサ
    public function getFormattedTotalWorkTimeAttribute()
    {
        return $this->formatSeconds($this->total_work_time);
    }

    public function getFormattedTotalBreakTimeAttribute()
    {
        return $this->formatSeconds($this->total_break_time);
    }
    // 秒数をフォーマットするヘルパーメソッド
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
    // 現在の勤務または最後の未完了の勤務を取得するクエリスコープ
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
    // 深夜0時をまたぐ勤務を自動的に処理するメソッドです。前日の未終了の勤務を終了し、新しい日の勤務を開始します。また、アクティブな休憩がある場合はそれも同様に処理
    public static function checkAndUpdateMidnightAttendance()
    {
        $now = Carbon::now();
        $yesterday = $now->copy()->subDay()->format('Y-m-d');

        $attendances = self::whereNull('end_time')
            ->where('date', $yesterday)
            ->get();

        foreach ($attendances as $attendance) {
            $midnight = $now->copy()->startOfDay();

            // 勤務を終了
            $attendance->end_time = $midnight->copy()->subSecond();
            $attendance->save();

            // 新しい勤務を開始
            $newAttendance = self::create([
                'user_id' => $attendance->user_id,
                'date' => $now->format('Y-m-d'),
                'start_time' => $midnight,
            ]);

            // アクティブな休憩があれば終了し、新しい休憩を開始
            $activeBreak = $attendance->breakTimes()->whereNull('end_time')->first();
            if ($activeBreak) {
                $activeBreak->end_time = $midnight->copy()->subSecond();
                $activeBreak->save();

                BreakTime::create([
                    'attendance_id' => $newAttendance->id,
                    'start_time' => $midnight,
                ]);
            }
        }
    }
    // 複数の勤務記録の総勤務時間を計算するスタティックメソッド
    public static function calculateTotalWorkTime($attendances)
    {
        $totalSeconds = 0;
        foreach ($attendances as $attendance) {
            if ($attendance->end_time && $attendance->start_time) {
                $totalSeconds += $attendance->total_work_time;
            }
        }
        return gmdate('H:i:s', $totalSeconds);
    }
}