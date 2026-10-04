@php
    $officialFormInstance = $officialFormInstance ?? null;
    $payload = $payload ?? [];
    $group = $officialFormInstance?->group ?? auth()->user()?->researchClassGroup;
    $class = $officialFormInstance?->researchClass ?? $group?->researchClass;
    $resolver = app(\App\Modules\OfficialForms\Services\InstitutionalActorResolver::class);

    // Build unique list of student researchers for this form
    $members = $group?->members?->values() ?? collect();
    if ($group?->leader && ! $members->contains(fn ($m) => (int) ($m->student_id ?? $m->student?->id) === (int) $group->leader->id)) {
        $members = $members->prepend((object) [
            'student' => $group->leader,
            'student_id' => $group->leader->id,
        ]);
    }

    $currentSignatures = $officialFormInstance?->currentVersion?->signatures ?? collect();
    $authorshipSignatures = $currentSignatures->filter(fn ($sig) => $sig->academic_action === 'sign_authorship');
    foreach ($authorshipSignatures as $sig) {
        if (! $members->contains(fn ($m) => (int) ($m->student_id ?? $m->student?->id) === (int) $sig->signer_user_id)) {
            $members = $members->push((object) [
                'student' => $sig->signer ?? (object) ['id' => $sig->signer_user_id, 'name' => $sig->signer_name_snapshot],
                'student_id' => $sig->signer_user_id,
            ]);
        }
    }

    if ($members->isEmpty() && auth()->user() && auth()->user()->user_type?->value === 'student') {
        $members = collect([(object) [
            'student' => auth()->user(),
            'student_id' => auth()->id(),
        ]]);
    }

    $displayMembers = $members->isNotEmpty() ? $members : collect([null]);
    $adviserName = $group?->adviser?->name ?? 'Research Adviser';
    $programCoordinator = $resolver->programCoordinatorForGroup($group) ?? $resolver->programCoordinatorForClass($class);
    $programCoordinatorName = $programCoordinator?->name ?? 'Program Coordinator';
@endphp
<div x-show="activeOfficialForm === 'RES-049'" x-cloak>
    <x-student-official-form code="RES-Form-049" title="Certificate of Authentic Authorship" guidebook-page="135">
        <p class="text-justify leading-6">
            I/We declare that this submission is my/our own work and to the best of my/our knowledge it contains no materials previously published or written by another person, nor material which to a substantial extent has been accepted for the award of any other degree or diploma at NDMU or elsewhere, except where due acknowledgment is made in the research paper.
        </p>
        <p class="text-justify leading-6">
            I/We also declare that the intellectual content of this research is the product of my/our work, except to the extent that assistance from others in the project's design and conception or in style, presentation, and linguistic expression is acknowledged.
        </p>
        <input type="hidden" name="payload[authorship_confirmed]" value="1">
        <div class="space-y-8 pt-8">
            @foreach ($displayMembers as $index => $member)
                @php
                    $student = $member?->student ?? $member;
                    $studentId = $student?->id ?? $member?->student_id ?? null;
                    $studentName = $student?->name ?? null;

                    $memberSignature = $officialFormInstance?->currentVersion?->signatures?->first(
                        fn ($sig) => $sig->academic_action === 'sign_authorship'
                            && (
                                ($studentId && (int) $sig->signer_user_id === (int) $studentId)
                                || (! $studentId && $index === 0)
                            )
                    );
                @endphp
                <div class="official-signature-row mx-auto w-full max-w-md text-center">
                    <x-official-signature-field
                        :signature="$memberSignature"
                        :instance="$officialFormInstance"
                        :authoritative-name="$studentName"
                        :signer-user-id="$studentId"
                        actor-type="student_researcher"
                        academic-action="sign_authorship"
                        label="Printed Name & Signature"
                    />
                </div>
            @endforeach
        </div>
    </x-student-official-form>
</div>
