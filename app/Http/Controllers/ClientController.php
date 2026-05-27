<?php

namespace App\Http\Controllers;

use App\Http\Requests\ClientRequest;
use App\Models\Client;
use App\Services\ClientLocationImportService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClientController extends Controller
{
    use AuthorizesRequests;

    public function index(): View
    {
        $this->authorize('viewAny', Client::class);

        return view('clients.index', [
            'clients' => Client::withCount(['locations', 'inventoryItems'])->orderBy('name')->paginate(25),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Client::class);

        return view('clients.form', ['client' => new Client]);
    }

    public function store(ClientRequest $request): RedirectResponse
    {
        $client = Client::create($request->validated());

        return redirect()->route('clients.show', $client)->with('status', 'Client created.');
    }

    public function import(Request $request, ClientLocationImportService $imports): RedirectResponse
    {
        $this->authorize('create', Client::class);

        $validated = $request->validate([
            'csv_file' => ['required', 'file', 'mimes:csv,txt', 'max:10240'],
        ]);

        $totals = $imports->importClients($validated['csv_file']);

        return redirect()
            ->route('clients.index')
            ->with('status', "Client import complete: {$totals['created']} created, {$totals['updated']} updated.");
    }

    public function show(Client $client): View
    {
        $this->authorize('view', $client);
        $client->load(['locations', 'inventoryItems.location', 'inventoryItems.phone.assignedSimCard', 'inventoryItems.modem.assignedSimCard', 'inventoryItems.simCard']);

        return view('clients.show', ['client' => $client]);
    }

    public function edit(Client $client): View
    {
        $this->authorize('update', $client);

        return view('clients.form', ['client' => $client]);
    }

    public function update(ClientRequest $request, Client $client): RedirectResponse
    {
        $client->update($request->validated());

        return redirect()->route('clients.show', $client)->with('status', 'Client updated.');
    }

    public function destroy(Client $client): RedirectResponse
    {
        $this->authorize('delete', $client);

        $name = $client->name;
        $client->delete();

        return redirect()->route('clients.index')->with('status', "Client {$name} deleted.");
    }
}
