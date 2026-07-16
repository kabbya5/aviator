<!DOCTYPE html>
    <html lang="en">
    <head>
    <meta charset="utf-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title> @yield('title') </title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{asset('/custom_wingo/css/wingo_admin2.css')}}">
    <script src="https://cdn.socket.io/4.7.5/socket.io.min.js"></script>

    @stack('style')
</head>
<body>
    <aside class="sidebar">
        <div class="sidebar-header">
            <img style="height:35px; margin-left:10px;" src="{{asset('wingo_asset/logo.png')}}" alt="">
        </div>
        <nav class="sidebar-nav">
            <a href="{{route('admin.dashboard')}}" class="nav-item ">
                <span class="icon">🏠</span><span>Main Dashboard</span>
            </a>
            <a href="{{route('admin.aviator.dashboard')}}" class="nav-item {{ request()->routeIs('admin.wingo.dashboard') ? 'active' : '' }}">
                <span class="icon">🏠</span><span>Home</span>
            </a>

            <a href="{{route('admin.aviator.transactions')}}" class="nav-item {{ request()->routeIs('admin.wingo.dashboard') ? 'active' : '' }}">
                <span class="icon">🏠</span><span>Transaction</span>
            </a>
        </nav>
    </aside>

    <header class="topbar">
        <div class="topbar-left">
            <h1 class="topbar-title"> Aviator Dashboard</h1>
        </div>

    </header>

    <main class="merchant-shell">
        @yield('content')
    </main>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="{{asset('/custom_aviator/js/admin_aviator.js')}}"></script>
    <script>
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': document
                    .querySelector('meta[name="csrf-token"]')
                    .getAttribute('content')
            }
        });
    </script>
    @yield('js')
</body>
