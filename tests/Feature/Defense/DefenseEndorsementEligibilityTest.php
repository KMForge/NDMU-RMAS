<?php

namespace Tests\Feature\Defense;

use App\Models\OfficialFormDefinition;
use App\Models\OfficialFormInstance;
use App\Models\OfficialFormVersion;
use App\Models\ResearchClass;
use App\Models\ResearchClassGroup;
use App\Models\User;
use App\Modules\DefenseScheduling\Queries\GetClassCommitteeAssignments;
use App\Modules\DefenseScheduling\Services\DefenseEndorsementEligibility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Tests\TestCase;

class DefenseEndorsementEligibilityTest extends TestCase
{
    use RefreshDatabase;

    private DefenseEndorsementEligibility $eligibility;

    private ResearchClassGroup $group;

    private OfficialFormDefinition $definition;

    private User $actor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->eligibility = app(DefenseEndorsementEligibility::class);
        $this->actor = User::factory()->create(['user_type' => 'faculty']);

        $researchClass = ResearchClass::query()->forceCreate([
            'facilitator_id' => $this->actor->id,
            'creation_token' => (string) Str::uuid(),
            'name' => 'Capstone Test Class',
            'join_code_hash' => hash('sha256', Str::uuid()->toString()),
            'join_code_encrypted' => 'TEST-CODE',
            'is_active' => true,
        ]);

        $this->group = ResearchClassGroup::query()->forceCreate([
            'research_class_id' => $researchClass->id,
            'creation_token' => (string) Str::uuid(),
            'name' => 'Capstone Group 1',
            'created_by' => $this->actor->id,
            'status' => 'active',
        ]);

        $this->definition = OfficialFormDefinition::query()->create([
            'code' => 'RES-033',
            'title' => 'Endorsement for Defense',
            'default_category' => 'Defense Endorsement',
            'ownership_scope' => 'research_group',
            'cardinality' => 'single_per_context',
            'template_view' => 'pages.adviser.forms.res-033',
            'is_active' => true,
            'sort_order' => 8,
        ]);
    }

    public function test_all_four_defense_stages_are_blocked_without_a_completed_endorsement(): void
    {
        foreach (['title_presentation', 'proposal_defense', 'pre_final_defense', 'final_defense'] as $defenseType) {
            try {
                $this->eligibility->ensureComplete($this->group, $defenseType);
                $this->fail("{$defenseType} was allowed without a completed RES-033.");
            } catch (InvalidArgumentException $exception) {
                $this->assertStringContainsString('RES-033', $exception->getMessage());
            }
        }
    }

    public function test_adviser_endorsement_alone_does_not_complete_the_gate(): void
    {
        $this->createEndorsement('proposal_defense', 'endorsed');

        $this->assertFalse($this->eligibility->isComplete($this->group, 'proposal_defense'));
    }

    public function test_received_endorsement_only_unlocks_its_matching_defense_stage(): void
    {
        $this->createEndorsement('proposal_defense', 'received');

        $this->assertTrue($this->eligibility->isComplete($this->group, 'proposal_defense'));
        $this->assertFalse($this->eligibility->isComplete($this->group, 'title_presentation'));
        $this->assertFalse($this->eligibility->isComplete($this->group, 'pre_final_defense'));
        $this->assertFalse($this->eligibility->isComplete($this->group, 'final_defense'));
    }

    public function test_received_endorsement_with_an_authoritative_context_does_not_require_duplicate_payload_metadata(): void
    {
        $endorsement = $this->createEndorsement('proposal_defense', 'received');
        $endorsement->currentVersion->update(['payload' => []]);

        $this->assertTrue($this->eligibility->isComplete($this->group, 'proposal_defense'));
        $this->assertFalse($this->eligibility->isComplete($this->group, 'final_defense'));
    }

    public function test_legacy_general_context_is_accepted_when_payload_identifies_the_stage(): void
    {
        $this->createEndorsement('final', 'received', 'general');

        $this->assertTrue($this->eligibility->isComplete($this->group, 'final_defense'));
    }

    public function test_scheduling_group_data_marks_incomplete_res033_as_unselectable(): void
    {
        $query = app(GetClassCommitteeAssignments::class);
        $researchClass = $this->group->researchClass;

        $before = $query->forClass($researchClass, 'proposal_defense')['groups']->sole();
        $this->assertFalse($before['res033_complete']);

        $this->createEndorsement('proposal_defense', 'received');

        $after = $query->forClass($researchClass, 'proposal_defense')['groups']->sole();
        $this->assertTrue($after['res033_complete']);
    }

    private function createEndorsement(string $payloadDefenseType, string $status, ?string $contextKey = null): OfficialFormInstance
    {
        $instance = OfficialFormInstance::query()->create([
            'official_form_definition_id' => $this->definition->id,
            'research_class_group_id' => $this->group->id,
            'context_key' => $contextKey ?? $payloadDefenseType,
            'initiated_by' => $this->actor->id,
            'status' => $status,
        ]);

        $version = OfficialFormVersion::query()->create([
            'official_form_instance_id' => $instance->id,
            'version_number' => 1,
            'payload' => ['defense_type' => $payloadDefenseType],
            'created_by' => $this->actor->id,
            'is_current' => true,
        ]);

        $instance->update(['current_version_id' => $version->id]);

        return $instance;
    }
}
