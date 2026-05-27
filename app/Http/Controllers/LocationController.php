<?php

namespace App\Http\Controllers;

use App\Http\Requests\LocationRequest;
use App\Models\Client;
use App\Models\Location;
use App\Services\ClientLocationImportService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LocationController extends Controller
{
    use AuthorizesRequests;

    public function index(): View
    {
        $this->authorize('viewAny', Location::class);

        return view('locations.index', [
            'locations' => Location::with('client')->withCount('inventoryItems')->orderBy('type')->orderBy('name')->paginate(30),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Location::class);

        return view('locations.form', ['location' => new Location(['type' => 'internal', 'is_active' => true]), 'clients' => Client::orderBy('name')->get()]);
    }

    public function store(LocationRequest $request): RedirectResponse
    {
        $location = Location::create($request->validated() + ['is_active' => $request->boolean('is_active', true)]);

        return redirect()->route('locations.index')->with('status', 'Location created.');
    }

    public function import(Request $request, ClientLocationImportService $imports): RedirectResponse
    {
        $this->authorize('create', Location::class);

        $validated = $request->validate([
            'csv_file' => ['required', 'file', 'mimes:csv,txt', 'max:10240'],
        ]);

        $totals = $imports->importLocations($validated['csv_file']);

        return redirect()
            ->route('locations.index')
            ->with('status', "Location import complete: {$totals['created']} created, {$totals['updated']} updated.");
    }

    public function edit(Location $location): View
    {
        $this->authorize('update', $location);

        return view('locations.form', ['location' => $location, 'clients' => Client::orderBy('name')->get()]);
    }

    public function update(LocationRequest $request, Location $location): RedirectResponse
    {
        $location->update($request->validated() + ['is_active' => $request->boolean('is_active')]);

        return redirect()->route('locations.index')->with('status', 'Location updated.');
    }

    public function destroy(Location $location): RedirectResponse
    {
        $this->authorize('delete', $location);

        $name = $location->name;
        $location->delete();

        return redirect()->route('locations.index')->with('status', "Location {$name} deleted.");
    }
}
