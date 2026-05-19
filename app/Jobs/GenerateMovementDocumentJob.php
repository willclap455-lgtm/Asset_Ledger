<?php

namespace App\Jobs;

use App\Models\InventoryMovement;
use App\Models\User;
use App\Services\MovementDocumentService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class GenerateMovementDocumentJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public InventoryMovement $movement,
        public User $user,
        public string $templateKey = 'standard_movement'
    ) {
    }

    public function handle(MovementDocumentService $documents): void
    {
        $documents->generate($this->movement, $this->user, $this->templateKey);
    }
}
