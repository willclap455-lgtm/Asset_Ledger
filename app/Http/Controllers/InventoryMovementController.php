<?php

namespace App\Http\Controllers;

use App\Http\Requests\InventoryMovementRequest;
use App\Models\Client;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\Location;
use App\Services\InventoryMovementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InventoryMovementController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', InventoryMovement::class);

        $movements = InventoryMovement::query()
            ->with(['user', 'client', 'fromLocation', 'toLocation'])
            ->withCount('lines')
            ->when($request->filled('type'), fn ($query) => $query->where('movement_type', $request->string('type')))
            ->when($request->filled('client_id'), fn ($query) => $query->where('client_id', $request->string('client_id')))
            ->latest('occurred_at')
            ->paginate(25)
            ->withQueryString();

        return view('movements.index', [
            'movements' => $movements,
            'types' => InventoryMovement::typeOptions(),
            'clients' => Client::orderBy('name')->get(),
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', InventoryMovement::class);

        return view('movements.form', [
            'types' => InventoryMovement::typeOptions(),
            'clients' => Client::orderBy('name')->get(),
            'locations' => Location::with('client')->orderBy('type')->orderBy('name')->get(),
            'items' => InventoryItem::with(['client', 'location'])->orderBy('asset_tag')->limit(500)->get(),
            'selectedItemIds' => collect((array) $request->input('item_ids'))->filter()->all(),
        ]);
    }

    public function store(InventoryMovementRequest $request, InventoryMovementService $movements): RedirectResponse
    {
        $movement = $movements->recordMovement($request->validated(), $request->user());

        return redirect()->route('movements.show', $movement)->with('status', 'Inventory movement recorded.');
    }

    public function show(InventoryMovement $movement): View
    {
        $this->authorize('view', $movement);
        $movement->load(['user', 'client', 'fromLocation', 'toLocation', 'lines.inventoryItem', 'lines.previousLocation', 'lines.newLocation', 'documents.user']);

        return view('movements.show', ['movement' => $movement]);
    }
}
