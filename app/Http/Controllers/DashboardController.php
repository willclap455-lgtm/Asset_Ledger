<?php

namespace App\Http\Controllers;

use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\Repair;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('dashboard', [
            'statusCounts' => InventoryItem::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
            'typeCounts' => InventoryItem::query()->selectRaw('item_type, count(*) as total')->groupBy('item_type')->pluck('total', 'item_type'),
            'recentMovements' => InventoryMovement::with(['user', 'client', 'toLocation'])->latest('occurred_at')->limit(8)->get(),
            'openRepairs' => Repair::with(['inventoryItem', 'technician'])->whereIn('status', ['open', 'in_progress', 'waiting_parts'])->latest('opened_at')->limit(8)->get(),
        ]);
    }
}
