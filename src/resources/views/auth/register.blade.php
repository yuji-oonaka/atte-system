@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/auth.css') }}">
<link rel="stylesheet" href="{{ asset('css/register.css') }}">
@endsection

@section('content')
<div class="container">
    <h2>会員登録</h2>
    <form method="POST" action="{{ route('register') }}" novalidate>
    @csrf
    <div class="form-group">
        <input id="name" type="text" name="name" value="{{ old('name') }}" placeholder="名前" required autofocus>
        @error('name')
            <span class="error">{{ $message }}</span>
        @enderror
    </div>
    <div class="form-group">
        <input id="email" type="email" name="email" value="{{ old('email') }}" placeholder="メールアドレス" required>
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
    <div class="form-group">
        <input id="password_confirmation" type="password" name="password_confirmation" placeholder="確認用パスワード" required>
    </div>
    <button type="submit">会員登録</button>
    </form>
    <div class="auth-link">
        <p>アカウントをお持ちの方は<a href="{{ route('login') }}">ログイン</a></p>
    </div>
</div>
@endsection