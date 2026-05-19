<?php

namespace App\Http\Controllers;

use App\Http\Requests\LocationRequest;
use App\Models\Client;
use App\Models\Location;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class LocationController extends Controller
{
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
}
