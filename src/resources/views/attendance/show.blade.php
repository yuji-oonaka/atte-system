@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/show.css') }}">
@endsection

@section('content')
<div class="container">
    <div class="date-navigation">
        @if($previousDate)
        <a href="{{ route('attendance.show', ['search_date' => $previousDate, 'search_name' => $searchName, 'sort_order' => $sortOrder]) }}">&lt;</a>
        @else
        <span class="disabled">&lt;</span>
        @endif
        <h2>{{ $searchDate }}</h2>
        @if($nextDate)
            <a href="{{ route('attendance.show', ['search_date' => $nextDate, 'search_name' => $searchName, 'sort_order' => $sortOrder]) }}">&gt;</a>
        @else
            <span class="disabled">&gt;</span>
        @endif
    </div>
    <form action="{{ route('attendance.show') }}" method="GET" class="search-form">
        <input type="date" name="search_date" value="{{ $searchDate }}" required>
        <input type="text" name="search_name" value="{{ $searchName }}" placeholder="日付内を名前で検索">
        <button type="submit">検索</button>
    </form>
    <table class="contact-table">
        @if($noResultsMessage)
        <div class="user__alert--error">
            {{ $noResultsMessage }}
        </div>
        @endif

        <thead>
            <tr>
                <th>名前</th>
                <th>勤務開始<a class="sortOrder" href="{{ route('attendance.show', ['search_date' => $searchDate, 'search_name' => $searchName, 'sort_order' => ($sortOrder == 'asc' ? 'desc' : 'asc')]) }}">
                {{ $sortOrder == 'asc' ? '▲' : '▼' }}</th>
                <th>勤務終了</th>
                <th>休憩時間</th>
                <th>勤務時間</th>
            </tr>
        </thead>
        <tbody>
            @foreach($attendances as $attendance)
            <tr>
                <td>
                    <a href="{{ route('attendance.user', $attendance->user_id) }}">
                        {{ $attendance->user->name }}
                    </a>
                </td>
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
    <form>
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
@endsection