<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\BreakTime;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class BreakTimeController extends Controller
{
    private const MIN_BREAK_INTERVAL = 15; // minutes

    public function startBreak(): RedirectResponse
    {
        $user = Auth::user();
        $attendance = $this->getTodayAttendance($user);

        $lastBreak = $attendance->breakTimes()->latest()->first();

        if ($lastBreak && $lastBreak->end_time) {
            $timeSinceLastBreak = Carbon::now()->diffInMinutes($lastBreak->end_time);

            if ($timeSinceLastBreak < self::MIN_BREAK_INTERVAL) {
                $remainingTime = self::MIN_BREAK_INTERVAL - $timeSinceLastBreak;
                return redirect()->route('home')->with('error', "次の休憩まであと{$remainingTime}分お待ちください。");
            }
        }

        BreakTime::create([
            'attendance_id' => $attendance->id,
            'start_time' => Carbon::now(),
        ]);

        return redirect()->route('home')->with('success', '休憩を開始しました。');
    }

    public function endBreak(): RedirectResponse
    {
        $user = Auth::user();
        $attendance = $this->getTodayAttendance($user);

        $activeBreak = $attendance->breakTimes()->whereNull('end_time')->latest()->first();

        $activeBreak->update([
            'end_time' => Carbon::now(),
        ]);

        return redirect()->route('home')->with('success', '休憩を終了しました。');
    }

    private function getTodayAttendance($user): Attendance
    {
        return Attendance::where('user_id', $user->id)
            ->whereDate('date', Carbon::today())
            ->whereNull('end_time')
            ->firstOrFail();
    }
}