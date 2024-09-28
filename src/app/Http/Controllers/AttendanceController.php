<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\User;
use App\Models\BreakTime;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AttendanceController extends Controller
{
    public function index()
    {
        //深夜0時をまたぐ勤務や休憩のデータを更新するメソッドの呼び出し
        Attendance::checkAndUpdateMidnightAttendance();
        BreakTime::checkAndUpdateMidnightBreak();

        $user = Auth::user();
        $attendance = Attendance::currentOrLastIncomplete($user->id)->first();

        //ユーザーが勤務開始、終了、休憩開始、休憩終了のアクションを実行できるかどうかを判断
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
        $user = Auth::user();
        $now = Carbon::now();
        $today = $now->toDateString();

        // 今日の勤務記録を全て取得
        $todayAttendances = Attendance::where('user_id', $user->id)
            ->whereDate('date', $today)
            ->orderBy('start_time', 'asc')
            ->get();

        if ($todayAttendances->isNotEmpty()) {
            $lastAttendance = $todayAttendances->last();

            // 日をまたいだ勤務の終了後の新しい勤務開始
            if ($todayAttendances->count() === 1 && $lastAttendance->start_time->format('H:i:s') === '00:00:00' && $lastAttendance->end_time !== null) {
                Attendance::create([
                    'user_id' => $user->id,
                    'date' => $today,
                    'start_time' => $now,
                ]);
                return redirect()->route('attendance.index')->with('success', '勤務を開始しました。');
            }

            // 通常の一日一回の勤務制限
            if ($lastAttendance->end_time !== null) {
                return redirect()->route('attendance.index')->with('error', '本日はすでに勤務を開始しています。');
            }
        }

        // 新しい勤務を開始
        Attendance::create([
            'user_id' => $user->id,
            'date' => $today,
            'start_time' => $now,
        ]);
        return redirect()->route('attendance.index')->with('success', '勤務を開始しました。');
    }

    public function endWork()
    {
        $user = Auth::user();
        $now = Carbon::now();

        //ユーザーの最新の未終了の勤務記録を取得
        $attendance = Attendance::where('user_id', $user->id)
            ->whereNull('end_time')
            ->latest()
            ->first();

        // 勤務記録が見つかった場合、終了時刻を設定して保存し、成功メッセージとともにリダイレクト
        if ($attendance) {
            $attendance->end_time = $now;
            $attendance->save();

            return redirect()->route('attendance.index')->with('success', '勤務を終了しました。');
        }

        return redirect()->route('attendance.index');
    }

    public static function checkAndUpdateMidnightAttendance()
    {
        //現在の日時と昨日の日付を取得
        $now = Carbon::now();
        $yesterday = $now->copy()->subDay()->format('Y-m-d');
         //昨日の日付で終了時刻が設定されていない勤務記録をすべて取得
        $attendances = Attendance::whereNull('end_time')
            ->where('date', $yesterday)
            ->get();
        //各勤務記録に対して、終了時刻を昨日の23:59:59に設定して保存
        foreach ($attendances as $attendance) {
            $midnight = $now->copy()->startOfDay();

            $attendance->end_time = $midnight->copy()->subSecond();
            $attendance->save();
            //同じユーザーの新しい勤務記録を今日の日付で作成し、開始時刻を0:00に設定
            Attendance::create([
                'user_id' => $attendance->user_id,
                'date' => $now->format('Y-m-d'),
                'start_time' => $midnight,
            ]);
        }
    }

    public function show(Request $request)
    {
        // 特定のユーザーを検索し、見つかった場合はそのユーザーの勤怠ページにリダイレクト
        $searchUser = $request->input('search_user');
            if ($searchUser) {
            $searchedUser = User::where('name', $searchUser)->first();
            if ($searchedUser) {
                return redirect()->route('attendance.user', $searchedUser->id);
            }
        }
        //検索日付、名前、ソート順を取得します。日付のデフォルトは今日
        $searchDate = $request->input('search_date', Carbon::today()->toDateString());
        $searchName = $request->input('search_name');
        $sortOrder = $request->input('sort_order', 'asc');
        // ユーザーと休憩時間をEager Loadingで取得するクエリを準備
        $query = Attendance::with(['user', 'breakTimes']);
        // 日付と名前で検索条件を追加
        if ($searchDate) {
            $query->whereDate('date', $searchDate);
        }
        if ($searchName) {
            $query->whereHas('user', function ($q) use ($searchName) {
                $q->where('name', 'like', '%' . $searchName . '%');
            });
        }
        // ソート順を設定
        if ($sortOrder === 'asc') {
            $query->orderBy('date', 'asc')->orderBy('start_time', 'asc');
        } else {
            $query->orderBy('date', 'desc')->orderBy('start_time', 'desc');
        }
        // クエリを実行し、結果をページネーションで取得
        $attendances = $query->orderBy('start_time', $sortOrder)->paginate(5);
        // 前後の日付を取得
        $previousDate = $this->findPreviousAttendanceDate($searchDate);
        $nextDate = $this->findNextAttendanceDate($searchDate);
        // 全ユーザーのIDと名前を取得
        $users = User::select('id', 'name')->get();
        // 検索結果が空の場合のメッセージを設定
        $noResultsMessage = '';
        if ($searchName && $attendances->isEmpty()) {
            $noResultsMessage = "指定された日付内に '{$searchName}' という名前の勤怠記録は存在しません。";
        }
        return view('attendance.show', compact('attendances', 'searchDate', 'previousDate', 'nextDate', 'searchName', 'sortOrder','users', 'noResultsMessage'));
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

    public function showUserAttendance(Request $request, $userId)
    {
        $searchUser = $request->input('search_user');
            if ($searchUser) {
            $searchedUser = User::where('name', $searchUser)->first();
            if ($searchedUser) {
                return redirect()->route('attendance.user', $searchedUser->id);
            }else {
                return redirect()->back()->with('error', '指定されたユーザーが存在しません。');
            }
        }
        // 対象ユーザー、期間、ソート順を取得。デフォルトは今月の開始日から終了日
        $user = User::findOrFail($userId);
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth()->toDateString());
        $sortOrder = $request->input('sort_order', 'asc');

        $query = Attendance::where('user_id', $userId)
            ->whereBetween('date', [$startDate, $endDate])
            ->with('breakTimes');

        if ($sortOrder === 'desc') {
            $query->orderBy('date', 'desc')->orderBy('start_time', 'desc');
        } else {
            $query->orderBy('date', 'asc')->orderBy('start_time', 'asc');
        }

        $attendances = $query->paginate(5);

        // 総勤務時間と深夜帯総勤務時間の計算
        $totalWorkSeconds = 0;
        $totalNightWorkSeconds = 0;

        $allAttendances = Attendance::where('user_id', $userId)
            ->whereBetween('date', [$startDate, $endDate])
            ->with('breakTimes')
            ->get();

        foreach ($allAttendances as $attendance) {
            if ($attendance->end_time) {
                $start = Carbon::parse($attendance->start_time);
                $end = Carbon::parse($attendance->end_time);

                // 総勤務時間の計算
                $workSeconds = $end->diffInSeconds($start);
                $breakSeconds = $attendance->breakTimes->sum(function ($break) {
                    return $break->end_time ? Carbon::parse($break->end_time)->diffInSeconds(Carbon::parse($break->start_time)) : 0;
                });
                $totalWorkSeconds += ($workSeconds - $breakSeconds);

                // 深夜帯勤務時間の計算
                $totalNightWorkSeconds += $this->calculateNightWorkSeconds($start, $end, $attendance->breakTimes);
            }
        }

        $totalWorkTime = $this->formatSeconds($totalWorkSeconds);
        $totalNightWorkTime = $this->formatSeconds($totalNightWorkSeconds);

        $users = User::select('id', 'name')->get();

        return view('attendance.user_attendance', compact('user', 'attendances', 'totalWorkTime', 'totalNightWorkTime', 'startDate', 'endDate', 'sortOrder','users'));
    }

    private function calculateNightWorkSeconds($start, $end, $breakTimes)
    {
        // 深夜時間帯の設定
        $nightStart1 = Carbon::createFromTime(22, 0, 0);
        if ($start->hour < 5) {
            // 開始時刻が5時前の場合は前日の22時から開始
            $nightStart1 = Carbon::createFromTime(22, 0, 0)->subDay();
        }

        // 深夜終了時間（翌日5時）
        $nightEnd1 = Carbon::createFromTime(5, 0, 0)->addDay();

        // 深夜帯の勤務時間を計算
        if ($end <= $nightStart1 || $start >= $nightEnd1) {
            return 0; // 深夜帯に含まれない場合
        }

        // 深夜帯内での勤務開始・終了を調整
        if ($start < $nightStart1) {
            $start = clone $nightStart1;
        }

        if ($end > $nightEnd1) {
            $end = clone $nightEnd1;
        }

        // 深夜帯内の総勤務秒数
        return max(0, ($end->diffInSeconds($start)) -
                ($this->calculateBreaksDuringNight($breakTimes, clone($nightStart1), clone($nightEnd1))));
    }

    private function calculateBreaksDuringNight($breakTimes, $nightStart1, $nightEnd1)
    {
        return array_reduce(
            iterator_to_array(
                collect($breakTimes)->map(function ($break) use ($nightStart1, $nightEnd1) {
                    return [
                        max(Carbon::parse($break['start_time']), $nightStart1),
                        min(Carbon::parse($break['end_time']), $nightEnd1),
                    ];
                })->filter(fn(array $range) => $range[0] < $range[1])
            ),
            fn(int $total, array $range) => $total + $range[0]->diffInSeconds($range[1]),
            0
        );
    }

    private function formatSeconds($seconds)
    {
    return sprintf('%02d:%02d:%02d',
        floor($seconds / 3600),
        floor(($seconds % 3600) / 60),
        floor($seconds % 60));
    }
}