<?php

namespace App\Modules\OfficialForms\Actions;

use App\Models\OfficialFormDefinition;

class SyncOfficialFormCatalog
{
    /** @return list<array<string, mixed>> */
    public function definitions(): array
    {
        return [
            ['code' => 'RES-026', 'title' => 'Research Title Approval', 'default_category' => 'Title Approval', 'ownership_scope' => 'research_group', 'cardinality' => 'single_per_group', 'template_view' => 'pages.student.forms.res-026', 'sort_order' => 1],
            ['code' => 'RES-027', 'title' => 'Invitation to Research Adviser', 'default_category' => 'Adviser Assignment', 'ownership_scope' => 'research_group', 'cardinality' => 'single_per_group', 'template_view' => 'pages.adviser.forms.res-027', 'sort_order' => 2],
            ['code' => 'RES-028', 'title' => 'Invitation to Research Examination Panelist', 'default_category' => 'Panelist Assignment', 'ownership_scope' => 'research_group', 'cardinality' => 'per_actor', 'template_view' => 'pages.panelist.forms.res-028', 'sort_order' => 3],
            ['code' => 'RES-029', 'title' => 'Invitation to Research Language Editor', 'default_category' => 'Editor Assignment', 'ownership_scope' => 'research_group', 'cardinality' => 'per_actor', 'template_view' => 'pages.student.forms.res-029', 'sort_order' => 4],
            ['code' => 'RES-030', 'title' => 'Request for Change of Personnel', 'default_category' => 'Personnel Change', 'ownership_scope' => 'research_group', 'cardinality' => 'repeatable', 'template_view' => 'pages.student.forms.res-030', 'sort_order' => 5],
            ['code' => 'RES-031', 'title' => 'Consultation Record with Research Adviser', 'default_category' => 'Adviser Consultation', 'ownership_scope' => 'research_group', 'cardinality' => 'repeatable', 'template_view' => 'pages.student.forms.res-031', 'sort_order' => 6],
            ['code' => 'RES-032', 'title' => 'Consultation Sheet (With Other Consultants)', 'default_category' => 'Specialist Consultation', 'ownership_scope' => 'research_group', 'cardinality' => 'repeatable', 'template_view' => 'pages.student.forms.res-032', 'sort_order' => 7],
            ['code' => 'RES-033', 'title' => 'Endorsement for Defense', 'default_category' => 'Defense Endorsement', 'ownership_scope' => 'research_group', 'cardinality' => 'single_per_context', 'template_view' => 'pages.adviser.forms.res-033', 'sort_order' => 8],
            ['code' => 'RES-034', 'title' => 'Defense Pre-Conference', 'default_category' => 'Defense Pre-Conference', 'ownership_scope' => 'research_group', 'cardinality' => 'single_per_context', 'template_view' => 'pages.student.forms.res-034', 'sort_order' => 9],
            ['code' => 'RES-035', 'title' => 'Defense Proceedings', 'default_category' => 'Defense Proceedings', 'ownership_scope' => 'research_group', 'cardinality' => 'single_per_context', 'template_view' => 'pages.adviser.forms.res-035', 'sort_order' => 10],
            ['code' => 'RES-036', 'title' => 'Evaluation of Research Defense', 'default_category' => 'Individual Defense Evaluation', 'ownership_scope' => 'research_group', 'cardinality' => 'per_actor', 'template_view' => 'pages.panelist.forms.res-036', 'sort_order' => 11],
            ['code' => 'RES-037', 'title' => 'Evaluation Summary of Research Defense', 'default_category' => 'Panel Evaluation Summary', 'ownership_scope' => 'research_group', 'cardinality' => 'single_per_context', 'template_view' => 'pages.panelist.forms.res-037', 'sort_order' => 12],
            ['code' => 'RES-038', 'title' => 'Endorsement of Student Researchers to Adviser', 'default_category' => 'Group Assignment Endorsement', 'ownership_scope' => 'research_group', 'cardinality' => 'single_per_group', 'template_view' => 'pages.adviser.forms.res-038', 'sort_order' => 13],
            ['code' => 'RES-039', 'title' => 'Research Revision Chart', 'default_category' => 'Revision Tracking', 'ownership_scope' => 'research_group', 'cardinality' => 'single_per_context', 'template_view' => 'pages.student.forms.res-039', 'sort_order' => 14],
            ['code' => 'RES-040', 'title' => 'Endorsement to Research Instructor', 'default_category' => 'Instructor Endorsement', 'ownership_scope' => 'research_group', 'cardinality' => 'single_per_context', 'template_view' => 'pages.adviser.forms.res-040', 'sort_order' => 15],
            ['code' => 'RES-041', 'title' => 'Endorsement to Program Coordinator', 'default_category' => 'Coordinator Endorsement', 'ownership_scope' => 'research_class', 'cardinality' => 'repeatable', 'template_view' => 'pages.facilitator.forms.res-041', 'sort_order' => 16],
            ['code' => 'RES-042', 'title' => 'Request for Instrument Validation', 'default_category' => 'Validation Request', 'ownership_scope' => 'research_group', 'cardinality' => 'repeatable', 'template_view' => 'pages.student.forms.res-042', 'sort_order' => 17],
            ['code' => 'RES-043A', 'title' => 'Research Instrument Item Validation', 'default_category' => 'Item Validation Rating', 'ownership_scope' => 'research_group', 'cardinality' => 'per_actor', 'template_view' => 'pages.facilitator.forms.res-043a', 'sort_order' => 18],
            ['code' => 'RES-043B', 'title' => 'Research Instrument Validation Rating', 'default_category' => 'Overall Validation Rating', 'ownership_scope' => 'research_group', 'cardinality' => 'per_actor', 'template_view' => 'pages.facilitator.forms.res-043b', 'sort_order' => 19],
            ['code' => 'RES-044', 'title' => 'Endorsement for Data Gathering', 'default_category' => 'Data Gathering Endorsement', 'ownership_scope' => 'research_group', 'cardinality' => 'single_per_group', 'template_view' => 'pages.adviser.forms.res-044', 'sort_order' => 20],
            ['code' => 'RES-045', 'title' => 'Certificate of Language Editing', 'default_category' => 'Language Editing Certification', 'ownership_scope' => 'research_group', 'cardinality' => 'single_per_group', 'template_view' => 'pages.facilitator.forms.res-045', 'sort_order' => 21],
            ['code' => 'RES-046', 'title' => 'Certificate of Technical Editing', 'default_category' => 'Technical Editing Certification', 'ownership_scope' => 'research_group', 'cardinality' => 'single_per_group', 'template_view' => 'pages.facilitator.forms.res-046', 'sort_order' => 22],
            ['code' => 'RES-047', 'title' => 'Endorsement for Reproduction', 'default_category' => 'Paper Reproduction Endorsement', 'ownership_scope' => 'research_group', 'cardinality' => 'single_per_group', 'template_view' => 'pages.facilitator.forms.res-047', 'sort_order' => 23],
            ['code' => 'RES-048', 'title' => 'Self and Peer Evaluation', 'default_category' => 'Peer Evaluation', 'ownership_scope' => 'research_group', 'cardinality' => 'single_per_group', 'template_view' => 'pages.student.forms.res-048', 'sort_order' => 24],
            ['code' => 'RES-049', 'title' => 'Certificate of Authentic Authorship', 'default_category' => 'Authorship Declaration', 'ownership_scope' => 'research_group', 'cardinality' => 'single_per_group', 'template_view' => 'pages.student.forms.res-049', 'sort_order' => 25],
        ];
    }

    public function handle(): void
    {
        foreach ($this->definitions() as $definition) {
            OfficialFormDefinition::query()->updateOrCreate(
                ['code' => $definition['code']],
                $definition,
            );
        }
    }
}
