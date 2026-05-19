@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3"><div><div class="page-title">Record Movement</div><div class="text-muted">Bulk movement records update current assignment while preserving immutable history snapshots.</div></div><a class="btn btn-outline-secondary btn-sm" href="{{ route('movements.index') }}">Movement History</a></div>
<form method="POST" action="{{ route('movements.store') }}">@csrf
<div class="row g-3">
    <div class="col-xl-4"><div class="card"><div class="card-header fw-semibold">Movement Details</div><div class="card-body row g-3">
        <div class="col-12"><label class="form-label">Movement Type</label><select required name="movement_type" class="form-select">@foreach($types as $key=>$label)<option value="{{ $key }}" @selected(old('movement_type')===$key)>{{ $label }}</option>@endforeach</select></div>
        <div class="col-12"><label class="form-label">Occurred At</label><input type="datetime-local" name="occurred_at" class="form-control" value="{{ old('occurred_at', now()->format('Y-m-d\TH:i')) }}"></div>
        <div class="col-12"><label class="form-label">Client</label><select name="client_id" class="form-select"><option value="">Internal / Unassigned</option>@foreach($clients as $client)<option value="{{ $client->id }}" @selected(old('client_id')===$client->id)>{{ $client->name }}</option>@endforeach</select></div>
        <div class="col-12"><label class="form-label">From Location</label><select name="from_location_id" class="form-select"><option value="">N/A or Current</option>@foreach($locations as $location)<option value="{{ $location->id }}" @selected(old('from_location_id')===$location->id)>{{ $location->label() }}</option>@endforeach</select></div>
        <div class="col-12"><label class="form-label">To Location</label><select name="to_location_id" class="form-select"><option value="">No location change</option>@foreach($locations as $location)<option value="{{ $location->id }}" @selected(old('to_location_id')===$location->id)>{{ $location->label() }}</option>@endforeach</select></div>
        <div class="col-12"><label class="form-label">Override New Status</label><input name="new_status" class="form-control" placeholder="Optional"></div>
        <div class="col-12"><label class="form-label">Notes</label><textarea name="notes" rows="4" class="form-control">{{ old('notes') }}</textarea></div>
        <div class="col-12"><button class="btn btn-primary w-100">Record Movement</button></div>
    </div></div></div>
    <div class="col-xl-8"><div class="card"><div class="card-header fw-semibold">Select Assets</div><div class="table-responsive" style="max-height:650px"><table class="table table-sm table-hover mb-0"><thead class="sticky-top bg-light"><tr><th></th><th>Asset</th><th>Type</th><th>Status</th><th>Client</th><th>Location</th></tr></thead><tbody>@foreach($items as $asset)<tr><td><input type="checkbox" name="item_ids[]" value="{{ $asset->id }}" @checked(in_array($asset->id, old('item_ids', $selectedItemIds), true))></td><td><span class="text-mono fw-semibold">{{ $asset->asset_tag }}</span><div class="small text-muted">{{ $asset->manufacturer }} {{ $asset->model }}</div></td><td>{{ App\Models\InventoryItem::typeOptions()[$asset->item_type] ?? $asset->item_type }}</td><td>{{ App\Models\InventoryItem::statusOptions()[$asset->status] ?? $asset->status }}</td><td>{{ $asset->client?->name ?? 'Internal' }}</td><td>{{ $asset->location?->label() ?? 'Unassigned' }}</td></tr>@endforeach</tbody></table></div></div></div>
</div></form>
@endsection
