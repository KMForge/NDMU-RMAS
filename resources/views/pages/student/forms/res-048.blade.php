@php
    $officialFormInstance = $officialFormInstance ?? null;
    $payload = $payload ?? [];
    $roster = collect($officialFormInstance?->currentVersion?->source_snapshot['roster'] ?? []);
    $totals = $payload['totals'] ?? [];
@endphp
<div x-show="activeOfficialForm === 'RES-048'" x-cloak><x-student-official-form code="RES-Form-048" title="Self and Peer Evaluation" guidebook-page="134">
    <fieldset><legend class="font-bold">Type of Evaluation Phase:</legend>
        <div class="mt-2 flex flex-wrap gap-6">
            <label><input type="radio" name="payload[evaluation_phase]" value="proposal" @checked(($payload['evaluation_phase'] ?? '') === 'proposal')> Research Proposal Phase (Research-I)</label>
            <label><input type="radio" name="payload[evaluation_phase]" value="pre_final" @checked(($payload['evaluation_phase'] ?? '') === 'pre_final')> Pre-Final Phase</label>
            <label><input type="radio" name="payload[evaluation_phase]" value="final" @checked(($payload['evaluation_phase'] ?? '') === 'final')> Final Phase (Research-II)</label>
        </div>
    </fieldset>
    <p class="text-xs"><strong>Instruction:</strong> Evaluate yourself and each group member by writing the number which represents his/her extent of participation in a specific area.</p>
    <div class="text-xs"><strong>Legend:</strong> 4 – Always &nbsp; 3 – Sometimes &nbsp; 2 – Rarely &nbsp; 1 – Never</div>
    <table class="official-form-table text-xs">
        <thead>
            <tr>
                <th>Area</th>
                @foreach ($roster as $person)
                    <th class="min-w-24 text-center">
                        <span class="block font-bold">{{ ($person['role'] ?? '') === 'self' ? 'Self' : 'Peer' }}</span>
                        <span class="block text-[10px] font-normal">{{ $person['name'] ?? 'Student' }}</span>
                    </th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach (['Participated willingly in all activities of the group.','Took extra effort to contribute for the development of the research paper.','Did best in doing the assigned research tasks.','Was consistent and punctual in attending group activities/meetings.','Contributed bright ideas in order to improve the research paper.','Did the assigned tasks on or before the deadline.','Took the initiative to perform some unaccomplished parts of the research.','Encouraged other members of the group to participate actively.','Showed favorable attitude towards other members of the group.','Accepted/listened to the opinions of other members of the group.'] as $criterionIndex => $criterion)
                <tr>
                    <td>{{ $criterionIndex + 1 }}. {{ $criterion }}</td>
                    @foreach ($roster as $column => $person)
                        <td><input type="number" min="1" max="4" step="1" required name="payload[ratings][{{ $criterionIndex }}][{{ $column }}]" value="{{ $payload['ratings'][$criterionIndex][$column] ?? '' }}" class="w-full text-center"></td>
                    @endforeach
                </tr>
            @endforeach
            <tr>
                <td class="font-bold">TOTAL (server calculated)</td>
                @foreach ($roster as $column => $person)
                    <td class="text-center font-bold">{{ $totals[$column] ?? '-' }}</td>
                @endforeach
            </tr>
        </tbody>
    </table>
    <div class="mx-auto mt-10 grid max-w-xl gap-6 md:grid-cols-2">
        <x-official-signature-field label="Student Evaluator" />
        <label class="text-center"><input type="date" name="payload[evaluation_date]" value="{{ $payload['evaluation_date'] ?? '' }}" class="w-full text-center"><span class="mt-1 block text-xs">Date of Evaluation</span></label>
    </div>
</x-student-official-form></div>
