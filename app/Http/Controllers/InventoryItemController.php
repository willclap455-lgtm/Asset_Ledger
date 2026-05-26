<?php

namespace App\Http\Controllers;

use App\Http\Requests\InventoryItemRequest;
use App\Models\Client;
use App\Models\InventoryItem;
use App\Models\Location;
use App\Models\Printer;
use App\Models\SimCard;
use App\Services\InventoryItemService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InventoryItemController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request): View
    {
        $this->authorize('viewAny', InventoryItem::class);

        $items = InventoryItem::query()
            ->with(['client', 'location', 'phone.assignedSimCard', 'printer', 'modem.assignedSimCard', 'simCard'])
            ->search($request->string('q')->toString())
            ->when($request->filled('type'), fn ($query) => $query->where('item_type', $request->string('type')))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->when($request->filled('client_id'), fn ($query) => $query->where('client_id', $request->string('client_id')))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('inventory.index', [
            'items' => $items,
            'clients' => Client::orderBy('name')->get(),
            'types' => InventoryItem::typeOptions(),
            'statuses' => InventoryItem::statusOptions(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', InventoryItem::class);

        return view('inventory.form', $this->formData(new InventoryItem));
    }

    public function store(InventoryItemRequest $request, InventoryItemService $items): RedirectResponse
    {
        $item = $items->create($request->validated());

        return redirect()->route('inventory-items.show', $item)->with('status', 'Inventory item created.');
    }

    public function show(InventoryItem $inventoryItem): View
    {
        $this->authorize('view', $inventoryItem);
        $inventoryItem->load([
            'client', 'location', 'phone.assignedSimCard.inventoryItem', 'phone.assignedPrinter.inventoryItem',
            'printer', 'modem.assignedSimCard.inventoryItem', 'simCard.assignedInventoryItem',
            'inventoryNotes.user', 'repairs.technician', 'movementLines.movement.user', 'movementLines.previousLocation', 'movementLines.newLocation',
        ]);

        return view('inventory.show', ['item' => $inventoryItem]);
    }

    public function edit(InventoryItem $inventoryItem): View
    {
        $this->authorize('update', $inventoryItem);

        return view('inventory.form', $this->formData($inventoryItem->load(['phone', 'printer', 'modem', 'simCard'])));
    }

    public function update(InventoryItemRequest $request, InventoryItem $inventoryItem, InventoryItemService $items): RedirectResponse
    {
        $item = $items->update($inventoryItem, $request->validated());

        return redirect()->route('inventory-items.show', $item)->with('status', 'Inventory item updated.');
    }

    public function destroy(InventoryItem $inventoryItem): RedirectResponse
    {
        $this->authorize('delete', $inventoryItem);

        $inventoryItem->loadCount(['movementLines', 'repairs']);

        if ($inventoryItem->movement_lines_count > 0 || $inventoryItem->repairs_count > 0) {
            return back()->withErrors('Inventory items with movement or repair history cannot be deleted. Retire the item instead to preserve the audit trail.');
        }

        try {
            $assetTag = $inventoryItem->asset_tag;
            $inventoryItem->delete();
        } catch (QueryException) {
            return back()->withErrors('This inventory item is linked to operational records and cannot be deleted.');
        }

        return redirect()->route('inventory-items.index')->with('status', "Inventory item {$assetTag} deleted.");
    }

    private function formData(InventoryItem $item): array
    {
        return [
            'item' => $item,
            'clients' => Client::orderBy('name')->get(),
            'locations' => Location::with('client')->orderBy('type')->orderBy('name')->get(),
            'types' => InventoryItem::typeOptions(),
            'statuses' => InventoryItem::statusOptions(),
            'simCards' => SimCard::with('inventoryItem')->orderBy('iccid')->get(),
            'printers' => Printer::with('inventoryItem')->get()->sortBy(fn ($printer) => $printer->inventoryItem?->asset_tag),
        ];
    }
}
