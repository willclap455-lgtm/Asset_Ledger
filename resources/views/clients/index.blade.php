@extends('layouts.app')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3"><div><div class="page-title">Clients</div><div class="text-muted">External parking clients and their operating sites.</div></div><a class="btn btn-primary btn-sm" href="{{ route('clients.create') }}">Add Client</a></div>
<form class="card card-body py-2 mb-3" method="POST" action="{{ route('clients.import') }}" enctype="multipart/form-data">@csrf
    <div class="row g-2 align-items-end">
        <div class="col-md-8"><label class="form-label mb-1">Bulk import clients (.csv)</label><input required type="file" name="csv_file" class="form-control form-control-sm" accept=".csv,text/csv"><div class="form-text">Headers: name, code, status, primary_contact_name, primary_contact_email, primary_contact_phone, notes.</div>@error('csv_file')<div class="text-danger small">{{ $message }}</div>@enderror</div>
        <div class="col-md-4"><button class="btn btn-outline-primary btn-sm w-100">Import Clients</button></div>
    </div>
</form>
<div class="card"><div class="table-responsive"><table class="table table-sm table-hover mb-0"><thead><tr><th>Name</th><th>Code</th><th>Status</th><th>Locations</th><th>Assets</th><th>Contact</th></tr></thead><tbody>@foreach($clients as $client)<tr><td><a href="{{ route('clients.show', $client) }}">{{ $client->name }}</a></td><td class="text-mono">{{ $client->code }}</td><td>{{ $client->status }}</td><td>{{ $client->locations_count }}</td><td>{{ $client->inventory_items_count }}</td><td>{{ $client->primary_contact_name }} {{ $client->primary_contact_email }}</td></tr>@endforeach</tbody></table></div><div class="card-footer py-2">{{ $clients->links() }}</div></div>
@endsection
