@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <div class="page-title">Operations Dashboard</div>
        <div class="text-muted">Current inventory posture, recent movements, and repair exceptions.</div>
    </div>
    <div class="btn-group">
        <a class="btn btn-primary btn-sm" href="{{ route('inventory-items.create') }}">Receive / Add Asset</a>
        <a class="btn btn-outline-primary btn-sm" href="{{ route('movements.create') }}">Record Movement</a>
    </div>
</div>

<div class="row g-3 mb-3">
    @foreach ($statusCounts as $status => $total)
        <div class="col-6 col-md-2"><div class="card"><div class="card-body py-3"><div class="text-muted small">{{ str($status)->headline() }}</div><div class="fs-4 fw-bold">{{ $total }}</div></div></div></div>
    @endforeach
</div>

<div class="row g-3">
    <div class="col-xl-7">
        <div class="card">
            <div class="card-header fw-semibold">Recent Movements</div>
            <div class="table-responsive"><table class="table table-sm table-hover mb-0">
                <thead><tr><th>Movement</th><th>Type</th><th>Client</th><th>To</th><th>User</th><th>Date</th></tr></thead>
                <tbody>@forelse ($recentMovements as $movement)<tr>
                    <td><a href="{{ route('movements.show', $movement) }}" class="text-mono">{{ $movement->movement_number }}</a></td>
                    <td>{{ App\Models\InventoryMovement::typeOptions()[$movement->movement_type] ?? $movement->movement_type }}</td>
                    <td>{{ $movement->client?->name ?? 'Internal' }}</td>
                    <td>{{ $movement->toLocation?->label() ?? 'N/A' }}</td>
                    <td>{{ $movement->user?->name }}</td>
                    <td>{{ $movement->occurred_at?->format('m/d/Y H:i') }}</td>
                </tr>@empty<tr><td colspan="6" class="text-muted">No movements recorded.</td></tr>@endforelse</tbody>
            </table></div>
        </div>
    </div>
    <div class="col-xl-5">
        <div class="card mb-3">
            <div class="card-header fw-semibold">Inventory by Type</div>
            <div class="card-body">
                @foreach ($typeCounts as $type => $total)
                    <div class="d-flex justify-content-between border-bottom py-1"><span>{{ App\Models\InventoryItem::typeOptions()[$type] ?? $type }}</span><strong>{{ $total }}</strong></div>
                @endforeach
            </div>
        </div>
        <div class="card">
            <div class="card-header fw-semibold">Open Repairs</div>
            <div class="list-group list-group-flush">
                @forelse ($openRepairs as $repair)
                    <a class="list-group-item list-group-item-action py-2" href="{{ route('inventory-items.show', $repair->inventoryItem) }}">
                        <span class="fw-semibold text-mono">{{ $repair->inventoryItem?->asset_tag }}</span>
                        <span class="badge bg-warning text-dark ms-2">{{ str($repair->status)->headline() }}</span>
                        <div class="small text-muted text-truncate">{{ $repair->issue_description }}</div>
                    </a>
                @empty
                    <div class="list-group-item text-muted">No open repairs.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
