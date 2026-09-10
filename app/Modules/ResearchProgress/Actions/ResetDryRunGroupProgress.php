<?php

namespace App\Modules\ResearchProgress\Actions;

use App\Models\ResearchClassGroup;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ResetDryRunGroupProgress
{
    public function __construct(
        private readonly InitializeGroupMilestones $initializeMilestones,
    ) {}

    /**
     * Resets progress, forms, and scheduled defenses for a given group or all dry-run groups.
     *
     * @return array{
     *     groups_reset: int,
     *     group_names: list<string>,
     *     defenses_deleted: int,
     *     forms_deleted: int,
     *     milestones_reset: int
     * }
     */
    public function execute(?ResearchClassGroup $targetGroup = null, bool $reinitializeMilestones = true): array
    {
        /** @var Collection<int, ResearchClassGroup> $groups */
        $groups = $targetGroup !== null
            ? collect([$targetGroup])
            : ResearchClassGroup::query()
                ->where(function ($query): void {
                    $query->whereRaw('LOWER(name) LIKE ?', ['%dry run%'])
                        ->orWhereRaw('LOWER(name) LIKE ?', ['%dryrun%'])
                        ->orWhereRaw('LOWER(name) LIKE ?', ['%3 idiots%'])
                        ->orWhereHas('members.student', function ($studentQuery): void {
                            $studentQuery->whereRaw('LOWER(email) LIKE ?', ['%dryrun%']);
                        });
                })
                ->get();

        $totalDefenses = 0;
        $totalForms = 0;
        $totalMilestones = 0;
        $groupNames = [];

        foreach ($groups as $group) {
            $groupNames[] = $group->name;

            DB::transaction(function () use ($group, &$totalDefenses, &$totalForms, &$totalMilestones, $reinitializeMilestones): void {
                $groupId = $group->getKey();

                // 1. Defense evaluations & evaluation rounds
                if (Schema::hasTable('defense_evaluation_rounds')) {
                    $roundIds = DB::table('defense_evaluation_rounds')
                        ->where('research_class_group_id', $groupId)
                        ->pluck('id');

                    if ($roundIds->isNotEmpty()) {
                        if (Schema::hasTable('defense_evaluation_student_scores')) {
                            DB::table('defense_evaluation_student_scores')->whereIn('round_id', $roundIds)->delete();
                        }
                        if (Schema::hasTable('defense_evaluation_student_summaries')) {
                            DB::table('defense_evaluation_student_summaries')->whereIn('round_id', $roundIds)->delete();
                        }
                        if (Schema::hasTable('defense_evaluation_summaries')) {
                            DB::table('defense_evaluation_summaries')->whereIn('round_id', $roundIds)->delete();
                        }
                        if (Schema::hasTable('defense_evaluations')) {
                            DB::table('defense_evaluations')->whereIn('defense_evaluation_round_id', $roundIds)->delete();
                        }
                        if (Schema::hasTable('defense_evaluation_round_panelists')) {
                            DB::table('defense_evaluation_round_panelists')->whereIn('defense_evaluation_round_id', $roundIds)->delete();
                        }
                        if (Schema::hasTable('defense_evaluation_round_students')) {
                            DB::table('defense_evaluation_round_students')->whereIn('defense_evaluation_round_id', $roundIds)->delete();
                        }
                        DB::table('defense_evaluation_rounds')->whereIn('id', $roundIds)->delete();
                    }
                }

                // 2. Defenses, schedules, panels, and linked title presentations
                if (Schema::hasTable('defenses')) {
                    $defenseIds = DB::table('defenses')
                        ->where('research_class_group_id', $groupId)
                        ->pluck('id');

                    if ($defenseIds->isNotEmpty()) {
                        if (Schema::hasTable('title_presentations')) {
                            DB::table('title_presentations')->whereIn('defense_id', $defenseIds)->delete();
                        }
                        if (Schema::hasTable('defense_schedules')) {
                            $scheduleIds = DB::table('defense_schedules')->whereIn('defense_id', $defenseIds)->pluck('id');
                            DB::table('defenses')->whereIn('id', $defenseIds)->update(['current_schedule_id' => null]);
                            DB::table('defense_schedules')->whereIn('id', $scheduleIds)->delete();
                        }
                        if (Schema::hasTable('defense_panel_assignments')) {
                            DB::table('defense_panel_assignments')->whereIn('defense_id', $defenseIds)->delete();
                        }
                        $deletedDefs = DB::table('defenses')->whereIn('id', $defenseIds)->delete();
                        $totalDefenses += $deletedDefs;
                    }
                }

                // 3. Group panel committees & members
                if (Schema::hasTable('research_group_panel_committees')) {
                    $groupCommitteeIds = DB::table('research_group_panel_committees')
                        ->where('research_class_group_id', $groupId)
                        ->pluck('id');

                    if ($groupCommitteeIds->isNotEmpty()) {
                        if (Schema::hasTable('research_group_panel_members')) {
                            DB::table('research_group_panel_members')->whereIn('committee_id', $groupCommitteeIds)->delete();
                        }
                        DB::table('research_group_panel_committees')->whereIn('id', $groupCommitteeIds)->delete();
                    }
                }

                // 4. Official forms for the group
                if (Schema::hasTable('official_form_instances')) {
                    $formInstanceIds = DB::table('official_form_instances')
                        ->where('research_class_group_id', $groupId)
                        ->pluck('id');

                    if ($formInstanceIds->isNotEmpty()) {
                        if (Schema::hasTable('title_presentations')) {
                            DB::table('title_presentations')->whereIn('official_form_instance_id', $formInstanceIds)->delete();
                        }
                        if (Schema::hasTable('official_form_versions')) {
                            $versionIds = DB::table('official_form_versions')->whereIn('official_form_instance_id', $formInstanceIds)->pluck('id');
                            if ($versionIds->isNotEmpty() && Schema::hasTable('official_form_verifications')) {
                                DB::table('official_form_verifications')->whereIn('official_form_version_id', $versionIds)->delete();
                            }
                            DB::table('official_form_instances')->whereIn('id', $formInstanceIds)->update(['current_version_id' => null]);
                            if (Schema::hasTable('official_form_signatures')) {
                                DB::table('official_form_signatures')->whereIn('official_form_instance_id', $formInstanceIds)->delete();
                            }
                            DB::table('official_form_versions')->whereIn('id', $versionIds)->delete();
                        }
                        $deletedForms = DB::table('official_form_instances')->whereIn('id', $formInstanceIds)->delete();
                        $totalForms += $deletedForms;
                    }
                }

                // 5. Research progress milestones, events & evidences
                if (Schema::hasTable('research_group_milestones')) {
                    $milestoneIds = DB::table('research_group_milestones')
                        ->where('research_class_group_id', $groupId)
                        ->pluck('id');

                    if ($milestoneIds->isNotEmpty()) {
                        if (Schema::hasTable('milestone_evidences')) {
                            DB::table('milestone_evidences')->whereIn('research_group_milestone_id', $milestoneIds)->delete();
                        }
                        if (Schema::hasTable('research_group_milestone_events')) {
                            DB::table('research_group_milestone_events')->whereIn('research_group_milestone_id', $milestoneIds)->delete();
                        }
                        $deletedMilestones = DB::table('research_group_milestones')->whereIn('id', $milestoneIds)->delete();
                        $totalMilestones += $deletedMilestones;
                    }

                    if ($reinitializeMilestones) {
                        $this->initializeMilestones->execute($group);
                    }
                }

                // 6. Consultations, revision requests, and documents
                if (Schema::hasTable('consultation_requests')) {
                    DB::table('consultation_requests')->where('research_class_group_id', $groupId)->delete();
                }
                if (Schema::hasTable('consultation_records')) {
                    DB::table('consultation_records')->where('research_class_group_id', $groupId)->delete();
                }
                if (Schema::hasTable('revision_requests')) {
                    DB::table('revision_requests')->where('research_class_group_id', $groupId)->delete();
                }
                if (Schema::hasTable('documents')) {
                    $docIds = DB::table('documents')->where('research_class_group_id', $groupId)->pluck('id');
                    if ($docIds->isNotEmpty()) {
                        if (Schema::hasTable('document_review_comments')) {
                            DB::table('document_review_comments')->whereIn('document_id', $docIds)->delete();
                        }
                        if (Schema::hasTable('document_reviews')) {
                            DB::table('document_reviews')->whereIn('document_id', $docIds)->delete();
                        }
                        DB::table('documents')->whereIn('id', $docIds)->delete();
                    }
                }
            });
        }

        return [
            'groups_reset' => count($groups),
            'group_names' => $groupNames,
            'defenses_deleted' => $totalDefenses,
            'forms_deleted' => $totalForms,
            'milestones_reset' => $totalMilestones,
        ];
    }
}
