<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>attendance-management</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/header.css') }}" />
    @yield('css')
</head>


<body>
    <header class="header">
        <div class="header__inner">
            <div class="header-left">
                <a href="/">
                    <img class="top-header__logo" src="{{ asset('img/logo.svg') }}" alt="ロゴ">
                </a>
            </div>
            <nav>
                <ul class="header-list">
                    @auth
                        @if(Auth::user()->is_admin)
                            <li class="header-item">
                                <a href="{{ route('admin.attendances') }}" class="header-link">勤怠一覧</a>
                            </li>
                            <li class="header-item">
                                <a href="{{ route('admin.users') }}" class="header-link">スタッフ一覧</a>
                            </li>
                            <li class="header-item">
                                <a href="{{ route('admin.requests') }}" class="header-link">申請一覧</a>
                            </li>
                        @else
                            <li class="header-item">
                                <a href="{{ route('attendance.create') }}" class="header-link">勤怠</a>
                            </li>
                            <li class="header-item">
                                <a href="{{ route('attendance.list') }}" class="header-link">勤怠一覧</a>
                            </li>
                            <li class="header-item">
                                <a href="{{ route('stamp_correction_request.list') }}" class="header-link">申請</a>
                            </li>
                        @endif
                            <li class="header-item">
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                        <button type="submit" class="header-link logout-button">ログアウト</button>
                                </form>
                            </li>
                    @endauth
                </ul>
            </nav>
        </div>
    </header>

    <main>
        @yield('content')
    </main>


    @yield('script')
</body>

</html>