@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div><div class="page-title">Inventory</div><div class="text-muted">Search devices, SIM cards, printers, modems, and generic equipment.</div></div>
    <a class="btn btn-primary btn-sm" href="{{ route('inventory-items.create') }}">Add Asset</a>
</div>
<form class="card card-body py-2 mb-3" method="GET">
    <div class="row g-2 align-items-end">
        <div class="col-md-4"><label class="form-label mb-1">Global search</label><input class="form-control form-control-sm" name="q" value="{{ request('q') }}" placeholder="Asset, serial, IMEI, ICCID, phone number"></div>
        <div class="col-md-2"><label class="form-label mb-1">Type</label><select class="form-select form-select-sm" name="type"><option value="">All</option>@foreach ($types as $key => $label)<option value="{{ $key }}" @selected(request('type')===$key)>{{ $label }}</option>@endforeach</select></div>
        <div class="col-md-2"><label class="form-label mb-1">Status</label><select class="form-select form-select-sm" name="status"><option value="">All</option>@foreach ($statuses as $key => $label)<option value="{{ $key }}" @selected(request('status')===$key)>{{ $label }}</option>@endforeach</select></div>
        <div class="col-md-3"><label class="form-label mb-1">Client</label><select class="form-select form-select-sm" name="client_id"><option value="">All</option>@foreach ($clients as $client)<option value="{{ $client->id }}" @selected(request('client_id')===$client->id)>{{ $client->name }}</option>@endforeach</select></div>
        <div class="col-md-1"><button class="btn btn-secondary btn-sm w-100">Filter</button></div>
    </div>
</form>
<div class="card"><div class="table-responsive"><table class="table table-sm table-hover mb-0">
    <thead><tr><th>Asset</th><th>Type</th><th>Status</th><th>Client</th><th>Location</th><th>Identifiers</th><th>Updated</th></tr></thead>
    <tbody>@forelse ($items as $item)<tr>
        <td><a class="text-mono fw-semibold" href="{{ route('inventory-items.show', $item) }}">{{ $item->asset_tag }}</a><div class="small text-muted">{{ $item->manufacturer }} {{ $item->model }}</div></td>
        <td>{{ $types[$item->item_type] ?? $item->item_type }}</td>
        <td><span class="badge badge-status bg-secondary">{{ $statuses[$item->status] ?? $item->status }}</span></td>
        <td>{{ $item->client?->name ?? 'Internal' }}</td>
        <td>{{ $item->location?->label() ?? 'Unassigned' }}</td>
        <td class="small text-mono">
            @if($item->serial_number) SN {{ $item->serial_number }}<br>@endif
            @if($item->phone?->imei) IMEI {{ $item->phone->imei }}<br>@endif
            @if($item->phone?->assignedSimCard?->associated_phone_number) SIM Phone {{ $item->phone->assignedSimCard->associated_phone_number }}<br>@endif
            @if($item->modem?->assignedSimCard?->associated_phone_number) SIM Phone {{ $item->modem->assignedSimCard->associated_phone_number }}<br>@endif
            @if($item->simCard?->iccid) ICCID {{ $item->simCard->iccid }}@if($item->simCard->activation_status)<br>Activation {{ $item->simCard->activation_status }}@endif @endif
        </td>
        <td>{{ $item->updated_at->format('m/d/Y') }}</td>
    </tr>@empty<tr><td colspan="7" class="text-muted">No inventory found.</td></tr>@endforelse</tbody>
</table></div><div class="card-footer py-2">{{ $items->links() }}</div></div>
@endsection
