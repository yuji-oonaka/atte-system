<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\BreakTime;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class BreakTimeController extends Controller
{
    public function startBreak()
    {
        $user = Auth::user();
        $attendance = Attendance::currentOrLastIncomplete($user->id)->first();

        if ($attendance) {
            $lastBreak = $attendance->breakTimes()
                ->whereNotNull('end_time')
                ->latest('end_time')
                ->first();

            if ($lastBreak) {
                $lastBreakEnd = Carbon::parse($lastBreak->end_time);
                $now = Carbon::now();

                // 日をまたぐ休憩の場合は除外（自動処理されているため）
                if ($lastBreakEnd->toDateString() !== $now->toDateString()) {
                    // 新しい休憩を開始
                    BreakTime::create([
                        'attendance_id' => $attendance->id,
                        'start_time' => $now,
                    ]);
                    return redirect()->route('attendance.index')->with('success', '休憩を開始しました。');
                }

                // 60分のマージンをチェック
                $sixtyMinutesLater = $lastBreakEnd->copy()->addMinutes(60);
                if ($now->lt($sixtyMinutesLater)) {
                    $remainingTime = $now->diffInMinutes($sixtyMinutesLater);
                    return redirect()->route('attendance.index')->with('error', "前回の休憩から60分経過していません。あと{$remainingTime}分お待ちください。");
                }
            }

            // 新しい休憩を開始
            BreakTime::create([
                'attendance_id' => $attendance->id,
                'start_time' => Carbon::now(),
            ]);

            return redirect()->route('attendance.index')->with('success', '休憩を開始しました。');
        }
    }

    public function endBreak()
    {
        $user = Auth::user();
        $attendance = Attendance::currentOrLastIncomplete($user->id)->first();

        if ($attendance) {
            $activeBreak = $attendance->breakTimes()->whereNull('end_time')->latest()->first();
            if ($activeBreak) {
                $now = Carbon::now();
                $activeBreak->end_time = $now;
                $activeBreak->save();

                return redirect()->route('attendance.index')->with('success', '休憩を終了しました。');
            }
        }
    }
}