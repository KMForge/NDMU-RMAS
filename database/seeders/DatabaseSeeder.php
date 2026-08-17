<?php

namespace Database\Seeders;

use App\Modules\OfficialForms\Actions\SyncOfficialFormCatalog;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            AcademicStructureSeeder::class,
            AllUsersSeeder::class,
            ResearchProgressSeeder::class,
        ]);

        app(SyncOfficialFormCatalog::class)->handle();
    }
}
