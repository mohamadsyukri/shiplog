<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

final class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        // One permission per guarded route, straight from the route list (no hand-kept list to drift).
        Artisan::call('permissions:sync');

        // Nothing is granted to the User role: the board is open to every signed-in user, and the
        // import history and upload are granted on Administration > Roles.
    }
}
