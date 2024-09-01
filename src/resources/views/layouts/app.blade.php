<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Atte</title>
    <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    @yield('css')
</head>
<body>
    <header>
        <h1>Atte</h1>
        @auth
        <nav>
            <a href="{{ route('home') }}">ホーム</a>
            <a href="{{ route('attendance.show') }}">日付一覧</a>
            <form action="{{ route('logout') }}" method="POST" style="display: inline;">
                @csrf
                <button type="submit" style="background: none; border: none; cursor: pointer;">ログアウト</button>
            </form>
        </nav>
        @endauth
    </header>
    <main>
        @yield('content')
    </main>
    <footer>
        <p>Atte, inc.</p>
    </footer>
</body>
</html>