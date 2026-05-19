@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div><div class="page-title"><span class="text-mono">{{ $item->asset_tag }}</span> {{ $item->name }}</div><div class="text-muted">{{ $item->typeLabel() }} - {{ $item->manufacturer }} {{ $item->model }}</div></div>
    <div class="btn-group"><a class="btn btn-outline-secondary btn-sm" href="{{ route('inventory-items.index') }}">Inventory</a><a class="btn btn-primary btn-sm" href="{{ route('inventory-items.edit', $item) }}">Edit</a><a class="btn btn-outline-primary btn-sm" href="{{ route('movements.create', ['item_ids[]' => $item->id]) }}">Move</a></div>
</div>
<div class="row g-3">
    <div class="col-xl-8">
        <div class="card mb-3"><div class="card-header fw-semibold">Current Assignment</div><div class="card-body row g-2">
            <div class="col-md-3"><div class="text-muted small">Status</div><span class="badge bg-secondary">{{ $item->statusLabel() }}</span></div>
            <div class="col-md-3"><div class="text-muted small">Client</div>{{ $item->client?->name ?? 'Internal / Unassigned' }}</div>
            <div class="col-md-3"><div class="text-muted small">Location</div>{{ $item->location?->label() ?? 'No location' }}</div>
            <div class="col-md-3"><div class="text-muted small">Serial</div><span class="text-mono">{{ $item->serial_number ?: 'N/A' }}</span></div>
        </div></div>
        <div class="card mb-3"><div class="card-header fw-semibold">Device Identifiers and Relationships</div><div class="card-body row g-3 small">
            @if($item->phone)<div class="col-md-6"><h6>Phone</h6><div>Number: <span class="text-mono">{{ $item->phone->phone_number }}</span></div><div>Carrier: {{ $item->phone->carrier }}</div><div>IMEI: <span class="text-mono">{{ $item->phone->imei }}</span></div><div>Android: {{ $item->phone->android_version }}</div><div>SIM: {{ $item->phone->assignedSimCard?->iccid ?? 'None' }}</div><div>Printer: {{ $item->phone->assignedPrinter?->inventoryItem?->asset_tag ?? 'None' }}</div></div>@endif
            @if($item->printer)<div class="col-md-6"><h6>Printer</h6><div>Printer ID: {{ $item->printer->printer_identifier }}</div><div>Color: {{ $item->printer->printer_color }}</div><div>Firmware: {{ $item->printer->firmware_version }}</div></div>@endif
            @if($item->modem)<div class="col-md-6"><h6>Modem</h6><div>IMEI: <span class="text-mono">{{ $item->modem->imei }}</span></div><div>Carrier: {{ $item->modem->carrier }}</div><div>SIM: {{ $item->modem->assignedSimCard?->iccid ?? 'None' }}</div></div>@endif
            @if($item->simCard)<div class="col-md-6"><h6>SIM Card</h6><div>ICCID: <span class="text-mono">{{ $item->simCard->iccid }}</span></div><div>IMSI: <span class="text-mono">{{ $item->simCard->imsi }}</span></div><div>Carrier: {{ $item->simCard->carrier }}</div><div>Phone #: {{ $item->simCard->associated_phone_number }}</div><div>Assigned Device: {{ $item->simCard->assignedInventoryItem?->asset_tag ?? 'None' }}</div></div>@endif
            @if(!$item->phone && !$item->printer && !$item->modem && !$item->simCard)<div class="col text-muted">No typed detail record exists for this asset.</div>@endif
        </div></div>
        <div class="card"><div class="card-header fw-semibold">Movement History</div><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>Date</th><th>Movement</th><th>From</th><th>To</th><th>Status</th><th>User</th></tr></thead><tbody>@forelse($item->movementLines as $line)<tr><td>{{ $line->movement->occurred_at->format('m/d/Y H:i') }}</td><td><a href="{{ route('movements.show', $line->movement) }}">{{ $line->movement->movement_number }}</a></td><td>{{ $line->previousLocation?->label() ?? 'N/A' }}</td><td>{{ $line->newLocation?->label() ?? 'N/A' }}</td><td>{{ $line->previous_status }} -> {{ $line->new_status }}</td><td>{{ $line->movement->user?->name }}</td></tr>@empty<tr><td colspan="6" class="text-muted">No movements recorded.</td></tr>@endforelse</tbody></table></div></div>
    </div>
    <div class="col-xl-4">
        <div class="card mb-3"><div class="card-header fw-semibold">Add Note</div><div class="card-body"><form method="POST" action="{{ route('inventory-items.notes.store', $item) }}">@csrf<input type="hidden" name="note_type" value="operational"><textarea required name="body" rows="3" class="form-control mb-2"></textarea><button class="btn btn-sm btn-primary">Add Note</button></form></div><div class="list-group list-group-flush">@foreach($item->notes as $note)<div class="list-group-item small"><div class="fw-semibold">{{ $note->user?->name }} <span class="text-muted">{{ $note->created_at->format('m/d/Y H:i') }}</span></div>{{ $note->body }}</div>@endforeach</div></div>
        <div class="card"><div class="card-header fw-semibold">Repair History</div><div class="card-body"><form method="POST" action="{{ route('inventory-items.repairs.store', $item) }}">@csrf<input type="hidden" name="status" value="open"><label class="form-label">Issue</label><textarea required name="issue_description" rows="3" class="form-control mb-2"></textarea><button class="btn btn-sm btn-warning">Open Repair</button></form></div><div class="list-group list-group-flush">@foreach($item->repairs as $repair)<div class="list-group-item small"><span class="badge bg-warning text-dark">{{ str($repair->status)->headline() }}</span> {{ $repair->opened_at?->format('m/d/Y') }}<div>{{ $repair->issue_description }}</div>@if($repair->resolution_details)<div class="text-muted">{{ $repair->resolution_details }}</div>@endif</div>@endforeach</div></div>
    </div>
</div>
@endsection
