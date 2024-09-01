<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\BreakTime;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class AttendanceController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $today = Carbon::today();
        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('date', $today)
            ->latest('start_time')  // 最新の勤務記録を取得
            ->first();

        return view('attendance.index', compact('user', 'attendance'));
    }

    public function startWork()
    {
        $user = Auth::user();
        $now = Carbon::now();

        // 最後の退勤記録を取得
        $lastAttendance = Attendance::where('user_id', $user->id)
        ->whereNotNull('end_time')
        ->latest('end_time')
        ->first();

        // 最小待機時間（分）
        $minWaitTime = 15;

    if ($lastAttendance) {
        $timeSinceLastEnd = $now->diffInMinutes($lastAttendance->end_time);

        if ($timeSinceLastEnd < $minWaitTime) {
            $remainingTime = $minWaitTime - $timeSinceLastEnd;
            return redirect('/')->with('error', "再出勤まであと{$remainingTime}分お待ちください。");
        }
    }

    // 新しい勤務を開始
    Attendance::create([
        'user_id' => $user->id,
        'date' => $now->toDateString(),
        'start_time' => $now,
    ]);

    return redirect('/')->with('success', '勤務を開始しました。');
}

    public function endWork()
    {
        $user = Auth::user();
        $today = Carbon::today();

        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('date', $today)
            ->whereNull('end_time')
            ->first();

        if ($attendance) {
            $attendance->update([
                'end_time' => Carbon::now(),
            ]);

            return redirect()->route('home')->with('success', '勤務を終了しました。');
        }
    }

    public function show(Request $request)
    {
        $date = $request->input('date', Carbon::today()->toDateString());
        $attendances = Attendance::whereDate('date', $date)
            ->with('user', 'breakTimes')
            ->paginate(10);

        $previousDate = Carbon::parse($date)->subDay()->toDateString();
        $nextDate = Carbon::parse($date)->addDay()->toDateString();

        return view('attendance.show', compact('attendances', 'date', 'previousDate', 'nextDate'));
    }
}