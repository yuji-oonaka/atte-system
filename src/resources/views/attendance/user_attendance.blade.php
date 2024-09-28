@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/show.css') }}">
@endsection

@section('content')
<div class="container">
    <h2>{{ $user->name }}さんの勤怠記録</h2>
    <form action="{{ route('attendance.user', $user->id) }}" method="GET" class="date-range-form">
        <div class="form-group">
            <label for="start_date">開始日:</label>
            <input type="date" id="start_date" name="start_date" value="{{ $startDate }}" required>
        </div>
        <div class="form-group">
            <label for="end_date">終了日:</label>
            <input type="date" id="end_date" name="end_date" value="{{ $endDate }}" required>
        </div>
            <button type="submit" class="btn">期間を指定</button>
            <a href="{{ route('attendance.user', $user->id) }}" class="default-btn">デフォルトに戻す</a>
        <div class="form-group user-search">
            <label for="search_user">ユーザー検索:</label>
            <input type="text" id="search_user" name="search_user" list="user-list" placeholder="名前を入力または選択">
            <datalist id="user-list">
                @foreach($users as $u)
                    <option value="{{ $u->name }}">
                @endforeach
            </datalist>
            <button type="submit" class="btn">検索</button>
        @if(session('error'))
            <div class="user__alert--error">
                {{ session('error') }}
            </div>
        @endif
        </div>
    </form>

    <p>総勤務時間 ({{ $startDate }} から {{ $endDate }} まで): <span class="work-time">{{ $totalWorkTime }}</span>&nbsp;&nbsp;|&nbsp;&nbsp;
    深夜帯総勤務時間: <span class="night-work-time">{{ $totalNightWorkTime }}</span></p>

    <table class="contact-table">
        <thead>
            <tr>
                <th>
                    日付
                    <a class="sortOrder" href="{{ route('attendance.user', ['userId' => $user->id, 'start_date' => $startDate, 'end_date' => $endDate, 'sort_order' => ($sortOrder == 'asc' ? 'desc' : 'asc')]) }}">
                        {{ $sortOrder == 'asc' ? '▲' : '▼' }}
                    </a>
                </th>
                <th>勤務開始</th>
                <th>勤務終了</th>
                <th>休憩時間</th>
                <th>勤務時間</th>
            </tr>
        </thead>
        <tbody>
            @foreach($attendances as $attendance)
            <tr>
                <td>{{ $attendance->date }}</td>
                <td>{{ $attendance->formatted_start_time }}</td>
                <td>{{ $attendance->formatted_end_time }}</td>
                <td>{{ $attendance->formatted_total_break_time }}</td>
                <td>{{ $attendance->formatted_total_work_time }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <div class="pagination-container">
        {{ $attendances->appends(request()->query())->links() }}
    </div>
</div>
@endsection