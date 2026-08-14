@php
    $officialFormInstance = $officialFormInstance ?? null;
    $payload = $payload ?? [];
    $sourceConsultation = $officialFormInstance?->source;
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
    <p class="text-xs italic text-gray-600">Authoritative consultation record entries linked from source.</p>
    <table class="official-form-table text-xs">
        <thead>
            <tr>
                <th class="w-1/5">Date & Conducted By</th>
                <th>Agenda, Discussion & Recommendations</th>
                <th class="w-1/4">Status & Signature</th>
            </tr>
        </thead>
        <tbody>
            @if ($sourceConsultation)
                <tr>
                    <td>
                        <p class="font-bold">{{ $sourceConsultation->consulted_at?->format('M j, Y g:i A') ?? 'N/A' }}</p>
                        <p class="mt-1 text-gray-500">By: {{ $sourceConsultation->conductedBy?->name ?? 'Adviser' }}</p>
                        <p class="mt-1 text-gray-500">Mode: {{ str($sourceConsultation->consultation_mode?->value ?? $sourceConsultation->consultation_mode)->headline() }}</p>
                    </td>
                    <td>
                        <div class="p-2 space-y-2">
                            @if ($sourceConsultation->agenda)
                                <p><span class="font-bold text-[#0e5c3a]">Agenda:</span> {{ $sourceConsultation->agenda }}</p>
                            @endif
                            @if ($sourceConsultation->discussion)
                                <p><span class="font-bold text-[#0e5c3a]">Discussion:</span> {{ $sourceConsultation->discussion }}</p>
                            @endif
                            @if ($sourceConsultation->recommendations)
                                <p><span class="font-bold text-[#0e5c3a]">Recommendations:</span> {{ $sourceConsultation->recommendations }}</p>
                            @endif
                        </div>
                    </td>
                    <td><x-official-signature-field label="Consultation Verified" /></td>
                </tr>
            @else
                @for ($i=1;$i<=3;$i++)
                    <tr>
                        <td>#{{ $i }}</td>
                        <td><div class="p-2 text-gray-400 italic">No source consultation record linked.</div></td>
                        <td><x-official-signature-field label="Student and adviser signatures" /></td>
                    </tr>
                @endfor
            @endif
        </tbody>
    </table>
</x-student-official-form></div>
