<?php

namespace App\Http\Controllers;

use App\Models\GeneratedDocument;
use App\Models\InventoryMovement;
use App\Services\MovementDocumentService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class GeneratedDocumentController extends Controller
{
    use AuthorizesRequests;

    public function store(InventoryMovement $movement, MovementDocumentService $documents): RedirectResponse
    {
        $this->authorize('view', $movement);
        $documents->generate($movement, request()->user());

        return back()->with('status', 'Movement document generated.');
    }

    public function download(GeneratedDocument $generatedDocument): BinaryFileResponse
    {
        $this->authorize('view', $generatedDocument->movement);

        return response()->download(storage_path('app/'.$generatedDocument->file_path), $generatedDocument->original_filename);
    }
}
