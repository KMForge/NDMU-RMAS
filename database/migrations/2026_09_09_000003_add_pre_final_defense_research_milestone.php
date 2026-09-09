<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            $now = now();
            $shiftedCodes = [
                'research-final-oral-defense',
                'revision-whole-research-paper',
                'language-technical-editing',
                'submission-final-research-paper',
            ];

            DB::table('milestone_definitions')
                ->whereIn('code', $shiftedCodes)
                ->get(['id'])
                ->each(fn (object $definition) => DB::table('milestone_definitions')
                    ->where('id', $definition->id)
                    ->update(['sequence' => 3000 + $definition->id]));

            DB::table('milestone_definitions')->updateOrInsert(
                ['code' => 'research-pre-final-defense'],
                [
                    'name' => 'Research Pre-Final Defense',
                    'sequence' => 10,
                    'weight' => 1,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            );

            foreach ([
                'research-final-oral-defense' => 11,
                'revision-whole-research-paper' => 12,
                'language-technical-editing' => 13,
                'submission-final-research-paper' => 14,
            ] as $code => $sequence) {
                DB::table('milestone_definitions')->where('code', $code)->update([
                    'sequence' => $sequence,
                    'updated_at' => $now,
                ]);
            }

            $definitionId = DB::table('milestone_definitions')
                ->where('code', 'research-pre-final-defense')
                ->value('id');

            $rows = DB::table('research_class_groups')->pluck('id')->map(fn (int $groupId): array => [
                'research_class_group_id' => $groupId,
                'milestone_definition_id' => $definitionId,
                'status' => 'pending',
                'created_at' => $now,
                'updated_at' => $now,
            ])->all();

            if ($rows !== []) {
                DB::table('research_group_milestones')->insertOrIgnore($rows);
            }
        });
    }

    public function down(): void
    {
        DB::transaction(function (): void {
            $definitionId = DB::table('milestone_definitions')
                ->where('code', 'research-pre-final-defense')
                ->value('id');

            if ($definitionId !== null) {
                DB::table('research_group_milestones')
                    ->where('milestone_definition_id', $definitionId)
                    ->delete();
                DB::table('milestone_definitions')->where('id', $definitionId)->delete();
            }

            $restoredSequences = [
                'research-final-oral-defense' => 10,
                'revision-whole-research-paper' => 11,
                'language-technical-editing' => 12,
                'submission-final-research-paper' => 13,
            ];

            DB::table('milestone_definitions')
                ->whereIn('code', array_keys($restoredSequences))
                ->get(['id'])
                ->each(fn (object $definition) => DB::table('milestone_definitions')
                    ->where('id', $definition->id)
                    ->update(['sequence' => 3000 + $definition->id]));

            foreach ($restoredSequences as $code => $sequence) {
                DB::table('milestone_definitions')->where('code', $code)->update([
                    'sequence' => $sequence,
                    'updated_at' => now(),
                ]);
            }
        });
    }
};
