@php
    $officialFormInstance = $officialFormInstance ?? null;
    $payload = $payload ?? [];
    $languageEditor = $officialFormInstance?->actorAssignments
        ?->firstWhere('actor_type', 'language_editor')
        ?->user;
    $members = $officialFormInstance?->group?->members?->values() ?? collect();
@endphp

<div x-show="activeOfficialForm === 'RES-029'" x-cloak>
    <x-student-official-form
        code="RES-Form-029"
        title="Invitation to Research Language Editor"
        guidebook-page="104"
    >
        <label class="flex w-fit items-center gap-2">
            Date:
            <input type="date" name="payload[date]" value="{{ $payload['date'] ?? '' }}">
        </label>

        <div>
            <label class="flex max-w-md items-end gap-2">
                <span>To:</span>
                <input
                    type="text"
                    value="{{ $languageEditor?->name }}"
                    class="flex-1 text-center"
                    placeholder="Name of Research Language Editor"
                    readonly
                >
            </label>
            <p class="ml-8 mt-1 text-center text-xs italic">(Name of Research Language Editor)</p>
        </div>

        <p class="leading-7">
            May I invite you to be the <strong>RESEARCH LANGUAGE EDITOR</strong> of the Research Paper of the following
            <input
                type="text"
                name="payload[course]"
                value="{{ $payload['course'] ?? '' }}"
                class="w-56 text-center"
                placeholder="course"
            >
            students:
        </p>

        <fieldset>
            <legend class="mb-2 font-bold">NAME/S:</legend>
            <div class="space-y-2">
                @for ($index = 0; $index < 4; $index++)
                    <label class="flex max-w-md items-center gap-2">
                        <span>{{ $index + 1 }}.</span>
                        <input
                            type="text"
                            value="{{ $members->get($index)?->student?->name }}"
                            class="flex-1"
                            readonly
                        >
                    </label>
                @endfor
            </div>
        </fieldset>

        <label class="block font-bold">
            Research Title:
            <textarea
                name="payload[research_title]"
                class="mt-1 min-h-20 w-full font-normal"
            >{{ $payload['research_title'] ?? ($officialFormInstance?->group?->title ?? '') }}</textarea>
        </label>

        <div class="space-y-2 text-xs leading-5">
            <p>As Research Language Editor please be guided by the following:</p>
            <ol class="list-decimal space-y-2 pl-8">
                <li>Possesses knowledge and skills in technical writing and the latest APA Style research paper format (7th edition).</li>
                <li>Refines the research manuscript by correcting the grammar, use of words, organization/composition, spelling, citations, etc. Although the role is limited to language editing, the language editor can further suggest to student researchers how to improve weak areas of the manuscript.</li>
                <li>Ensures the coherence of different parts of the manuscript. However, redirecting the methodology of the study from its original form is beyond the responsibility of the language editor.</li>
                <li>Issues the prescribed <em>Certification of Language Editing</em> as evidence that the research manuscript was duly edited.</li>
            </ol>
        </div>

        <p>Thank you very much.</p>

        <div class="grid gap-8 pt-8 text-center md:grid-cols-2">
            <x-official-signature-field actor-type="program_coordinator" label="Program Coordinator (Name & Signature)" />
            <x-official-signature-field actor-type="language_editor" label="Language Editor (Conforme Signature)" />
            <x-official-signature-field actor-type="dean" label="Noted: Dean (Name & Signature)" />
            <div>
                <div class="border-b border-[#173c30] px-2 py-1">
                    {{ $officialFormInstance?->currentVersion?->signatures?->firstWhere('actor_type', 'language_editor')?->signed_at?->format('M d, Y') ?? 'Pending conforme' }}
                </div>
                <span class="mt-2 block font-bold">Date Conformed</span>
            </div>
        </div>
    </x-student-official-form>
</div>
