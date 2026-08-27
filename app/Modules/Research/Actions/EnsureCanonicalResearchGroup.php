<?php

namespace App\Modules\Research\Actions;

use App\Models\ResearchClassGroup;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class EnsureCanonicalResearchGroup
{
    public function handle(ResearchClassGroup $classGroup, User $actor): ResearchClassGroup
    {
        return DB::transaction(function () use ($classGroup, $actor): ResearchClassGroup {
            $group = ResearchClassGroup::query()
                ->with(['researchClass:id,name', 'members:id,research_class_group_id,student_id,created_at'])
                ->lockForUpdate()
                ->findOrFail($classGroup->id);

            if ($group->research_group_id !== null) {
                return $group;
            }

            $studentIds = $group->members->pluck('student_id')
                ->when($group->leader_student_id !== null, fn ($ids) => $ids->push($group->leader_student_id))
                ->unique()
                ->values();

            if ($studentIds->isEmpty()) {
                throw new InvalidArgumentException('The class group must have at least one student before its research title can be finalized.');
            }

            $students = User::query()->whereIn('id', $studentIds)->lockForUpdate()->get();
            $programIds = $students->map(fn (User $student): int => $this->resolveProgramId($student))->unique();

            if ($programIds->count() !== 1) {
                throw new InvalidArgumentException('All members of a canonical research group must belong to the same academic program.');
            }

            $academicTermId = DB::table('academic_terms')->where('is_current', true)->value('id');
            if ($academicTermId === null) {
                throw new InvalidArgumentException('No current academic term is configured. Configure it before finalizing RES-026.');
            }

            $now = now();
            $canonicalGroupId = DB::table('research_groups')->insertGetId([
                'program_id' => $programIds->first(),
                'academic_term_id' => $academicTermId,
                'name' => sprintf('%s - %s (#%d)', $group->researchClass?->name ?? 'Research Class', $group->name, $group->id),
                'created_by' => $group->created_by ?? $actor->id,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            foreach ($students as $student) {
                $studentProfileId = $this->ensureStudentProfile($student, (int) $programIds->first(), $now);
                $classMembership = $group->members->firstWhere('student_id', $student->id);

                DB::table('research_group_members')->updateOrInsert(
                    [
                        'research_group_id' => $canonicalGroupId,
                        'student_profile_id' => $studentProfileId,
                    ],
                    [
                        'member_role' => (int) $group->leader_student_id === (int) $student->id ? 'leader' : 'member',
                        'joined_at' => $classMembership?->created_at ?? $now,
                        'left_at' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ],
                );
            }

            $group->update(['research_group_id' => $canonicalGroupId]);

            return $group->fresh();
        }, 3);
    }

    private function resolveProgramId(User $student): int
    {
        $existingProgramId = DB::table('student_profiles')->where('user_id', $student->id)->value('program_id');
        if ($existingProgramId !== null) {
            return (int) $existingProgramId;
        }

        $program = collect(config('academic.programs', []))->first(
            fn (array $candidate): bool => in_array($student->program, [
                $candidate['code'],
                $candidate['name'],
                $candidate['label'],
            ], true),
        );
        $programId = $program === null ? null : DB::table('programs')->where('code', $program['code'])->value('id');

        if ($programId === null) {
            throw new InvalidArgumentException("The academic program for {$student->name} is not configured in the canonical program catalog.");
        }

        return (int) $programId;
    }

    private function ensureStudentProfile(User $student, int $programId, mixed $now): int
    {
        $profileId = DB::table('student_profiles')->where('user_id', $student->id)->value('id');
        if ($profileId !== null) {
            return (int) $profileId;
        }

        preg_match('/\d+/', (string) $student->year_level, $yearMatch);

        return DB::table('student_profiles')->insertGetId([
            'user_id' => $student->id,
            'program_id' => $programId,
            'student_number' => $student->student_id ?: 'STUDENT-'.$student->id,
            'year_level' => isset($yearMatch[0]) ? (int) $yearMatch[0] : null,
            'contact_number' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}
