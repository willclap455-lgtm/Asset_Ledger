<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Location;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class RolesAndReferenceDataSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['Administrator', 'Inventory Staff', 'Repair Technician', 'Read-Only User'] as $role) {
            Role::findOrCreate($role);
        }

        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            ['name' => 'Operations Administrator', 'password' => Hash::make('password')]
        );
        $admin->assignRole('Administrator');

        foreach ([
            ['name' => 'Home Office', 'code' => 'HOME', 'type' => 'internal'],
            ['name' => 'Warehouse', 'code' => 'WH', 'type' => 'internal'],
            ['name' => 'Repair Bench', 'code' => 'REPAIR', 'type' => 'internal'],
            ['name' => 'Storage', 'code' => 'STORAGE', 'type' => 'internal'],
        ] as $location) {
            Location::firstOrCreate(['code' => $location['code']], $location + ['is_active' => true]);
        }

        $client = Client::firstOrCreate(
            ['code' => 'DEMO'],
            ['name' => 'Demo Parking Client', 'status' => 'active', 'primary_contact_email' => 'ops@example.com']
        );

        Location::firstOrCreate(
            ['code' => 'DEMO-LOT-A'],
            ['client_id' => $client->id, 'type' => 'client', 'name' => 'Demo Lot A', 'is_active' => true]
        );
    }
}
