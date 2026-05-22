<?php

namespace App\Http\Controllers;

use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\Repair;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    use AuthorizesRequests;

    public function index(): View
    {
        $this->authorize('viewAny', InventoryItem::class);

        return view('reports.index', [
            'byClient' => InventoryItem::query()->leftJoin('clients', 'clients.id', '=', 'inventory_items.client_id')->selectRaw('coalesce(clients.name, ?) as label, count(*) as total', ['Internal / Unassigned'])->groupBy('label')->orderByDesc('total')->get(),
            'byLocation' => InventoryItem::query()->leftJoin('locations', 'locations.id', '=', 'inventory_items.location_id')->selectRaw('coalesce(locations.name, ?) as label, count(*) as total', ['No Location'])->groupBy('label')->orderByDesc('total')->get(),
            'byCarrier' => DB::query()->fromSub($this->carrierUnion(), 'carriers')->selectRaw('carrier, count(*) as total')->whereNotNull('carrier')->groupBy('carrier')->orderByDesc('total')->get(),
            'outdatedAndroid' => InventoryItem::with('phone')->whereHas('phone', fn ($query) => $query->whereNotNull('android_version')->where('android_version', '<', '10'))->orderBy('asset_tag')->limit(50)->get(),
            'printerRepairFrequency' => Repair::query()->join('inventory_items', 'inventory_items.id', '=', 'repairs.inventory_item_id')->where('inventory_items.item_type', InventoryItem::TYPE_PRINTER)->selectRaw('inventory_items.asset_tag, count(*) as repair_count')->groupBy('inventory_items.asset_tag')->orderByDesc('repair_count')->limit(20)->get(),
            'recentMovementCount' => InventoryMovement::where('occurred_at', '>=', now()->subDays(30))->count(),
        ]);
    }

    public function exportInventory(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', InventoryItem::class);

        $filename = 'inventory-export-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Asset ID', 'Type', 'Status', 'Client', 'Location', 'Manufacturer', 'Model', 'Serial', 'Received', 'Deployed']);
            InventoryItem::with(['client', 'location'])->orderBy('asset_tag')->chunk(500, function ($items) use ($handle): void {
                foreach ($items as $item) {
                    fputcsv($handle, [
                        $item->asset_tag,
                        $item->item_type,
                        $item->status,
                        $item->client?->name,
                        $item->location?->label(),
                        $item->manufacturer,
                        $item->model,
                        $item->serial_number,
                        optional($item->received_at)->toDateString(),
                        optional($item->deployed_at)->toDateString(),
                    ]);
                }
            });
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    private function carrierUnion()
    {
        return DB::table('phones')->select('carrier')
            ->unionAll(DB::table('modems')->select('carrier'))
            ->unionAll(DB::table('sim_cards')->select('carrier'));
    }
}
