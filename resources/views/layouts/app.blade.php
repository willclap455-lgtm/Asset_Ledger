<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Clancy Asset Ledger') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f4f6f8; font-size: .92rem; }
        .navbar-brand { font-weight: 700; letter-spacing: .02em; }
        .table-sm td, .table-sm th { padding: .42rem .5rem; vertical-align: middle; }
        .card { border-color: #d8dee4; box-shadow: 0 .05rem .15rem rgba(0,0,0,.03); }
        .badge-status { min-width: 5.8rem; }
        .form-label { font-weight: 600; color: #344054; }
        .page-title { font-size: 1.35rem; font-weight: 700; }
        .text-mono { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace; }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark border-bottom border-secondary">
    <div class="container-fluid">
        <a class="navbar-brand" href="{{ route('dashboard') }}">Clancy Asset Ledger</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav"><span class="navbar-toggler-icon"></span></button>
        <div class="collapse navbar-collapse" id="mainNav">
            @auth
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item"><a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link {{ request()->routeIs('inventory-items.*') ? 'active' : '' }}" href="{{ route('inventory-items.index') }}">Inventory</a></li>
                    <li class="nav-item"><a class="nav-link {{ request()->routeIs('movements.*') ? 'active' : '' }}" href="{{ route('movements.index') }}">Movements</a></li>
                    <li class="nav-item"><a class="nav-link {{ request()->routeIs('clients.*') ? 'active' : '' }}" href="{{ route('clients.index') }}">Clients</a></li>
                    <li class="nav-item"><a class="nav-link {{ request()->routeIs('locations.*') ? 'active' : '' }}" href="{{ route('locations.index') }}">Locations</a></li>
                    <li class="nav-item"><a class="nav-link {{ request()->routeIs('reports.*') ? 'active' : '' }}" href="{{ route('reports.index') }}">Reports</a></li>
                </ul>
                <div class="d-flex align-items-center gap-2 text-white-50 small">
                    <span>{{ auth()->user()->name }}</span>
                    <form method="POST" action="{{ route('logout') }}">@csrf <button class="btn btn-outline-light btn-sm">Sign out</button></form>
                </div>
            @else
                <ul class="navbar-nav ms-auto"><li class="nav-item"><a class="nav-link" href="{{ route('login') }}">Sign in</a></li></ul>
            @endauth
        </div>
    </div>
</nav>
<main class="container-fluid py-3">
    @if (session('status'))
        <div class="alert alert-success py-2">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger py-2"><strong>Review the form:</strong><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif
    {{ $slot ?? '' }}
    @yield('content')
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
