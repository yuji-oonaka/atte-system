@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/auth.css') }}">
<link rel="stylesheet" href="{{ asset('css/login.css') }}">
@endsection

@section('content')
<div class="container">
    <h2>ログイン</h2>
    <form method="POST" action="{{ route('login') }}" novalidate>
    @csrf
    <div class="form-group">
        <input id="email" type="email" name="email" value="{{ old('email') }}" placeholder="メールアドレス" required autofocus>
        @error('email')
            <span class="error">{{ $message }}</span>
        @enderror
    </div>
    <div class="form-group">
        <input id="password" type="password" name="password" placeholder="パスワード" required>
        @error('password')
            <span class="error">{{ $message }}</span>
        @enderror
    </div>
    <button type="submit">ログイン</button>
    </form>
    <div class="auth-link">
        <p>アカウントをお持ちでない方は<a href="{{ route('register') }}">会員登録</a></p>
    </div>
</div>
@endsection