<?php

namespace Database\Seeders;

use App\Models\ResearchClassGroup;
use App\Modules\ResearchProgress\Actions\InitializeGroupMilestones;
use Illuminate\Database\Seeder;

class ResearchProgressSeeder extends Seeder
{
    public function run(): void
    {
        $initialize = app(InitializeGroupMilestones::class);

        ResearchClassGroup::query()
            ->where('status', 'active')
            ->whereNull('disbanded_at')
            ->orderBy('id')
            ->eachById(fn (ResearchClassGroup $group) => $initialize->execute($group));
    }
}
