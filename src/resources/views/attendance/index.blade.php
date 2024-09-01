@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/index.css') }}">
@endsection


@section('content')
<div class="container">
    <h2>{{ $user->name }}さんお疲れ様です！</h2>
    <div class="attendance__alert">
        @if(session('success'))
            <div class="attendance__alert--success">
                {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="attendance__alert--error">
                {{session('error')}}
            </div>
        @endif
    </div>
    <div class="attendance-buttons">
        <form action="{{ route('attendance.start') }}" method="POST">
            @csrf
            <button type="submit" class="attendance-button" {{ $attendance && !$attendance->end_time ? 'disabled' : '' }}>勤務開始</button>
        </form>
        <form action="{{ route('attendance.end') }}" method="POST">
            @csrf
            <button type="submit" class="attendance-button" {{ !$attendance || $attendance->end_time ? 'disabled' : '' }}>勤務終了</button>
        </form>
        <form action="{{ route('break.start') }}" method="POST">
            @csrf
            <button type="submit" class="attendance-button" {{ !$attendance || $attendance->end_time || ($attendance->breakTimes->last() && !$attendance->breakTimes->last()->end_time) ? 'disabled' : '' }}>休憩開始</button>
        </form>
        <form action="{{ route('break.end') }}" method="POST">
            @csrf
            <button type="submit" class="attendance-button" {{ !$attendance || $attendance->end_time || !$attendance->breakTimes->last() || $attendance->breakTimes->last()->end_time ? 'disabled' : '' }}>休憩終了</button>
        </form>
    </div>
</div>
@endsection