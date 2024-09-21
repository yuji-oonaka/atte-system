<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AttendanceController extends Controller
{
    public function index()
    {
        Attendance::checkAndUpdateMidnightAttendance();
        $user = Auth::user();
        $attendance = Attendance::currentOrLastIncomplete($user->id)->first();

        $canStartWork = !$attendance || $attendance->end_time;
        $canEndWork = $attendance && !$attendance->end_time;
        $activeBreak = $attendance ? $attendance->breakTimes()->whereNull('end_time')->first() : null;
        $canStartBreak = $canEndWork && !$activeBreak;
        $canEndBreak = $canEndWork && $activeBreak;
        $canEndWork = $canEndWork && !$activeBreak;

        return view('attendance.index', compact('user', 'attendance', 'canStartWork', 'canEndWork', 'canStartBreak', 'canEndBreak'));
    }

    public function startWork()
    {
        Attendance::checkAndUpdateMidnightAttendance();
        $user = Auth::user();
        $now = Carbon::now();

        Attendance::create([
            'user_id' => $user->id,
            'date' => $now->toDateString(),
            'start_time' => $now,
        ]);

        return redirect()->route('attendance.index')->with('success', '勤務を開始しました。');
    }

    public function endWork()
    {
        Attendance::checkAndUpdateMidnightAttendance();
        $user = Auth::user();
        $attendance = Attendance::currentOrLastIncomplete($user->id)->first();

        if ($attendance) {
            $now = Carbon::now();
            $startOfDay = $now->copy()->startOfDay();

            if ($attendance->start_time->lt($startOfDay)) {
                // 日をまたいでいる場合
                $attendance->end_time = $startOfDay->copy()->subSecond();
                $attendance->save();

                // 新しい日の勤怠を作成
                Attendance::create([
                    'user_id' => $user->id,
                    'date' => $now->toDateString(),
                    'start_time' => $startOfDay,
                ]);
            } else {
                // 同じ日の場合
                $attendance->end_time = $now;
                $attendance->save();
            }
            return redirect()->route('attendance.index')->with('success', '勤務を終了しました。');
        }

    return redirect()->route('attendance.index');
    }

    public function show(Request $request)
    {
        $date = $request->input('date', Carbon::today()->toDateString());
        $attendances = Attendance::where('date', $date)
            ->with(['user', 'breakTimes'])
            ->orderBy('start_time', 'desc')
            ->paginate(5);

        $previousDate = $this->findPreviousAttendanceDate($date);
        $nextDate = $this->findNextAttendanceDate($date);

        return view('attendance.show', compact('attendances', 'date', 'previousDate', 'nextDate'));
    }

    private function formatSeconds($seconds)
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $secs = $seconds % 60;
        return sprintf('%02d:%02d:%02d', $hours, $minutes, $secs);
    }

    private function findPreviousAttendanceDate($date)
    {
        return Attendance::where('date', '<', $date)
            ->orderBy('date', 'desc')
            ->value('date');
    }

    private function findNextAttendanceDate($date)
    {
        return Attendance::where('date', '>', $date)
            ->orderBy('date', 'asc')
            ->value('date');
    }
}