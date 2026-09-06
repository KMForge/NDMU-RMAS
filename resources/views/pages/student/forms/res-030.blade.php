@php
    $officialFormInstance = $officialFormInstance ?? null;
    $payload = $payload ?? [];
    $group = $officialFormInstance?->group;
    $class = $officialFormInstance?->researchClass ?? $group?->researchClass;
    $resolver = app(\App\Modules\OfficialForms\Services\InstitutionalActorResolver::class);

    $currentResearchTitle = $payload['research_title']
        ?? $payload['title']
        ?? $group?->researchGroup?->currentProject?->title
        ?? $group?->titlePresentation?->approved_title
        ?? '';

    $members = $group?->members?->values() ?? collect();
    $joinedResearchers = $members->map(fn($m) => $m->student?->name)->filter()->join(', ');
    $program = $members->first()?->student?->studentProfile?->program;
    $programName = $payload['degree_program'] ?? $program?->name ?? $members->first()?->student?->program ?? 'Information Technology';

    $programCoordinator = $resolver->programCoordinatorForGroup($group) ?? $resolver->programCoordinatorForClass($class);
    $programCoordinatorName = $programCoordinator?->name ?? 'Program Coordinator';
    $dean = $resolver->dean();
    $deanName = $dean?->name ?? 'Dr. Lourdes Castillo';
@endphp
<div x-show="activeOfficialForm === 'RES-030'" x-cloak>
    <x-student-official-form code="RES-Form-030" title="Request for Change of Research Adviser / Panelist / Language Editor" guidebook-page="105">
        <div class="grid gap-4 md:grid-cols-2">
            <label class="flex items-center gap-2">Date: <input type="date" name="payload[date]" value="{{ $payload['date'] ?? now()->format('Y-m-d') }}" class="w-full"></label>
            <label class="flex items-center gap-2">Degree Program: <input name="payload[degree_program]" value="{{ $programName }}" class="w-full font-semibold" readonly></label>
        </div>
        <label class="block">Name of Student/s:
            <textarea class="mt-1 min-h-16 w-full font-bold" readonly>{{ $joinedResearchers }}</textarea>
        </label>
        <label class="block font-bold">Research Title:<input name="payload[research_title]" value="{{ $currentResearchTitle }}" class="w-full font-bold" readonly></label>
        <fieldset>
            <legend class="font-bold">Specific Request (Please check):</legend>
            <div class="mt-2 grid gap-3 md:grid-cols-2">
                @foreach (['Change of Research Adviser', 'Change of Panelist', 'Change of Language Editor'] as $index => $role)
                    <label><input type="checkbox" name="payload[personnel_type][]" value="{{ $role }}" @checked(in_array($role, $payload['personnel_type'] ?? [], true))> {{ $role }}</label>
                    <label>NAME: <input name="payload[current_names][]" value="{{ $payload['current_names'][$index] ?? '' }}" class="w-full" placeholder="Current Personnel Name"></label>
                @endforeach
            </div>
        </fieldset>
        <label class="block font-bold">Proposed Adviser / Panelist / Language Editor:<input name="payload[proposed_replacement]" value="{{ $payload['proposed_replacement'] ?? '' }}" class="w-full" placeholder="Name of Proposed Replacement"></label>
        <label class="block font-bold">Reason/s for replacement:<textarea name="payload[reasons]" class="mt-2 min-h-24 w-full" placeholder="State valid justification for replacement...">{{ $payload['reasons'] ?? '' }}</textarea></label>
        <div class="pt-6">
            <p class="mb-3 font-bold">Requested by:</p>
            <div class="grid gap-4 md:grid-cols-2">
                @for ($i = 1; $i <= 4; $i++)
                    <div>
                        <x-official-signature-field :label="'Student '.$i" />
                        <p class="text-[11px] font-bold text-slate-700 text-center">{{ $members->get($i - 1)?->student?->name }}</p>
                    </div>
                @endfor
            </div>
        </div>
        <div class="grid grid-cols-2 gap-8 pt-10 text-center">
            <div>
                <x-official-signature-field label="Approved by: College Dean" />
                <p class="mt-1 text-xs font-bold text-slate-800">{{ $deanName }}</p>
            </div>
            <div>
                <x-official-signature-field label="Noted by: Program Coordinator" />
                <p class="mt-1 text-xs font-bold text-slate-800">{{ $programCoordinatorName }}</p>
            </div>
        </div>
    </x-student-official-form>
</div>
