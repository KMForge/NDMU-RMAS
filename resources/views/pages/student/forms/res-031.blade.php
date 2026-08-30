@php
    $officialFormInstance = $officialFormInstance ?? null;
    $payload = $payload ?? [];
    $sourceConsultation = $officialFormInstance?->source;
    $consultationRecords = $sourceConsultation
        ? collect([$sourceConsultation])
        : ($officialFormInstance?->group?->consultationRecords()->where('is_superseded', false)->latest('consulted_at')->get() ?? collect());
@endphp
<div x-show="activeOfficialForm === 'RES-031'" x-cloak><x-student-official-form code="RES-Form-031" title="Consultation Record with Research Adviser" guidebook-page="106">
    <div class="grid gap-4 md:grid-cols-2">
        <label>Name of Researchers:
            <textarea class="mt-1 min-h-20 w-full" readonly>{{ $officialFormInstance?->group?->members?->pluck('student.name')->filter()->join(', ') }}</textarea>
        </label>
        <div class="space-y-4">
            <label class="block">Course / Section:<input value="{{ $officialFormInstance?->group?->researchClass?->name ?? 'N/A' }}" class="w-full" readonly></label>
            <label class="block">Adviser Name:<input value="{{ $officialFormInstance?->group?->adviser?->name ?? $sourceConsultation?->conductedBy?->name ?? 'N/A' }}" class="w-full" readonly></label>
        </div>
    </div>
    <label class="block">Research Title:<input value="{{ $officialFormInstance?->group?->title ?? 'N/A' }}" class="w-full" readonly></label>
    <div class="flex items-center justify-between">
        <p class="text-xs italic text-gray-600">Authoritative consultation record entries linked from source.</p>
        @if (auth()->user() && ((int) $officialFormInstance?->group?->adviser_id === (int) auth()->id() || auth()->user()->can('dashboards.adviser.view')))
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
                            <p class="mt-1 text-gray-500">By: {{ $consultation->conductedBy?->name ?? 'Adviser' }}</p>
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
                                actor-type="adviser"
                                academic-action="sign"
                                label="Research Adviser"
                            />
                        </td>
                    </tr>
                @endforeach
                @for ($i = $consultationRecords->count() + 1; $i <= max(3, $consultationRecords->count()); $i++)
                    <tr>
                        <td>#{{ $i }}</td>
                        <td><div class="p-2 text-gray-400 italic">No additional consultation record linked.</div></td>
                        <td>
                            <x-official-signature-field
                                actor-type="adviser"
                                academic-action="sign"
                                label="Research Adviser"
                            />
                        </td>
                    </tr>
                @endfor
            @else
                @for ($i = 1; $i <= 3; $i++)
                    <tr>
                        <td>#{{ $i }}</td>
                        <td><div class="p-2 text-gray-400 italic">No source consultation record linked.</div></td>
                        <td>
                            <x-official-signature-field
                                actor-type="adviser"
                                academic-action="sign"
                                label="Research Adviser"
                            />
                        </td>
                    </tr>
                @endfor
            @endif
        </tbody>
    </table>
</x-student-official-form></div>
