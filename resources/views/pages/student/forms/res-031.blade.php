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
            <label class="block">Degree Program:<input name="payload[degree_program]" value="{{ $payload['degree_program'] ?? '' }}" class="w-full"></label>
            <label class="block">Date of Proposal/Final Defense:<input type="date" name="payload[defense_date]" value="{{ $payload['defense_date'] ?? '' }}" class="w-full"></label>
        </div>
    </div>
    <label class="block">Name of Adviser:<input value="{{ $officialFormInstance?->group?->adviser?->name }}" class="w-full" readonly></label>
    <label class="block">Research Title:<input value="{{ $officialFormInstance?->group?->title }}" class="w-full" readonly></label>
    <p class="text-xs italic">Authoritative consultation record entries linked from source.</p>
    <table class="official-form-table text-xs">
        <thead>
            <tr>
                <th class="w-1/5">Number & Date of Consultation</th>
                <th>Topics Discussed or Concerns</th>
                <th class="w-1/4">Student / Adviser Signatures</th>
            </tr>
        </thead>
        <tbody>
            @if ($sourceConsultation)
                <tr>
                    <td>Date: {{ $sourceConsultation->consulted_at?->format('M j, Y') ?? 'N/A' }}</td>
                    <td>
                        <div class="p-2">
                            <p class="font-bold">Topics:</p>
                            <p class="mt-1">{{ $sourceConsultation->topics ?? 'N/A' }}</p>
                            @if ($sourceConsultation->notes)
                                <p class="mt-2 font-bold">Notes:</p>
                                <p class="mt-1">{{ $sourceConsultation->notes }}</p>
                            @endif
                        </div>
                    </td>
                    <td><x-official-signature-field label="Consultation verified" /></td>
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
