<?php

namespace App\Http\Controllers;

use App\Http\Requests\ClientRequest;
use App\Models\Client;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ClientController extends Controller
{
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

        return view('clients.form', ['client' => new Client()]);
    }

    public function store(ClientRequest $request): RedirectResponse
    {
        $client = Client::create($request->validated());

        return redirect()->route('clients.show', $client)->with('status', 'Client created.');
    }

    public function show(Client $client): View
    {
        $this->authorize('view', $client);
        $client->load(['locations', 'inventoryItems.location']);

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
}
