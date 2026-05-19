<?php

namespace App\Http\Controllers;

use App\Http\Requests\RepairRequest;
use App\Models\InventoryItem;
use Illuminate\Http\RedirectResponse;

class RepairController extends Controller
{
    public function store(RepairRequest $request, InventoryItem $inventoryItem): RedirectResponse
    {
        $inventoryItem->repairs()->create($request->validated() + [
            'technician_id' => $request->user()->id,
            'opened_at' => $request->input('opened_at') ?: now(),
        ]);

        if ($request->input('status') !== 'completed') {
            $inventoryItem->update(['status' => InventoryItem::STATUS_IN_REPAIR]);
        }

        return back()->with('status', 'Repair record added.');
    }
}
