@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/show.css') }}">
@endsection

@section('content')
<div class="container">
    <div class="date-navigation">
        <a href="{{ route('attendance.show', ['date' => $previousDate]) }}">&lt;</a>
        <h2>{{ $date }}</h2>
        <a href="{{ route('attendance.show', ['date' => $nextDate]) }}">&gt;</a>
    </div>
    <table class="contact-table">
        <thead>
            <tr>
                <th>名前</th>
                <th>勤務開始</th>
                <th>勤務終了</th>
                <th>休憩時間</th>
                <th>勤務時間</th>
            </tr>
        </thead>
        <tbody>
            @foreach($attendances as $attendance)
            <tr>
                <td>{{ $attendance->user->name }}</td>
                <td>{{ $attendance->start_time }}</td>
                <td>{{ $attendance->end_time ? $attendance->end_time : '-' }}</td>
                <td>{{ $attendance->total_break_time }}</td>
                <td>{{ $attendance->total_work_time }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <div class="pagination-container">
        {{ $attendances->links() }}
    </div>
</div>
@endsection