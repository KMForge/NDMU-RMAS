<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $invitationDefinitionIds = DB::table('official_form_definitions')
            ->whereIn('code', ['RES-027', 'RES-028', 'RES-029'])
            ->pluck('id')
            ->all();

        if (empty($invitationDefinitionIds)) {
            return;
        }

        $instances = DB::table('official_form_instances')
            ->whereIn('official_form_definition_id', $invitationDefinitionIds)
            ->where('status', 'approved')
            ->get();

        foreach ($instances as $instance) {
            $hasDeanSignature = DB::table('official_form_signatures')
                ->where('official_form_instance_id', $instance->id)
                ->where(function ($query) {
                    $query->where('actor_type', 'dean')
                        ->orWhere('academic_action', 'approve');
                })
                ->exists();

            if (! $hasDeanSignature) {
                $hasInviteeSignature = DB::table('official_form_signatures')
                    ->where('official_form_instance_id', $instance->id)
                    ->where(function ($query) {
                        $query->whereIn('actor_type', ['panelist', 'adviser', 'language_editor'])
                            ->orWhereIn('academic_action', ['respond', 'conforme']);
                    })
                    ->exists();

                $hasCoordinatorSignature = DB::table('official_form_signatures')
                    ->where('official_form_instance_id', $instance->id)
                    ->where(function ($query) {
                        $query->whereIn('actor_type', ['program_coordinator', 'program_head'])
                            ->orWhere('academic_action', 'endorse');
                    })
                    ->exists();

                $newStatus = $hasInviteeSignature
                    ? 'conformed'
                    : ($hasCoordinatorSignature ? 'endorsed' : 'submitted');

                DB::table('official_form_instances')
                    ->where('id', $instance->id)
                    ->update(['status' => $newStatus]);
            }
        }
    }

    public function down(): void
    {
        // Reversible data migration: existing incomplete statuses remain aligned with actual signatures.
    }
};
