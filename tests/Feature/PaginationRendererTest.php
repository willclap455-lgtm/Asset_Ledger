<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PaginationRendererTest extends TestCase
{
    use RefreshDatabase;

    public function test_paginated_bootstrap_pages_do_not_render_svg_arrow_controls(): void
    {
        $user = User::factory()->create();
        Role::findOrCreate('Administrator');
        $user->assignRole('Administrator');

        foreach (range(1, 30) as $index) {
            Client::create([
                'name' => sprintf('Client %02d', $index),
                'code' => sprintf('C%02d', $index),
                'status' => 'active',
            ]);
        }

        $this->actingAs($user)
            ->get(route('clients.index'))
            ->assertOk()
            ->assertSee('pagination')
            ->assertSee('page-item')
            ->assertDontSee('<svg', false);
    }
}
