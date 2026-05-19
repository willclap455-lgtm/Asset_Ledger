<?php

namespace App\Http\Controllers;

use App\Http\Requests\InventoryNoteRequest;
use App\Models\InventoryItem;
use Illuminate\Http\RedirectResponse;

class InventoryNoteController extends Controller
{
    public function store(InventoryNoteRequest $request, InventoryItem $inventoryItem): RedirectResponse
    {
        $inventoryItem->notes()->create($request->validated() + ['user_id' => $request->user()->id]);

        activity('inventory_notes')
            ->performedOn($inventoryItem)
            ->causedBy($request->user())
            ->event('note_added')
            ->log('Inventory note added');

        return back()->with('status', 'Note added.');
    }
}
