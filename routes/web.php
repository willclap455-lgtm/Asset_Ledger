<?php

use App\Http\Controllers\ClientController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GeneratedDocumentController;
use App\Http\Controllers\InventoryItemController;
use App\Http\Controllers\InventoryMovementController;
use App\Http\Controllers\InventoryNoteController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RepairController;
use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::resource('clients', ClientController::class);
    Route::resource('locations', LocationController::class)->except(['show']);
    Route::resource('inventory-items', InventoryItemController::class);
    Route::resource('movements', InventoryMovementController::class)->only(['index', 'create', 'store', 'show']);

    Route::post('inventory-items/{inventory_item}/notes', [InventoryNoteController::class, 'store'])->name('inventory-items.notes.store');
    Route::post('inventory-items/{inventory_item}/repairs', [RepairController::class, 'store'])->name('inventory-items.repairs.store');
    Route::post('movements/{movement}/documents', [GeneratedDocumentController::class, 'store'])->name('movements.documents.store');
    Route::get('generated-documents/{generatedDocument}/download', [GeneratedDocumentController::class, 'download'])->name('generated-documents.download');

    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/inventory.csv', [ReportController::class, 'exportInventory'])->name('reports.inventory-export');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
