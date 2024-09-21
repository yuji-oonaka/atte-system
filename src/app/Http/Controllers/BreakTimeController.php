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
            $lastBreakEnd = $attendance->breakTimes()
                ->whereNotNull('end_time')
                ->latest('end_time')
                ->value('end_time');

            if ($lastBreakEnd && Carbon::parse($lastBreakEnd)->addMinutes(60)->gt(Carbon::now())) {
                $remainingTime = Carbon::parse($lastBreakEnd)->addMinutes(60)->diffInMinutes(Carbon::now());
                return redirect()->route('attendance.index')->with('error', "前回の休憩から60分経過していません。あと{$remainingTime}分お待ちください。");
            }

            BreakTime::create([
                'attendance_id' => $attendance->id,
                'start_time' => Carbon::now(),
            ]);

            return redirect()->route('attendance.index')->with('success', '休憩を開始しました。');
        }

        return redirect()->route('attendance.index');
    }

    public function endBreak()
    {
        $user = Auth::user();
        $attendance = Attendance::currentOrLastIncomplete($user->id)->first();

        if ($attendance) {
            $activeBreak = $attendance->breakTimes()->whereNull('end_time')->latest()->first();
            if ($activeBreak) {
                $activeBreak->end_time = Carbon::now();
                $activeBreak->save();

                return redirect()->route('attendance.index')->with('success', '休憩を終了しました。');
            }
        }

        return redirect()->route('attendance.index');
    }
}