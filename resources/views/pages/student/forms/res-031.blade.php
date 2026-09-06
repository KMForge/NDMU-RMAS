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
        <div class="grid gap-4 md:grid-cols-2">
            <label>Name of Researchers:
                <textarea class="mt-1 min-h-20 w-full font-bold" readonly>{{ $joinedResearchers }}</textarea>
            </label>
            <div class="space-y-4">
                <label class="block">Course / Section:<input value="{{ $courseSection }}" class="w-full font-semibold" readonly></label>
                <label class="block">Adviser Name:<input value="{{ $adviserName }}" class="w-full font-semibold" readonly></label>
            </div>
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
                    <th class="w-1/5">Date & Conducted By</th>
                    <th>Agenda, Discussion & Recommendations</th>
                    <th class="w-1/4">Status & Signature</th>
                </tr>
            </thead>
            <tbody>
                @if ($consultationRecords->isNotEmpty())
                    @foreach ($consultationRecords as $index => $consultation)
                        <tr>
                            <td>
                                <p class="font-bold text-[#0e5c3a]">#{{ $index + 1 }}</p>
                                <p class="font-bold">{{ $consultation->consulted_at?->format('M j, Y g:i A') ?? 'N/A' }}</p>
                                <p class="mt-1 text-gray-500">By: {{ $consultation->conductedBy?->name ?? $adviserName }}</p>
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
                            <td>
                                <x-official-signature-field
                                    label="Adviser Session Conforme"
                                    actor-type="adviser"
                                    name-field="res_031_consultation_{{ $consultation->id }}_signer_name"
                                />
                            </td>
                        </tr>
                    @endforeach
                @else
                    <tr>
                        <td colspan="3" class="p-8 text-center text-gray-500">
                            No consultation sessions recorded yet. Consultation sessions recorded via the Adviser Portal will automatically synchronize into this form.
                        </td>
                    </tr>
                @endif
            </tbody>
        </table>
        <div class="grid grid-cols-2 gap-8 pt-6 text-center">
            <x-official-signature-field name-field="res_031_student_rep_signature" label="Lead Student Researcher" />
            <x-official-signature-field name-field="res_031_adviser_signature" label="Thesis Adviser" />
        </div>
    </x-student-official-form>
</div>
