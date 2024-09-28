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
    public static function checkAndUpdateMidnightBreak()
    {
        $now = Carbon::now();
        $yesterday = $now->copy()->subDay()->format('Y-m-d');

        $activeBreaks = self::whereNull('end_time')
            ->whereDate('start_time', $yesterday)
            ->get();

        foreach ($activeBreaks as $break) {
            $break->end_time = $now->copy()->startOfDay()->subSecond();
            $break->save();

            // 新しい日の休憩を作成
            self::create([
                'attendance_id' => $break->attendance_id,
                'start_time' => $now->copy()->startOfDay(),
            ]);
        }
    }
}