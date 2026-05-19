<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Clancy Asset Ledger') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container min-vh-100 d-flex align-items-center justify-content-center">
        <div class="card shadow-sm" style="max-width: 430px; width: 100%;">
            <div class="card-header bg-dark text-white fw-bold">Clancy Asset Ledger</div>
            <div class="card-body p-4">{{ $slot }}</div>
        </div>
    </div>
</body>
</html>
