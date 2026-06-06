<!DOCTYPE html>
<html lang="en-US">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Admin' }} · {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/css/admin.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/file-upload.css') }}">
    @stack('head')
</head>
<body class="admin">
    <a class="sr-only sr-only-focusable" href="#admin-main">Skip to content</a>

    <div class="admin-shell" data-admin-shell>
        @include('admin.partials.sidebar')

        <button type="button" class="admin-backdrop" data-admin-backdrop aria-hidden="true" tabindex="-1"></button>

        <div class="admin-main-wrap">
            @include('admin.partials.topbar')

            @if(session('success'))
                <p class="admin-banner admin-banner--ok" role="status">{{ session('success') }}</p>
            @endif
            @if(session('error'))
                <p class="admin-banner admin-banner--err" role="alert">{{ session('error') }}</p>
            @endif

            <main id="admin-main" class="admin-main" tabindex="-1">
                <header class="module-head">
                    @include('admin.partials.breadcrumb')
                    <div class="module-head__row">
                        <h1 class="module-head__title">{{ $title ?? '' }}</h1>
                        @hasSection('module-actions')
                            <div class="module-head__actions">@yield('module-actions')</div>
                        @endif
                    </div>
                    @hasSection('module-meta')
                        <p class="module-head__meta">@yield('module-meta')</p>
                    @endif
                </header>

                <section class="module-card">
                    @yield('content')
                </section>
            </main>
        </div>
    </div>

    @stack('scripts')
    <script src="{{ asset('assets/js/file-upload.js') }}" defer></script>
    <script src="{{ asset('assets/js/admin.js') }}" defer></script>
</body>
</html>
