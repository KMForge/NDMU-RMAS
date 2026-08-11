<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            $canonical = [
                ['code' => 'research-title-presentation', 'name' => 'Research Title Presentation', 'sequence' => 1, 'weight' => 1],
                ['code' => 'formulation-research-proposal', 'name' => 'Formulation of Research Proposal', 'sequence' => 2, 'weight' => 1],
                ['code' => 'research-proposal-defense', 'name' => 'Research Proposal Defense', 'sequence' => 3, 'weight' => 1],
                ['code' => 'revision-research-proposal', 'name' => 'Revision of Research Proposal Paper', 'sequence' => 4, 'weight' => 1],
                ['code' => 'validation-survey-instrument', 'name' => 'Validation of Survey Instrument', 'sequence' => 5, 'weight' => 1],
                ['code' => 'submission-complete-research-proposal', 'name' => 'Submission of the Complete Research Proposal Paper & Others', 'sequence' => 6, 'weight' => 1],
                ['code' => 'data-gathering', 'name' => 'Data Gathering', 'sequence' => 7, 'weight' => 1],
                ['code' => 'data-processing', 'name' => 'Data Processing', 'sequence' => 8, 'weight' => 1],
                ['code' => 'report-writing', 'name' => 'Report Writing', 'sequence' => 9, 'weight' => 1],
                ['code' => 'research-final-oral-defense', 'name' => 'Research Final/Oral Defense', 'sequence' => 10, 'weight' => 1],
                ['code' => 'revision-whole-research-paper', 'name' => 'Revision of the Whole Research Paper', 'sequence' => 11, 'weight' => 1],
                ['code' => 'language-technical-editing', 'name' => 'Language and Technical Editing', 'sequence' => 12, 'weight' => 1],
                ['code' => 'submission-final-research-paper', 'name' => 'Submission of the Final Copy of the Research Paper', 'sequence' => 13, 'weight' => 1],
            ];

            $canonicalCodes = array_column($canonical, 'code');
            $now = now();

            // 1. Mark obsolete definitions inactive & move sequences to 1000 + id to prevent unique constraint conflicts
            DB::statement('UPDATE milestone_definitions SET sequence = 1000 + id, is_active = false');

            // 2. Upsert canonical active definitions snapshot
            foreach ($canonical as $item) {
                $existing = DB::table('milestone_definitions')
                    ->where('code', $item['code'])
                    ->first();

                if ($existing) {
                    DB::table('milestone_definitions')
                        ->where('id', $existing->id)
                        ->update([
                            'name' => $item['name'],
                            'sequence' => $item['sequence'],
                            'weight' => $item['weight'],
                            'is_active' => true,
                            'updated_at' => $now,
                        ]);
                } else {
                    DB::table('milestone_definitions')->insert([
                        'code' => $item['code'],
                        'name' => $item['name'],
                        'sequence' => $item['sequence'],
                        'weight' => $item['weight'],
                        'is_active' => true,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }

            // 3. Process legacy group milestones under inactive definitions
            $inactiveIds = DB::table('milestone_definitions')
                ->where('is_active', false)
                ->pluck('id');

            if ($inactiveIds->isNotEmpty()) {
                $legacyMilestones = DB::table('research_group_milestones')
                    ->whereIn('milestone_definition_id', $inactiveIds)
                    ->get();

                foreach ($legacyMilestones as $legacy) {
                    $hasEvents = DB::table('research_group_milestone_events')
                        ->where('research_group_milestone_id', $legacy->id)
                        ->exists();

                    $hasEvidence = DB::table('milestone_evidences')
                        ->where('research_group_milestone_id', $legacy->id)
                        ->exists();

                    $isPurePlaceholder = $legacy->status === 'pending'
                        && $legacy->started_at === null
                        && $legacy->completed_at === null
                        && $legacy->due_at === null
                        && ($legacy->remarks === null || trim((string) $legacy->remarks) === '')
                        && $legacy->not_applicable_reason === null
                        && ! $hasEvents
                        && ! $hasEvidence;

                    if ($isPurePlaceholder) {
                        DB::table('research_group_milestones')
                            ->where('id', $legacy->id)
                            ->delete();
                    }
                }
            }

            // 4. Ensure active research groups have all canonical active group milestones
            $activeDefinitions = DB::table('milestone_definitions')
                ->where('is_active', true)
                ->get();

            $activeGroupIds = DB::table('research_class_groups')->pluck('id');

            foreach ($activeGroupIds as $groupId) {
                foreach ($activeDefinitions as $definition) {
                    $exists = DB::table('research_group_milestones')
                        ->where('research_class_group_id', $groupId)
                        ->where('milestone_definition_id', $definition->id)
                        ->exists();

                    if (! $exists) {
                        DB::table('research_group_milestones')->insert([
                            'research_class_group_id' => $groupId,
                            'milestone_definition_id' => $definition->id,
                            'status' => 'pending',
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]);
                    }
                }
            }
        });
    }

    public function down(): void
    {
        DB::table('milestone_definitions')->update(['is_active' => true]);
    }
};
