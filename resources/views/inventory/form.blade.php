@extends('layouts.app')

@section('content')
@php($editing = $item->exists)
<div class="d-flex justify-content-between align-items-center mb-3">
    <div><div class="page-title">{{ $editing ? 'Edit Asset' : 'Add Asset' }}</div><div class="text-muted">Capture core asset data plus device-specific identifiers and assignments.</div></div>
    @if($editing)<a class="btn btn-outline-secondary btn-sm" href="{{ route('inventory-items.show', $item) }}">Back to Asset</a>@endif
</div>
<form method="POST" action="{{ $editing ? route('inventory-items.update', $item) : route('inventory-items.store') }}">
    @csrf @if($editing) @method('PUT') @endif
    <div class="row g-3">
        <div class="col-xl-8">
            <div class="card mb-3"><div class="card-header fw-semibold">Core Asset</div><div class="card-body row g-3">
                <div class="col-md-3"><label class="form-label">Asset ID</label><input required name="asset_tag" class="form-control" value="{{ old('asset_tag', $item->asset_tag) }}"></div>
                <div class="col-md-3"><label class="form-label">Type</label><select required name="item_type" class="form-select">@foreach($types as $key=>$label)<option value="{{ $key }}" @selected(old('item_type', $item->item_type)===$key)>{{ $label }}</option>@endforeach</select></div>
                <div class="col-md-3"><label class="form-label">Status</label><select required name="status" class="form-select">@foreach($statuses as $key=>$label)<option value="{{ $key }}" @selected(old('status', $item->status ?: 'received')===$key)>{{ $label }}</option>@endforeach</select></div>
                <div class="col-md-3"><label class="form-label">Category</label><input name="category" class="form-control" value="{{ old('category', $item->category) }}"></div>
                <div class="col-md-4"><label class="form-label">Manufacturer</label><input name="manufacturer" class="form-control" value="{{ old('manufacturer', $item->manufacturer) }}"></div>
                <div class="col-md-4"><label class="form-label">Model</label><input name="model" class="form-control" value="{{ old('model', $item->model) }}"></div>
                <div class="col-md-4"><label class="form-label">Serial Number</label><input name="serial_number" class="form-control" value="{{ old('serial_number', $item->serial_number) }}"></div>
                <div class="col-md-6"><label class="form-label">Display Name</label><input name="name" class="form-control" value="{{ old('name', $item->name) }}"></div>
                <div class="col-md-3"><label class="form-label">Condition</label><input name="condition" class="form-control" value="{{ old('condition', $item->condition) }}"></div>
                <div class="col-md-3"><label class="form-label">Received Date</label><input type="date" name="received_at" class="form-control" value="{{ old('received_at', optional($item->received_at)->toDateString()) }}"></div>
                <div class="col-md-6"><label class="form-label">Client</label><select name="client_id" class="form-select"><option value="">Internal / Unassigned</option>@foreach($clients as $client)<option value="{{ $client->id }}" @selected(old('client_id', $item->client_id)===$client->id)>{{ $client->name }}</option>@endforeach</select></div>
                <div class="col-md-6"><label class="form-label">Location</label><select name="location_id" class="form-select"><option value="">No location</option>@foreach($locations as $location)<option value="{{ $location->id }}" @selected(old('location_id', $item->location_id)===$location->id)>{{ $location->label() }}</option>@endforeach</select></div>
                <div class="col-12"><label class="form-label">Description</label><textarea name="description" rows="2" class="form-control">{{ old('description', $item->description) }}</textarea></div>
                <div class="col-12"><label class="form-label">Notes</label><textarea name="notes" rows="3" class="form-control">{{ old('notes', $item->notes) }}</textarea></div>
            </div></div>
            <div class="card"><div class="card-header fw-semibold">Typed Details</div><div class="card-body">
                <div class="alert alert-info py-2 small">Fill the section matching the selected asset type. These fields preserve phone, SIM, carrier, modem, and printer relationships.</div>
                <div class="row g-3">
                    <div class="col-md-6"><h6>Phone / Modem</h6><label class="form-label">Phone Number</label><input name="phone_number" class="form-control mb-2" value="{{ old('phone_number', $item->phone?->phone_number) }}"><label class="form-label">IMEI</label><input name="imei" class="form-control mb-2" value="{{ old('imei', $item->phone?->imei ?? $item->modem?->imei) }}"><label class="form-label">Carrier</label><input name="carrier" class="form-control mb-2" value="{{ old('carrier', $item->phone?->carrier ?? $item->modem?->carrier ?? $item->simCard?->carrier) }}"><label class="form-label">Android Version</label><input name="android_version" class="form-control mb-2" value="{{ old('android_version', $item->phone?->android_version) }}"><label class="form-label">Assigned SIM</label><select name="assigned_sim_card_id" class="form-select mb-2"><option value="">None</option>@foreach($simCards as $sim)<option value="{{ $sim->id }}" @selected(old('assigned_sim_card_id', $item->phone?->assigned_sim_card_id ?? $item->modem?->assigned_sim_card_id)===$sim->id)>{{ $sim->iccid }} {{ $sim->associated_phone_number ? '('.$sim->associated_phone_number.')' : '' }}</option>@endforeach</select></div>
                    <div class="col-md-6"><h6>Printer / SIM Card</h6><label class="form-label">Assigned Printer</label><select name="assigned_printer_id" class="form-select mb-2"><option value="">None</option>@foreach($printers as $printer)<option value="{{ $printer->id }}" @selected(old('assigned_printer_id', $item->phone?->assigned_printer_id)===$printer->id)>{{ $printer->inventoryItem?->asset_tag }} {{ $printer->inventoryItem?->serial_number }}</option>@endforeach</select><label class="form-label">Printer ID</label><input name="printer_identifier" class="form-control mb-2" value="{{ old('printer_identifier', $item->printer?->printer_identifier) }}"><label class="form-label">Printer Color</label><input name="printer_color" class="form-control mb-2" value="{{ old('printer_color', $item->printer?->printer_color) }}"><label class="form-label">Firmware Version</label><input name="firmware_version" class="form-control mb-2" value="{{ old('firmware_version', $item->printer?->firmware_version) }}"><label class="form-label">ICCID</label><input name="iccid" class="form-control mb-2" value="{{ old('iccid', $item->simCard?->iccid) }}"><label class="form-label">IMSI</label><input name="imsi" class="form-control mb-2" value="{{ old('imsi', $item->simCard?->imsi) }}"><label class="form-label">Associated Phone Number</label><input name="associated_phone_number" class="form-control mb-2" value="{{ old('associated_phone_number', $item->simCard?->associated_phone_number) }}"><label class="form-label">Activation Status</label><input name="activation_status" class="form-control" value="{{ old('activation_status', $item->simCard?->activation_status) }}"></div>
                </div>
            </div></div>
        </div>
        <div class="col-xl-4"><div class="card sticky-top" style="top:1rem"><div class="card-header fw-semibold">Save</div><div class="card-body"><p class="text-muted small">Current state belongs on the asset. Historical changes belong in movements, notes, repairs, and audit logs.</p><button class="btn btn-primary w-100">{{ $editing ? 'Update Asset' : 'Create Asset' }}</button></div></div></div>
    </div>
</form>
@endsection
