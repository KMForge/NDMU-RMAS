<?php

namespace App\Modules\OfficialForms\Actions;

use App\Models\AuditLog;
use App\Models\OfficialFormActorAssignment;
use App\Models\OfficialFormInstance;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ConfirmLanguageEditorAssignment
{
    public function handle(OfficialFormInstance $instance, User $editor): void
    {
        $code = strtoupper($instance->definition->code ?? '');
        if ($code !== 'RES-029') {
            throw new InvalidArgumentException("ConfirmLanguageEditorAssignment requires RES-029 instance, given {$code}.");
        }

        DB::transaction(function () use ($instance, $editor) {
            $assignment = OfficialFormActorAssignment::query()
                ->where('official_form_instance_id', $instance->id)
                ->where('actor_type', 'language_editor')
                ->where('user_id', $editor->id)
                ->first();

            if ($assignment) {
                $assignment->update([
                    'status' => 'active',
                    'assigned_at' => now(),
                ]);
            } else {
                OfficialFormActorAssignment::query()->create([
                    'official_form_instance_id' => $instance->id,
                    'actor_type' => 'language_editor',
                    'user_id' => $editor->id,
                    'assigned_by' => $editor->id,
                    'assigned_at' => now(),
                    'status' => 'active',
                ]);
            }

            AuditLog::query()->create([
                'user_id' => $editor->id,
                'actor_name' => $editor->name,
                'actor_email' => $editor->email,
                'event' => 'language_editor.confirmed_assignment',
                'auditable_type' => OfficialFormInstance::class,
                'auditable_id' => $instance->id,
                'description' => "Language Editor {$editor->name} confirmed assignment for form instance #{$instance->id} via RES-029 conforme.",
            ]);
        });
    }
}
