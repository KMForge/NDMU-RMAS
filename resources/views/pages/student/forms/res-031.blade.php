@php
    $officialFormInstance = $officialFormInstance ?? null;
    $payload = $payload ?? [];
    $group = $officialFormInstance?->group;
    $class = $officialFormInstance?->researchClass ?? $group?->researchClass;

    $currentResearchTitle = $payload['research_title']
        ?? $payload['title']
        ?? $group?->researchGroup?->currentProject?->title
        ?? $group?->titlePresentation?->approved_title
        ?? '';

    $members = $group?->members?->values() ?? collect();
    $joinedResearchers = $members->map(fn($m) => $m->student?->name)->filter()->join(', ');
    $adviserName = $group?->adviser?->name ?? 'N/A';
    $courseSection = $class?->name ?? 'N/A';

    $sourceConsultation = $officialFormInstance?->source;
    $consultationRecords = $sourceConsultation
        ? collect([$sourceConsultation])
        : ($group?->consultationRecords()->where('is_superseded', false)->latest('consulted_at')->get() ?? collect());
@endphp
<div x-show="activeOfficialForm === 'RES-031'" x-cloak>
    <x-student-official-form code="RES-Form-031" title="Consultation Record with Research Adviser" guidebook-page="106">
        <fieldset>
            <legend class="mb-2 font-bold">Name of Researchers:</legend>
            @for ($i = 1; $i <= 4; $i++)
                <label class="mb-2 flex gap-2"><span>{{ $i }}.</span><input value="{{ $members->get($i - 1)?->student?->name }}" class="flex-1" readonly></label>
            @endfor
        </fieldset>
        <div class="grid gap-4 md:grid-cols-2">
            <label class="block">Degree Program / Section:<input value="{{ $courseSection }}" class="w-full font-semibold" readonly></label>
            <label class="block">Research Adviser:<input value="{{ $adviserName }}" class="w-full font-semibold" readonly></label>
            <label class="block">Date of Proposal Defense:<input type="date" name="payload[proposal_defense_date]" value="{{ $payload['proposal_defense_date'] ?? '' }}" class="w-full"></label>
            <label class="block">Date of Final Defense:<input type="date" name="payload[final_defense_date]" value="{{ $payload['final_defense_date'] ?? '' }}" class="w-full"></label>
        </div>
        <label class="block">Research Title:<input value="{{ $currentResearchTitle }}" class="w-full font-bold" readonly></label>
        <div class="flex items-center justify-between">
            <p class="text-xs italic text-gray-600">Authoritative consultation record entries linked from source.</p>
            @if (auth()->user() && ((int) $group?->adviser_id === (int) auth()->id() || auth()->user()->can('dashboards.adviser.view')))
                <a href="{{ route('adviser.dashboard', ['tab' => 'consultation']) }}" class="text-xs font-bold text-[#0e5c3a] hover:underline flex items-center gap-1">
                    <i class="ph ph-plus-circle"></i> Record More Consultation Sessions
                </a>
            @endif
        </div>
        <table class="official-form-table text-xs">
            <thead>
                <tr>
                    <th class="w-1/5">Number & Date</th>
                    <th>Topics Discussed or Concerns</th>
                    <th class="w-1/5">Student-Researchers</th>
                    <th class="w-1/5">Adviser</th>
                </tr>
            </thead>
            <tbody>
                @if ($consultationRecords->isNotEmpty())
                    @foreach ($consultationRecords as $index => $consultation)
                        <tr>
                            <td>
                                <p class="font-bold text-[#0e5c3a]">#{{ $index + 1 }}</p>
                                <p class="font-bold">{{ $consultation->consulted_at?->format('M j, Y g:i A') ?? 'N/A' }}</p>
                                <p class="mt-1 text-gray-500">Mode: {{ str($consultation->consultation_mode?->value ?? $consultation->consultation_mode)->headline() }}</p>
                            </td>
                            <td>
                                <div class="p-2 space-y-2">
                                    @if ($consultation->agenda)
                                        <p><span class="font-bold text-[#0e5c3a]">Agenda:</span> {{ $consultation->agenda }}</p>
                                    @endif
                                    @if ($consultation->discussion)
                                        <p><span class="font-bold text-[#0e5c3a]">Discussion:</span> {{ $consultation->discussion }}</p>
                                    @endif
                                    @if ($consultation->recommendations)
                                        <p><span class="font-bold text-[#0e5c3a]">Recommendations:</span> {{ $consultation->recommendations }}</p>
                                    @endif
                                </div>
                            </td>
                            <td><x-official-signature-field label="Student-Researchers" name-field="res_031_student_{{ $consultation->id }}_signature" /></td>
                            <td>
                                <x-official-signature-field
                                    label="Adviser"
                                    actor-type="adviser"
                                    name-field="res_031_consultation_{{ $consultation->id }}_signer_name"
                                />
                            </td>
                        </tr>
                    @endforeach
                @else
                    <tr>
                        <td colspan="4" class="p-8 text-center text-gray-500">
                            No consultation sessions recorded yet. Consultation sessions recorded via the Adviser Portal will automatically synchronize into this form.
                        </td>
                    </tr>
                @endif
            </tbody>
        </table>
    </x-student-official-form>
</div>
