<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Panel Admin Dento')</title>
    <style>
        :root {
            --color-sky: #4AA8D8;
            --color-sky-dark: #2F7FAE;
            --color-sky-light: #E8F6FB;
            --color-navy: #25435C;
            --color-title: #1F6E9C;
        }
        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background-color: #f8fafc;
            color: var(--color-navy);
            margin: 0;
            padding: 0;
        }
        .admin-header {
            background-color: #ffffff;
            border-bottom: 1px solid #e2e8f0;
            padding: 16px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .admin-nav a {
            color: var(--color-sky-dark);
            text-decoration: none;
            margin-right: 16px;
            font-weight: 600;
        }
        .admin-container {
            max-width: 1200px;
            margin: 32px auto;
            padding: 0 24px;
        }
    </style>
    @stack('styles')
</head>
<body>
    <header class="admin-header">
        <div>
            <strong style="color: var(--color-title); font-size: 20px;">Dento Admin</strong>
        </div>
        <nav class="admin-nav">
            @auth
                <a href="{{ url('/admin') }}">Dashboard</a>
                <a href="{{ url('/admin/students') }}">Murid</a>
                <a href="{{ url('/admin/results') }}">Hasil</a>
                <a href="{{ url('/admin/habits') }}">Kebiasaan</a>
                <form action="{{ url('/admin/logout') }}" method="POST" style="display:inline;">
                    @csrf
                    <button type="submit" style="background:none; border:none; color:#ef4444; font-weight:600; cursor:pointer;">Keluar</button>
                </form>
            @endauth
        </nav>
    </header>

    <main class="admin-container">
        @if (session('success'))
            <div style="background-color: #ecfdf5; color: #065f46; padding: 12px 16px; border-radius: 8px; margin-bottom: 16px;">
                {{ session('success') }}
            </div>
        @endif
        @if (session('error'))
            <div style="background-color: #fef2f2; color: #991b1b; padding: 12px 16px; border-radius: 8px; margin-bottom: 16px;">
                {{ session('error') }}
            </div>
        @endif

        @yield('content')
    </main>

    @stack('scripts')
</body>
</html>
